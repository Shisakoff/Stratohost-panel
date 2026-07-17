<template>
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <nav v-if="auth.isAuthenticated" class="flex items-center gap-6 border-b border-slate-800 px-6 py-3">
            <span class="text-lg font-semibold">StratoHost</span>
            <RouterLink to="/" class="text-sm text-slate-300 hover:text-white">Tableau de bord</RouterLink>
            <RouterLink to="/nodes" class="text-sm text-slate-300 hover:text-white">Nodes</RouterLink>
            <RouterLink to="/nests" class="text-sm text-slate-300 hover:text-white">Nests &amp; Eggs</RouterLink>
            <RouterLink to="/servers" class="text-sm text-slate-300 hover:text-white">Serveurs</RouterLink>
            <button type="button" class="ml-auto text-sm text-slate-400 hover:text-white" @click="logout">
                Déconnexion
            </button>
        </nav>
        <main class="p-6">
            <RouterView />
        </main>
    </div>
</template>

<script setup>
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';

const auth = useAuthStore();
const router = useRouter();

async function logout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>
