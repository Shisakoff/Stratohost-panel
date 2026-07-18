<template>
    <div v-if="server">
        <PageHeader :icon="Database" title="Bases de données" subtitle="Toutes les bases de données disponibles pour ce serveur." :breadcrumbs="['Mes serveurs', server.name, 'Bases de données']" />

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
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Database, Plus, Trash2 } from '@lucide/vue';
import axios from '../../lib/api';
import PageHeader from '../../components/PageHeader.vue';
import { useCurrentServerStore } from '../../stores/currentServer';

const props = defineProps({ uuid: { type: String, required: true } });
const store = useCurrentServerStore();
const server = computed(() => store.server);

const databases = ref([]);
const hosts = ref([]);
const selectedHostId = ref('');
const creatingDb = ref(false);
const dbError = ref('');
const revealed = reactive({});

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

onMounted(() => Promise.all([loadDatabases(), loadHosts()]));
</script>
