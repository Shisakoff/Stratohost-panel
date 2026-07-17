<template>
    <div v-if="node" class="max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold">{{ node.name }}</h1>
            <p class="text-sm text-slate-400">{{ node.scheme }}://{{ node.fqdn }}:{{ node.daemon_port }}</p>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900 p-6">
            <h2 class="mb-4 text-lg font-medium">Allocations</h2>

            <form class="mb-4 flex flex-wrap items-end gap-3" @submit.prevent="addAllocations">
                <Field label="IP"><input v-model="allocForm.ip" required class="input w-40" /></Field>
                <Field label="Ports (ex: 25565-25570 ou 25565)">
                    <input v-model="allocForm.portsRaw" required class="input w-64" />
                </Field>
                <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium hover:bg-indigo-500">
                    Ajouter
                </button>
            </form>
            <p v-if="error" class="mb-4 text-sm text-red-400">{{ error }}</p>

            <table class="w-full text-sm">
                <thead class="text-left text-slate-400">
                    <tr>
                        <th class="pb-2">IP</th>
                        <th>Port</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in allocations" :key="a.id" class="border-t border-slate-800">
                        <td class="py-2">{{ a.ip }}</td>
                        <td>{{ a.port }}</td>
                        <td>{{ a.server_id ? 'utilisée' : 'libre' }}</td>
                        <td>
                            <button
                                v-if="!a.server_id"
                                type="button"
                                class="text-red-400 hover:underline"
                                @click="removeAllocation(a)"
                            >
                                Supprimer
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import axios from '../lib/api';
import Field from '../components/Field.vue';

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
