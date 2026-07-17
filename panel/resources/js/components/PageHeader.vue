<template>
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400">
                <component :is="icon" class="size-5" />
            </div>
            <div>
                <h1 class="text-xl font-semibold text-slate-100">{{ title }}</h1>
                <p v-if="subtitle" class="mt-0.5 text-sm text-slate-500">{{ subtitle }}</p>
            </div>
        </div>

        <nav v-if="breadcrumbs.length" class="hidden text-sm text-slate-500 sm:block">
            <span v-for="(crumb, i) in breadcrumbs" :key="crumb">
                <span :class="i === breadcrumbs.length - 1 ? 'text-slate-300' : ''">{{ crumb }}</span>
                <span v-if="i < breadcrumbs.length - 1" class="mx-2 text-slate-700">/</span>
            </span>
        </nav>
    </div>
</template>

<script setup>
defineProps({
    icon: { type: [Object, Function], required: true },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    breadcrumbs: { type: Array, default: () => [] },
});
</script>
