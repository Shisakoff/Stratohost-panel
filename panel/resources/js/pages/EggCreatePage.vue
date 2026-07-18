<template>
    <div>
        <PageHeader
            :icon="Egg"
            title="Nouvel egg"
            subtitle="Créer un modèle de jeu à installer sur les serveurs."
            :breadcrumbs="['Admin', 'Nests & Eggs', nest?.name ?? '…', 'Nouvel egg']"
        />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="card space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Configuration</h2>
                <div class="grid grid-cols-2 gap-4">
                    <Field label="Nom"><input v-model="form.name" required class="input" /></Field>
                    <Field label="Image Docker">
                        <input v-model="form.docker_image" required placeholder="itzg/minecraft-server" class="input" />
                    </Field>
                </div>
                <Field label="Description">
                    <textarea v-model="form.description" rows="2" class="input"></textarea>
                </Field>
                <div class="grid grid-cols-2 gap-4">
                    <Field label="Commande de démarrage">
                        <textarea v-model="form.startup" required rows="3" class="input font-mono text-xs"></textarea>
                    </Field>
                    <Field label="Commande d'arrêt">
                        <input v-model="form.stop_command" placeholder="stop" class="input" />
                    </Field>
                </div>
            </div>

            <div class="card space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Script d'installation</h2>
                <p class="text-sm text-slate-500">
                    Exécuté une seule fois dans un conteneur jetable avant le premier démarrage, pour préparer les
                    fichiers du serveur. Laisse vide s'il n'y a rien à installer.
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <Field label="Image d'installation">
                        <input v-model="form.install_image" placeholder="alpine:3.19" class="input" />
                    </Field>
                    <Field label="Point d'entrée">
                        <input v-model="form.install_entrypoint" placeholder="bash" class="input" />
                    </Field>
                </div>
                <Field label="Script">
                    <textarea v-model="form.install_script" rows="8" class="input font-mono text-xs"></textarea>
                </Field>
            </div>

            <p v-if="error" class="rounded-lg bg-red-950/60 px-3 py-2 text-sm text-red-300">{{ error }}</p>
            <div class="flex gap-3">
                <button type="submit" :disabled="loading" class="btn-primary">
                    {{ loading ? 'Création...' : "Créer l'egg" }}
                </button>
                <RouterLink to="/nests" class="btn-secondary">Annuler</RouterLink>
            </div>
        </form>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { Egg } from '@lucide/vue';
import axios from '../lib/api';
import Field from '../components/Field.vue';
import PageHeader from '../components/PageHeader.vue';

const props = defineProps({ nestId: { type: [String, Number], required: true } });
const router = useRouter();

const nest = ref(null);
const error = ref('');
const loading = ref(false);

const form = ref({
    name: '',
    description: '',
    docker_image: '',
    startup: '',
    stop_command: 'stop',
    install_image: '',
    install_entrypoint: '',
    install_script: '',
});

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        const { data } = await axios.post(`/api/nests/${props.nestId}/eggs`, form.value);
        router.push(`/eggs/${data.id}`);
    } catch (e) {
        error.value = e.response?.data?.message || 'Création impossible.';
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    const { data } = await axios.get(`/api/nests/${props.nestId}`);
    nest.value = data;
});
</script>
