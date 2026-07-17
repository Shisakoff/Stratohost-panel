<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Nodes</h1>
            <button
                type="button"
                class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium hover:bg-indigo-500"
                @click="showForm = !showForm"
            >
                {{ showForm ? 'Annuler' : 'Nouveau node' }}
            </button>
        </div>

        <form
            v-if="showForm"
            class="grid grid-cols-2 gap-4 rounded-lg border border-slate-800 bg-slate-900 p-6"
            @submit.prevent="createNode"
        >
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
                <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium hover:bg-indigo-500">
                    Créer
                </button>
            </div>
        </form>

        <div v-if="createdToken" class="rounded-lg border border-amber-700 bg-amber-950/40 p-4 text-sm">
            <p class="mb-2 font-medium text-amber-300">Token affiché une seule fois - copie-le maintenant.</p>
            <p>token id: <code>{{ createdToken.id }}</code></p>
            <p>token: <code>{{ createdToken.token }}</code></p>
            <p class="mb-1 mt-2">Commande à lancer sur le node (en root) :</p>
            <pre class="overflow-x-auto rounded bg-slate-950 p-3 text-xs">{{ createdCommand }}</pre>
        </div>

        <table class="w-full text-sm">
            <thead class="text-left text-slate-400">
                <tr>
                    <th class="pb-2">Nom</th>
                    <th>FQDN</th>
                    <th>Allocations</th>
                    <th>Serveurs</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="node in nodes" :key="node.id" class="border-t border-slate-800">
                    <td class="py-2">
                        <RouterLink :to="`/nodes/${node.id}`" class="text-indigo-400 hover:underline">
                            {{ node.name }}
                        </RouterLink>
                    </td>
                    <td>{{ node.fqdn }}</td>
                    <td>{{ node.allocations_count }}</td>
                    <td>{{ node.servers_count }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import axios from '../lib/api';
import Field from '../components/Field.vue';

const nodes = ref([]);
const showForm = ref(false);
const createdToken = ref(null);
const createdCommand = ref('');
const error = ref('');
const form = ref({ name: '', fqdn: '', scheme: 'https', daemon_port: 8080, memory: 2048, disk: 10240 });

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
