<template>
    <div
        class="flex min-h-screen items-center justify-center bg-slate-950 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(99,102,241,0.18),transparent)] px-4"
    >
        <div class="w-full max-w-sm">
            <div class="mb-8 flex justify-center">
                <Logo :size="40" />
            </div>

            <form class="card space-y-5" @submit.prevent="submit">
                <div>
                    <h1 class="text-lg font-semibold text-slate-100">Connexion</h1>
                    <p class="mt-1 text-sm text-slate-500">Accède à ton panel StratoHost.</p>
                </div>

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-300">Email</span>
                    <input v-model="email" type="email" required autofocus class="input" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-300">Mot de passe</span>
                    <input v-model="password" type="password" required class="input" />
                </label>

                <p v-if="error" class="rounded-lg bg-red-950/60 px-3 py-2 text-sm text-red-300">{{ error }}</p>

                <button type="submit" :disabled="loading" class="btn-primary w-full justify-center">
                    {{ loading ? 'Connexion...' : 'Se connecter' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import Logo from '../components/Logo.vue';

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
