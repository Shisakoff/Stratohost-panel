<template>
    <div class="card">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <div class="text-sm font-medium text-slate-200">{{ title }}</div>
                <div class="text-lg font-semibold text-slate-100">{{ display }}</div>
            </div>
        </div>

        <svg viewBox="0 0 300 80" preserveAspectRatio="none" class="h-20 w-full overflow-visible">
            <polyline :points="areaPoints" :fill="color" fill-opacity="0.15" stroke="none" />
            <polyline :points="linePoints" fill="none" :stroke="color" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
            <circle v-if="lastPoint" :cx="lastPoint.x" :cy="lastPoint.y" r="3" :fill="color" />
        </svg>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    values: { type: Array, required: true },
    max: { type: Number, default: null },
    color: { type: String, default: '#34d399' },
    format: { type: Function, default: (v) => `${Math.round(v)}` },
});

const display = computed(() => (props.values.length ? props.format(props.values[props.values.length - 1]) : '—'));

const scaledMax = computed(() => {
    if (props.max) return props.max;
    return Math.max(1, ...props.values);
});

function coords() {
    const n = props.values.length;
    if (n === 0) return [];
    if (n === 1) return [{ x: 300, y: 80 - (props.values[0] / scaledMax.value) * 80 }];

    return props.values.map((v, i) => ({
        x: (i / (n - 1)) * 300,
        y: 80 - Math.min(1, v / scaledMax.value) * 78 - 1,
    }));
}

const linePoints = computed(() => coords().map((p) => `${p.x},${p.y}`).join(' '));
const areaPoints = computed(() => {
    const pts = coords();
    if (pts.length === 0) return '';
    return `0,80 ${pts.map((p) => `${p.x},${p.y}`).join(' ')} 300,80`;
});
const lastPoint = computed(() => {
    const pts = coords();
    return pts.length ? pts[pts.length - 1] : null;
});
</script>
