<template>
    <div v-if="server">
        <PageHeader :icon="SlidersHorizontal" title="Server Options" subtitle="Options propres au jeu installé sur ce serveur." :breadcrumbs="['Mes serveurs', server.name, 'Server Options']" />

        <div v-if="visibleVariables.length" class="card">
            <form class="grid grid-cols-2 gap-4" @submit.prevent="saveVariables">
                <Field v-for="v in visibleVariables" :key="v.id" :label="v.eggVariable.name">
                    <input
                        v-model="variableValues[v.eggVariable.id]"
                        :disabled="!canEditVariable(v)"
                        class="input"
                        :class="{ 'opacity-50': !canEditVariable(v) }"
                    />
                </Field>
                <p v-if="variablesError" class="col-span-2 text-sm text-red-400">{{ variablesError }}</p>
                <p v-if="variablesSaved" class="col-span-2 text-sm text-emerald-400">
                    Enregistré. Redémarre le serveur pour appliquer les changements.
                </p>
                <div class="col-span-2">
                    <button type="submit" :disabled="savingVariables" class="btn-primary">
                        {{ savingVariables ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </div>

        <EmptyState v-else :icon="SlidersHorizontal" title="Aucune option" message="Cet egg ne définit aucune variable de démarrage." />
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { SlidersHorizontal } from '@lucide/vue';
import axios from '../../lib/api';
import EmptyState from '../../components/EmptyState.vue';
import Field from '../../components/Field.vue';
import PageHeader from '../../components/PageHeader.vue';
import { useAuthStore } from '../../stores/auth';
import { useCurrentServerStore } from '../../stores/currentServer';

const props = defineProps({ uuid: { type: String, required: true } });
const auth = useAuthStore();
const store = useCurrentServerStore();
const server = computed(() => store.server);

const variableValues = reactive({});
const variablesError = ref('');
const variablesSaved = ref(false);
const savingVariables = ref(false);

const visibleVariables = computed(() => {
    if (!server.value) return [];
    return server.value.variables.filter((v) => auth.user?.root_admin || v.eggVariable.user_viewable);
});

function canEditVariable(serverVariable) {
    return auth.user?.root_admin || serverVariable.eggVariable.user_editable;
}

function syncVariableValues() {
    for (const v of server.value.variables) {
        variableValues[v.eggVariable.id] = v.value ?? v.eggVariable.default_value;
    }
}

async function saveVariables() {
    variablesError.value = '';
    variablesSaved.value = false;
    savingVariables.value = true;
    try {
        const variables = visibleVariables.value
            .filter((v) => canEditVariable(v))
            .map((v) => ({ egg_variable_id: v.eggVariable.id, value: variableValues[v.eggVariable.id] }));
        const { data } = await axios.patch(`/api/servers/${props.uuid}/variables`, { variables });
        store.server.variables = data.variables;
        syncVariableValues();
        variablesSaved.value = true;
    } catch (e) {
        variablesError.value = e.response?.data?.message || 'Enregistrement impossible.';
    } finally {
        savingVariables.value = false;
    }
}

onMounted(syncVariableValues);
</script>
