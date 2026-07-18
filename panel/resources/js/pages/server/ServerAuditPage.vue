<template>
    <div v-if="server">
        <PageHeader :icon="Fingerprint" title="Journaux d'audit" subtitle="Voir tout ce qui se passe sur ton serveur." :breadcrumbs="breadcrumbs" />

        <EmptyState
            :icon="Fingerprint"
            title="Aucun élément"
            message="On dirait que tu n'as aucun journal d'audit. Cette fonctionnalité arrive dans une prochaine mise à jour."
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Fingerprint } from '@lucide/vue';
import EmptyState from '../../components/EmptyState.vue';
import PageHeader from '../../components/PageHeader.vue';
import { useCurrentServerStore } from '../../stores/currentServer';

const store = useCurrentServerStore();
const server = computed(() => store.server);
const breadcrumbs = computed(() => ['Mes serveurs', server.value?.name ?? '…', "Journaux d'audit"]);
</script>
