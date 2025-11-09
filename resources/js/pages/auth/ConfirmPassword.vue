<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { store } from '@/routes/password/confirm';
import { Form, Head } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

const { t } = useTranslation();
</script>

<template>
    <AppLayout>
        <Head :title="t('auth.confirm_password.title', 'Confirm password')" />

        <div class="mx-auto max-w-md px-4 py-16">
            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{
                        t(
                            'auth.confirm_password.title',
                            'Confirm your password',
                        )
                    }}
                </h1>
                <p class="mt-2 text-muted-foreground">
                    {{
                        t(
                            'auth.confirm_password.description',
                            'This is a secure area of the application. Please confirm your password before continuing.',
                        )
                    }}
                </p>
            </div>

            <!-- Confirm Password Form -->
            <div class="rounded-lg border bg-card p-8">
                <Form
                    v-slot="{ errors, processing }"
                    reset-on-success
                    v-bind="store.form()"
                >
                    <div class="space-y-6">
                        <!-- Password -->
                        <div class="grid gap-2">
                            <Label for="password">
                                {{
                                    t(
                                        'auth.confirm_password.password',
                                        'Password',
                                    )
                                }}
                            </Label>
                            <Input
                                id="password"
                                :placeholder="
                                    t('auth.placeholders.password', 'Password')
                                "
                                autocomplete="current-password"
                                autofocus
                                name="password"
                                required
                                type="password"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <!-- Submit Button -->
                        <Button
                            :disabled="processing"
                            class="w-full bg-[#25D366] hover:bg-[#128C7E]"
                            data-test="confirm-password-button"
                            type="submit"
                        >
                            <Loader2
                                v-if="processing"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            {{
                                t(
                                    'auth.confirm_password.submit',
                                    'Confirm Password',
                                )
                            }}
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
