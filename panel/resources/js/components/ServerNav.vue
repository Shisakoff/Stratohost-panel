<template>
    <div class="space-y-4">
        <RouterLink to="/servers" class="flex items-center gap-2 px-1 py-1 text-xs font-medium text-slate-500 transition-colors hover:text-slate-300">
            <ArrowLeft class="size-3.5" /> Mes serveurs
        </RouterLink>

        <div v-if="server" class="rounded-lg border border-slate-800/80 bg-slate-900/40 px-3 py-3">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate text-sm font-semibold text-slate-100">{{ server.name }}</span>
                <StatusBadge :status="store.liveStatus || server.status" />
            </div>
            <p class="mt-0.5 truncate font-mono text-xs text-slate-500">{{ server.allocation.ip }}:{{ server.allocation.port }}</p>
            <div class="mt-2.5 grid grid-cols-2 gap-y-1 text-[11px] text-slate-500">
                <span>CPU <span class="text-slate-300">{{ store.stats ? `${store.stats.cpu_percent.toFixed(0)}%` : '—' }}</span></span>
                <span>RAM <span class="text-slate-300">{{ store.stats ? formatMemory(store.stats.memory_usage_bytes) : '—' }}</span></span>
            </div>
        </div>

        <nav class="space-y-1">
            <RouterLink v-for="item in topItems" :key="item.to" :to="item.to" class="nav-link" active-class="nav-link-active">
                <component :is="item.icon" class="size-[18px] shrink-0" /> {{ item.label }}
            </RouterLink>
        </nav>

        <div v-for="group in groups" :key="group.label" class="space-y-1">
            <button type="button" class="nav-group-toggle" @click="open[group.label] = !open[group.label]">
                {{ group.label }}
                <ChevronDown class="size-3.5 transition-transform" :class="{ '-rotate-90': !open[group.label] }" />
            </button>
            <nav v-show="open[group.label]" class="space-y-1">
                <RouterLink v-for="item in group.items" :key="item.to" :to="item.to" class="nav-link" active-class="nav-link-active">
                    <component :is="item.icon" class="size-[18px] shrink-0" /> {{ item.label }}
                </RouterLink>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { RouterLink } from 'vue-router';
import {
    Archive,
    ArrowLeft,
    CalendarClock,
    ChevronDown,
    Database,
    Fingerprint,
    FolderOpen,
    Info,
    KeySquare,
    Settings2,
    Share2,
    SlidersHorizontal,
    Terminal,
    Users,
} from '@lucide/vue';
import { useCurrentServerStore } from '../stores/currentServer';
import StatusBadge from './StatusBadge.vue';

const props = defineProps({ uuid: { type: String, required: true } });
const store = useCurrentServerStore();
const server = computed(() => store.server);

const open = reactive({ Système: true, Gestion: true, Advanced: false });

function formatMemory(bytes) {
    const mb = bytes / 1024 / 1024;
    if (mb >= 1024) return `${(mb / 1024).toFixed(2)} GB`;
    return `${Math.round(mb)} MB`;
}

const base = computed(() => `/servers/${props.uuid}`);

const topItems = computed(() => [
    { to: `${base.value}/console`, label: 'Console', icon: Terminal },
    { to: `${base.value}/files`, label: 'Gestionnaire de fichiers', icon: FolderOpen },
    { to: `${base.value}/options`, label: 'Server Options', icon: SlidersHorizontal },
]);

const groups = computed(() => [
    {
        label: 'Système',
        items: [
            { to: `${base.value}/details`, label: 'Détails du serveur', icon: Info },
            { to: `${base.value}/sftp`, label: 'Détails SFTP', icon: KeySquare },
            { to: `${base.value}/audit`, label: "Journaux d'audit", icon: Fingerprint },
        ],
    },
    {
        label: 'Gestion',
        items: [
            { to: `${base.value}/databases`, label: 'Bases de données', icon: Database },
            { to: `${base.value}/backups`, label: 'Backups', icon: Archive },
            { to: `${base.value}/subusers`, label: 'Sous-utilisateur', icon: Users },
            { to: `${base.value}/allocations`, label: 'Allocations', icon: Share2 },
        ],
    },
    {
        label: 'Advanced',
        items: [
            { to: `${base.value}/advanced`, label: 'Avancé', icon: Settings2 },
            { to: `${base.value}/schedules`, label: 'Tâche automatique', icon: CalendarClock },
        ],
    },
]);
</script>
