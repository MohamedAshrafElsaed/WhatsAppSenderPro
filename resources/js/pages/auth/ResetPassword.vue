<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { update } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    token: string;
    email: string;
}>();

const { t } = useTranslation();
const inputEmail = ref(props.email);
</script>

<template>
    <AppLayout>
        <Head :title="t('auth.reset_password.title', 'Reset password')" />

        <div class="mx-auto max-w-md px-4 py-16">
            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ t('auth.reset_password.title', 'Reset password') }}
                </h1>
                <p class="mt-2 text-muted-foreground">
                    {{
                        t(
                            'auth.reset_password.description',
                            'Please enter your new password below',
                        )
                    }}
                </p>
            </div>

            <!-- Reset Password Form -->
            <div class="rounded-lg border bg-card p-8">
                <Form
                    v-slot="{ errors, processing }"
                    :reset-on-success="['password', 'password_confirmation']"
                    :transform="(data) => ({ ...data, token, email })"
                    v-bind="update.form()"
                >
                    <div class="grid gap-6">
                        <!-- Email (readonly) -->
                        <div class="grid gap-2">
                            <Label for="email">
                                {{ t('auth.reset_password.email', 'Email') }}
                            </Label>
                            <Input
                                id="email"
                                v-model="inputEmail"
                                autocomplete="email"
                                class="bg-muted"
                                name="email"
                                readonly
                                type="email"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <!-- New Password -->
                        <div class="grid gap-2">
                            <Label for="password">
                                {{
                                    t(
                                        'auth.reset_password.password',
                                        'Password',
                                    )
                                }}
                            </Label>
                            <Input
                                id="password"
                                :placeholder="
                                    t('auth.placeholders.password', 'Password')
                                "
                                autocomplete="new-password"
                                autofocus
                                name="password"
                                type="password"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <!-- Confirm Password -->
                        <div class="grid gap-2">
                            <Label for="password_confirmation">
                                {{
                                    t(
                                        'auth.reset_password.password_confirmation',
                                        'Confirm Password',
                                    )
                                }}
                            </Label>
                            <Input
                                id="password_confirmation"
                                :placeholder="
                                    t(
                                        'auth.placeholders.password',
                                        'Confirm password',
                                    )
                                "
                                autocomplete="new-password"
                                name="password_confirmation"
                                type="password"
                            />
                            <InputError
                                :message="errors.password_confirmation"
                            />
                        </div>

                        <!-- Submit Button -->
                        <Button
                            :disabled="processing"
                            class="mt-2 w-full bg-[#25D366] hover:bg-[#128C7E]"
                            data-test="reset-password-button"
                            type="submit"
                        >
                            <Loader2
                                v-if="processing"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            {{
                                t(
                                    'auth.reset_password.submit',
                                    'Reset password',
                                )
                            }}
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
