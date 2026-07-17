<template>
    <span :class="['badge', classes]">
        <span class="mr-1.5 size-1.5 rounded-full" :class="dotClasses" />
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({ status: { type: String, default: 'offline' } });

const styles = {
    running: { badge: 'bg-emerald-500/10 text-emerald-300', dot: 'bg-emerald-400', label: 'En ligne' },
    installing: { badge: 'bg-amber-500/10 text-amber-300', dot: 'bg-amber-400', label: 'Installation...' },
    install_failed: { badge: 'bg-red-500/10 text-red-300', dot: 'bg-red-400', label: 'Échec install' },
    offline: { badge: 'bg-slate-500/10 text-slate-400', dot: 'bg-slate-500', label: 'Hors ligne' },
    stopped: { badge: 'bg-slate-500/10 text-slate-400', dot: 'bg-slate-500', label: 'Arrêté' },
};

const current = computed(() => styles[props.status] ?? styles.offline);
const classes = computed(() => current.value.badge);
const dotClasses = computed(() => current.value.dot);
const label = computed(() => current.value.label);
</script>
