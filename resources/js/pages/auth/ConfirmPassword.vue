<script setup lang="ts">
import { Head, Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTranslation } from '@/composables/useTranslation';
import { Loader2 } from 'lucide-vue-next';
import { store } from '@/routes/password/confirm';

const { t } = useTranslation();
</script>

<template>
    <AppLayout>
        <Head :title="t('auth.confirm_password.title', 'Confirm password')" />

        <div class="mx-auto max-w-md px-4 py-16">
            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ t('auth.confirm_password.title', 'Confirm your password') }}
                </h1>
                <p class="mt-2 text-muted-foreground">
                    {{ t('auth.confirm_password.description', 'This is a secure area of the application. Please confirm your password before continuing.') }}
                </p>
            </div>

            <!-- Confirm Password Form -->
            <div class="rounded-lg border bg-card p-8">
                <Form
                    v-bind="store.form()"
                    reset-on-success
                    v-slot="{ errors, processing }"
                >
                    <div class="space-y-6">
                        <!-- Password -->
                        <div class="grid gap-2">
                            <Label for="password">
                                {{ t('auth.confirm_password.password', 'Password') }}
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                autofocus
                                :placeholder="t('auth.placeholders.password', 'Password')"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <!-- Submit Button -->
                        <Button
                            type="submit"
                            class="w-full bg-[#25D366] hover:bg-[#128C7E]"
                            :disabled="processing"
                            data-test="confirm-password-button"
                        >
                            <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                            {{ t('auth.confirm_password.submit', 'Confirm Password') }}
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
