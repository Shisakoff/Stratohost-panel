<template>
    <div class="flex items-center gap-2.5">
        <svg :width="size" :height="size" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" class="shrink-0">
            <defs>
                <linearGradient :id="gradId" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                    <stop offset="0" stop-color="#818cf8" />
                    <stop offset="1" stop-color="#a855f7" />
                </linearGradient>
            </defs>
            <rect width="40" height="40" rx="10" :fill="`url(#${gradId})`" />
            <rect x="9" y="10.5" width="22" height="5" rx="2.5" fill="white" fill-opacity="0.95" />
            <rect x="9" y="17.5" width="22" height="5" rx="2.5" fill="white" fill-opacity="0.75" />
            <rect x="9" y="24.5" width="14" height="5" rx="2.5" fill="white" fill-opacity="0.55" />
        </svg>
        <span v-if="wordmark" class="text-lg font-semibold tracking-tight text-slate-100">
            Strato<span class="text-indigo-400">Host</span>
        </span>
    </div>
</template>

<script setup>
defineProps({
    size: { type: [Number, String], default: 32 },
    wordmark: { type: Boolean, default: true },
});

// Unique per instance so multiple logos on the same page (e.g. a future
// favicon preview, or SSR hydration mismatches) never collide on the
// gradient's id.
const gradId = `stratohost-logo-grad-${Math.random().toString(36).slice(2)}`;
</script>
