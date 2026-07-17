<template>
    <div>
        <PageHeader :icon="Sprout" title="Nests & Eggs" subtitle="Les modèles de jeux disponibles à l'installation." :breadcrumbs="['Admin', 'Nests & Eggs']" />

        <div class="mb-4 flex justify-end">
            <button type="button" class="btn-primary" @click="showNestForm = !showNestForm">
                <Plus class="size-4" />
                {{ showNestForm ? 'Annuler' : 'Nouveau nest' }}
            </button>
        </div>

        <form v-if="showNestForm" class="card mb-4 flex items-end gap-3" @submit.prevent="createNest">
            <Field label="Nom"><input v-model="nestForm.name" required class="input w-64" /></Field>
            <Field label="Description"><input v-model="nestForm.description" class="input w-96" /></Field>
            <button type="submit" class="btn-primary">Créer</button>
        </form>

        <div class="space-y-4">
            <div v-for="nest in nests" :key="nest.id" class="card">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-medium text-slate-100">{{ nest.name }}</h2>
                        <p class="text-sm text-slate-500">{{ nest.description }}</p>
                    </div>
                    <button type="button" class="btn-secondary" @click="toggleNest(nest)">
                        {{ expanded[nest.id] ? 'Masquer les eggs' : `Voir les eggs (${nest.eggs_count})` }}
                    </button>
                </div>

                <div v-if="expanded[nest.id]" class="mt-4 space-y-3 border-t border-slate-800 pt-4">
                    <ul class="space-y-2">
                        <li
                            v-for="egg in eggsByNest[nest.id]"
                            :key="egg.id"
                            class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-slate-800/40"
                        >
                            <RouterLink :to="`/eggs/${egg.id}`" class="font-medium text-slate-200 hover:text-emerald-400">
                                {{ egg.name }}
                            </RouterLink>
                            <span class="text-slate-500">{{ egg.docker_image }}</span>
                        </li>
                        <li v-if="eggsByNest[nest.id] && eggsByNest[nest.id].length === 0" class="text-sm text-slate-500">
                            Aucun egg pour l'instant.
                        </li>
                    </ul>

                    <button type="button" class="btn-ghost !px-0" @click="toggleEggForm(nest)">
                        <Plus class="size-4" /> Ajouter un egg
                    </button>

                    <form
                        v-if="showEggForm[nest.id]"
                        class="space-y-3 rounded-lg border border-slate-800 bg-slate-950/60 p-4"
                        @submit.prevent="createEgg(nest)"
                    >
                        <Field label="Nom"><input v-model="eggForm.name" required class="input" /></Field>
                        <Field label="Image Docker">
                            <input v-model="eggForm.docker_image" required placeholder="itzg/minecraft-server" class="input" />
                        </Field>
                        <Field label="Commande de démarrage">
                            <textarea v-model="eggForm.startup" required rows="2" class="input"></textarea>
                        </Field>
                        <Field label="Commande d'arrêt"><input v-model="eggForm.stop_command" placeholder="stop" class="input" /></Field>
                        <Field label="Script d'installation (optionnel)">
                            <textarea v-model="eggForm.install_script" rows="4" class="input"></textarea>
                        </Field>
                        <p v-if="eggError" class="text-sm text-red-400">{{ eggError }}</p>
                        <button type="submit" class="btn-primary">Créer l'egg</button>
                    </form>
                </div>
            </div>

            <div v-if="nests.length === 0" class="card text-center text-slate-500">Aucun nest pour l'instant.</div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Plus, Sprout } from '@lucide/vue';
import axios from '../lib/api';
import Field from '../components/Field.vue';
import PageHeader from '../components/PageHeader.vue';

const nests = ref([]);
const expanded = reactive({});
const eggsByNest = reactive({});
const showNestForm = ref(false);
const showEggForm = reactive({});
const eggError = ref('');

const nestForm = ref({ name: '', description: '' });
const emptyEggForm = () => ({ name: '', docker_image: '', startup: '', stop_command: 'stop', install_script: '' });
const eggForm = ref(emptyEggForm());

async function load() {
    const { data } = await axios.get('/api/nests');
    nests.value = data;
}

async function loadEggs(nest) {
    const { data } = await axios.get(`/api/nests/${nest.id}/eggs`);
    eggsByNest[nest.id] = data;
    nest.eggs_count = data.length;
}

async function toggleNest(nest) {
    expanded[nest.id] = !expanded[nest.id];
    if (expanded[nest.id] && !eggsByNest[nest.id]) {
        await loadEggs(nest);
    }
}

function toggleEggForm(nest) {
    showEggForm[nest.id] = !showEggForm[nest.id];
    eggError.value = '';
}

async function createNest() {
    await axios.post('/api/nests', nestForm.value);
    nestForm.value = { name: '', description: '' };
    showNestForm.value = false;
    await load();
}

async function createEgg(nest) {
    eggError.value = '';
    try {
        await axios.post(`/api/nests/${nest.id}/eggs`, eggForm.value);
        eggForm.value = emptyEggForm();
        showEggForm[nest.id] = false;
        await loadEggs(nest);
    } catch (e) {
        eggError.value = e.response?.data?.message || 'Création impossible.';
    }
}

onMounted(load);
</script>
