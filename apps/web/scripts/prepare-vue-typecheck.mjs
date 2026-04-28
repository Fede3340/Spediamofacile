import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const files = ['.nuxt/tsconfig.json', '.nuxt/tsconfig.app.json'];
const incompatiblePlugin = 'vue-router/volar/sfc-route-blocks';

for (const relativePath of files) {
  const filePath = resolve(process.cwd(), relativePath);

  if (!existsSync(filePath)) {
    continue;
  }

  const config = JSON.parse(readFileSync(filePath, 'utf8'));
  const plugins = config.vueCompilerOptions?.plugins;

  if (!Array.isArray(plugins)) {
    continue;
  }

  const nextPlugins = plugins.filter(plugin => plugin !== incompatiblePlugin);

  if (nextPlugins.length > 0) {
    config.vueCompilerOptions.plugins = nextPlugins;
  } else {
    delete config.vueCompilerOptions.plugins;

    if (Object.keys(config.vueCompilerOptions).length === 0) {
      delete config.vueCompilerOptions;
    }
  }

  writeFileSync(filePath, `${JSON.stringify(config, null, 2)}\n`);
}
