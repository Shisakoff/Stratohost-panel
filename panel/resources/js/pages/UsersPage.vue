<template>
    <div>
        <PageHeader :icon="Users" title="Utilisateurs" subtitle="Tous les comptes enregistrés sur le panel." :breadcrumbs="['Admin', 'Utilisateurs']" />

        <div class="mb-4 flex gap-3">
            <input v-model="search" placeholder="Recherche" class="input flex-1" />
            <button type="button" class="btn-primary" @click="showForm = !showForm">
                <Plus class="size-4" />
                {{ showForm ? 'Annuler' : 'Créer' }}
            </button>
        </div>

        <form v-if="showForm" class="card mb-4 grid grid-cols-2 gap-4" @submit.prevent="createUser">
            <Field label="Nom"><input v-model="form.name" required class="input" /></Field>
            <Field label="Email"><input v-model="form.email" type="email" required class="input" /></Field>
            <Field label="Mot de passe"><input v-model="form.password" type="password" required class="input" /></Field>
            <label class="flex items-center gap-2 self-end pb-2">
                <input v-model="form.root_admin" type="checkbox" class="size-4 rounded border-slate-700 bg-slate-800" />
                <span class="text-sm text-slate-300">Administrateur</span>
            </label>
            <p v-if="error" class="col-span-2 text-sm text-red-400">{{ error }}</p>
            <div class="col-span-2">
                <button type="submit" class="btn-primary">Créer</button>
            </div>
        </form>

        <div class="card p-0">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th class="pl-6">Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Serveurs</th>
                        <th class="pr-6"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in filteredUsers" :key="user.id">
                        <td class="pl-6 font-medium text-slate-100">{{ user.name }}</td>
                        <td class="text-slate-400">{{ user.email }}</td>
                        <td>
                            <span class="badge" :class="user.root_admin ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-500/10 text-slate-400'">
                                {{ user.root_admin ? 'Admin' : 'Utilisateur' }}
                            </span>
                        </td>
                        <td class="text-slate-400">{{ user.servers_count }}</td>
                        <td class="pr-6 text-right">
                            <button
                                type="button"
                                class="text-red-400 hover:text-red-300 disabled:opacity-40"
                                :disabled="user.id === currentUserId"
                                title="Supprimer"
                                @click="removeUser(user)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="filteredUsers.length === 0">
                        <td colspan="5" class="py-6 pl-6 text-slate-500">Aucun utilisateur pour l'instant.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Plus, Trash2, Users } from '@lucide/vue';
import axios from '../lib/api';
import Field from '../components/Field.vue';
import PageHeader from '../components/PageHeader.vue';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const currentUserId = computed(() => auth.user?.id);

const users = ref([]);
const search = ref('');
const showForm = ref(false);
const error = ref('');
const form = ref({ name: '', email: '', password: '', root_admin: false });

const filteredUsers = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return users.value;
    return users.value.filter((u) => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
});

async function load() {
    const { data } = await axios.get('/api/users');
    users.value = data;
}

async function createUser() {
    error.value = '';
    try {
        await axios.post('/api/users', form.value);
        form.value = { name: '', email: '', password: '', root_admin: false };
        showForm.value = false;
        await load();
    } catch (e) {
        error.value = e.response?.data?.message || 'Création impossible.';
    }
}

async function removeUser(user) {
    await axios.delete(`/api/users/${user.id}`);
    await load();
}

onMounted(load);
</script>
