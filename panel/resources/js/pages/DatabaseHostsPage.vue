<template>
    <div>
        <PageHeader
            :icon="Database"
            title="Hôtes base de données"
            subtitle="Serveurs MySQL sur lesquels les serveurs peuvent avoir des bases de données créées."
            :breadcrumbs="['Admin', 'Bases de données']"
        />

        <div class="mb-4 flex gap-3">
            <input v-model="search" placeholder="Recherche" class="input flex-1" />
            <button type="button" class="btn-primary" @click="showForm = !showForm">
                <Plus class="size-4" />
                {{ showForm ? 'Annuler' : 'Créer' }}
            </button>
        </div>

        <form v-if="showForm" class="card mb-4 grid grid-cols-2 gap-4" @submit.prevent="createHost">
            <Field label="Nom"><input v-model="form.name" required class="input" /></Field>
            <Field label="Hôte"><input v-model="form.host" required placeholder="db ou une IP" class="input" /></Field>
            <Field label="Port"><input v-model.number="form.port" type="number" class="input" /></Field>
            <Field label="Nom d'utilisateur (avec droits admin)"><input v-model="form.username" required class="input" /></Field>
            <Field label="Mot de passe"><input v-model="form.password" type="password" required class="input" /></Field>
            <Field label="Limite de bases (vide = illimité)">
                <input v-model.number="form.max_databases" type="number" class="input" />
            </Field>
            <p v-if="error" class="col-span-2 text-sm text-red-400">{{ error }}</p>
            <div class="col-span-2">
                <button type="submit" :disabled="creating" class="btn-primary">
                    {{ creating ? 'Connexion...' : 'Créer' }}
                </button>
            </div>
        </form>

        <div class="card p-0">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th class="pl-6">Nom</th>
                        <th>Hôte</th>
                        <th>Port</th>
                        <th>Utilisateur</th>
                        <th>Bases de données</th>
                        <th class="pr-6"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="host in filteredHosts" :key="host.id">
                        <td class="pl-6 font-medium text-slate-100">{{ host.name }}</td>
                        <td class="font-mono text-xs text-slate-400">{{ host.host }}</td>
                        <td class="text-slate-400">{{ host.port }}</td>
                        <td class="text-slate-400">{{ host.username }}</td>
                        <td class="text-slate-400">{{ host.server_databases_count }} / {{ host.max_databases ?? '∞' }}</td>
                        <td class="pr-6 text-right">
                            <button
                                type="button"
                                class="text-red-400 hover:text-red-300 disabled:opacity-40"
                                :disabled="host.server_databases_count > 0"
                                :title="host.server_databases_count > 0 ? 'Des bases de données existent encore dessus' : 'Supprimer'"
                                @click="removeHost(host)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="filteredHosts.length === 0">
                        <td colspan="6" class="py-6 pl-6 text-slate-500">Aucun hôte pour l'instant.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Database, Plus, Trash2 } from '@lucide/vue';
import axios from '../lib/api';
import Field from '../components/Field.vue';
import PageHeader from '../components/PageHeader.vue';

const hosts = ref([]);
const search = ref('');
const showForm = ref(false);
const creating = ref(false);
const error = ref('');
const emptyForm = () => ({ name: '', host: '', port: 3306, username: '', password: '', max_databases: null });
const form = ref(emptyForm());

const filteredHosts = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return hosts.value;
    return hosts.value.filter((h) => h.name.toLowerCase().includes(q) || h.host.toLowerCase().includes(q));
});

async function load() {
    const { data } = await axios.get('/api/database-hosts');
    hosts.value = data;
}

async function createHost() {
    error.value = '';
    creating.value = true;
    try {
        await axios.post('/api/database-hosts', form.value);
        form.value = emptyForm();
        showForm.value = false;
        await load();
    } catch (e) {
        error.value = e.response?.data?.message || 'Création impossible.';
    } finally {
        creating.value = false;
    }
}

async function removeHost(host) {
    await axios.delete(`/api/database-hosts/${host.id}`);
    await load();
}

onMounted(load);
</script>
