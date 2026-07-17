<template>
    <div>
        <PageHeader :icon="ShieldCheck" title="Sécurité" subtitle="Authentification à deux facteurs pour ton compte." :breadcrumbs="['Mon compte', 'Sécurité']" />

        <div class="card max-w-xl space-y-5">
            <div v-if="auth.user?.two_factor_confirmed_at || confirmed" class="space-y-4">
                <p class="flex items-center gap-2 text-sm text-emerald-400">
                    <ShieldCheck class="size-4" /> La double authentification est activée sur ce compte.
                </p>

                <form class="space-y-3" @submit.prevent="disable">
                    <Field label="Mot de passe (pour désactiver)">
                        <input v-model="disablePassword" type="password" required class="input max-w-sm" />
                    </Field>
                    <p v-if="error" class="text-sm text-red-400">{{ error }}</p>
                    <button type="submit" class="btn-danger">Désactiver la 2FA</button>
                </form>
            </div>

            <div v-else-if="!setup" class="space-y-4">
                <p class="text-sm text-slate-400">
                    Protège ton compte avec une application d'authentification (Google Authenticator, Authy, etc.).
                </p>
                <button type="button" class="btn-primary" @click="startSetup">Activer la 2FA</button>
            </div>

            <div v-else-if="!recoveryCodes" class="space-y-4">
                <p class="text-sm text-slate-400">Scanne ce QR code avec ton application d'authentification.</p>
                <canvas ref="qrCanvas" class="rounded-lg border border-slate-800"></canvas>
                <p class="text-xs text-slate-500">
                    Ou entre ce secret manuellement : <code class="text-slate-300">{{ setup.secret }}</code>
                </p>

                <form class="space-y-3" @submit.prevent="confirmSetup">
                    <Field label="Code de vérification">
                        <input v-model="confirmCode" required autofocus class="input max-w-xs" placeholder="123456" />
                    </Field>
                    <p v-if="error" class="text-sm text-red-400">{{ error }}</p>
                    <button type="submit" class="btn-primary">Confirmer</button>
                </form>
            </div>

            <div v-else class="space-y-4">
                <p class="flex items-center gap-2 text-sm text-emerald-400">
                    <ShieldCheck class="size-4" /> 2FA activée avec succès.
                </p>
                <p class="text-sm text-slate-400">
                    Garde ces codes de récupération en lieu sûr - chacun ne fonctionne qu'une seule fois si tu perds
                    l'accès à ton application d'authentification.
                </p>
                <pre class="overflow-x-auto rounded-lg bg-slate-950 p-4 text-sm text-slate-200">{{ recoveryCodes.join('\n') }}</pre>
            </div>
        </div>
    </div>
</template>

<script setup>
import { nextTick, ref } from 'vue';
import { ShieldCheck } from '@lucide/vue';
import QRCode from 'qrcode';
import axios from '../lib/api';
import Field from '../components/Field.vue';
import PageHeader from '../components/PageHeader.vue';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();

const setup = ref(null);
const confirmCode = ref('');
const recoveryCodes = ref(null);
const confirmed = ref(false);
const disablePassword = ref('');
const error = ref('');
const qrCanvas = ref(null);

async function startSetup() {
    error.value = '';
    const { data } = await axios.post('/api/two-factor/enable');
    setup.value = data;
    await nextTick();
    await QRCode.toCanvas(qrCanvas.value, data.otpauth_url, { width: 220 });
}

async function confirmSetup() {
    error.value = '';
    try {
        const { data } = await axios.post('/api/two-factor/confirm', { code: confirmCode.value });
        recoveryCodes.value = data.recovery_codes;
        confirmed.value = true;
        await auth.fetchUser();
    } catch (e) {
        error.value = e.response?.data?.message || 'Code invalide.';
    }
}

async function disable() {
    error.value = '';
    try {
        await axios.post('/api/two-factor/disable', { password: disablePassword.value });
        disablePassword.value = '';
        confirmed.value = false;
        setup.value = null;
        recoveryCodes.value = null;
        await auth.fetchUser();
    } catch (e) {
        error.value = e.response?.data?.message || 'Mot de passe incorrect.';
    }
}
</script>
