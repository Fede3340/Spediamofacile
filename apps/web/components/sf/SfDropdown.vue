<script setup lang="ts">
/**
 * SfDropdown — dropdown menu accessibile (azioni contestuali).
 *
 * Pattern:
 *   <SfDropdown :items="[
 *     { label: 'Modifica', icon: 'mdi:pencil', onClick: () => edit() },
 *     { label: 'Elimina', icon: 'mdi:delete', tone: 'danger', onClick: () => del() },
 *   ]">
 *     <SfButton variant="ghost" size="sm">Azioni</SfButton>
 *   </SfDropdown>
 */

interface DropdownItem {
	label: string;
	icon?: string;
	tone?: 'default' | 'danger';
	disabled?: boolean;
	onClick: () => void;
}

interface Props {
	items: DropdownItem[];
	/** Posizione menu rispetto al trigger. */
	align?: 'left' | 'right';
}

withDefaults(defineProps<Props>(), {
	align: 'right',
});

const open = ref(false);
const wrapper = ref<HTMLElement | null>(null);

function handleOutsideClick(event: MouseEvent) {
	if (!open.value || !wrapper.value) return;
	if (!wrapper.value.contains(event.target as Node)) {
		open.value = false;
	}
}

onMounted(() => {
	if (typeof document !== 'undefined') {
		document.addEventListener('click', handleOutsideClick);
	}
});

onBeforeUnmount(() => {
	if (typeof document !== 'undefined') {
		document.removeEventListener('click', handleOutsideClick);
	}
});

function handleClick(item: DropdownItem) {
	if (item.disabled) return;
	item.onClick();
	open.value = false;
}
</script>

<template>
	<div ref="wrapper" class="relative inline-block">
		<div @click="open = !open">
			<slot />
		</div>

		<Transition
			enter-active-class="transition duration-100 ease-out"
			enter-from-class="opacity-0 scale-95"
			enter-to-class="opacity-100 scale-100"
			leave-active-class="transition duration-75 ease-in"
			leave-from-class="opacity-100 scale-100"
			leave-to-class="opacity-0 scale-95"
		>
			<div
				v-if="open"
				role="menu"
				:class="[
					'absolute z-40 mt-1 min-w-[180px] py-1 bg-brand-card rounded-card border border-brand-border shadow-sf-lg',
					align === 'left' ? 'left-0' : 'right-0',
				]"
			>
				<button
					v-for="item in items"
					:key="item.label"
					type="button"
					role="menuitem"
					:disabled="item.disabled"
					:class="[
						'flex w-full items-center gap-2 px-3 py-2 text-sm text-left transition',
						item.tone === 'danger' ? 'text-brand-error hover:bg-red-50' : 'text-brand-text hover:bg-brand-bg-alt',
						item.disabled ? 'opacity-50 cursor-not-allowed' : '',
					]"
					@click="handleClick(item)"
				>
					<UIcon v-if="item.icon" :name="item.icon" class="h-4 w-4 shrink-0" />
					{{ item.label }}
				</button>
			</div>
		</Transition>
	</div>
</template>
