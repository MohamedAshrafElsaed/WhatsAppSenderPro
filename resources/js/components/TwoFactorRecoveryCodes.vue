<script setup lang="ts">
import AlertError from '@/components/AlertError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { useTranslation } from '@/composables/useTranslation';
import { regenerateRecoveryCodes } from '@/routes/two-factor';
import { Form, usePage } from '@inertiajs/vue3';
import { Eye, EyeOff, LockKeyhole, RefreshCw } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, useTemplateRef } from 'vue';

const { t } = useTranslation();
const page = usePage();
const locale = computed(() => page.props.locale || 'en');
const isRTL = computed(() => locale.value === 'ar');

const { recoveryCodesList, fetchRecoveryCodes, errors } = useTwoFactorAuth();
const isRecoveryCodesVisible = ref<boolean>(false);
const recoveryCodeSectionRef = useTemplateRef('recoveryCodeSectionRef');

const toggleRecoveryCodesVisibility = async () => {
    if (!isRecoveryCodesVisible.value && !recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }

    isRecoveryCodesVisible.value = !isRecoveryCodesVisible.value;

    if (isRecoveryCodesVisible.value) {
        await nextTick();
        recoveryCodeSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
    }
};

onMounted(async () => {
    if (!recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }
});
</script>

<template>
    <Card class="w-full">
        <CardHeader :class="isRTL ? 'text-right' : 'text-left'">
            <CardTitle class="flex gap-3" :class="isRTL ? 'flex-row-reverse' : ''">
                <LockKeyhole class="size-4" />
                {{ t('two_factor.recovery_codes', '2FA Recovery Codes') }}
            </CardTitle>
            <CardDescription>
                {{ t('two_factor.recovery_codes_desc', 'Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.') }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div
                class="flex flex-col gap-3 select-none sm:flex-row sm:items-center sm:justify-between"
                :class="isRTL ? 'sm:flex-row-reverse' : ''"
            >
                <Button @click="toggleRecoveryCodesVisibility" class="w-fit">
                    <component
                        :is="isRecoveryCodesVisible ? EyeOff : Eye"
                        class="size-4"
                    />
                    {{ isRecoveryCodesVisible ? t('two_factor.hide_codes', 'Hide') : t('two_factor.view_codes', 'View') }}
                    {{ t('two_factor.recovery_codes_short', 'Recovery Codes') }}
                </Button>

                <Form
                    v-if="isRecoveryCodesVisible && recoveryCodesList.length"
                    v-bind="regenerateRecoveryCodes.form()"
                    method="post"
                    :options="{ preserveScroll: true }"
                    @success="fetchRecoveryCodes"
                    #default="{ processing }"
                >
                    <Button
                        variant="secondary"
                        type="submit"
                        :disabled="processing"
                    >
                        <RefreshCw />
                        {{ t('two_factor.regenerate_codes', 'Regenerate Codes') }}
                    </Button>
                </Form>
            </div>
            <div
                :class="[
                    'relative overflow-hidden transition-all duration-300',
                    isRecoveryCodesVisible
                        ? 'h-auto opacity-100'
                        : 'h-0 opacity-0',
                ]"
            >
                <div v-if="errors?.length" class="mt-6">
                    <AlertError :errors="errors" />
                </div>
                <div v-else class="mt-3 space-y-3">
                    <div
                        ref="recoveryCodeSectionRef"
                        class="grid gap-1 rounded-lg bg-muted p-4 font-mono text-sm"
                        :class="isRTL ? 'text-right' : 'text-left'"
                    >
                        <div v-if="!recoveryCodesList.length" class="space-y-2">
                            <div
                                v-for="n in 8"
                                :key="n"
                                class="h-4 animate-pulse rounded bg-muted-foreground/20"
                            ></div>
                        </div>
                        <div
                            v-else
                            v-for="(code, index) in recoveryCodesList"
                            :key="index"
                        >
                            {{ code }}
                        </div>
                    </div>
                    <p
                        class="text-xs text-muted-foreground select-none"
                        :class="isRTL ? 'text-right' : 'text-left'"
                    >
                        {{ t('two_factor.recovery_codes_note', 'Each recovery code can be used once to access your account and will be removed after use. If you need more, click') }}
                        <span class="font-bold">{{ t('two_factor.regenerate_codes', 'Regenerate Codes') }}</span>
                        {{ t('two_factor.above', 'above') }}.
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
