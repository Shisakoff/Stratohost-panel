<template>
    <RouterView v-if="store.server" />
</template>

<script setup>
import { onMounted, onUnmounted, watch } from 'vue';
import { RouterView } from 'vue-router';
import { useCurrentServerStore } from '../../stores/currentServer';

const props = defineProps({ uuid: { type: String, required: true } });
const store = useCurrentServerStore();

let statusHandle = null;
let statsHandle = null;

function stopPolling() {
    if (statusHandle) clearInterval(statusHandle);
    if (statsHandle) clearInterval(statsHandle);
    statusHandle = null;
    statsHandle = null;
}

async function start(uuid) {
    await store.load(uuid);
    await store.pollStatus(uuid);
    await store.pollStats(uuid);
    statusHandle = setInterval(() => store.pollStatus(uuid), 5000);
    statsHandle = setInterval(() => store.pollStats(uuid), 3000);
}

onMounted(() => start(props.uuid));

watch(
    () => props.uuid,
    (uuid) => {
        stopPolling();
        store.clear();
        start(uuid);
    }
);

onUnmounted(() => {
    stopPolling();
    store.clear();
});
</script>
