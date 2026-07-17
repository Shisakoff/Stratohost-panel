<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Serveurs</h1>
            <RouterLink
                to="/servers/new"
                class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium hover:bg-indigo-500"
            >
                Nouveau serveur
            </RouterLink>
        </div>

        <table class="w-full text-sm">
            <thead class="text-left text-slate-400">
                <tr>
                    <th class="pb-2">Nom</th>
                    <th>Node</th>
                    <th>Egg</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="server in servers" :key="server.uuid" class="border-t border-slate-800">
                    <td class="py-2">
                        <RouterLink :to="`/servers/${server.uuid}`" class="text-indigo-400 hover:underline">
                            {{ server.name }}
                        </RouterLink>
                    </td>
                    <td>{{ server.node.name }}</td>
                    <td>{{ server.egg.name }}</td>
                    <td><StatusBadge :status="server.status" /></td>
                </tr>
                <tr v-if="servers.length === 0">
                    <td colspan="4" class="py-4 text-slate-500">Aucun serveur pour l'instant.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import axios from '../lib/api';
import StatusBadge from '../components/StatusBadge.vue';

const servers = ref([]);

onMounted(async () => {
    const { data } = await axios.get('/api/servers');
    servers.value = data;
});
</script>
