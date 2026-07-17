<template>
    <label class="block">
        <span class="mb-1 block text-sm text-slate-400">{{ label }}</span>
        <slot />
    </label>
</template>

<script setup>
defineProps({ label: { type: String, required: true } });
</script>
