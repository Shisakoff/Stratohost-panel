import { defineStore } from 'pinia';
import axios from '../lib/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        checked: false,
    }),
    getters: {
        isAuthenticated: (state) => state.user !== null,
    },
    actions: {
        async fetchUser() {
            try {
                const { data } = await axios.get('/api/me');
                this.user = data;
            } catch {
                this.user = null;
            } finally {
                this.checked = true;
            }
        },
        /**
         * Returns { twoFactor: true } instead of logging in when the
         * account has 2FA enabled - the caller must then call
         * completeTwoFactorChallenge() with a code before the user is
         * actually authenticated.
         */
        async login(email, password) {
            // Sanctum SPA auth: grab the XSRF-TOKEN cookie before the
            // stateful POST, or the login request gets rejected as a CSRF
            // mismatch.
            await axios.get('/sanctum/csrf-cookie');
            const { data } = await axios.post('/api/login', { email, password });

            if (data.two_factor) {
                return { twoFactor: true };
            }

            await this.fetchUser();
            return { twoFactor: false };
        },
        async completeTwoFactorChallenge(code) {
            await axios.post('/api/two-factor-challenge', { code });
            await this.fetchUser();
        },
        async logout() {
            await axios.post('/api/logout');
            this.user = null;
        },
    },
});
