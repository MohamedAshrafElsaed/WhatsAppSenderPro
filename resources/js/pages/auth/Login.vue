<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/composables/useTranslation';
import LandingLayout from '@/layouts/LandingLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const { t } = useTranslation();
</script>

<template>
    <LandingLayout>
        <Head :title="t('auth.login.title', 'Log in')" />

        <div class="mx-auto max-w-md px-4 py-16">
            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ t('auth.login.title', 'Log in to your account') }}
                </h1>
                <p class="mt-2 text-muted-foreground">
                    {{
                        t(
                            'auth.login.description',
                            'Enter your email and password below to log in',
                        )
                    }}
                </p>
            </div>

            <!-- Login Form -->
            <div class="rounded-lg border bg-card p-8 shadow-sm">
                <div
                    v-if="status"
                    class="mb-4 text-center text-sm font-medium text-green-600"
                >
                    {{ status }}
                </div>

                <Form
                    v-slot="{ errors, processing }"
                    :reset-on-success="['password']"
                    class="flex flex-col gap-6"
                    v-bind="store.form()"
                >
                    <div class="grid gap-6">
                        <!-- Email -->
                        <div class="grid gap-2">
                            <Label for="email">
                                {{ t('auth.login.email', 'Email address') }}
                                <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="email"
                                :class="{ 'border-destructive': errors.email }"
                                :placeholder="
                                    t(
                                        'auth.placeholders.email',
                                        'email@example.com',
                                    )
                                "
                                :tabindex="1"
                                autocomplete="email"
                                autofocus
                                name="email"
                                required
                                type="email"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <!-- Password -->
                        <div class="grid gap-2">
                            <div class="flex items-center justify-between">
                                <Label for="password">
                                    {{ t('auth.login.password', 'Password') }}
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Link
                                    v-if="canResetPassword"
                                    :href="request()"
                                    :tabindex="5"
                                    class="text-sm font-medium text-[#25D366] hover:text-[#128C7E] hover:underline"
                                >
                                    {{
                                        t(
                                            'auth.login.forgot_password',
                                            'Forgot password?',
                                        )
                                    }}
                                </Link>
                            </div>
                            <Input
                                id="password"
                                :class="{
                                    'border-destructive': errors.password,
                                }"
                                :placeholder="
                                    t('auth.placeholders.password', 'Password')
                                "
                                :tabindex="2"
                                autocomplete="current-password"
                                name="password"
                                required
                                type="password"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between">
                            <Label
                                class="flex cursor-pointer items-center space-x-3"
                                for="remember"
                            >
                                <Checkbox
                                    id="remember"
                                    :tabindex="3"
                                    name="remember"
                                />
                                <span>{{
                                    t('auth.login.remember_me', 'Remember me')
                                }}</span>
                            </Label>
                        </div>

                        <!-- Submit Button -->
                        <Button
                            :disabled="processing"
                            :tabindex="4"
                            class="mt-2 w-full bg-[#25D366] hover:bg-[#128C7E]"
                            data-test="login-button"
                            type="submit"
                        >
                            <Loader2
                                v-if="processing"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            {{ t('auth.login.submit', 'Log in') }}
                        </Button>
                    </div>

                    <!-- Register Link -->
                    <div
                        v-if="canRegister"
                        class="text-center text-sm text-muted-foreground"
                    >
                        {{
                            t('auth.login.no_account', "Don't have an account?")
                        }}
                        <Link
                            :href="register()"
                            :tabindex="5"
                            class="font-medium text-[#25D366] hover:text-[#128C7E] hover:underline"
                        >
                            {{ t('auth.login.sign_up', 'Sign up') }}
                        </Link>
                    </div>
                </Form>
            </div>
        </div>
    </LandingLayout>
</template>
