<script setup lang="ts">
import { Head, Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    PinInput,
    PinInputGroup,
    PinInputSlot,
} from '@/components/ui/pin-input';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTranslation } from '@/composables/useTranslation';
import { computed, ref } from 'vue';
import { store } from '@/routes/two-factor/login';

interface AuthConfigContent {
    title: string;
    description: string;
    toggleText: string;
}

const { t } = useTranslation();

const showRecoveryInput = ref<boolean>(false);
const code = ref<number[]>([]);
const codeValue = computed<string>(() => code.value.join(''));

const authConfigContent = computed<AuthConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: t('auth.two_factor.recovery_title', 'Recovery Code'),
            description: t('auth.two_factor.recovery_description', 'Please confirm access to your account by entering one of your emergency recovery codes.'),
            toggleText: t('auth.two_factor.use_code', 'login using an authentication code'),
        };
    }

    return {
        title: t('auth.two_factor.title', 'Authentication Code'),
        description: t('auth.two_factor.description', 'Enter the authentication code provided by your authenticator application.'),
        toggleText: t('auth.two_factor.use_recovery', 'login using a recovery code'),
    };
});

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = [];
};
</script>

<template>
    <AppLayout>
        <Head :title="t('auth.two_factor.page_title', 'Two-Factor Authentication')" />

        <div class="mx-auto max-w-md px-4 py-16">
            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ authConfigContent.title }}
                </h1>
                <p class="mt-2 text-muted-foreground">
                    {{ authConfigContent.description }}
                </p>
            </div>

            <!-- Two-Factor Form -->
            <div class="rounded-lg border bg-card p-8">
                <div class="space-y-6">
                    <!-- Authentication Code (PIN Input) -->
                    <template v-if="!showRecoveryInput">
                        <Form
                            v-bind="store.form()"
                            class="space-y-4"
                            reset-on-error
                            @error="code = []"
                            #default="{ errors, processing, clearErrors }"
                        >
                            <input type="hidden" name="code" :value="codeValue" />
                            <div
                                class="flex flex-col items-center justify-center space-y-3 text-center"
                            >
                                <div class="flex w-full items-center justify-center">
                                    <PinInput
                                        id="otp"
                                        placeholder="○"
                                        v-model="code"
                                        type="number"
                                        otp
                                    >
                                        <PinInputGroup>
                                            <PinInputSlot
                                                v-for="(id, index) in 6"
                                                :key="id"
                                                :index="index"
                                                :disabled="processing"
                                                autofocus
                                            />
                                        </PinInputGroup>
                                    </PinInput>
                                </div>
                                <InputError :message="errors.code" />
                            </div>

                            <Button
                                type="submit"
                                class="w-full bg-[#25D366] hover:bg-[#128C7E]"
                                :disabled="processing"
                            >
                                {{ t('auth.two_factor.submit', 'Continue') }}
                            </Button>

                            <div class="text-center text-sm text-muted-foreground">
                                <span>{{ t('auth.two_factor.or', 'or you can') }} </span>
                                <button
                                    type="button"
                                    class="font-medium text-[#25D366] hover:text-[#128C7E] hover:underline"
                                    @click="() => toggleRecoveryMode(clearErrors)"
                                >
                                    {{ authConfigContent.toggleText }}
                                </button>
                            </div>
                        </Form>
                    </template>

                    <!-- Recovery Code Input -->
                    <template v-else>
                        <Form
                            v-bind="store.form()"
                            class="space-y-4"
                            reset-on-error
                            #default="{ errors, processing, clearErrors }"
                        >
                            <Input
                                name="recovery_code"
                                type="text"
                                :placeholder="t('auth.two_factor.recovery_placeholder', 'Enter recovery code')"
                                :autofocus="showRecoveryInput"
                                required
                            />
                            <InputError :message="errors.recovery_code" />

                            <Button
                                type="submit"
                                class="w-full bg-[#25D366] hover:bg-[#128C7E]"
                                :disabled="processing"
                            >
                                {{ t('auth.two_factor.submit', 'Continue') }}
                            </Button>

                            <div class="text-center text-sm text-muted-foreground">
                                <span>{{ t('auth.two_factor.or', 'or you can') }} </span>
                                <button
                                    type="button"
                                    class="font-medium text-[#25D366] hover:text-[#128C7E] hover:underline"
                                    @click="() => toggleRecoveryMode(clearErrors)"
                                >
                                    {{ authConfigContent.toggleText }}
                                </button>
                            </div>
                        </Form>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
