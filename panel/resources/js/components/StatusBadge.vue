<template>
    <span :class="['rounded px-2 py-0.5 text-xs font-medium', classes]">{{ status }}</span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({ status: { type: String, default: 'offline' } });

const styles = {
    running: 'bg-emerald-900 text-emerald-300',
    installing: 'bg-amber-900 text-amber-300',
    install_failed: 'bg-red-900 text-red-300',
    offline: 'bg-slate-800 text-slate-400',
    stopped: 'bg-slate-800 text-slate-400',
};

const classes = computed(() => styles[props.status] ?? 'bg-slate-800 text-slate-400');
</script>
