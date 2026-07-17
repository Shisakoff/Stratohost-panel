<template>
    <div class="space-y-4">
        <h1 class="text-2xl font-semibold">Tableau de bord</h1>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <RouterLink to="/nodes" class="rounded-lg border border-slate-800 bg-slate-900 p-6 hover:border-slate-600">
                <div class="text-lg font-medium">Nodes</div>
                <div class="text-sm text-slate-400">Les machines qui hébergent les serveurs de jeu</div>
            </RouterLink>
            <RouterLink to="/nests" class="rounded-lg border border-slate-800 bg-slate-900 p-6 hover:border-slate-600">
                <div class="text-lg font-medium">Nests &amp; Eggs</div>
                <div class="text-sm text-slate-400">Les modèles de jeux disponibles à l'installation</div>
            </RouterLink>
            <RouterLink to="/servers" class="rounded-lg border border-slate-800 bg-slate-900 p-6 hover:border-slate-600">
                <div class="text-lg font-medium">Serveurs</div>
                <div class="text-sm text-slate-400">Créer et piloter les serveurs de jeu</div>
            </RouterLink>
        </div>
    </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
</script>
