<template>
    <div v-if="server">
        <PageHeader :icon="CalendarClock" title="Tâche automatique" subtitle="Gère toutes les tâches planifiées." :breadcrumbs="['Mes serveurs', server.name, 'Tâche automatique']" />

        <EmptyState
            :icon="CalendarClock"
            title="Aucun élément"
            message="La planification de tâches (redémarrages, commandes, sauvegardes automatiques...) arrive dans une prochaine mise à jour."
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { CalendarClock } from '@lucide/vue';
import EmptyState from '../../components/EmptyState.vue';
import PageHeader from '../../components/PageHeader.vue';
import { useCurrentServerStore } from '../../stores/currentServer';

const store = useCurrentServerStore();
const server = computed(() => store.server);
</script>
