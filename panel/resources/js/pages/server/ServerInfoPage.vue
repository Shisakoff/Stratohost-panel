<template>
    <div v-if="server">
        <PageHeader :icon="Info" title="Détails du serveur" subtitle="Voir les détails sur ton serveur de jeu." :breadcrumbs="['Mes serveurs', server.name, 'Détails du serveur']" />

        <div v-if="auth.user?.root_admin" class="card mb-6 space-y-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Nom du serveur</h2>
            <form class="flex items-end gap-3" @submit.prevent="rename">
                <Field label="Nom" class="flex-1"><input v-model="name" required class="input" /></Field>
                <button type="submit" :disabled="renaming" class="btn-primary">Mettre à jour</button>
            </form>
            <p v-if="renameError" class="text-sm text-red-400">{{ renameError }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="card">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Limites serveur</h2>
                <dl class="grid grid-cols-2 gap-y-3 text-sm">
                    <dt class="text-slate-500">Mémoire</dt>
                    <dd class="text-slate-200">{{ server.memory }} MB</dd>
                    <dt class="text-slate-500">Disque</dt>
                    <dd class="text-slate-200">{{ server.disk }} MB</dd>
                    <dt class="text-slate-500">CPU</dt>
                    <dd class="text-slate-200">{{ server.cpu }}%</dd>
                    <dt class="text-slate-500">Swap</dt>
                    <dd class="text-slate-200">{{ server.swap ?? 0 }} MB</dd>
                </dl>
            </div>

            <div class="card">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Informations serveur</h2>
                <dl class="grid grid-cols-2 gap-y-3 text-sm">
                    <dt class="text-slate-500">Egg</dt>
                    <dd class="text-slate-200">{{ server.egg.name }}</dd>
                    <dt class="text-slate-500">Node</dt>
                    <dd class="text-slate-200">{{ server.node.name }}</dd>
                    <dt class="text-slate-500">Statut</dt>
                    <dd class="text-slate-200">{{ store.liveStatus || server.status }}</dd>
                    <dt class="text-slate-500">IP</dt>
                    <dd class="font-mono text-xs text-slate-200">{{ server.allocation.ip }}:{{ server.allocation.port }}</dd>
                    <dt class="text-slate-500">UUID</dt>
                    <dd class="truncate font-mono text-xs text-slate-200">{{ server.uuid }}</dd>
                </dl>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Info } from '@lucide/vue';
import axios from '../../lib/api';
import Field from '../../components/Field.vue';
import PageHeader from '../../components/PageHeader.vue';
import { useAuthStore } from '../../stores/auth';
import { useCurrentServerStore } from '../../stores/currentServer';

const props = defineProps({ uuid: { type: String, required: true } });
const auth = useAuthStore();
const store = useCurrentServerStore();
const server = computed(() => store.server);

const name = ref(server.value?.name ?? '');
const renaming = ref(false);
const renameError = ref('');

watch(server, (s) => {
    if (s) name.value = s.name;
});

async function rename() {
    renaming.value = true;
    renameError.value = '';
    try {
        const { data } = await axios.put(`/api/servers/${props.uuid}`, { name: name.value });
        store.server.name = data.name;
    } catch (e) {
        renameError.value = e.response?.data?.message || 'Mise à jour impossible.';
    } finally {
        renaming.value = false;
    }
}
</script>
