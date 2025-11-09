<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const { t } = useTranslation();
</script>

<template>
    <AppLayout>
        <Head :title="t('auth.forgot_password.title', 'Forgot password')" />

        <div class="mx-auto max-w-md px-4 py-16">
            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ t('auth.forgot_password.title', 'Forgot password') }}
                </h1>
                <p class="mt-2 text-muted-foreground">
                    {{
                        t(
                            'auth.forgot_password.description',
                            'Enter your email to receive a password reset link',
                        )
                    }}
                </p>
            </div>

            <!-- Forgot Password Form -->
            <div class="rounded-lg border bg-card p-8">
                <div
                    v-if="status"
                    class="mb-4 text-center text-sm font-medium text-green-600"
                >
                    {{ status }}
                </div>

                <div class="space-y-6">
                    <Form v-slot="{ errors, processing }" v-bind="email.form()">
                        <!-- Email -->
                        <div class="grid gap-2">
                            <Label for="email">
                                {{
                                    t(
                                        'auth.forgot_password.email',
                                        'Email address',
                                    )
                                }}
                            </Label>
                            <Input
                                id="email"
                                :placeholder="
                                    t(
                                        'auth.placeholders.email',
                                        'email@example.com',
                                    )
                                "
                                autocomplete="email"
                                autofocus
                                name="email"
                                type="email"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <!-- Submit Button -->
                        <div class="my-6 flex items-center justify-start">
                            <Button
                                :disabled="processing"
                                class="w-full bg-[#25D366] hover:bg-[#128C7E]"
                                data-test="email-password-reset-link-button"
                                type="submit"
                            >
                                <Loader2
                                    v-if="processing"
                                    class="mr-2 h-4 w-4 animate-spin"
                                />
                                {{
                                    t(
                                        'auth.forgot_password.submit',
                                        'Email password reset link',
                                    )
                                }}
                            </Button>
                        </div>
                    </Form>

                    <!-- Back to Login Link -->
                    <div class="text-center text-sm text-muted-foreground">
                        {{
                            t('auth.forgot_password.back_text', 'Or, return to')
                        }}
                        <Link
                            :href="login()"
                            class="font-medium text-[#25D366] hover:text-[#128C7E] hover:underline"
                        >
                            {{ t('auth.forgot_password.back_link', 'log in') }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
