<template>
    <div v-if="server">
        <PageHeader :icon="Share2" title="Allocations" subtitle="Les adresses IP et ports assignés à ce serveur." :breadcrumbs="['Mes serveurs', server.name, 'Allocations']" />

        <div class="card p-0">
            <table class="table-clean">
                <thead><tr><th class="pl-6">IP</th><th>Port</th><th class="pr-6">Principal</th></tr></thead>
                <tbody>
                    <tr>
                        <td class="pl-6 text-slate-300">{{ server.allocation.ip }}</td>
                        <td class="text-slate-300">{{ server.allocation.port }}</td>
                        <td class="pr-6"><span class="badge bg-emerald-500/10 text-emerald-300">Principal</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-sm text-slate-500">
            Ce serveur n'a qu'une seule allocation pour le moment - les ports supplémentaires par serveur arrivent
            plus tard.
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Share2 } from '@lucide/vue';
import PageHeader from '../../components/PageHeader.vue';
import { useCurrentServerStore } from '../../stores/currentServer';

const store = useCurrentServerStore();
const server = computed(() => store.server);
</script>
