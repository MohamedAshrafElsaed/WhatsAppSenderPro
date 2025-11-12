<script setup lang="ts">
import RecipientSelector from '@/components/RecipientSelector.vue';
import SessionSelector from '@/components/SessionSelector.vue';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
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
import { index } from '@/routes/dashboard/campaigns';
import { index as dashboard } from '@/routes/dashboard';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    Calendar,
    CheckCircle,
    FileText,
    MessageSquare,
    Upload,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

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

interface UsageStats {
    used: number;
    limit: number | string;
    remaining: number | string;
}

interface Props {
    contacts: Contact[];
    templates: Template[];
    sessions: WhatsAppSession[];
    usage: UsageStats;
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('dashboard.title', 'Dashboard'), href: dashboard().url },
    { title: t('campaigns.title', 'Campaigns'), href: index().url },
    { title: t('campaigns.create', 'Create Campaign') },
];

const currentStep = ref(1);
const mediaPreview = ref<string | null>(null);

const form = useForm({
    name: '',
    template_id: null as number | null,
    message_type: 'text',
    message_content: '',
    message_caption: '',
    scheduled_at: '',
    recipient_ids: [] as number[],
    session_id: '',
    media: null as File | null,
});

const usagePercentage = computed(() => {
    if (props.usage.limit === 'unlimited' || props.usage.limit === 0) return 0;
    return Math.round((props.usage.used / (props.usage.limit as number)) * 100);
});

const usageColor = computed(() => {
    const pct = usagePercentage.value;
    if (pct >= 90) return 'text-red-600';
    if (pct >= 70) return 'text-yellow-600';
    return 'text-[#25D366]';
});

const estimatedUsage = computed(() => {
    return props.usage.used + form.recipient_ids.length;
});

const estimatedPercentage = computed(() => {
    if (props.usage.limit === 'unlimited' || props.usage.limit === 0) return 0;
    return Math.round((estimatedUsage.value / (props.usage.limit as number)) * 100);
});

const willExceedQuota = computed(() => {
    if (props.usage.limit === 'unlimited') return false;
    return form.recipient_ids.length > (props.usage.remaining as number);
});

const selectedTemplate = computed(() => {
    if (!form.template_id) return null;
    return props.templates.find(t => t.id === form.template_id);
});

const canProceedStep1 = computed(() => {
    return form.name.trim().length > 0;
});

const canProceedStep2 = computed(() => {
    return form.recipient_ids.length > 0;
});

const canProceedStep3 = computed(() => {
    return (
        form.message_content.trim().length > 0 &&
        form.session_id.length > 0 &&
        (form.message_type === 'text' || form.media !== null)
    );
});

const handleTemplateSelect = (templateId: string) => {
    const template = props.templates.find(t => t.id === parseInt(templateId));
    if (template) {
        form.template_id = template.id;
        form.message_type = template.type === 'text' ? 'text' : template.type.replace('text_', '') as any;
        form.message_content = template.content;
        form.message_caption = template.caption || '';
    }
};

const handleMediaUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        form.media = file;

        // Create preview
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

