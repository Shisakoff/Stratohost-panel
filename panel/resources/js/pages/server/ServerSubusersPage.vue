<template>
    <div v-if="server">
        <PageHeader :icon="Users" title="Sous-utilisateur" subtitle="Contrôle qui peut accéder à ton serveur." :breadcrumbs="['Mes serveurs', server.name, 'Sous-utilisateur']" />

        <EmptyState
            :icon="Users"
            title="Aucun élément"
            message="Le partage d'accès à ce serveur avec d'autres comptes arrive dans une prochaine mise à jour."
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Users } from '@lucide/vue';
import EmptyState from '../../components/EmptyState.vue';
import PageHeader from '../../components/PageHeader.vue';
import { useCurrentServerStore } from '../../stores/currentServer';

const store = useCurrentServerStore();
const server = computed(() => store.server);
</script>
