<script lang="ts" setup>
import AlertError from '@/components/AlertError.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    PinInput,
    PinInputGroup,
    PinInputSlot,
} from '@/components/ui/pin-input';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/composables/useTranslation';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { confirm } from '@/routes/two-factor';
import { Form, usePage } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { Check, Copy, ScanLine } from 'lucide-vue-next';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';

interface Props {
    requiresConfirmation: boolean;
    twoFactorEnabled: boolean;
}

const props = defineProps<Props>();
const isOpen = defineModel<boolean>('isOpen');

const { t } = useTranslation();
const page = usePage();
const locale = computed(() => page.props.locale || 'en');
const isRTL = computed(() => locale.value === 'ar');

const { copy, copied } = useClipboard();
const { qrCodeSvg, manualSetupKey, clearSetupData, fetchSetupData, errors } =
    useTwoFactorAuth();

const showVerificationStep = ref(false);
const code = ref<number[]>([]);
const codeValue = computed<string>(() => code.value.join(''));

const pinInputContainerRef = useTemplateRef('pinInputContainerRef');

const modalConfig = computed<{
    title: string;
    description: string;
    buttonText: string;
}>(() => {
    if (props.twoFactorEnabled) {
        return {
            title: t(
                'two_factor.enabled_title',
                'Two-Factor Authentication Enabled',
            ),
            description: t(
                'two_factor.enabled_desc',
                'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
            ),
            buttonText: t('common.close', 'Close'),
        };
    }

    if (showVerificationStep.value) {
        return {
            title: t('two_factor.verify_title', 'Verify Authentication Code'),
            description: t(
                'two_factor.verify_desc',
                'Enter the 6-digit code from your authenticator app',
            ),
            buttonText: t('common.continue', 'Continue'),
        };
    }

    return {
        title: t('two_factor.enable_title', 'Enable Two-Factor Authentication'),
        description: t(
            'two_factor.enable_desc',
            'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app',
        ),
        buttonText: t('common.continue', 'Continue'),
    };
});

const handleModalNextStep = () => {
    if (props.requiresConfirmation) {
        showVerificationStep.value = true;

        nextTick(() => {
            pinInputContainerRef.value?.querySelector('input')?.focus();
        });

        return;
    }

    clearSetupData();
    isOpen.value = false;
};

const resetModalState = () => {
    if (props.twoFactorEnabled) {
        clearSetupData();
    }

    showVerificationStep.value = false;
    code.value = [];
};

watch(
    () => isOpen.value,
    async (isOpen) => {
        if (!isOpen) {
            resetModalState();
            return;
        }

        if (!qrCodeSvg.value) {
            await fetchSetupData();
        }
    },
);
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader
                :class="isRTL ? 'text-right' : 'text-left'"
                class="flex items-center justify-center"
            >
                <div
                    class="mb-3 w-auto rounded-full border border-border bg-card p-0.5 shadow-sm"
                >
                    <div
                        class="relative overflow-hidden rounded-full border border-border bg-muted p-2.5"
                    >
                        <div
                            class="absolute inset-0 grid grid-cols-5 opacity-50"
                        >
                            <div
                                v-for="i in 5"
                                :key="`col-${i}`"
                                class="border-r border-border last:border-r-0"
                            ></div>
                        </div>
                        <ScanLine class="relative size-8" />
                    </div>
                </div>

                <DialogTitle class="text-xl">
                    {{ modalConfig.title }}
                </DialogTitle>
                <DialogDescription class="text-center">
                    {{ modalConfig.description }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-6 py-4">
                <AlertError v-if="errors?.length" :errors="errors" />

                <div
                    v-if="showVerificationStep"
                    class="flex items-center justify-center"
                >
                    <Form
                        #default="{ errors, processing }"
                        class="w-full"
                        reset-on-error
                        v-bind="confirm.form()"
                        @error="code = []"
                        @success="isOpen = false"
                    >
                        <input :value="codeValue" name="code" type="hidden" />
                        <div class="space-y-6">
                            <div
                                ref="pinInputContainerRef"
                                class="flex flex-col items-center justify-center space-y-3"
                            >
                                <PinInput
                                    id="code"
                                    v-model="code"
                                    :disabled="processing"
                                    :name="t('two_factor.code_label', 'Code')"
                                    placeholder="○"
                                    type="number"
                                >
                                    <PinInputGroup>
                                        <PinInputSlot
                                            v-for="(id, index) in 6"
                                            :key="id"
                                            :index="index"
                                        />
                                    </PinInputGroup>
                                </PinInput>

                                <InputError :message="errors.code" />
                            </div>
                            <Button
                                :disabled="processing || code.length !== 6"
                                class="w-full bg-[#25D366] hover:bg-[#20BA5A]"
                                type="submit"
                            >
                                <Spinner
                                    v-if="processing"
                                    class="size-4 text-white"
                                />
                                <span v-else>{{ modalConfig.buttonText }}</span>
                            </Button>
                        </div>
                    </Form>
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-if="qrCodeSvg"
                        class="flex items-center justify-center rounded-lg border bg-white p-4"
                        v-html="qrCodeSvg"
                    ></div>
                    <div v-else class="flex items-center justify-center py-12">
                        <Spinner class="size-8" />
                    </div>

                    <div class="space-y-2">
                        <p
                            :class="isRTL ? 'text-right' : 'text-left'"
                            class="text-sm font-medium"
                        >
                            {{
                                t(
                                    'two_factor.manual_entry',
                                    'Or enter this code manually:',
                                )
                            }}
                        </p>
                        <div
                            class="flex items-center justify-between gap-2 rounded-lg border bg-muted p-3"
                        >
                            <code
                                :class="isRTL ? 'text-right' : 'text-left'"
                                class="flex-1 text-sm"
                            >
                                {{ manualSetupKey }}
                            </code>
                            <Button
                                class="size-8 shrink-0"
                                size="icon"
                                variant="ghost"
                                @click="() => copy(manualSetupKey)"
                            >
                                <Check v-if="copied" class="size-4" />
                                <Copy v-else class="size-4" />
                            </Button>
                        </div>
                    </div>

                    <Button
                        class="w-full bg-[#25D366] hover:bg-[#20BA5A]"
                        @click="handleModalNextStep"
                    >
                        {{ modalConfig.buttonText }}
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
