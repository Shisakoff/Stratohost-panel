<template>
    <div v-if="server">
        <PageHeader :icon="Terminal" title="Console" subtitle="Contrôlez votre serveur en temps réel." :breadcrumbs="['Mes serveurs', server.name, 'Console']" />

        <p v-if="error" class="mb-4 rounded-lg bg-red-950/60 px-3 py-2 text-sm text-red-300">{{ error }}</p>

        <div class="card mb-6 p-0">
            <div class="flex h-72 flex-col justify-end overflow-y-auto rounded-t-xl bg-slate-950/80 p-4 font-mono text-xs text-slate-500">
                <p>La console en temps réel arrive bientôt (Phase C) - utilise les boutons ci-dessous pour piloter le serveur en attendant.</p>
            </div>
            <div class="flex items-center gap-2 border-t border-slate-800 px-4 py-3">
                <span class="text-emerald-500">$</span>
                <input disabled placeholder="Console indisponible pour le moment..." class="input flex-1 !bg-transparent !border-none !px-0" />
            </div>
        </div>

        <div class="mb-6 flex gap-3">
            <button type="button" :disabled="powering" class="btn-secondary" @click="power('start')">
                <Play class="size-4" /> Démarrer
            </button>
            <button type="button" :disabled="powering" class="btn-secondary" @click="power('restart')">
                <RotateCw class="size-4" /> Redémarrer
            </button>
            <button type="button" :disabled="powering" class="btn-secondary" @click="power('stop')">
                <Square class="size-4" /> Arrêter
            </button>
            <button type="button" :disabled="powering" class="btn-danger" @click="power('kill')">
                <Skull class="size-4" /> Forcer l'arrêt
            </button>
        </div>

        <div v-if="store.cpuHistory.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <StatGraph title="Utilisation CPU" :values="store.cpuHistory" :max="100" color="#34d399" :format="(v) => `${v.toFixed(1)} %`" />
            <StatGraph title="Utilisation de la mémoire" :values="store.memHistory" color="#22d3ee" :format="formatMemory" />
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Play, RotateCw, Skull, Square, Terminal } from '@lucide/vue';
import PageHeader from '../../components/PageHeader.vue';
import StatGraph from '../../components/StatGraph.vue';
import { useCurrentServerStore } from '../../stores/currentServer';

const props = defineProps({ uuid: { type: String, required: true } });
const store = useCurrentServerStore();
const server = computed(() => store.server);

const powering = ref(false);
const error = ref('');

function formatMemory(bytes) {
    const mb = bytes / 1024 / 1024;
    if (mb >= 1024) return `${(mb / 1024).toFixed(2)} GB`;
    return `${Math.round(mb)} MB`;
}

async function power(action) {
    powering.value = true;
    error.value = '';
    try {
        await store.power(props.uuid, action);
    } catch (e) {
        error.value = e.response?.data?.message || 'Action impossible.';
    } finally {
        powering.value = false;
    }
}
</script>
