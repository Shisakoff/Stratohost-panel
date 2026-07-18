<template>
    <div v-if="egg">
        <PageHeader :icon="Egg" :title="egg.name" :subtitle="egg.docker_image" :breadcrumbs="['Admin', 'Nests & Eggs', egg.name]" />

        <form class="card mb-6 space-y-4" @submit.prevent="saveConfig">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Configuration</h2>
            <div class="grid grid-cols-2 gap-4">
                <Field label="Nom"><input v-model="configForm.name" required class="input" /></Field>
                <Field label="Image Docker"><input v-model="configForm.docker_image" required class="input" /></Field>
            </div>
            <Field label="Description">
                <textarea v-model="configForm.description" rows="2" class="input"></textarea>
            </Field>
            <div class="grid grid-cols-2 gap-4">
                <Field label="Commande de démarrage">
                    <textarea v-model="configForm.startup" required rows="3" class="input font-mono text-xs"></textarea>
                </Field>
                <Field label="Commande d'arrêt"><input v-model="configForm.stop_command" class="input" /></Field>
            </div>
            <p v-if="configError" class="text-sm text-red-400">{{ configError }}</p>
            <button type="submit" :disabled="savingConfig" class="btn-primary">
                {{ savingConfig ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
        </form>

        <div class="card">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Variables</h2>

            <table class="table-clean mb-6">
                <thead><tr><th>Nom</th><th>Env</th><th>Défaut</th><th>Règles</th><th>Visible</th><th>Modifiable</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="v in variables" :key="v.id">
                        <td class="font-medium text-slate-200">{{ v.name }}</td>
                        <td><code class="text-slate-400">{{ v.env_variable }}</code></td>
                        <td class="text-slate-400">{{ v.default_value }}</td>
                        <td class="text-slate-500">{{ v.rules }}</td>
                        <td>
                            <span :class="v.user_viewable ? 'text-emerald-400' : 'text-slate-600'">{{ v.user_viewable ? 'Oui' : 'Non' }}</span>
                        </td>
                        <td>
                            <span :class="v.user_editable ? 'text-emerald-400' : 'text-slate-600'">{{ v.user_editable ? 'Oui' : 'Non' }}</span>
                        </td>
                        <td class="text-right">
                            <button type="button" class="text-red-400 hover:text-red-300" title="Supprimer" @click="removeVariable(v)">
                                <Trash2 class="size-4" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="variables.length === 0">
                        <td colspan="7" class="py-6 text-slate-500">Aucune variable pour l'instant.</td>
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
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input v-model="form.user_viewable" type="checkbox" class="size-4 rounded border-slate-700 bg-slate-900" />
                    Visible par le propriétaire du serveur
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input v-model="form.user_editable" type="checkbox" class="size-4 rounded border-slate-700 bg-slate-900" />
                    Modifiable par le propriétaire du serveur
                </label>
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
const form = ref({ name: '', env_variable: '', default_value: '', rules: 'nullable|string', user_viewable: true, user_editable: true });

const configForm = ref({ name: '', description: '', docker_image: '', startup: '', stop_command: '' });
const configError = ref('');
const savingConfig = ref(false);

async function load() {
    const { data } = await axios.get(`/api/eggs/${props.id}`);
    egg.value = data;
    variables.value = data.variables;
    configForm.value = {
        name: data.name,
        description: data.description ?? '',
        docker_image: data.docker_image,
        startup: data.startup,
        stop_command: data.stop_command,
    };
}

async function saveConfig() {
    configError.value = '';
    savingConfig.value = true;
    try {
        await axios.put(`/api/eggs/${props.id}`, configForm.value);
        await load();
    } catch (e) {
        configError.value = e.response?.data?.message || 'Enregistrement impossible.';
    } finally {
        savingConfig.value = false;
    }
}

async function addVariable() {
    error.value = '';
    try {
        await axios.post(`/api/eggs/${props.id}/variables`, form.value);
        form.value = { name: '', env_variable: '', default_value: '', rules: 'nullable|string', user_viewable: true, user_editable: true };
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
