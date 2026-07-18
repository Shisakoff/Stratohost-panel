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

                    <RouterLink :to="`/nests/${nest.id}/eggs/new`" class="btn-ghost !px-0">
                        <Plus class="size-4" /> Ajouter un egg
                    </RouterLink>
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

const nestForm = ref({ name: '', description: '' });

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

async function createNest() {
    await axios.post('/api/nests', nestForm.value);
    nestForm.value = { name: '', description: '' };
    showNestForm.value = false;
    await load();
}

onMounted(load);
</script>
