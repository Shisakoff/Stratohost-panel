<template>
    <div>
        <PageHeader :icon="Server" title="Nodes" subtitle="Toutes les machines qui hébergent des serveurs de jeu." :breadcrumbs="['Admin', 'Nodes']" />

        <div class="mb-4 flex gap-3">
            <input v-model="search" placeholder="Recherche" class="input flex-1" />
            <button type="button" class="btn-primary" @click="showForm = !showForm">
                <Plus class="size-4" />
                {{ showForm ? 'Annuler' : 'Créer' }}
            </button>
        </div>

        <form v-if="showForm" class="card mb-4 grid grid-cols-2 gap-4" @submit.prevent="createNode">
            <Field label="Nom"><input v-model="form.name" required class="input" /></Field>
            <Field label="FQDN"><input v-model="form.fqdn" required placeholder="node1.example.com" class="input" /></Field>
            <Field label="Schéma">
                <select v-model="form.scheme" class="input">
                    <option value="https">https</option>
                    <option value="http">http</option>
                </select>
            </Field>
            <Field label="Port agent"><input v-model.number="form.daemon_port" type="number" required class="input" /></Field>
            <Field label="Mémoire (MB)"><input v-model.number="form.memory" type="number" required class="input" /></Field>
            <Field label="Disque (MB)"><input v-model.number="form.disk" type="number" required class="input" /></Field>
            <p v-if="error" class="col-span-2 text-sm text-red-400">{{ error }}</p>
            <div class="col-span-2">
                <button type="submit" class="btn-primary">Créer</button>
            </div>
        </form>

        <div v-if="createdToken" class="card mb-4 border-amber-700/50 bg-amber-950/20">
            <p class="mb-3 flex items-center gap-2 text-sm font-medium text-amber-300">
                <TriangleAlert class="size-4" /> Token affiché une seule fois - copie-le maintenant.
            </p>
            <p class="text-sm text-slate-300">token id: <code class="text-amber-200">{{ createdToken.id }}</code></p>
            <p class="text-sm text-slate-300">token: <code class="text-amber-200">{{ createdToken.token }}</code></p>
            <p class="mb-1.5 mt-3 text-sm text-slate-400">Commande à lancer sur le node (en root) :</p>
            <pre class="overflow-x-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-300">{{ createdCommand }}</pre>
        </div>

        <div class="card p-0">
            <table class="table-clean">
                <thead>
                    <tr class="px-6">
                        <th class="pl-6">Nom</th>
                        <th>FQDN</th>
                        <th>Allocations</th>
                        <th>Serveurs</th>
                        <th class="pr-6"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="node in filteredNodes" :key="node.id">
                        <td class="pl-6 font-medium text-slate-100">{{ node.name }}</td>
                        <td class="text-slate-400">{{ node.fqdn }}</td>
                        <td class="text-slate-400">{{ node.allocations_count }}</td>
                        <td class="text-slate-400">{{ node.servers_count }}</td>
                        <td class="pr-6 text-right">
                            <RouterLink :to="`/nodes/${node.id}`" class="btn-secondary">Gérer</RouterLink>
                        </td>
                    </tr>
                    <tr v-if="filteredNodes.length === 0">
                        <td colspan="5" class="py-6 pl-6 text-slate-500">Aucun node pour l'instant.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Plus, Server, TriangleAlert } from '@lucide/vue';
import axios from '../lib/api';
import Field from '../components/Field.vue';
import PageHeader from '../components/PageHeader.vue';

const nodes = ref([]);
const search = ref('');
const showForm = ref(false);
const createdToken = ref(null);
const createdCommand = ref('');
const error = ref('');
const form = ref({ name: '', fqdn: '', scheme: 'https', daemon_port: 8080, memory: 2048, disk: 10240 });

const filteredNodes = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return nodes.value;
    return nodes.value.filter((n) => n.name.toLowerCase().includes(q) || n.fqdn.toLowerCase().includes(q));
});

async function load() {
    const { data } = await axios.get('/api/nodes');
    nodes.value = data;
}

async function createNode() {
    error.value = '';
    try {
        const { data } = await axios.post('/api/nodes', form.value);
        createdToken.value = data.daemon_token;
        createdCommand.value = data.install_command;
        showForm.value = false;
        await load();
    } catch (e) {
        error.value = e.response?.data?.message || 'Création impossible.';
    }
}

onMounted(load);
</script>
