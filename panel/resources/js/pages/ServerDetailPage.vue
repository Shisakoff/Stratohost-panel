<template>
    <div v-if="server" class="max-w-2xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">{{ server.name }}</h1>
                <p class="text-sm text-slate-400">
                    {{ server.egg.name }} · {{ server.node.name }} · {{ server.allocation.ip }}:{{ server.allocation.port }}
                </p>
            </div>
            <StatusBadge :status="liveStatus || server.status" />
        </div>

        <div class="flex gap-3">
            <button
                type="button"
                :disabled="powering"
                class="rounded bg-emerald-700 px-4 py-2 text-sm font-medium hover:bg-emerald-600 disabled:opacity-50"
                @click="power('start')"
            >
                Démarrer
            </button>
            <button
                type="button"
                :disabled="powering"
                class="rounded bg-slate-700 px-4 py-2 text-sm font-medium hover:bg-slate-600 disabled:opacity-50"
                @click="power('stop')"
            >
                Arrêter
            </button>
            <button
                type="button"
                :disabled="powering"
                class="rounded bg-slate-700 px-4 py-2 text-sm font-medium hover:bg-slate-600 disabled:opacity-50"
                @click="power('restart')"
            >
                Redémarrer
            </button>
            <button
                type="button"
                :disabled="powering"
                class="rounded bg-red-800 px-4 py-2 text-sm font-medium hover:bg-red-700 disabled:opacity-50"
                @click="power('kill')"
            >
                Forcer l'arrêt
            </button>
        </div>

        <p v-if="error" class="text-sm text-red-400">{{ error }}</p>

        <div class="rounded-lg border border-slate-800 bg-slate-900 p-6 text-sm">
            <dl class="grid grid-cols-2 gap-y-2">
                <dt class="text-slate-400">Mémoire</dt>
                <dd>{{ server.memory }} MB</dd>
                <dt class="text-slate-400">Disque</dt>
                <dd>{{ server.disk }} MB</dd>
                <dt class="text-slate-400">CPU</dt>
                <dd>{{ server.cpu }}%</dd>
                <dt class="text-slate-400">Démarrage</dt>
                <dd class="col-span-2 break-all font-mono text-xs text-slate-300">{{ server.startup }}</dd>
            </dl>
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
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
