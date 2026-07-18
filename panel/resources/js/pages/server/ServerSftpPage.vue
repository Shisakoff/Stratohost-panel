<template>
    <div v-if="server">
        <PageHeader :icon="KeySquare" title="Détails SFTP" subtitle="Détails du compte pour les connexions SFTP." :breadcrumbs="['Mes serveurs', server.name, 'Détails SFTP']" />

        <EmptyState
            :icon="KeySquare"
            title="SFTP bientôt disponible"
            message="En attendant, utilise le gestionnaire de fichiers du panel une fois disponible."
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { KeySquare } from '@lucide/vue';
import EmptyState from '../../components/EmptyState.vue';
import PageHeader from '../../components/PageHeader.vue';
import { useCurrentServerStore } from '../../stores/currentServer';

const store = useCurrentServerStore();
const server = computed(() => store.server);
</script>
