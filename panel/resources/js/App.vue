<template>
    <div v-if="auth.isAuthenticated" class="flex min-h-screen bg-slate-950 text-slate-100">
        <aside class="flex w-64 shrink-0 flex-col border-r border-slate-800/80 bg-black/30">
            <div class="flex h-16 items-center px-5">
                <Logo :size="30" />
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
                <div v-for="group in navGroups" :key="group.label" class="space-y-1">
                    <div class="nav-group-label mb-1">{{ group.label }}</div>
                    <RouterLink
                        v-for="item in group.items"
                        :key="item.to"
                        :to="item.to"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-400 transition-colors hover:bg-slate-800/60 hover:text-slate-100"
                        active-class="!bg-emerald-500/10 !text-emerald-400"
                    >
                        <component :is="item.icon" class="size-[18px] shrink-0" />
                        {{ item.label }}
                    </RouterLink>
                </div>
            </nav>

            <div class="border-t border-slate-800/80 p-3">
                <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                    <div
                        class="flex size-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-sm font-semibold text-emerald-400"
                    >
                        {{ initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium text-slate-200">{{ auth.user?.name }}</div>
                        <div class="truncate text-xs text-slate-500">{{ auth.user?.email }}</div>
                    </div>
                    <button
                        type="button"
                        title="Déconnexion"
                        class="rounded-md p-1.5 text-slate-500 transition-colors hover:bg-slate-800 hover:text-slate-200"
                        @click="logout"
                    >
                        <LogOut class="size-4" />
                    </button>
                </div>
            </div>
        </aside>

        <main class="min-w-0 flex-1 overflow-y-auto">
            <div class="mx-auto max-w-6xl px-8 py-8">
                <RouterView />
            </div>
        </main>
    </div>

    <RouterView v-else />
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { Database, LayoutDashboard, LogOut, Server, Sprout, Swords } from '@lucide/vue';
import { useAuthStore } from './stores/auth';
import Logo from './components/Logo.vue';

const auth = useAuthStore();
const router = useRouter();

const navGroups = [
    {
        label: 'Général',
        items: [{ to: '/', label: 'Tableau de bord', icon: LayoutDashboard }],
    },
    {
        label: 'Infrastructure',
        items: [
            { to: '/nodes', label: 'Nodes', icon: Server },
            { to: '/database-hosts', label: 'Bases de données', icon: Database },
        ],
    },
    {
        label: 'Contenu',
        items: [{ to: '/nests', label: 'Nests & Eggs', icon: Sprout }],
    },
    {
        label: 'Serveurs',
        items: [{ to: '/servers', label: 'Serveurs', icon: Swords }],
    },
];

const initials = computed(() => {
    const name = auth.user?.name ?? '';
    return name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});

async function logout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>
