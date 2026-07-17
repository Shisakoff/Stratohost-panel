<template>
    <div v-if="node">
        <PageHeader
            :icon="Server"
            :title="node.name"
            :subtitle="`${node.scheme}://${node.fqdn}:${node.daemon_port}`"
            :breadcrumbs="['Admin', 'Nodes', node.name]"
        />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="card">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Allocations</h2>

                    <form class="mb-4 flex flex-wrap items-end gap-3" @submit.prevent="addAllocations">
                        <Field label="IP"><input v-model="allocForm.ip" required class="input w-40" /></Field>
                        <Field label="Ports (ex: 25565-25570 ou 25565)">
                            <input v-model="allocForm.portsRaw" required class="input w-64" />
                        </Field>
                        <button type="submit" class="btn-primary">
                            <Plus class="size-4" /> Ajouter
                        </button>
                    </form>
                    <p v-if="error" class="mb-4 text-sm text-red-400">{{ error }}</p>

                    <table class="table-clean">
                        <thead><tr><th>IP</th><th>Port</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="a in allocations" :key="a.id">
                                <td>{{ a.ip }}</td>
                                <td>{{ a.port }}</td>
                                <td>
                                    <span class="badge" :class="a.server_id ? 'bg-slate-500/10 text-slate-400' : 'bg-emerald-500/10 text-emerald-400'">
                                        {{ a.server_id ? 'utilisée' : 'libre' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <button
                                        v-if="!a.server_id"
                                        type="button"
                                        class="text-red-400 hover:text-red-300"
                                        title="Supprimer"
                                        @click="removeAllocation(a)"
                                    >
                                        <Trash2 class="size-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="allocations.length === 0">
                                <td colspan="4" class="py-6 text-slate-500">Aucune allocation pour l'instant.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card h-fit space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">En bref</h2>
                <dl class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400">
                            <MemoryStick class="size-4" />
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Mémoire</dt>
                            <dd class="text-sm font-medium text-slate-200">{{ node.memory }} MB</dd>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400">
                            <HardDrive class="size-4" />
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Disque</dt>
                            <dd class="text-sm font-medium text-slate-200">{{ node.disk }} MB</dd>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400">
                            <Swords class="size-4" />
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Serveurs</dt>
                            <dd class="text-sm font-medium text-slate-200">{{ node.servers_count }}</dd>
                        </div>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { HardDrive, MemoryStick, Plus, Server, Swords, Trash2 } from '@lucide/vue';
import axios from '../lib/api';
import Field from '../components/Field.vue';
import PageHeader from '../components/PageHeader.vue';

const props = defineProps({ id: { type: [String, Number], required: true } });

const node = ref(null);
const allocations = ref([]);
const error = ref('');
const allocForm = ref({ ip: '0.0.0.0', portsRaw: '' });

function parsePorts(raw) {
    const ports = [];
    for (const part of raw.split(',').map((p) => p.trim()).filter(Boolean)) {
        if (part.includes('-')) {
            const [start, end] = part.split('-').map(Number);
            for (let p = start; p <= end; p += 1) ports.push(p);
        } else {
            ports.push(Number(part));
        }
    }
    return ports;
}

async function load() {
    const { data } = await axios.get(`/api/nodes/${props.id}`);
    node.value = data;
    const allocRes = await axios.get(`/api/nodes/${props.id}/allocations`);
    allocations.value = allocRes.data;
}

async function addAllocations() {
    error.value = '';
    try {
        await axios.post(`/api/nodes/${props.id}/allocations`, {
            ip: allocForm.value.ip,
            ports: parsePorts(allocForm.value.portsRaw),
        });
        allocForm.value.portsRaw = '';
        await load();
    } catch (e) {
        error.value = e.response?.data?.message || 'Ajout impossible.';
    }
}

async function removeAllocation(allocation) {
    await axios.delete(`/api/allocations/${allocation.id}`);
    await load();
}

onMounted(load);
</script>
