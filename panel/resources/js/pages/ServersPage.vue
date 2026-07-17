<template>
    <div>
        <PageHeader :icon="Swords" title="Serveurs" subtitle="Tous les serveurs disponibles sur le système." :breadcrumbs="['Admin', 'Serveurs']" />

        <div class="mb-4 flex gap-3">
            <input v-model="search" placeholder="Recherche" class="input flex-1" />
            <RouterLink to="/servers/new" class="btn-primary">
                <Plus class="size-4" /> Créer
            </RouterLink>
        </div>

        <div class="card p-0">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th class="pl-6">Nom</th>
                        <th>Propriétaire</th>
                        <th>Node</th>
                        <th>Egg</th>
                        <th>Connexion</th>
                        <th>Statut</th>
                        <th class="pr-6"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="server in filteredServers" :key="server.uuid">
                        <td class="pl-6 font-medium text-slate-100">{{ server.name }}</td>
                        <td class="text-slate-400">{{ server.owner?.name ?? '—' }}</td>
                        <td class="text-slate-400">{{ server.node.name }}</td>
                        <td class="text-slate-400">{{ server.egg.name }}</td>
                        <td class="font-mono text-xs text-slate-400">{{ server.allocation.ip }}:{{ server.allocation.port }}</td>
                        <td><StatusBadge :status="server.status" /></td>
                        <td class="pr-6 text-right">
                            <RouterLink :to="`/servers/${server.uuid}`" class="btn-secondary">Gérer</RouterLink>
                        </td>
                    </tr>
                    <tr v-if="filteredServers.length === 0">
                        <td colspan="7" class="py-6 pl-6 text-slate-500">Aucun serveur pour l'instant.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Plus, Swords } from '@lucide/vue';
import axios from '../lib/api';
import StatusBadge from '../components/StatusBadge.vue';
import PageHeader from '../components/PageHeader.vue';

const servers = ref([]);
const search = ref('');

const filteredServers = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return servers.value;
    return servers.value.filter((s) => s.name.toLowerCase().includes(q));
});

onMounted(async () => {
    const { data } = await axios.get('/api/servers');
    servers.value = data;
});
</script>
