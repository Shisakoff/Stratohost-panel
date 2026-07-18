<template>
    <div v-if="server">
        <PageHeader :icon="Archive" title="Backups" subtitle="Gère et déploie les sauvegardes de ton serveur ici." :breadcrumbs="['Mes serveurs', server.name, 'Backups']" />

        <div class="mb-6 card w-fit">
            <div class="flex items-center gap-3">
                <Archive class="size-5 text-emerald-400" />
                <div>
                    <div class="text-xs text-slate-500">Total des sauvegardes</div>
                    <div class="text-lg font-semibold text-slate-100">0</div>
                </div>
            </div>
        </div>

        <EmptyState
            :icon="Archive"
            title="Aucun élément"
            message="Les sauvegardes automatiques et manuelles arrivent dans une prochaine mise à jour."
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Archive } from '@lucide/vue';
import EmptyState from '../../components/EmptyState.vue';
import PageHeader from '../../components/PageHeader.vue';
import { useCurrentServerStore } from '../../stores/currentServer';

const store = useCurrentServerStore();
const server = computed(() => store.server);
</script>
