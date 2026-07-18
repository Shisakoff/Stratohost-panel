import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

import LoginPage from './pages/LoginPage.vue';
import DashboardPage from './pages/DashboardPage.vue';
import NodesPage from './pages/NodesPage.vue';
import NodeDetailPage from './pages/NodeDetailPage.vue';
import NestsPage from './pages/NestsPage.vue';
import EggCreatePage from './pages/EggCreatePage.vue';
import EggDetailPage from './pages/EggDetailPage.vue';
import ServersPage from './pages/ServersPage.vue';
import ServerCreatePage from './pages/ServerCreatePage.vue';
import ServerDetailPage from './pages/ServerDetailPage.vue';
import DatabaseHostsPage from './pages/DatabaseHostsPage.vue';
import UsersPage from './pages/UsersPage.vue';
import SecurityPage from './pages/SecurityPage.vue';

const routes = [
    { path: '/login', name: 'login', component: LoginPage, meta: { guest: true } },
    { path: '/', name: 'dashboard', component: DashboardPage },
    { path: '/nodes', name: 'nodes', component: NodesPage, meta: { adminOnly: true } },
    { path: '/nodes/:id', name: 'node-detail', component: NodeDetailPage, props: true, meta: { adminOnly: true } },
    { path: '/nests', name: 'nests', component: NestsPage, meta: { adminOnly: true } },
    { path: '/nests/:nestId/eggs/new', name: 'egg-create', component: EggCreatePage, props: true, meta: { adminOnly: true } },
    { path: '/eggs/:id', name: 'egg-detail', component: EggDetailPage, props: true, meta: { adminOnly: true } },
    { path: '/servers', name: 'servers', component: ServersPage },
    { path: '/servers/new', name: 'server-create', component: ServerCreatePage, meta: { adminOnly: true } },
    { path: '/servers/:uuid', name: 'server-detail', component: ServerDetailPage, props: true },
    { path: '/database-hosts', name: 'database-hosts', component: DatabaseHostsPage, meta: { adminOnly: true } },
    { path: '/users', name: 'users', component: UsersPage, meta: { adminOnly: true } },
    { path: '/security', name: 'security', component: SecurityPage },
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
    if (to.meta.adminOnly && !auth.user?.root_admin) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;
