<template>
    <div class="card flex flex-col items-center gap-3 py-12 text-center">
        <div class="flex size-12 items-center justify-center rounded-full bg-slate-800/60 text-slate-500">
            <component :is="icon" class="size-6" />
        </div>
        <div>
            <p class="font-medium text-slate-300">{{ title }}</p>
            <p v-if="message" class="mt-1 text-sm text-slate-500">{{ message }}</p>
        </div>
        <slot />
    </div>
</template>

<script setup>
defineProps({
    icon: { type: [Object, Function], required: true },
    title: { type: String, required: true },
    message: { type: String, default: '' },
});
</script>
