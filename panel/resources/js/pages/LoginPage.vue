<template>
    <div class="flex min-h-[80vh] items-center justify-center">
        <form class="w-full max-w-sm space-y-4 rounded-lg border border-slate-800 bg-slate-900 p-8" @submit.prevent="submit">
            <h1 class="text-xl font-semibold">StratoHost</h1>
            <Field label="Email">
                <input v-model="email" type="email" required autofocus class="input" />
            </Field>
            <Field label="Mot de passe">
                <input v-model="password" type="password" required class="input" />
            </Field>
            <p v-if="error" class="text-sm text-red-400">{{ error }}</p>
            <button
                type="submit"
                :disabled="loading"
                class="w-full rounded bg-indigo-600 px-4 py-2 font-medium hover:bg-indigo-500 disabled:opacity-50"
            >
                {{ loading ? 'Connexion...' : 'Se connecter' }}
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import Field from '../components/Field.vue';

const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

async function submit() {
    loading.value = true;
    error.value = '';
    try {
        await auth.login(email.value, password.value);
        router.push(route.query.redirect || { name: 'dashboard' });
    } catch (e) {
        error.value = e.response?.data?.message || 'Connexion impossible.';
    } finally {
        loading.value = false;
    }
}
</script>
