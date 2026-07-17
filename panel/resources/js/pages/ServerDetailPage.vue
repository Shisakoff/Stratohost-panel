<template>
    <div v-if="server">
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400">
                    <Swords class="size-5" />
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-slate-100">{{ server.name }}</h1>
                    <p class="mt-0.5 text-sm text-slate-500">
                        {{ server.egg.name }} · {{ server.node.name }} · {{ server.allocation.ip }}:{{ server.allocation.port }}
                    </p>
                </div>
            </div>
            <StatusBadge :status="liveStatus || server.status" />
        </div>

        <div class="mb-6 flex gap-3">
            <button type="button" :disabled="powering" class="btn-secondary" @click="power('start')">
                <Play class="size-4" /> Démarrer
            </button>
            <button type="button" :disabled="powering" class="btn-secondary" @click="power('stop')">
                <Square class="size-4" /> Arrêter
            </button>
            <button type="button" :disabled="powering" class="btn-secondary" @click="power('restart')">
                <RotateCw class="size-4" /> Redémarrer
            </button>
            <button type="button" :disabled="powering" class="btn-danger" @click="power('kill')">
                <Skull class="size-4" /> Forcer l'arrêt
            </button>
        </div>

        <p v-if="error" class="mb-4 rounded-lg bg-red-950/60 px-3 py-2 text-sm text-red-300">{{ error }}</p>

        <div class="card">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Configuration</h2>
            <dl class="grid grid-cols-3 gap-y-4 text-sm">
                <dt class="text-slate-500">Mémoire</dt>
                <dd class="col-span-2 text-slate-200">{{ server.memory }} MB</dd>
                <dt class="text-slate-500">Disque</dt>
                <dd class="col-span-2 text-slate-200">{{ server.disk }} MB</dd>
                <dt class="text-slate-500">CPU</dt>
                <dd class="col-span-2 text-slate-200">{{ server.cpu }}%</dd>
                <dt class="text-slate-500">Démarrage</dt>
                <dd class="col-span-2 break-all font-mono text-xs text-slate-400">{{ server.startup }}</dd>
            </dl>
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Play, RotateCw, Skull, Square, Swords } from '@lucide/vue';
import axios from '../lib/api';
import StatusBadge from '../components/StatusBadge.vue';

const props = defineProps({ uuid: { type: String, required: true } });

const server = ref(null);
const liveStatus = ref('');
const powering = ref(false);
const error = ref('');
let pollHandle = null;

async function load() {
    const { data } = await axios.get(`/api/servers/${props.uuid}`);
    server.value = data;
}

async function pollStatus() {
    try {
        const { data } = await axios.get(`/api/servers/${props.uuid}/status`);
        liveStatus.value = data.status;
    } catch {
        // Node unreachable right now - keep showing the last known status
        // rather than blanking it out on every failed poll.
    }
}

async function power(action) {
    powering.value = true;
    error.value = '';
    try {
        await axios.post(`/api/servers/${props.uuid}/power`, { action });
        await pollStatus();
    } catch (e) {
        error.value = e.response?.data?.message || 'Action impossible.';
    } finally {
        powering.value = false;
    }
}

onMounted(async () => {
    await load();
    await pollStatus();
    pollHandle = setInterval(pollStatus, 5000);
});

onUnmounted(() => {
    if (pollHandle) {
        clearInterval(pollHandle);
    }
});
</script>
