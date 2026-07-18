<template>
    <div v-if="server">
        <PageHeader :icon="FolderOpen" title="Gestionnaire de fichiers" subtitle="Gère tous tes fichiers directement depuis le web." :breadcrumbs="['Mes serveurs', server.name, 'Fichiers']" />

        <EmptyState
            :icon="FolderOpen"
            title="Gestionnaire de fichiers bientôt disponible"
            message="Le parcours de fichiers, l'édition et l'upload arrivent avec la prochaine mise à jour de l'agent."
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { FolderOpen } from '@lucide/vue';
import EmptyState from '../../components/EmptyState.vue';
import PageHeader from '../../components/PageHeader.vue';
import { useCurrentServerStore } from '../../stores/currentServer';

const store = useCurrentServerStore();
const server = computed(() => store.server);
</script>
