<script setup lang="ts">
import RecipientSelector from '@/components/RecipientSelector.vue';
import SessionSelector from '@/components/SessionSelector.vue';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import CampaignController from '@/actions/App/Http/Controllers/CampaignController';
import { index, show } from '@/routes/dashboard/campaigns';
import { index as dashboard } from '@/routes/dashboard';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    MessageSquare,
    Upload,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface Contact {
    id: number;
    first_name: string;
    last_name: string | null;
    phone_number: string;
}

interface Template {
    id: number;
    name: string;
    type: string;
    content: string;
    caption: string | null;
    media_url: string | null;
}

interface WhatsAppSession {
    id: string;
    session_name: string;
    phone_number?: string;
    status: string;
}

interface Campaign {
    id: number;
    name: string;
    template_id: number | null;
    message_type: string;
    message_content: string;
    message_caption: string | null;
    media_url: string | null;
    scheduled_at: string | null;
    session_id: string;
}

interface Props {
    campaign: Campaign;
    contacts: Contact[];
    templates: Template[];
    sessions: WhatsAppSession[];
    selectedRecipients: number[];
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('dashboard.title', 'Dashboard'), href: dashboard().url },
    { title: t('campaigns.title', 'Campaigns'), href: index().url },
    { title: t('campaigns.edit', 'Edit Campaign') },
];

const mediaPreview = ref<string | null>(props.campaign.media_url);

const form = useForm({
    name: props.campaign.name,
    template_id: props.campaign.template_id,
    message_type: props.campaign.message_type,
    message_content: props.campaign.message_content,
    message_caption: props.campaign.message_caption || '',
    scheduled_at: props.campaign.scheduled_at || '',
    recipient_ids: props.selectedRecipients,
    session_id: props.campaign.session_id,
    media: null as File | null,
});

const handleMediaUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        form.media = file;

        const reader = new FileReader();
        reader.onload = (e) => {
            mediaPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeMedia = () => {
    form.media = null;
    mediaPreview.value = null;
};

const submit = () => {
    form.post(CampaignController.update.url({ campaign: props.campaign.id }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('campaigns.edit', 'Edit Campaign')" />

        <div :class="isRTL() ? 'text-right' : 'text-left'" class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <Heading
                :description="t('campaigns.edit_description', 'Edit your draft campaign')"
                :title="t('campaigns.edit', 'Edit Campaign')"
            >
                <template #icon>
                    <div class="flex items-center justify-center rounded-lg bg-[#25D366] p-2">
                        <MessageSquare class="h-5 w-5 text-white" />
                    </div>
                </template>
            </Heading>

            <!-- Warning -->
            <Alert class="border-yellow-500 bg-yellow-50 dark:bg-yellow-950">
                <AlertCircle class="h-4 w-4 text-yellow-600" />
                <AlertDescription class="text-yellow-800 dark:text-yellow-200">
                    {{ t('campaigns.edit_warning', 'Only draft campaigns can be edited. Changes will be lost if the campaign has been sent.') }}
                </AlertDescription>
            </Alert>

            <!-- Campaign Details -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('campaigns.campaign_details', 'Campaign Details') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Campaign Name -->
                    <div>
                        <Label :for="'name'">
                            {{ t('campaigns.name', 'Campaign Name') }}
                        </Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            :placeholder="t('campaigns.name_placeholder', 'Enter campaign name')"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Schedule -->
                    <div>
                        <Label :for="'scheduled_at'">
                            {{ t('campaigns.schedule_at', 'Schedule For') }}
                            <span class="text-xs text-muted-foreground">{{ t('common.optional', '(optional)') }}</span>
                        </Label>
                        <Input
                            id="scheduled_at"
                            v-model="form.scheduled_at"
                            type="datetime-local"
                            :min="new Date().toISOString().slice(0, 16)"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- Recipients -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('campaigns.select_recipients', 'Select Recipients') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <RecipientSelector
                        v-model="form.recipient_ids"
                        :contacts="contacts"
                    />
                    <p v-if="form.errors.recipient_ids" class="mt-2 text-sm text-red-500">
                        {{ form.errors.recipient_ids }}
                    </p>
                </CardContent>
            </Card>

            <!-- Message Content -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('campaigns.message_content', 'Message Content') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- WhatsApp Session -->
                    <SessionSelector
                        v-model="form.session_id"
                        :sessions="sessions"
                        :error="form.errors.session_id"
                    />

                    <!-- Message Type -->
                    <div>
                        <Label>{{ t('campaigns.message_type', 'Message Type') }}</Label>
                        <Select v-model="form.message_type">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="text">{{ t('campaigns.type.text', 'Text Only') }}</SelectItem>
                                <SelectItem value="image">{{ t('campaigns.type.image', 'Text + Image') }}</SelectItem>
                                <SelectItem value="video">{{ t('campaigns.type.video', 'Text + Video') }}</SelectItem>
                                <SelectItem value="audio">{{ t('campaigns.type.audio', 'Audio') }}</SelectItem>
                                <SelectItem value="document">{{ t('campaigns.type.document', 'Document') }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Message Content -->
                    <div>
                        <Label :for="'message_content'">
                            {{ form.message_type === 'text' ? t('campaigns.message_content', 'Message Content') : t('campaigns.message_caption', 'Caption') }}
                        </Label>
                        <Textarea
                            id="message_content"
                            v-model="form.message_content"
                            :placeholder="t('campaigns.message_placeholder', 'Type your message here...')"
                            rows="6"
                        />
                        <p v-if="form.errors.message_content" class="mt-1 text-sm text-red-500">
                            {{ form.errors.message_content }}
                        </p>
                    </div>

                    <!-- Media Upload -->
                    <div v-if="form.message_type !== 'text'">
                        <Label>{{ t('campaigns.upload_media', 'Upload Media') }}</Label>

                        <div v-if="!mediaPreview" class="mt-2">
                            <label
                                class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/25 p-6 transition-colors hover:border-[#25D366] hover:bg-[#25D366]/5"
                            >
                                <Upload class="mb-2 h-8 w-8 text-muted-foreground" />
                                <span class="text-sm font-medium">
                                    {{ t('campaigns.click_to_upload', 'Click to upload') }}
                                </span>
                                <input
                                    type="file"
                                    class="hidden"
                                    :accept="form.message_type === 'image' ? 'image/*' : form.message_type === 'video' ? 'video/*' : form.message_type === 'audio' ? 'audio/*' : '.pdf,.doc,.docx,.xls,.xlsx'"
                                    @change="handleMediaUpload"
                                />
                            </label>
                        </div>

                        <div v-else class="mt-2 rounded-lg border p-4">
                            <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex items-center justify-between">
                                <div :class="isRTL() ? 'text-right' : 'text-left'">
                                    <p class="font-medium">{{ form.media?.name || 'Current media' }}</p>
                                </div>
                                <Button variant="destructive" size="sm" @click="removeMedia">
                                    {{ t('common.remove', 'Remove') }}
                                </Button>
                            </div>

                            <div v-if="mediaPreview && form.message_type === 'image'" class="mt-4">
                                <img :src="mediaPreview" alt="Preview" class="max-h-48 rounded-lg" />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Actions -->
            <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex justify-end gap-2">
                <Button variant="outline" as-child>
                    <Link :href="show(campaign.id)">
                        {{ t('common.cancel', 'Cancel') }}
                    </Link>
                </Button>

                <Button
                    :disabled="form.processing"
                    class="bg-[#25D366] hover:bg-[#25D366]/90"
                    @click="submit"
                >
                    {{ form.processing ? t('common.saving', 'Saving...') : t('common.save', 'Save Changes') }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
