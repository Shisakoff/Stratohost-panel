<template>
    <div v-if="egg" class="max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold">{{ egg.name }}</h1>
            <p class="text-sm text-slate-400">{{ egg.docker_image }}</p>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900 p-6">
            <h2 class="mb-4 text-lg font-medium">Variables</h2>

            <table class="mb-4 w-full text-sm">
                <thead class="text-left text-slate-400">
                    <tr>
                        <th class="pb-2">Nom</th>
                        <th>Env</th>
                        <th>Défaut</th>
                        <th>Règles</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="v in variables" :key="v.id" class="border-t border-slate-800">
                        <td class="py-2">{{ v.name }}</td>
                        <td><code>{{ v.env_variable }}</code></td>
                        <td>{{ v.default_value }}</td>
                        <td class="text-slate-500">{{ v.rules }}</td>
                        <td>
                            <button type="button" class="text-red-400 hover:underline" @click="removeVariable(v)">
                                Supprimer
                            </button>
                        </td>
                    </tr>
                    <tr v-if="variables.length === 0">
                        <td colspan="5" class="py-2 text-slate-500">Aucune variable pour l'instant.</td>
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
                    <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium hover:bg-indigo-500">
                        Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import axios from '../lib/api';
import Field from '../components/Field.vue';

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
