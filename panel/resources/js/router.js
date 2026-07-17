import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

import LoginPage from './pages/LoginPage.vue';
import DashboardPage from './pages/DashboardPage.vue';
import NodesPage from './pages/NodesPage.vue';
import NodeDetailPage from './pages/NodeDetailPage.vue';
import NestsPage from './pages/NestsPage.vue';
import EggDetailPage from './pages/EggDetailPage.vue';
import ServersPage from './pages/ServersPage.vue';
import ServerCreatePage from './pages/ServerCreatePage.vue';
import ServerDetailPage from './pages/ServerDetailPage.vue';
import DatabaseHostsPage from './pages/DatabaseHostsPage.vue';

const routes = [
    { path: '/login', name: 'login', component: LoginPage, meta: { guest: true } },
    { path: '/', name: 'dashboard', component: DashboardPage },
    { path: '/nodes', name: 'nodes', component: NodesPage },
    { path: '/nodes/:id', name: 'node-detail', component: NodeDetailPage, props: true },
    { path: '/nests', name: 'nests', component: NestsPage },
    { path: '/eggs/:id', name: 'egg-detail', component: EggDetailPage, props: true },
    { path: '/servers', name: 'servers', component: ServersPage },
    { path: '/servers/new', name: 'server-create', component: ServerCreatePage },
    { path: '/servers/:uuid', name: 'server-detail', component: ServerDetailPage, props: true },
    { path: '/database-hosts', name: 'database-hosts', component: DatabaseHostsPage },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();
    if (!auth.checked) {
        await auth.fetchUser();
    }

    if (!auth.isAuthenticated && !to.meta.guest) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }
    if (auth.isAuthenticated && to.meta.guest) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;
