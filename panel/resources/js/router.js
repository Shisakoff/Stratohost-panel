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
import DatabaseHostsPage from './pages/DatabaseHostsPage.vue';
import UsersPage from './pages/UsersPage.vue';
import SecurityPage from './pages/SecurityPage.vue';

import ServerShell from './pages/server/ServerShell.vue';
import ServerConsolePage from './pages/server/ServerConsolePage.vue';
import ServerFilesPage from './pages/server/ServerFilesPage.vue';
import ServerOptionsPage from './pages/server/ServerOptionsPage.vue';
import ServerInfoPage from './pages/server/ServerInfoPage.vue';
import ServerSftpPage from './pages/server/ServerSftpPage.vue';
import ServerAuditPage from './pages/server/ServerAuditPage.vue';
import ServerDatabasesPage from './pages/server/ServerDatabasesPage.vue';
import ServerBackupsPage from './pages/server/ServerBackupsPage.vue';
import ServerSubusersPage from './pages/server/ServerSubusersPage.vue';
import ServerAllocationsPage from './pages/server/ServerAllocationsPage.vue';
import ServerAdvancedPage from './pages/server/ServerAdvancedPage.vue';
import ServerSchedulesPage from './pages/server/ServerSchedulesPage.vue';

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
    {
        path: '/servers/:uuid',
        component: ServerShell,
        props: true,
        meta: { serverScoped: true },
        children: [
            { path: '', redirect: (to) => ({ path: `/servers/${to.params.uuid}/console` }) },
            { path: 'console', name: 'server-console', component: ServerConsolePage, props: true },
            { path: 'files', name: 'server-files', component: ServerFilesPage, props: true },
            { path: 'options', name: 'server-options', component: ServerOptionsPage, props: true },
            { path: 'details', name: 'server-details', component: ServerInfoPage, props: true },
            { path: 'sftp', name: 'server-sftp', component: ServerSftpPage, props: true },
            { path: 'audit', name: 'server-audit', component: ServerAuditPage, props: true },
            { path: 'databases', name: 'server-databases', component: ServerDatabasesPage, props: true },
            { path: 'backups', name: 'server-backups', component: ServerBackupsPage, props: true },
            { path: 'subusers', name: 'server-subusers', component: ServerSubusersPage, props: true },
            { path: 'allocations', name: 'server-allocations', component: ServerAllocationsPage, props: true },
            { path: 'advanced', name: 'server-advanced', component: ServerAdvancedPage, props: true },
            { path: 'schedules', name: 'server-schedules', component: ServerSchedulesPage, props: true },
        ],
    },
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
