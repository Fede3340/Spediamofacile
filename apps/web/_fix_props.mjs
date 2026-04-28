// Fix defineProps()/defineEmits() vuoti — riscrive in forma runtime usando l'originale TS di c93d98a.
import {execSync} from 'node:child_process';
import fs from 'node:fs';

const files = [
  'components/admin/servizio/ServizioFormBase.vue',
  'components/admin/servizio/ServizioFormOverview.vue',
  'components/admin/servizio/ServizioFormStack.vue',
  'components/admin/servizio/ServizioFormSummary.vue',
  'components/auth/AuthForgotForm.vue',
  'components/auth/AuthLoginForm.vue',
  'components/auth/AuthRegisterForm.vue',
  'components/auth/AuthSocialButtons.vue',
  'components/auth/AuthVerifyForm.vue',
  'components/layout/PublicPageHeader.vue',
  'components/pudo/PudoDetailPanel.vue',
  'components/pudo/PudoList.vue',
  'components/pudo/PudoMap.vue',
  'components/sf/SfModal.vue',
  'components/sf/SfSkeleton.vue',
];

// Mappa tipi TS → runtime Vue
function tsTypeToRuntime(ts) {
  const t = ts.trim();
  if (/^string(\s*\|\s*string)*$/.test(t) || t === 'string') return 'String';
  if (/^number$/.test(t)) return 'Number';
  if (/^boolean$/.test(t)) return 'Boolean';
  if (/\[\]$/.test(t) || /^Array</.test(t)) return 'Array';
  if (/^\(.*\)\s*=>/.test(t) || t.startsWith('Function')) return 'Function';
  if (t.includes("'") && t.includes('|')) return 'String'; // union literali → String
  if (t.startsWith('{') || t.startsWith('Record<')) return 'Object';
  if (/^[A-Z]\w+$/.test(t) && !['String','Number','Boolean','Array','Function','Object'].includes(t)) {
    // tipo custom (es. User, PublicPageCrumb) → Object
    return 'Object';
  }
  return null; // sconosciuto → omesso
}

// Estrae { name?: Type, name2: Type } dentro defineProps<{...}>() o defineProps<InterfaceName>()
function parsePropsGeneric(tsBlock) {
  let body;
  // Caso 1: defineProps<{ inline }>()
  let m = tsBlock.match(/defineProps<\{([\s\S]*?)\}>\(\)/);
  if (m) {
    body = m[1];
  } else {
    // Caso 2: defineProps<InterfaceName>()
    const im = tsBlock.match(/defineProps<(\w+)>\(\)/);
    if (!im) return null;
    const ifaceName = im[1];
    // trova interface IfaceName { ... } o type IfaceName = { ... }
    const ire = new RegExp(`(?:interface|type)\\s+${ifaceName}\\s*=?\\s*\\{([\\s\\S]*?)\\n\\}`);
    const ifMatch = tsBlock.match(ire);
    if (!ifMatch) return null;
    body = ifMatch[1];
  }
  // split su righe terminate da newline o virgola top-level
  const lines = body.split(/\n/).map(l => l.trim()).filter(l => l && !l.startsWith('//'));
  const props = [];
  for (const line of lines) {
    // pattern: nameOptional?: Type   oppure  name: Type
    const lm = line.match(/^(\w+)(\?)?\s*:\s*(.+?)[,;]?$/);
    if (!lm) continue;
    const [, name, opt, type] = lm;
    props.push({ name, optional: !!opt, type: type.trim().replace(/[,;]$/, '') });
  }
  return props;
}

// Estrae i defaults da withDefaults(defineProps<...>(), { ... })
function parseWithDefaults(tsBlock) {
  const m = tsBlock.match(/withDefaults\(\s*defineProps<[^>]*>\(\),\s*\{([\s\S]*?)\}\s*\)/);
  if (!m) return null;
  const body = m[1];
  // Parse semplice: nome: valore
  const defaults = {};
  // Matcha "name: ..." con valori che possono essere stringhe, fn, array, etc.
  const re = /(\w+)\s*:\s*(\([^)]*\)\s*=>\s*[^\n,]+|'[^']*'|"[^"]*"|true|false|\d+|null|\[[^\]]*\]|\{[^}]*\})/g;
  let mm;
  while ((mm = re.exec(body))) {
    defaults[mm[1]] = mm[2].trim().replace(/,$/, '');
  }
  return defaults;
}

function buildRuntimeProps(propsList, defaults) {
  const lines = ['{'];
  for (const p of propsList) {
    const rt = tsTypeToRuntime(p.type);
    const parts = [];
    if (rt) parts.push(`type: ${rt}`);
    if (defaults && Object.prototype.hasOwnProperty.call(defaults, p.name)) {
      parts.push(`default: ${defaults[p.name]}`);
    } else if (!p.optional && rt && rt !== 'Boolean') {
      parts.push('required: true');
    } else if (rt === 'Boolean') {
      parts.push('default: false');
    } else if (p.optional) {
      // valore di default sicuro
      if (rt === 'String') parts.push("default: ''");
      else if (rt === 'Number') parts.push('default: 0');
      else if (rt === 'Array') parts.push('default: () => []');
      else if (rt === 'Object') parts.push('default: () => ({})');
    }
    lines.push(`  ${p.name}: { ${parts.join(', ')} },`);
  }
  lines.push('}');
  return lines.join('\n');
}

let fixed = 0, failed = [];
for (const rel of files) {
  // Recupera il file TS originale dal commit snapshot
  const oldPath = `nuxt-spedizionefacile-master/${rel}`;
  let originalTs;
  try {
    originalTs = execSync(`git show c93d98a:${oldPath}`, {encoding: 'utf8'});
  } catch (e) {
    console.error(`❌ Cannot get original for ${rel}`);
    failed.push(rel);
    continue;
  }

  const propsList = parsePropsGeneric(originalTs);
  if (!propsList) {
    // Forse non ha defineProps generico — skip
    console.log(`⏭  ${rel}: no defineProps<{}> generic`);
    continue;
  }
  const defaults = parseWithDefaults(originalTs);
  const runtimeProps = buildRuntimeProps(propsList, defaults);

  // Modifica il file corrente JS
  const cur = fs.readFileSync(rel, 'utf8');

  let newSrc;
  // Caso 1: con withDefaults
  if (defaults) {
    newSrc = cur.replace(
      /withDefaults\(defineProps\(\),\s*\{[\s\S]*?\}\)/,
      `defineProps(${runtimeProps})`
    );
  } else {
    // Caso 2: solo defineProps()
    newSrc = cur.replace(
      /defineProps\(\)/,
      `defineProps(${runtimeProps})`
    );
  }

  if (newSrc === cur) {
    console.log(`⚠  ${rel}: nessuna sostituzione (pattern non trovato)`);
    failed.push(rel);
    continue;
  }

  fs.writeFileSync(rel, newSrc, 'utf8');
  console.log(`✅ ${rel} (${propsList.length} props)`);
  fixed++;
}

console.log(`\nFatto: ${fixed} file. Falliti: ${failed.length}`);
if (failed.length) console.log('Falliti:', failed);
