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

        <div v-if="cpuHistory.length" class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <StatGraph title="Utilisation CPU" :values="cpuHistory" :max="100" color="#34d399" :format="(v) => `${v.toFixed(1)} %`" />
            <StatGraph title="Utilisation de la mémoire" :values="memHistory" color="#22d3ee" :format="formatMemory" />
        </div>

        <div class="card mb-6">
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

        <div v-if="visibleVariables.length" class="card mb-6">
            <h2 class="mb-1 text-sm font-semibold uppercase tracking-wide text-slate-400">Variables du jeu</h2>
            <p class="mb-4 text-sm text-slate-500">
                Options propres à {{ server.egg.name }}. Redémarre le serveur après enregistrement pour appliquer les
                changements.
            </p>

            <form class="grid grid-cols-2 gap-4" @submit.prevent="saveVariables">
                <Field v-for="v in visibleVariables" :key="v.id" :label="v.eggVariable.name">
                    <input
                        v-model="variableValues[v.eggVariable.id]"
                        :disabled="!canEditVariable(v)"
                        class="input"
                        :class="{ 'opacity-50': !canEditVariable(v) }"
                    />
                </Field>
                <p v-if="variablesError" class="col-span-2 text-sm text-red-400">{{ variablesError }}</p>
                <p v-if="variablesSaved" class="col-span-2 text-sm text-emerald-400">Enregistré.</p>
                <div class="col-span-2">
                    <button type="submit" :disabled="savingVariables" class="btn-primary">
                        {{ savingVariables ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Bases de données</h2>
                <div v-if="hosts.length" class="flex items-center gap-2">
                    <select v-model.number="selectedHostId" class="input w-56">
                        <option v-for="h in hosts" :key="h.id" :value="h.id">{{ h.name }}</option>
                    </select>
                    <button type="button" :disabled="creatingDb" class="btn-secondary" @click="createDatabase">
                        <Plus class="size-4" /> Créer
                    </button>
                </div>
            </div>

            <p v-if="hosts.length === 0" class="text-sm text-slate-500">
                Aucun hôte de base de données configuré -
                <RouterLink to="/database-hosts" class="text-emerald-400 hover:underline">en ajouter un</RouterLink>.
            </p>
            <p v-if="dbError" class="mb-3 text-sm text-red-400">{{ dbError }}</p>

            <table v-if="databases.length" class="table-clean">
                <thead><tr><th>Base</th><th>Utilisateur</th><th>Mot de passe</th><th>Hôte</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="db in databases" :key="db.id">
                        <td class="font-mono text-xs text-slate-300">{{ db.database }}</td>
                        <td class="font-mono text-xs text-slate-300">{{ db.username }}</td>
                        <td class="font-mono text-xs text-slate-300">
                            <button type="button" class="hover:text-emerald-400" @click="revealed[db.id] = !revealed[db.id]">
                                {{ revealed[db.id] ? db.password : '••••••••••••' }}
                            </button>
                        </td>
                        <td class="text-slate-400">{{ db.database_host.host }}:{{ db.database_host.port }}</td>
                        <td class="text-right">
                            <button type="button" class="text-red-400 hover:text-red-300" title="Supprimer" @click="removeDatabase(db)">
                                <Trash2 class="size-4" />
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p v-else-if="hosts.length" class="text-sm text-slate-500">Aucune base de données pour l'instant.</p>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Play, Plus, RotateCw, Skull, Square, Swords, Trash2 } from '@lucide/vue';
import axios from '../lib/api';
import Field from '../components/Field.vue';
import StatGraph from '../components/StatGraph.vue';
import StatusBadge from '../components/StatusBadge.vue';
import { useAuthStore } from '../stores/auth';

const props = defineProps({ uuid: { type: String, required: true } });

const auth = useAuthStore();

const server = ref(null);
const liveStatus = ref('');
const powering = ref(false);
const error = ref('');
const databases = ref([]);
const hosts = ref([]);
const selectedHostId = ref('');
const creatingDb = ref(false);
const dbError = ref('');
const revealed = reactive({});
let pollHandle = null;
let statsPollHandle = null;

const STATS_HISTORY_LENGTH = 20;
const cpuHistory = ref([]);
const memHistory = ref([]);

function formatMemory(bytes) {
    const mb = bytes / 1024 / 1024;
    if (mb >= 1024) return `${(mb / 1024).toFixed(2)} GB`;
    return `${Math.round(mb)} MB`;
}

function pushHistory(list, value) {
    list.push(value);
    if (list.length > STATS_HISTORY_LENGTH) list.shift();
}

async function pollStats() {
    try {
        const { data } = await axios.get(`/api/servers/${props.uuid}/stats`);
        if (data.memory_usage_bytes > 0 || data.cpu_percent > 0) {
            pushHistory(cpuHistory.value, data.cpu_percent);
            pushHistory(memHistory.value, data.memory_usage_bytes);
        }
    } catch {
        // Node unreachable - just skip this tick of the graph.
    }
}

const variableValues = reactive({});
const variablesError = ref('');
const variablesSaved = ref(false);
const savingVariables = ref(false);

const visibleVariables = computed(() => {
    if (!server.value) return [];
    return server.value.variables.filter((v) => auth.user?.root_admin || v.eggVariable.user_viewable);
});

function canEditVariable(serverVariable) {
    return auth.user?.root_admin || serverVariable.eggVariable.user_editable;
}

function syncVariableValues() {
    for (const v of server.value.variables) {
        variableValues[v.eggVariable.id] = v.value ?? v.eggVariable.default_value;
    }
}

async function saveVariables() {
    variablesError.value = '';
    variablesSaved.value = false;
    savingVariables.value = true;
    try {
        const variables = visibleVariables.value
            .filter((v) => canEditVariable(v))
            .map((v) => ({ egg_variable_id: v.eggVariable.id, value: variableValues[v.eggVariable.id] }));
        const { data } = await axios.patch(`/api/servers/${props.uuid}/variables`, { variables });
        server.value.variables = data.variables;
        syncVariableValues();
        variablesSaved.value = true;
    } catch (e) {
        variablesError.value = e.response?.data?.message || 'Enregistrement impossible.';
    } finally {
        savingVariables.value = false;
    }
}

async function load() {
    const { data } = await axios.get(`/api/servers/${props.uuid}`);
    server.value = data;
    syncVariableValues();
}

async function loadDatabases() {
    const { data } = await axios.get(`/api/servers/${props.uuid}/databases`);
    databases.value = data;
}

async function loadHosts() {
    const { data } = await axios.get('/api/database-hosts');
    hosts.value = data;
    if (data.length && !selectedHostId.value) {
        selectedHostId.value = data[0].id;
    }
}

async function createDatabase() {
    dbError.value = '';
    creatingDb.value = true;
    try {
        await axios.post(`/api/servers/${props.uuid}/databases`, { database_host_id: selectedHostId.value });
        await loadDatabases();
    } catch (e) {
        dbError.value = e.response?.data?.message || 'Création impossible.';
    } finally {
        creatingDb.value = false;
    }
}

async function removeDatabase(db) {
    await axios.delete(`/api/databases/${db.id}`);
    await loadDatabases();
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
    await Promise.all([loadDatabases(), loadHosts()]);
    pollHandle = setInterval(pollStatus, 5000);
    await pollStats();
    statsPollHandle = setInterval(pollStats, 3000);
});

onUnmounted(() => {
    if (pollHandle) {
        clearInterval(pollHandle);
    }
    if (statsPollHandle) {
        clearInterval(statsPollHandle);
    }
});
</script>
