<template>
    <div v-if="server">
        <PageHeader :icon="Settings2" title="Avancé" subtitle="Gère les options supplémentaires pour ton serveur ici." :breadcrumbs="['Mes serveurs', server.name, 'Avancé']" />

        <EmptyState
            :icon="Settings2"
            title="Rien à configurer pour le moment"
            message="Les options avancées propres à chaque egg (réinstallation, variantes d'image...) arrivent dans une prochaine mise à jour."
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Settings2 } from '@lucide/vue';
import EmptyState from '../../components/EmptyState.vue';
import PageHeader from '../../components/PageHeader.vue';
import { useCurrentServerStore } from '../../stores/currentServer';

const store = useCurrentServerStore();
const server = computed(() => store.server);
</script>
