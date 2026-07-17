<template>
    <div>
        <PageHeader :icon="LayoutDashboard" title="Tableau de bord" subtitle="Vue d'ensemble de ton infrastructure." :breadcrumbs="['Admin', 'Tableau de bord']" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="stat in stats" :key="stat.label" class="card">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-400">{{ stat.label }}</span>
                    <component :is="stat.icon" class="size-4 text-slate-600" />
                </div>
                <div class="mt-2 text-2xl font-semibold text-slate-100">{{ stat.value }}</div>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <RouterLink
                v-for="link in links"
                :key="link.to"
                :to="link.to"
                class="card group flex flex-col gap-3 transition-colors hover:border-emerald-500/40"
            >
                <div
                    class="flex size-10 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400 transition-colors group-hover:bg-emerald-500/20"
                >
                    <component :is="link.icon" class="size-5" />
                </div>
                <div>
                    <div class="font-medium text-slate-100">{{ link.title }}</div>
                    <div class="mt-0.5 text-sm text-slate-500">{{ link.description }}</div>
                </div>
            </RouterLink>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Database, LayoutDashboard, Server, Sprout, Swords } from '@lucide/vue';
import axios from '../lib/api';
import PageHeader from '../components/PageHeader.vue';

const stats = ref([
    { label: 'Nodes', value: '—', icon: Server },
    { label: 'Serveurs', value: '—', icon: Swords },
    { label: 'Eggs', value: '—', icon: Sprout },
    { label: 'Bases de données', value: '—', icon: Database },
]);

const links = [
    { to: '/nodes', title: 'Nodes', description: 'Machines qui hébergent les serveurs de jeu', icon: Server },
    { to: '/nests', title: 'Nests & Eggs', description: 'Modèles de jeux disponibles', icon: Sprout },
    { to: '/servers', title: 'Serveurs', description: 'Créer et piloter les serveurs de jeu', icon: Swords },
    { to: '/database-hosts', title: 'Bases de données', description: 'Serveurs MySQL pour les bases par serveur', icon: Database },
];

onMounted(async () => {
    const [nodes, servers, nests, hosts] = await Promise.all([
        axios.get('/api/nodes'),
        axios.get('/api/servers'),
        axios.get('/api/nests'),
        axios.get('/api/database-hosts'),
    ]);

    const eggCount = nests.data.reduce((sum, nest) => sum + nest.eggs_count, 0);

    stats.value = [
        { label: 'Nodes', value: nodes.data.length, icon: Server },
        { label: 'Serveurs', value: servers.data.length, icon: Swords },
        { label: 'Eggs', value: eggCount, icon: Sprout },
        { label: 'Bases de données', value: hosts.data.length, icon: Database },
    ];
});
</script>
