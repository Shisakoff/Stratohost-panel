<template>
    <div v-if="auth.isAuthenticated" class="flex min-h-screen bg-slate-950 text-slate-100">
        <aside class="flex w-64 shrink-0 flex-col border-r border-slate-800/80 bg-black/30">
            <div class="flex h-16 items-center px-5">
                <Logo :size="30" />
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
                <ServerNav v-if="isServerScoped" :uuid="String(route.params.uuid)" />

                <template v-else>
                    <RouterLink to="/" class="nav-link mb-2" active-class="nav-link-active">
                        <LayoutDashboard class="size-[18px] shrink-0" /> Tableau de bord
                    </RouterLink>

                    <div v-for="group in visibleNavGroups" :key="group.label" class="space-y-1">
                        <button type="button" class="nav-group-toggle" @click="openGroups[group.label] = !openGroups[group.label]">
                            {{ group.label }}
                            <ChevronDown class="size-3.5 transition-transform" :class="{ '-rotate-90': !openGroups[group.label] }" />
                        </button>
                        <div v-show="openGroups[group.label]" class="space-y-1">
                            <RouterLink v-for="item in group.items" :key="item.to" :to="item.to" class="nav-link" active-class="nav-link-active">
                                <component :is="item.icon" class="size-[18px] shrink-0" />
                                {{ item.label }}
                            </RouterLink>
                        </div>
                    </div>
                </template>
            </nav>

            <div class="border-t border-slate-800/80 p-3">
                <RouterLink
                    to="/security"
                    class="mb-1 flex items-center gap-3 rounded-lg px-2 py-2 transition-colors hover:bg-slate-800/60"
                >
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
                        @click.prevent="logout"
                    >
                        <LogOut class="size-4" />
                    </button>
                </RouterLink>
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
import { computed, reactive } from 'vue';
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router';
import { ChevronDown, Database, LayoutDashboard, LogOut, Server, Sprout, Swords, Users } from '@lucide/vue';
import { useAuthStore } from './stores/auth';
import Logo from './components/Logo.vue';
import ServerNav from './components/ServerNav.vue';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const isServerScoped = computed(() => route.matched.some((record) => record.meta.serverScoped));

const navGroups = [
    {
        label: 'Infrastructure',
        adminOnly: true,
        items: [
            { to: '/nodes', label: 'Nodes', icon: Server },
            { to: '/database-hosts', label: 'Bases de données', icon: Database },
        ],
    },
    {
        label: 'Contenu',
        adminOnly: true,
        items: [{ to: '/nests', label: 'Nests & Eggs', icon: Sprout }],
    },
    {
        label: 'Serveurs',
        items: [{ to: '/servers', label: auth.user?.root_admin ? 'Serveurs' : 'Mes serveurs', icon: Swords }],
    },
    {
        label: 'Gestion',
        adminOnly: true,
        items: [{ to: '/users', label: 'Utilisateurs', icon: Users }],
    },
];

const openGroups = reactive(Object.fromEntries(navGroups.map((group) => [group.label, true])));

const visibleNavGroups = computed(() => navGroups.filter((group) => !group.adminOnly || auth.user?.root_admin));

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
