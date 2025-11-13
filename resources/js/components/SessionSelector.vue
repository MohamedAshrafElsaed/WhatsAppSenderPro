<script lang="ts" setup>
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/composables/useTranslation';
import { CheckCircle, Phone } from 'lucide-vue-next';
import { computed } from 'vue';

interface WhatsAppSession {
    id: string;
    session_name: string;
    phone_number?: string;
    status: string;
}

interface Props {
    sessions: WhatsAppSession[];
    modelValue: string;
    error?: string;
}

interface Emits {
    (e: 'update:modelValue', value: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { t, isRTL } = useTranslation();

const selectedSession = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const connectedSessions = computed(() => {
    return props.sessions.filter((s) => s.status === 'connected');
});

const hasConnectedSessions = computed(() => connectedSessions.value.length > 0);
</script>

<template>
    <div class="space-y-2">
        <Label :class="isRTL() ? 'text-right' : 'text-left'">
            {{ t('campaigns.select_session', 'Select WhatsApp Session') }}
        </Label>

        <div
            v-if="!hasConnectedSessions"
            class="rounded-lg border border-red-200 bg-red-50 p-4 dark:bg-red-950"
        >
            <div
                :class="isRTL() ? 'flex-row-reverse text-right' : 'text-left'"
                class="flex items-start gap-3"
            >
                <Phone class="mt-0.5 h-5 w-5 text-red-600" />
                <div class="flex-1">
                    <p class="font-medium text-red-900 dark:text-red-100">
                        {{
                            t(
                                'campaigns.errors.no_connected_session',
                                'No Connected WhatsApp Session',
                            )
                        }}
                    </p>
                    <p class="mt-1 text-sm text-red-700 dark:text-red-200">
                        {{
                            t(
                                'campaigns.errors.no_connected_session_desc',
                                'Please connect a WhatsApp session before creating a campaign.',
                            )
                        }}
                    </p>
                </div>
            </div>
        </div>

        <Select v-else v-model="selectedSession">
            <SelectTrigger :class="error ? 'border-red-500' : ''">
                <SelectValue
                    :placeholder="
                        t('campaigns.select_session', 'Select WhatsApp Session')
                    "
                />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="session in connectedSessions"
                    :key="session.id"
                    :value="session.id"
                >
                    <div
                        :class="
                            isRTL()
                                ? 'flex-row-reverse text-right'
                                : 'text-left'
                        "
                        class="flex items-center gap-2"
                    >
                        <CheckCircle class="h-4 w-4 text-[#25D366]" />
                        <div>
                            <div class="font-medium">
                                {{ session.session_name }}
                            </div>
                            <div
                                v-if="session.phone_number"
                                class="text-xs text-muted-foreground"
                            >
                                {{ session.phone_number }}
                            </div>
                        </div>
                    </div>
                </SelectItem>
            </SelectContent>
        </Select>

        <p v-if="error" class="text-sm text-red-500">{{ error }}</p>
    </div>
</template>
