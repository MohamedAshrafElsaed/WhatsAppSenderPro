<script setup lang="ts">
import { Head, Link, Form } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTranslation } from '@/composables/useTranslation';
import { Loader2 } from 'lucide-vue-next';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineProps<{
    status?: string;
}>();

const { t } = useTranslation();
</script>

<template>
    <AppLayout>
        <Head :title="t('auth.verify_email.title', 'Email verification')" />

        <div class="mx-auto max-w-md px-4 py-16">
            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ t('auth.verify_email.title', 'Verify email') }}
                </h1>
                <p class="mt-2 text-muted-foreground">
                    {{ t('auth.verify_email.description', 'Please verify your email address by clicking on the link we just emailed to you.') }}
                </p>
            </div>

            <!-- Verify Email Form -->
            <div class="rounded-lg border bg-card p-8">
                <div
                    v-if="status === 'verification-link-sent'"
                    class="mb-6 text-center text-sm font-medium text-green-600"
                >
                    {{ t('auth.verify_email.link_sent', 'A new verification link has been sent to the email address you provided during registration.') }}
                </div>

                <Form
                    v-bind="send.form()"
                    class="space-y-6 text-center"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        class="w-full bg-[#25D366] hover:bg-[#128C7E]"
                        :disabled="processing"
                    >
                        <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ t('auth.verify_email.resend', 'Resend verification email') }}
                    </Button>

                    <Link
                        :href="logout()"
                        as="button"
                        class="mx-auto block text-sm font-medium text-[#25D366] hover:text-[#128C7E] hover:underline"
                    >
                        {{ t('auth.verify_email.logout', 'Log out') }}
                    </Link>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