const nextStep = () => {
    if (currentStep.value < 4) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const submit = () => {
    form.post(CampaignController.store.url(), {
        preserveScroll: true,
    });
};

const steps = computed(() => [
    {
        number: 1,
        title: t('campaigns.step1_title', 'Campaign Details'),
        icon: FileText,
        completed: currentStep.value > 1,
    },
    {
        number: 2,
        title: t('campaigns.step2_title', 'Select Recipients'),
        icon: Users,
        completed: currentStep.value > 2,
    },
    {
        number: 3,
        title: t('campaigns.step3_title', 'Compose Message'),
        icon: MessageSquare,
        completed: currentStep.value > 3,
    },
    {
        number: 4,
        title: t('campaigns.step4_title', 'Review & Confirm'),
        icon: CheckCircle,
        completed: false,
    },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('campaigns.create', 'Create Campaign')" />

        <div :class="isRTL() ? 'text-right' : 'text-left'" class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <Heading
                :description="t('campaigns.create_description', 'Create a new WhatsApp bulk messaging campaign')"
                :title="t('campaigns.create', 'Create Campaign')"
            >
                <template #icon>
                    <div class="flex items-center justify-center rounded-lg bg-[#25D366] p-2">
                        <MessageSquare class="h-5 w-5 text-white" />
                    </div>
                </template>
            </Heading>

            <!-- Step Indicator -->
            <div class="flex items-center justify-center gap-4">
                <div
                    v-for="step in steps"
                    :key="step.number"
                    :class="[
                        'flex items-center gap-2',
                        isRTL() ? 'flex-row-reverse' : '',
                    ]"
                >
                    <div
                        :class="[
                            'flex h-10 w-10 items-center justify-center rounded-full transition-colors',
                            currentStep === step.number
                                ? 'bg-[#25D366] text-white'
                                : step.completed
                                  ? 'bg-[#25D366]/20 text-[#25D366]'
                                  : 'bg-muted text-muted-foreground',
                        ]"
                    >
                        <component :is="step.icon" v-if="!step.completed" class="h-5 w-5" />
                        <CheckCircle v-else class="h-5 w-5" />
                    </div>
                    <div v-if="step.number < 4" class="h-0.5 w-8 bg-muted md:w-16"></div>
                </div>
            </div>

            <!-- Step 1: Campaign Details -->
            <Card v-show="currentStep === 1">
                <CardHeader>
                    <CardTitle>{{ t('campaigns.step1_title', 'Campaign Details') }}</CardTitle>
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
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ t('campaigns.schedule_description', 'Leave empty to send immediately') }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Step 2: Select Recipients -->
            <Card v-show="currentStep === 2">
                <CardHeader>
                    <CardTitle>{{ t('campaigns.step2_title', 'Select Recipients') }}</CardTitle>
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

            <!-- Step 3: Compose Message -->
            <Card v-show="currentStep === 3">
                <CardHeader>
                    <CardTitle>{{ t('campaigns.step3_title', 'Compose Message') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- WhatsApp Session -->
                    <SessionSelector
                        v-model="form.session_id"
                        :sessions="sessions"
                        :error="form.errors.session_id"
                    />

                    <!-- Use Template -->
                    <div>
                        <Label>{{ t('campaigns.use_template', 'Use Template') }} ({{ t('common.optional', 'optional') }})</Label>
                        <Select @update:model-value="handleTemplateSelect">
                            <SelectTrigger>
                                <SelectValue :placeholder="t('campaigns.select_template', 'Select a template or write custom message')" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="template in templates"
                                    :key="template.id"
                                    :value="template.id.toString()"
                                >
                                    {{ template.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

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
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ t('campaigns.placeholders_hint', 'Use {{first_name}}, {{last_name}}, {{phone}} to personalize') }}
                        </p>
                        <p v-if="form.errors.message_content" class="mt-1 text-sm text-red-500">
                            {{ form.errors.message_content }}
                        </p>
                    </div>

                    <!-- Media Upload -->
                    <div v-if="form.message_type !== 'text'">
                        <Label>{{ t('campaigns.upload_media', 'Upload Media') }}</Label>

                        <div v-if="!form.media" class="mt-2">
                            <label
                                class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/25 p-6 transition-colors hover:border-[#25D366] hover:bg-[#25D366]/5"
                            >
                                <Upload class="mb-2 h-8 w-8 text-muted-foreground" />
                                <span class="text-sm font-medium">
                                    {{ t('campaigns.click_to_upload', 'Click to upload') }}
                                </span>
                                <span class="mt-1 text-xs text-muted-foreground">
                                    {{ t('campaigns.max_size', 'Max size: 16MB') }}
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
                                    <p class="font-medium">{{ form.media.name }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ (form.media.size / 1024 / 1024).toFixed(2) }} MB
                                    </p>
                                </div>
                                <Button variant="destructive" size="sm" @click="removeMedia">
                                    {{ t('common.remove', 'Remove') }}
                                </Button>
                            </div>

                            <!-- Preview -->
                            <div v-if="mediaPreview && form.message_type === 'image'" class="mt-4">
                                <img :src="mediaPreview" alt="Preview" class="max-h-48 rounded-lg" />
                            </div>
                        </div>

                        <p v-if="form.errors.media" class="mt-1 text-sm text-red-500">
                            {{ form.errors.media }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Step 4: Review & Confirm -->
            <Card v-show="currentStep === 4">
                <CardHeader>
                    <CardTitle>{{ t('campaigns.step4_title', 'Review & Confirm') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Campaign Summary -->
                    <div class="rounded-lg bg-muted p-4">
                        <h3 class="mb-3 font-semibold">{{ t('campaigns.summary', 'Campaign Summary') }}</h3>
                        <div class="space-y-2 text-sm">
                            <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex justify-between">
                                <span class="text-muted-foreground">{{ t('campaigns.name', 'Name') }}:</span>
                                <span class="font-medium">{{ form.name }}</span>
                            </div>
                            <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex justify-between">
                                <span class="text-muted-foreground">{{ t('campaigns.total_recipients', 'Recipients') }}:</span>
                                <span class="font-medium">{{ form.recipient_ids.length }}</span>
                            </div>
                            <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex justify-between">
                                <span class="text-muted-foreground">{{ t('campaigns.message_type', 'Type') }}:</span>
                                <span class="font-medium">{{ form.message_type }}</span>
                            </div>
                            <div v-if="form.scheduled_at" :class="isRTL() ? 'flex-row-reverse' : ''" class="flex justify-between">
                                <span class="text-muted-foreground">{{ t('campaigns.scheduled_for', 'Scheduled') }}:</span>
                                <span class="font-medium">{{ new Date(form.scheduled_at).toLocaleString() }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quota Usage -->
                    <Alert :class="willExceedQuota ? 'border-red-500 bg-red-50 dark:bg-red-950' : 'border-[#25D366]/20 bg-[#25D366]/10'">
                        <AlertCircle :class="willExceedQuota ? 'text-red-600' : 'text-[#25D366]'" class="h-4 w-4" />
                        <AlertDescription :class="willExceedQuota ? 'text-red-900 dark:text-red-100' : ''">
                            <div class="space-y-2">
                                <div>
                                    <strong>{{ t('campaigns.quota_usage', 'Quota Usage') }}:</strong>
                                </div>
                                <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex justify-between text-sm">
                                    <span>{{ t('campaigns.current_usage', 'Current') }}:</span>
                                    <span :class="usageColor">{{ usage.used }} / {{ usage.limit }}</span>
                                </div>
                                <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex justify-between text-sm">
                                    <span>{{ t('campaigns.after_campaign', 'After Campaign') }}:</span>
                                    <span :class="willExceedQuota ? 'text-red-600' : 'text-[#25D366]'">
                                        {{ estimatedUsage }} / {{ usage.limit }}
                                    </span>
                                </div>
                                <div v-if="willExceedQuota" class="mt-2 font-medium text-red-600">
                                    {{ t('campaigns.quota_exceeded', 'This campaign exceeds your quota!') }}
                                </div>
                            </div>
                        </AlertDescription>
                    </Alert>

                    <!-- Message Preview -->
                    <div>
                        <h3 class="mb-2 font-semibold">{{ t('campaigns.message_preview', 'Message Preview') }}</h3>
                        <div class="rounded-lg border bg-muted p-4">
                            <p class="whitespace-pre-wrap text-sm">{{ form.message_content }}</p>
                            <div v-if="form.media" class="mt-2">
                                <Badge>{{ form.message_type }} {{ t('campaigns.media_attached', 'attached') }}</Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Navigation Buttons -->
            <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex justify-between">
                <Button
                    v-if="currentStep > 1"
                    variant="outline"
                    @click="prevStep"
                >
                    <ArrowLeft :class="isRTL() ? 'ml-2' : 'mr-2'" class="h-4 w-4" />
                    {{ t('common.previous', 'Previous') }}
                </Button>
                <div v-else></div>

                <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="index()">
                            {{ t('common.cancel', 'Cancel') }}
                        </Link>
                    </Button>

                    <Button
                        v-if="currentStep < 4"
                        :disabled="
                            (currentStep === 1 && !canProceedStep1) ||
                            (currentStep === 2 && !canProceedStep2) ||
                            (currentStep === 3 && !canProceedStep3)
                        "
                        @click="nextStep"
                    >
                        {{ t('common.next', 'Next') }}
                        <ArrowRight :class="isRTL() ? 'mr-2' : 'ml-2'" class="h-4 w-4" />
                    </Button>

                    <Button
                        v-else
                        :disabled="form.processing || willExceedQuota"
                        class="bg-[#25D366] hover:bg-[#25D366]/90"
                        @click="submit"
                    >
                        {{ form.processing ? t('common.creating', 'Creating...') : t('campaigns.create_campaign', 'Create Campaign') }}
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
