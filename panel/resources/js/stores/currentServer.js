import { defineStore } from 'pinia';
import axios from '../lib/api';

const STATS_HISTORY_LENGTH = 20;

/**
 * Single source of truth for "the server whose sub-pages are currently
 * open" (console, files, options, databases...). Living in a store rather
 * than being fetched per-page means the sidebar (rendered by App.vue,
 * outside the routed page) and every tab share one fetch and one poll
 * instead of each re-fetching and re-polling independently.
 */
export const useCurrentServerStore = defineStore('currentServer', {
    state: () => ({
        server: null,
        liveStatus: '',
        stats: null,
        cpuHistory: [],
        memHistory: [],
    }),
    actions: {
        async load(uuid) {
            const { data } = await axios.get(`/api/servers/${uuid}`);
            this.server = data;
        },
        async pollStatus(uuid) {
            try {
                const { data } = await axios.get(`/api/servers/${uuid}/status`);
                this.liveStatus = data.status;
            } catch {
                // Node unreachable right now - keep showing the last known status.
            }
        },
        async pollStats(uuid) {
            try {
                const { data } = await axios.get(`/api/servers/${uuid}/stats`);
                this.stats = data;
                if (data.memory_usage_bytes > 0 || data.cpu_percent > 0) {
                    this.cpuHistory.push(data.cpu_percent);
                    this.memHistory.push(data.memory_usage_bytes);
                    if (this.cpuHistory.length > STATS_HISTORY_LENGTH) this.cpuHistory.shift();
                    if (this.memHistory.length > STATS_HISTORY_LENGTH) this.memHistory.shift();
                }
            } catch {
                // Node unreachable right now - just skip this tick of the graph.
            }
        },
        async power(uuid, action) {
            await axios.post(`/api/servers/${uuid}/power`, { action });
            await this.pollStatus(uuid);
        },
        clear() {
            this.server = null;
            this.liveStatus = '';
            this.stats = null;
            this.cpuHistory = [];
            this.memHistory = [];
        },
    },
});
