<template>
    <div v-if="egg">
        <PageHeader :icon="Egg" :title="egg.name" :subtitle="egg.docker_image" :breadcrumbs="['Admin', 'Nests & Eggs', egg.name]" />

        <div class="card">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Variables</h2>

            <table class="table-clean mb-6">
                <thead><tr><th>Nom</th><th>Env</th><th>Défaut</th><th>Règles</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="v in variables" :key="v.id">
                        <td class="font-medium text-slate-200">{{ v.name }}</td>
                        <td><code class="text-slate-400">{{ v.env_variable }}</code></td>
                        <td class="text-slate-400">{{ v.default_value }}</td>
                        <td class="text-slate-500">{{ v.rules }}</td>
                        <td class="text-right">
                            <button type="button" class="text-red-400 hover:text-red-300" title="Supprimer" @click="removeVariable(v)">
                                <Trash2 class="size-4" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="variables.length === 0">
                        <td colspan="5" class="py-6 text-slate-500">Aucune variable pour l'instant.</td>
                    </tr>
                </tbody>
            </table>

            <form class="grid grid-cols-2 gap-3" @submit.prevent="addVariable">
                <Field label="Nom"><input v-model="form.name" required class="input" /></Field>
                <Field label="Variable d'env (ex: SERVER_JARFILE)">
                    <input v-model="form.env_variable" required class="input" />
                </Field>
                <Field label="Valeur par défaut"><input v-model="form.default_value" class="input" /></Field>
                <Field label="Règles de validation">
                    <input v-model="form.rules" placeholder="required|string" class="input" />
                </Field>
                <p v-if="error" class="col-span-2 text-sm text-red-400">{{ error }}</p>
                <div class="col-span-2">
                    <button type="submit" class="btn-primary">
                        <Plus class="size-4" /> Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { Egg, Plus, Trash2 } from '@lucide/vue';
import axios from '../lib/api';
import Field from '../components/Field.vue';
import PageHeader from '../components/PageHeader.vue';

const props = defineProps({ id: { type: [String, Number], required: true } });

const egg = ref(null);
const variables = ref([]);
const error = ref('');
const form = ref({ name: '', env_variable: '', default_value: '', rules: 'nullable|string' });

async function load() {
    const { data } = await axios.get(`/api/eggs/${props.id}`);
    egg.value = data;
    variables.value = data.variables;
}

async function addVariable() {
    error.value = '';
    try {
        await axios.post(`/api/eggs/${props.id}/variables`, form.value);
        form.value = { name: '', env_variable: '', default_value: '', rules: 'nullable|string' };
        await load();
    } catch (e) {
        error.value = e.response?.data?.message || 'Ajout impossible.';
    }
}

async function removeVariable(variable) {
    await axios.delete(`/api/variables/${variable.id}`);
    await load();
}

onMounted(load);
</script>
