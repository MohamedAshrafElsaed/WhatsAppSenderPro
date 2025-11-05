<script setup lang="ts">
import { Head, Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTranslation } from '@/composables/useTranslation';
import { Loader2 } from 'lucide-vue-next';
import { update } from '@/routes/password';
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
                    {{ t('auth.reset_password.description', 'Please enter your new password below') }}
                </p>
            </div>

            <!-- Reset Password Form -->
            <div class="rounded-lg border bg-card p-8">
                <Form
                    v-bind="update.form()"
                    :transform="(data) => ({ ...data, token, email })"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-6">
                        <!-- Email (readonly) -->
                        <div class="grid gap-2">
                            <Label for="email">
                                {{ t('auth.reset_password.email', 'Email') }}
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                v-model="inputEmail"
                                autocomplete="email"
                                readonly
                                class="bg-muted"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <!-- New Password -->
                        <div class="grid gap-2">
                            <Label for="password">
                                {{ t('auth.reset_password.password', 'Password') }}
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                autocomplete="new-password"
                                autofocus
                                :placeholder="t('auth.placeholders.password', 'Password')"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <!-- Confirm Password -->
                        <div class="grid gap-2">
                            <Label for="password_confirmation">
                                {{ t('auth.reset_password.password_confirmation', 'Confirm Password') }}
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                autocomplete="new-password"
                                :placeholder="t('auth.placeholders.password', 'Confirm password')"
                            />
                            <InputError :message="errors.password_confirmation" />
                        </div>

                        <!-- Submit Button -->
                        <Button
                            type="submit"
                            class="mt-2 w-full bg-[#25D366] hover:bg-[#128C7E]"
                            :disabled="processing"
                            data-test="reset-password-button"
                        >
                            <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                            {{ t('auth.reset_password.submit', 'Reset password') }}
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
