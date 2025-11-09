<script lang="ts" setup>
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { useTranslation } from '@/composables/useTranslation';
import { Form } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';

// Components
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const { t } = useTranslation();
const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall
            :description="
                t(
                    'settings.delete_account_desc',
                    'Delete your account and all of its resources',
                )
            "
            :title="t('settings.delete_account', 'Delete account')"
        />
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">{{ t('common.warning', 'Warning') }}</p>
                <p class="text-sm">
                    {{
                        t(
                            'settings.delete_warning',
                            'Please proceed with caution, this cannot be undone.',
                        )
                    }}
                </p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        data-test="delete-user-button"
                        variant="destructive"
                    >
                        {{ t('settings.delete_account', 'Delete account') }}
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-slot="{ errors, processing, reset, clearErrors }"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        reset-on-success
                        v-bind="ProfileController.destroy.form()"
                        @error="() => passwordInput?.$el?.focus()"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>
                                {{
                                    t(
                                        'settings.delete_confirm_title',
                                        'Are you sure you want to delete your account?',
                                    )
                                }}
                            </DialogTitle>
                            <DialogDescription>
                                {{
                                    t(
                                        'settings.delete_confirm_desc',
                                        'Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
                                    )
                                }}
                            </DialogDescription>
                        </DialogHeader>
                        <div class="space-y-2">
                            <Label for="password">
                                {{ t('auth.password', 'Password') }}
                            </Label>
                            <Input
                                id="password"
                                ref="passwordInput"
                                :placeholder="t('auth.password', 'Password')"
                                name="password"
                                type="password"
                            />
                            <InputError :message="errors.password" />
                        </div>
                        <DialogFooter>
                            <DialogClose as-child>
                                <Button
                                    :disabled="processing"
                                    type="button"
                                    variant="outline"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    {{ t('common.cancel', 'Cancel') }}
                                </Button>
                            </DialogClose>
                            <Button
                                :disabled="processing"
                                type="submit"
                                variant="destructive"
                            >
                                {{
                                    t(
                                        'settings.delete_account',
                                        'Delete account',
                                    )
                                }}
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
