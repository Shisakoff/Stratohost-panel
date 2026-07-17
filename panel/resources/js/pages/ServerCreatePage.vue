<template>
    <div>
        <PageHeader :icon="Swords" title="Nouveau serveur" subtitle="Ajouter un nouveau serveur au panel." :breadcrumbs="['Admin', 'Serveurs', 'Nouveau serveur']" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="card space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Détails du serveur</h2>
                <div class="grid grid-cols-2 gap-4">
                    <Field label="Nom"><input v-model="form.name" required class="input" /></Field>
                    <Field label="Node">
                        <select v-model.number="form.node_id" required class="input" @change="onNodeChange">
                            <option disabled value="">Choisir un node</option>
                            <option v-for="n in nodes" :key="n.id" :value="n.id">{{ n.name }}</option>
                        </select>
                    </Field>
                    <Field label="Allocation (IP:port)">
                        <select v-model.number="form.allocation_id" required class="input" :disabled="!form.node_id">
                            <option disabled value="">Choisir une allocation libre</option>
                            <option v-for="a in freeAllocations" :key="a.id" :value="a.id">{{ a.ip }}:{{ a.port }}</option>
                        </select>
                    </Field>
                    <Field label="Egg">
                        <select v-model.number="form.egg_id" required class="input" @change="onEggChange">
                            <option disabled value="">Choisir un egg</option>
                            <optgroup v-for="nest in nests" :key="nest.id" :label="nest.name">
                                <option v-for="egg in nestEggs[nest.id] || []" :key="egg.id" :value="egg.id">
                                    {{ egg.name }}
                                </option>
                            </optgroup>
                        </select>
                    </Field>
                </div>
            </div>

            <div class="card space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Limites des ressources</h2>
                <div class="grid grid-cols-3 gap-4">
                    <Field label="Mémoire (MB)"><input v-model.number="form.memory" type="number" required class="input" /></Field>
                    <Field label="Disque (MB)"><input v-model.number="form.disk" type="number" required class="input" /></Field>
                    <Field label="CPU (%)"><input v-model.number="form.cpu" type="number" class="input" /></Field>
                </div>
            </div>

            <div v-if="selectedEggVariables.length" class="card space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Variables</h2>
                <div class="grid grid-cols-2 gap-4">
                    <Field v-for="v in selectedEggVariables" :key="v.id" :label="v.name">
                        <input v-model="variableValues[v.id]" :placeholder="v.default_value ?? ''" class="input" />
                    </Field>
                </div>
            </div>

            <p v-if="error" class="rounded-lg bg-red-950/60 px-3 py-2 text-sm text-red-300">{{ error }}</p>
            <button type="submit" :disabled="loading" class="btn-primary">
                {{ loading ? 'Création...' : 'Créer le serveur' }}
            </button>
        </form>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { Swords } from '@lucide/vue';
import axios from '../lib/api';
import Field from '../components/Field.vue';
import PageHeader from '../components/PageHeader.vue';

const router = useRouter();

const nodes = ref([]);
const nests = ref([]);
const nestEggs = reactive({});
const freeAllocations = ref([]);
const selectedEggVariables = ref([]);
const variableValues = reactive({});
const error = ref('');
const loading = ref(false);

const form = ref({ name: '', node_id: '', allocation_id: '', egg_id: '', memory: 1024, disk: 5000, cpu: 100 });

async function onNodeChange() {
    form.value.allocation_id = '';
    const { data } = await axios.get(`/api/nodes/${form.value.node_id}/allocations`);
    freeAllocations.value = data.filter((a) => !a.server_id);
}

async function onEggChange() {
    const { data } = await axios.get(`/api/eggs/${form.value.egg_id}`);
    selectedEggVariables.value = data.variables;
    for (const v of data.variables) {
        variableValues[v.id] = v.default_value;
    }
    if (!form.value.name) {
        form.value.name = data.name;
    }
}

async function submit() {
    loading.value = true;
    error.value = '';
    try {
        const variables = selectedEggVariables.value.map((v) => ({
            egg_variable_id: v.id,
            value: variableValues[v.id],
        }));
        const { data } = await axios.post('/api/servers', { ...form.value, variables });
        router.push(`/servers/${data.uuid}`);
    } catch (e) {
        error.value = e.response?.data?.message || 'Création impossible.';
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    const [nodesRes, nestsRes] = await Promise.all([axios.get('/api/nodes'), axios.get('/api/nests')]);
    nodes.value = nodesRes.data;
    nests.value = nestsRes.data;

    await Promise.all(
        nests.value.map(async (nest) => {
            const { data } = await axios.get(`/api/nests/${nest.id}/eggs`);
            nestEggs[nest.id] = data;
        })
    );
});
</script>
