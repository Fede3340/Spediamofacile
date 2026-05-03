<script setup lang="ts">
/**
 * SfTabs — tab bar accessibile (orizzontale).
 *
 * Pattern:
 *   <SfTabs v-model="activeTab" :items="[
 *     { id: 'profile', label: 'Profilo', icon: 'mdi:account' },
 *     { id: 'security', label: 'Sicurezza', icon: 'mdi:lock' },
 *   ]" />
 */

interface TabItem {
	id: string;
	label: string;
	icon?: string;
	disabled?: boolean;
	count?: number;
}

interface Props {
	modelValue: string;
	items: TabItem[];
	/** Variante visuale (default 'underline', 'pills' per pill stile). */
	variant?: 'underline' | 'pills';
	/** Layout (default 'horizontal', 'vertical' per sidebar). */
	orientation?: 'horizontal' | 'vertical';
}

const props = withDefaults(defineProps<Props>(), {
	variant: 'underline',
	orientation: 'horizontal',
});

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const wrapperClasses = computed(() => {
	const base = 'flex gap-1';
	if (props.orientation === 'vertical') return `${base} flex-col`;
	if (props.variant === 'underline') return `${base} border-b border-brand-border`;
	return base;
});

function tabClass(item: TabItem) {
	const isActive = props.modelValue === item.id;
	const base = 'inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-brand-primary';

	if (props.variant === 'pills') {
		return [
			base,
			'rounded-full',
			isActive
				? 'bg-brand-primary text-white shadow-sf-sm'
				: 'text-brand-text-secondary hover:bg-brand-bg-alt',
		];
	}

	return [
		base,
		'border-b-2 -mb-px',
		isActive
			? 'border-brand-primary text-brand-primary'
			: 'border-transparent text-brand-text-secondary hover:text-brand-text hover:border-brand-border',
		item.disabled ? 'opacity-50 cursor-not-allowed' : '',
	];
}
</script>

<template>
	<div role="tablist" :aria-orientation="orientation" :class="wrapperClasses">
		<button
			v-for="item in items"
			:key="item.id"
			type="button"
			role="tab"
			:aria-selected="modelValue === item.id"
			:disabled="item.disabled"
			:tabindex="modelValue === item.id ? 0 : -1"
			:class="tabClass(item)"
			@click="emit('update:modelValue', item.id)"
		>
			<UIcon v-if="item.icon" :name="item.icon" class="h-4 w-4 shrink-0" />
			<span>{{ item.label }}</span>
			<span
				v-if="typeof item.count === 'number'"
				class="ml-1 inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-brand-bg-alt text-brand-text-muted text-xs"
			>
				{{ item.count }}
			</span>
		</button>
	</div>
</template>
