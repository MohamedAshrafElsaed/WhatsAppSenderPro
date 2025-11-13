<script lang="ts" setup>
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy, edit, index } from '@/routes/dashboard/contacts';
import { Head, router } from '@inertiajs/vue3';
import {
    Calendar,
    CheckCircle2,
    Clock,
    Edit,
    Mail,
    Phone,
    Trash2,
    XCircle,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface Contact {
    id: number;
    full_name: string;
    first_name: string;
    last_name: string;
    phone_number: string;
    email: string;
    country: { name_en: string; name_ar: string } | null;
    tags: Array<{ id: number; name: string; color: string }>;
    source: string;
    is_whatsapp_valid: boolean;
    validated_at: string | null;
    notes: string;
    created_at: string;
    import: { filename: string } | null;
}

interface Props {
    contact: Contact;
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();

const showDeleteDialog = ref(false);

const deleteContact = () => {
    router.delete(destroy(props.contact.id), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            router.visit(index());
        },
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="contact.full_name" />

        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <Heading :title="contact.full_name" />
                <div class="flex gap-2">
                    <Button
                        class="bg-[#25D366] hover:bg-[#128C7E]"
                        @click="$inertia.visit(edit(contact.id))"
                    >
                        <Edit
                            :class="isRTL() ? 'ml-2' : 'mr-2'"
                            class="h-4 w-4"
                        />
                        {{ t('common.edit', 'Edit') }}
                    </Button>
                    <Button
                        variant="destructive"
                        @click="showDeleteDialog = true"
                    >
                        <Trash2
                            :class="isRTL() ? 'ml-2' : 'mr-2'"
                            class="h-4 w-4"
                        />
                        {{ t('common.delete', 'Delete') }}
                    </Button>
                </div>
            </div>

            <!-- Contact Information -->
            <Card>
                <CardHeader>
                    <CardTitle
                        >{{
                            t(
                                'contacts.fields.contact_info',
                                'Contact Information',
                            )
                        }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="grid gap-6 md:grid-cols-2">
                    <!-- Phone -->
                    <div>
                        <p class="mb-1 text-sm text-muted-foreground">
                            {{ t('contacts.fields.phone_number') }}
                        </p>
                        <div class="flex items-center gap-2">
                            <Phone class="h-4 w-4 text-[#25D366]" />
                            <span class="font-medium">{{
                                contact.phone_number
                            }}</span>
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <p class="mb-1 text-sm text-muted-foreground">
                            {{ t('contacts.fields.email') }}
                        </p>
                        <div class="flex items-center gap-2">
                            <Mail class="h-4 w-4 text-[#25D366]" />
                            <span class="font-medium">{{
                                contact.email || '-'
                            }}</span>
                        </div>
                    </div>

                    <!-- Country -->
                    <div v-if="contact.country">
                        <p class="mb-1 text-sm text-muted-foreground">
                            {{ t('contacts.fields.country') }}
                        </p>
                        <span class="font-medium">
                            {{
                                isRTL()
                                    ? contact.country.name_ar
                                    : contact.country.name_en
                            }}
                        </span>
                    </div>

                    <!-- WhatsApp Status -->
                    <div>
                        <p class="mb-1 text-sm text-muted-foreground">
                            {{ t('contacts.fields.whatsapp_status') }}
                        </p>
                        <Badge
                            v-if="contact.is_whatsapp_valid"
                            class="bg-[#25D366] hover:bg-[#128C7E]"
                        >
                            <CheckCircle2
                                :class="isRTL() ? 'ml-1' : 'mr-1'"
                                class="h-3 w-3"
                            />
                            {{ t('contacts.validation.valid') }}
                        </Badge>
                        <Badge v-else variant="destructive">
                            <XCircle
                                :class="isRTL() ? 'ml-1' : 'mr-1'"
                                class="h-3 w-3"
                            />
                            {{ t('contacts.validation.invalid') }}
                        </Badge>
                    </div>

                    <!-- Source -->
                    <div>
                        <p class="mb-1 text-sm text-muted-foreground">
                            {{ t('contacts.fields.source') }}
                        </p>
                        <Badge
                            :class="
                                contact.source === 'csv_import' ||
                                contact.source === 'excel_import'
                                    ? 'border-[#25D366]/20 bg-[#25D366]/10 text-[#25D366]'
                                    : ''
                            "
                            variant="outline"
                        >
                            {{ t(`contacts.sources.${contact.source}`) }}
                        </Badge>
                    </div>

                    <!-- Import File -->
                    <div v-if="contact.import">
                        <p class="mb-1 text-sm text-muted-foreground">
                            {{ t('imports.history.filename') }}
                        </p>
                        <span class="font-medium">{{
                            contact.import.filename
                        }}</span>
                    </div>

                    <!-- Created Date -->
                    <div>
                        <p class="mb-1 text-sm text-muted-foreground">
                            {{ t('contacts.fields.created') }}
                        </p>
                        <div class="flex items-center gap-2">
                            <Calendar class="h-4 w-4 text-[#25D366]" />
                            <span class="font-medium">{{
                                formatDate(contact.created_at)
                            }}</span>
                        </div>
                    </div>

                    <!-- Validated Date -->
                    <div v-if="contact.validated_at">
                        <p class="mb-1 text-sm text-muted-foreground">
                            {{
                                t(
                                    'contacts.validation.validated_at',
                                    'Validated At',
                                )
                            }}
                        </p>
                        <div class="flex items-center gap-2">
                            <Clock class="h-4 w-4 text-[#25D366]" />
                            <span class="font-medium">{{
                                formatDate(contact.validated_at)
                            }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tags -->
            <Card v-if="contact.tags.length > 0">
                <CardHeader>
                    <CardTitle>{{ t('contacts.fields.tags') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-2">
                        <Badge
                            v-for="tag in contact.tags"
                            :key="tag.id"
                            :style="{ backgroundColor: tag.color }"
                            class="text-white"
                        >
                            {{ tag.name }}
                        </Badge>
                    </div>
                </CardContent>
            </Card>

            <!-- Notes -->
            <Card v-if="contact.notes">
                <CardHeader>
                    <CardTitle>{{ t('contacts.fields.notes') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="whitespace-pre-wrap">{{ contact.notes }}</p>
                </CardContent>
            </Card>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle
                        >{{ t('contacts.messages.confirm_delete') }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            t(
                                'common.cannot_undone',
                                'This action cannot be undone.',
                            )
                        }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        {{ t('common.cancel', 'Cancel') }}
                    </Button>
                    <Button variant="destructive" @click="deleteContact">
                        {{ t('common.delete', 'Delete') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
