<script lang="ts" setup>
import CampaignController from '@/actions/App/Http/Controllers/CampaignController';
import Heading from '@/components/Heading.vue';
import RecipientSelectorLazy from '@/components/RecipientSelectorLazy.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import SessionSelector from '@/components/SessionSelector.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as dashboard } from '@/routes/dashboard';
import { index } from '@/routes/dashboard/campaigns';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    Calendar,
    CheckCircle,
    FileText,
    HelpCircle,
    Info,
    MessageSquare,
    Upload,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const PRIMARY_COLOR = '#25D366';

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
    totalContactsCount: number;
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
const isSubmitting = ref(false);

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

// Template options for SearchableSelect
const templateOptions = computed(() =>
    props.templates.map((template) => ({
        value: template.id.toString(),
        label: template.name,
    })),
);

// Message type options for SearchableSelect
const messageTypeOptions = computed(() => [
    { value: 'text', label: t('campaigns.type.text', 'Text Only') },
    { value: 'image', label: t('campaigns.type.image', 'Text + Image') },
    { value: 'video', label: t('campaigns.type.video', 'Text + Video') },
    { value: 'audio', label: t('campaigns.type.audio', 'Audio') },
    { value: 'document', label: t('campaigns.type.document', 'Document') },
]);

const usagePercentage = computed(() => {
    if (props.usage.limit === 'unlimited' || props.usage.limit === 0) return 0;
    return Math.round((props.usage.used / (props.usage.limit as number)) * 100);
});

const usageColor = computed(() => {
    const pct = usagePercentage.value;
    if (pct >= 90) return 'text-red-600';
    if (pct >= 70) return 'text-yellow-600';
    return 'text-green-600';
});

const estimatedUsage = computed(() => {
    return props.usage.used + form.recipient_ids.length;
});

const estimatedPercentage = computed(() => {
    if (props.usage.limit === 'unlimited' || props.usage.limit === 0) return 0;
    return Math.round(
        (estimatedUsage.value / (props.usage.limit as number)) * 100,
    );
});

const willExceedQuota = computed(() => {
    if (props.usage.limit === 'unlimited') return false;
    return form.recipient_ids.length > (props.usage.remaining as number);
});

const selectedTemplate = computed(() => {
    if (!form.template_id) return null;
    return props.templates.find((t) => t.id === form.template_id);
});

// Check if message content is from template (read-only mode)
const isTemplateMode = computed(() => !!selectedTemplate.value);

const canProceedStep1 = computed(() => {
    return form.name.trim().length > 0;
});

const canProceedStep2 = computed(() => {
    return form.recipient_ids.length > 0;
});

const canProceedStep3 = computed(() => {
    const hasMessageContent = form.message_content.trim().length > 0;
    const hasSession = form.session_id.length > 0;
    const hasMediaIfRequired =
        form.message_type === 'text' ||
        form.media !== null ||
        selectedTemplate.value?.media_url;

    return hasMessageContent && hasSession && hasMediaIfRequired;
});

// Watch template selection
watch(
    () => form.template_id,
    (newTemplateId) => {
        if (!newTemplateId) {
            // Template deselected - clear template data but keep custom message
            return;
        }

        const template = props.templates.find((t) => t.id === newTemplateId);
        if (template) {
            form.message_type =
                template.type === 'text'
                    ? 'text'
                    : (template.type.replace('text_', '') as any);
            form.message_content = template.content;
            form.message_caption = template.caption || '';

            // Clear media if switching to template
            if (form.media) {
                form.media = null;
                mediaPreview.value = null;
            }
        }
    },
);

const handleTemplateSelect = (templateId: string | number | null) => {
    if (!templateId || templateId === '') {
        form.template_id = null;
        return;
    }

    form.template_id = parseInt(templateId.toString());
};

const handleMediaUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        form.media = file;

        // Create preview for images
        if (form.message_type === 'image') {
            const reader = new FileReader();
            reader.onload = (e) => {
                mediaPreview.value = e.target?.result as string;
            };
            reader.readAsDataURL(file);
        }
    }
};

const removeMedia = () => {
    form.media = null;
    mediaPreview.value = null;
};

const nextStep = () => {
    if (currentStep.value < 4) {
        currentStep.value++;

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const submit = () => {
    isSubmitting.value = true;

    form.post(CampaignController.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
};

const steps = computed(() => [
    {
        number: 1,
        title: t('campaigns.step1_title', 'Campaign Details'),
        description: t(
            'campaigns.step1_desc',
            'Name and schedule your campaign',
        ),
        icon: FileText,
        completed: currentStep.value > 1,
    },
    {
        number: 2,
        title: t('campaigns.step2_title', 'Select Recipients'),
        description: t(
            'campaigns.step2_desc',
            'Choose who will receive your message',
        ),
        icon: Users,
        completed: currentStep.value > 2,
    },
    {
        number: 3,
        title: t('campaigns.step3_title', 'Compose Message'),
        description: t('campaigns.step3_desc', 'Create or select your message'),
        icon: MessageSquare,
        completed: currentStep.value > 3,
    },
    {
        number: 4,
        title: t('campaigns.step4_title', 'Review & Confirm'),
        description: t(
            'campaigns.step4_desc',
            'Verify campaign before sending',
        ),
        icon: CheckCircle,
        completed: false,
    },
]);

// Extract template variables from message
const extractVariables = (content: string): string[] => {
    const regex = /\{([^}]+)\}/g;
    const matches = [];
    let match;
    while ((match = regex.exec(content)) !== null) {
        matches.push(match[1]);
    }
    return [...new Set(matches)];
};

const templateVariables = computed(() => {
    if (!selectedTemplate.value) return [];
    return extractVariables(selectedTemplate.value.content);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('campaigns.create', 'Create Campaign')" />

        <div
            :class="isRTL() ? 'text-right' : 'text-left'"
            class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6"
        >
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    :description="
                        t(
                            'campaigns.create_description',
                            'Create a new WhatsApp bulk messaging campaign',
                        )
                    "
                    :title="t('campaigns.create', 'Create Campaign')"
                >
                    <template #icon>
                        <div
                            :style="`background-color: ${PRIMARY_COLOR}`"
                            class="flex items-center justify-center rounded-lg p-2"
                        >
                            <MessageSquare class="h-5 w-5 text-white" />
                        </div>
                    </template>
                </Heading>

                <Button as-child variant="outline">
                    <Link :href="index()">
                        <X :class="isRTL() ? 'ml-2' : 'mr-2'" class="h-4 w-4" />
                        {{ t('common.cancel', 'Cancel') }}
                    </Link>
                </Button>
            </div>

            <!-- Step Indicator -->
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div
                            v-for="(step, index) in steps"
                            :key="step.number"
                            class="flex flex-1 items-center"
                        >
                            <div class="flex flex-col items-center gap-2">
                                <!-- Step Icon -->
                                <div
                                    :class="[
                                        'flex h-12 w-12 items-center justify-center rounded-full border-2 transition-all',
                                        currentStep === step.number
                                            ? 'border-[#25D366] bg-[#25D366] text-white shadow-lg'
                                            : step.completed
                                              ? 'border-[#25D366] bg-[#25D366] text-white'
                                              : 'border-muted-foreground/30 bg-background text-muted-foreground',
                                    ]"
                                >
                                    <component
                                        :is="step.icon"
                                        v-if="!step.completed"
                                        class="h-5 w-5"
                                    />
                                    <CheckCircle v-else class="h-5 w-5" />
                                </div>

                                <!-- Step Info -->
                                <div class="text-center">
                                    <p
                                        :class="[
                                            'text-sm font-medium',
                                            currentStep === step.number ||
                                            step.completed
                                                ? 'text-[#25D366]'
                                                : 'text-muted-foreground',
                                        ]"
                                    >
                                        {{ step.title }}
                                    </p>
                                    <p
                                        class="hidden text-xs text-muted-foreground md:block"
                                    >
                                        {{ step.description }}
                                    </p>
                                </div>
                            </div>

                            <!-- Connector Line -->
                            <div
                                v-if="index < steps.length - 1"
                                :class="[
                                    'mx-2 h-0.5 flex-1',
                                    step.completed
                                        ? 'bg-[#25D366]'
                                        : 'bg-muted',
                                ]"
                            ></div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Step 1: Campaign Details -->
            <Card
                v-show="currentStep === 1"
                class="border-t-4 border-t-[#25D366]"
            >
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="h-5 w-5 text-[#25D366]" />
                        {{ t('campaigns.step1_title', 'Campaign Details') }}
                    </CardTitle>
                    <CardDescription>
                        {{
                            t(
                                'campaigns.step1_full_desc',
                                'Give your campaign a name and optionally schedule it for later',
                            )
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Campaign Name -->
                    <div class="space-y-2">
                        <Label class="flex items-center gap-2" for="name">
                            {{ t('campaigns.name', 'Campaign Name') }}
                            <span class="text-red-500">*</span>
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <HelpCircle
                                            class="h-4 w-4 cursor-help text-muted-foreground"
                                        />
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>
                                            {{
                                                t(
                                                    'campaigns.name_hint',
                                                    'Choose a descriptive name to identify this campaign',
                                                )
                                            }}
                                        </p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            :class="form.errors.name ? 'border-red-500' : ''"
                            :dir="isRTL() ? 'rtl' : 'ltr'"
                            :placeholder="
                                t(
                                    'campaigns.name_placeholder',
                                    'e.g., Summer Sale 2025',
                                )
                            "
                            class="focus-visible:ring-[#25D366]"
                        />
                        <p
                            v-if="form.errors.name"
                            class="flex items-center gap-1 text-sm text-red-500"
                        >
                            <AlertCircle class="h-4 w-4" />
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Schedule -->
                    <div class="space-y-2">
                        <Label
                            class="flex items-center gap-2"
                            for="scheduled_at"
                        >
                            <Calendar class="h-4 w-4" />
                            {{ t('campaigns.schedule_at', 'Schedule For') }}
                            <Badge class="text-xs" variant="secondary">
                                {{ t('common.optional', 'Optional') }}
                            </Badge>
                        </Label>
                        <Input
                            id="scheduled_at"
                            v-model="form.scheduled_at"
                            :min="new Date().toISOString().slice(0, 16)"
                            class="focus-visible:ring-[#25D366]"
                            type="datetime-local"
                        />
                        <p
                            class="flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <Info class="h-3 w-3" />
                            {{
                                t(
                                    'campaigns.schedule_description',
                                    'Leave empty to send immediately after review',
                                )
                            }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Step 2: Select Recipients -->
            <Card
                v-show="currentStep === 2"
                class="border-t-4 border-t-[#25D366]"
            >
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Users class="h-5 w-5 text-[#25D366]" />
                        {{ t('campaigns.step2_title', 'Select Recipients') }}
                    </CardTitle>
                    <CardDescription>
                        {{
                            t(
                                'campaigns.step2_full_desc',
                                'Choose the contacts who will receive this campaign',
                            )
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- No Contacts Warning -->
                    <Alert
                        v-if="totalContactsCount === 0"
                        variant="destructive"
                    >
                        <AlertCircle class="h-4 w-4" />
                        <AlertTitle>{{
                            t(
                                'campaigns.no_contacts_title',
                                'No Contacts Available',
                            )
                        }}</AlertTitle>
                        <AlertDescription>
                            {{
                                t(
                                    'campaigns.no_contacts_available',
                                    'No contacts available. Please import contacts first.',
                                )
                            }}
                            <Button
                                as-child
                                class="mt-2"
                                size="sm"
                                variant="outline"
                            >
                                <Link href="/dashboard/contacts/imports">
                                    <Upload
                                        :class="isRTL() ? 'ml-2' : 'mr-2'"
                                        class="h-4 w-4"
                                    />
                                    {{
                                        t('contacts.import', 'Import Contacts')
                                    }}
                                </Link>
                            </Button>
                        </AlertDescription>
                    </Alert>

                    <!-- Recipient Selector -->
                    <RecipientSelectorLazy
                        v-else
                        v-model="form.recipient_ids"
                        :total-contacts-count="totalContactsCount"
                    />

                    <!-- Selection Summary -->
                    <Alert
                        v-if="form.recipient_ids.length > 0"
                        class="border-[#25D366]/20 bg-[#25D366]/10"
                    >
                        <CheckCircle class="h-4 w-4 text-[#25D366]" />
                        <AlertDescription class="text-[#25D366]">
                            <strong>{{
                                form.recipient_ids.length.toLocaleString()
                            }}</strong>
                            {{
                                t(
                                    'campaigns.contacts_selected',
                                    'contacts selected',
                                )
                            }}
                        </AlertDescription>
                    </Alert>

                    <p
                        v-if="form.errors.recipient_ids"
                        class="flex items-center gap-1 text-sm text-red-500"
                    >
                        <AlertCircle class="h-4 w-4" />
                        {{ form.errors.recipient_ids }}
                    </p>
                </CardContent>
            </Card>

            <!-- Step 3: Compose Message -->
            <Card
                v-show="currentStep === 3"
                class="border-t-4 border-t-[#25D366]"
            >
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <MessageSquare class="h-5 w-5 text-[#25D366]" />
                        {{ t('campaigns.step3_title', 'Compose Message') }}
                    </CardTitle>
                    <CardDescription>
                        {{
                            t(
                                'campaigns.step3_full_desc',
                                'Select your WhatsApp session and create your message',
                            )
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- WhatsApp Session -->
                    <div class="space-y-2">
                        <Label class="flex items-center gap-2">
                            {{
                                t(
                                    'campaigns.select_session',
                                    'WhatsApp Session',
                                )
                            }}
                            <span class="text-red-500">*</span>
                        </Label>
                        <SessionSelector
                            v-model="form.session_id"
                            :error="form.errors.session_id"
                            :sessions="sessions"
                        />
                        <p
                            v-if="sessions.length === 0"
                            class="flex items-center gap-1 text-sm text-yellow-600"
                        >
                            <AlertCircle class="h-4 w-4" />
                            {{
                                t(
                                    'campaigns.errors.no_connected_session_desc',
                                    'Please connect a WhatsApp session before creating a campaign.',
                                )
                            }}
                        </p>
                    </div>

                    <!-- Template Selection -->
                    <div
                        v-if="templates && templates.length > 0"
                        class="space-y-2"
                    >
                        <Label class="flex items-center gap-2">
                            {{ t('campaigns.use_template', 'Use Template') }}
                            <Badge class="text-xs" variant="secondary">
                                {{ t('common.optional', 'Optional') }}
                            </Badge>
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <HelpCircle
                                            class="h-4 w-4 cursor-help text-muted-foreground"
                                        />
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>
                                            {{
                                                t(
                                                    'campaigns.template_hint',
                                                    'Select a pre-made template or write a custom message',
                                                )
                                            }}
                                        </p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </Label>
                        <SearchableSelect
                            :model-value="form.template_id?.toString()"
                            :options="templateOptions"
                            :placeholder="
                                t(
                                    'campaigns.select_template_placeholder',
                                    'Select a template or write custom message',
                                )
                            "
                            :search-placeholder="
                                t(
                                    'campaigns.search_templates',
                                    'Search templates...',
                                )
                            "
                            @update:model-value="handleTemplateSelect"
                        />

                        <!-- Template Mode Indicator -->
                        <Alert
                            v-if="isTemplateMode"
                            class="border-blue-200 bg-blue-50 dark:bg-blue-950"
                        >
                            <Info class="h-4 w-4 text-blue-600" />
                            <AlertDescription
                                class="text-blue-900 dark:text-blue-100"
                            >
                                {{
                                    t(
                                        'campaigns.template_mode',
                                        'Template mode active - Message content is read-only',
                                    )
                                }}
                                <br />
                                <span
                                    v-if="templateVariables.length > 0"
                                    class="text-sm"
                                >
                                    {{
                                        t(
                                            'campaigns.template_variables',
                                            'Variables:',
                                        )
                                    }}
                                    <Badge
                                        v-for="variable in templateVariables"
                                        :key="variable"
                                        class="ml-1"
                                        variant="secondary"
                                    >
                                        {{ '{' + variable + '}' }}
                                    </Badge>
                                </span>
                            </AlertDescription>
                        </Alert>
                    </div>

                    <!-- Message Type -->
                    <div class="space-y-2">
                        <Label class="flex items-center gap-2">
                            {{ t('campaigns.message_type', 'Message Type') }}
                            <span class="text-red-500">*</span>
                        </Label>
                        <SearchableSelect
                            v-model="form.message_type"
                            :disabled="isTemplateMode"
                            :options="messageTypeOptions"
                            :placeholder="
                                t(
                                    'campaigns.select_message_type',
                                    'Select message type',
                                )
                            "
                            :search-placeholder="
                                t('campaigns.search_type', 'Search type...')
                            "
                        />
                    </div>

                    <!-- Message Content -->
                    <div class="space-y-2">
                        <Label
                            class="flex items-center gap-2"
                            for="message_content"
                        >
                            {{
                                form.message_type === 'text'
                                    ? t(
                                          'campaigns.message_content',
                                          'Message Content',
                                      )
                                    : t('campaigns.message_caption', 'Caption')
                            }}
                            <span class="text-red-500">*</span>
                        </Label>

                        <!-- Template Preview (Read-only) -->
                        <div v-if="isTemplateMode" class="relative">
                            <Textarea
                                id="message_content"
                                :dir="isRTL() ? 'rtl' : 'ltr'"
                                :model-value="form.message_content"
                                class="cursor-not-allowed resize-none bg-muted"
                                readonly
                                rows="6"
                            />
                            <div class="absolute top-2 right-2">
                                <Badge variant="secondary">
                                    {{ t('campaigns.read_only', 'Read-only') }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Editable Message Content -->
                        <Textarea
                            v-else
                            id="message_content"
                            v-model="form.message_content"
                            :class="
                                form.errors.message_content
                                    ? 'border-red-500'
                                    : ''
                            "
                            :dir="isRTL() ? 'rtl' : 'ltr'"
                            :placeholder="
                                t(
                                    'campaigns.message_placeholder',
                                    'Type your message here...',
                                )
                            "
                            class="focus-visible:ring-[#25D366]"
                            rows="6"
                        />

                        <p
                            class="flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <Info class="h-3 w-3" />
                            {{
                                t(
                                    'campaigns.placeholders_hint',
                                    'Use {first_name}, {last_name}, {phone} to personalize',
                                )
                            }}
                        </p>
                        <p
                            v-if="form.errors.message_content"
                            class="flex items-center gap-1 text-sm text-red-500"
                        >
                            <AlertCircle class="h-4 w-4" />
                            {{ form.errors.message_content }}
                        </p>
                    </div>

                    <!-- Media Upload -->
                    <div v-if="form.message_type !== 'text'" class="space-y-2">
                        <Label class="flex items-center gap-2">
                            {{ t('campaigns.upload_media', 'Upload Media') }}
                            <span
                                v-if="!selectedTemplate?.media_url"
                                class="text-red-500"
                                >*</span
                            >
                        </Label>

                        <!-- Show template media if available -->
                        <Alert
                            v-if="selectedTemplate?.media_url"
                            class="border-blue-200 bg-blue-50 dark:bg-blue-950"
                        >
                            <Info class="h-4 w-4 text-blue-600" />
                            <AlertDescription
                                class="text-blue-900 dark:text-blue-100"
                            >
                                {{
                                    t(
                                        'campaigns.template_media',
                                        'Media from template will be used',
                                    )
                                }}
                            </AlertDescription>
                        </Alert>

                        <!-- Upload Area -->
                        <div v-if="!form.media && !selectedTemplate?.media_url">
                            <label
                                class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/25 p-8 transition-colors hover:border-[#25D366] hover:bg-[#25D366]/5"
                            >
                                <Upload
                                    class="mb-3 h-10 w-10 text-muted-foreground"
                                />
                                <span class="text-sm font-medium">
                                    {{
                                        t(
                                            'campaigns.click_to_upload',
                                            'Click to upload or drag and drop',
                                        )
                                    }}
                                </span>
                                <span
                                    class="mt-2 text-center text-xs text-muted-foreground"
                                >
                                    {{
                                        t(
                                            'campaigns.max_size',
                                            'Max size: 16MB',
                                        )
                                    }}
                                    <br />
                                    {{
                                        t(
                                            'campaigns.supported_formats',
                                            'Supported formats based on message type',
                                        )
                                    }}
                                </span>
                                <input
                                    :accept="
                                        form.message_type === 'image'
                                            ? 'image/*'
                                            : form.message_type === 'video'
                                              ? 'video/*'
                                              : form.message_type === 'audio'
                                                ? 'audio/*'
                                                : '.pdf,.doc,.docx,.xls,.xlsx'
                                    "
                                    class="hidden"
                                    type="file"
                                    @change="handleMediaUpload"
                                />
                            </label>
                        </div>

                        <!-- Media Preview -->
                        <div
                            v-if="form.media"
                            class="space-y-3 rounded-lg border bg-muted p-4"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p
                                        class="flex items-center gap-2 font-medium"
                                    >
                                        <FileText class="h-4 w-4" />
                                        {{ form.media.name }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{
                                            (
                                                form.media.size /
                                                1024 /
                                                1024
                                            ).toFixed(2)
                                        }}
                                        MB
                                    </p>
                                </div>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    @click="removeMedia"
                                >
                                    <X class="h-4 w-4" />
                                    {{ t('common.remove', 'Remove') }}
                                </Button>
                            </div>

                            <!-- Image Preview -->
                            <div
                                v-if="
                                    mediaPreview &&
                                    form.message_type === 'image'
                                "
                                class="mt-4"
                            >
                                <img
                                    :src="mediaPreview"
                                    alt="Preview"
                                    class="max-h-64 w-full rounded-lg bg-black/5 object-contain"
                                />
                            </div>
                        </div>

                        <p
                            v-if="form.errors.media"
                            class="flex items-center gap-1 text-sm text-red-500"
                        >
                            <AlertCircle class="h-4 w-4" />
                            {{ form.errors.media }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Step 4: Review & Confirm -->
            <Card
                v-show="currentStep === 4"
                class="border-t-4 border-t-[#25D366]"
            >
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CheckCircle class="h-5 w-5 text-[#25D366]" />
                        {{ t('campaigns.step4_title', 'Review & Confirm') }}
                    </CardTitle>
                    <CardDescription>
                        {{
                            t(
                                'campaigns.step4_full_desc',
                                'Review your campaign details before sending',
                            )
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Campaign Summary -->
                    <div class="space-y-4 rounded-lg border bg-muted/50 p-6">
                        <h3
                            class="flex items-center gap-2 text-lg font-semibold"
                        >
                            <FileText class="h-5 w-5" />
                            {{ t('campaigns.summary', 'Campaign Summary') }}
                        </h3>
                        <div
                            class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2"
                        >
                            <div class="flex justify-between border-b py-2">
                                <span class="text-muted-foreground"
                                    >{{ t('campaigns.name', 'Name') }}:</span
                                >
                                <span class="font-medium">{{ form.name }}</span>
                            </div>
                            <div class="flex justify-between border-b py-2">
                                <span class="text-muted-foreground"
                                    >{{
                                        t(
                                            'campaigns.total_recipients',
                                            'Recipients',
                                        )
                                    }}:</span
                                >
                                <Badge class="bg-[#25D366] hover:bg-[#128C7E]">
                                    {{
                                        form.recipient_ids.length.toLocaleString()
                                    }}
                                </Badge>
                            </div>
                            <div class="flex justify-between border-b py-2">
                                <span class="text-muted-foreground"
                                    >{{
                                        t('campaigns.message_type', 'Type')
                                    }}:</span
                                >
                                <Badge variant="outline">{{
                                    form.message_type
                                }}</Badge>
                            </div>
                            <div
                                v-if="form.scheduled_at"
                                class="flex justify-between border-b py-2"
                            >
                                <span class="text-muted-foreground"
                                    >{{
                                        t(
                                            'campaigns.scheduled_for',
                                            'Scheduled',
                                        )
                                    }}:</span
                                >
                                <span class="font-medium">{{
                                    new Date(form.scheduled_at).toLocaleString()
                                }}</span>
                            </div>
                            <div
                                v-if="selectedTemplate"
                                class="flex justify-between border-b py-2"
                            >
                                <span class="text-muted-foreground"
                                    >{{
                                        t('campaigns.template', 'Template')
                                    }}:</span
                                >
                                <span class="font-medium">{{
                                    selectedTemplate.name
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quota Usage -->
                    <Alert
                        :class="
                            willExceedQuota
                                ? 'border-red-500 bg-red-50 dark:bg-red-950'
                                : 'border-green-200 bg-green-50 dark:bg-green-950'
                        "
                    >
                        <AlertCircle
                            :class="
                                willExceedQuota
                                    ? 'text-red-600'
                                    : 'text-green-600'
                            "
                            class="h-4 w-4"
                        />
                        <AlertTitle>{{
                            t('campaigns.quota_usage', 'Quota Usage')
                        }}</AlertTitle>
                        <AlertDescription
                            :class="
                                willExceedQuota
                                    ? 'text-red-900 dark:text-red-100'
                                    : ''
                            "
                        >
                            <div class="mt-2 space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span
                                        >{{
                                            t(
                                                'campaigns.current_usage',
                                                'Current',
                                            )
                                        }}:</span
                                    >
                                    <span
                                        :class="usageColor"
                                        class="font-medium"
                                    >
                                        {{ usage.used.toLocaleString() }} /
                                        {{
                                            usage.limit === 'unlimited'
                                                ? '∞'
                                                : usage.limit.toLocaleString()
                                        }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span
                                        >{{
                                            t(
                                                'campaigns.after_campaign',
                                                'After Campaign',
                                            )
                                        }}:</span
                                    >
                                    <span
                                        :class="
                                            willExceedQuota
                                                ? 'font-medium text-red-600'
                                                : 'font-medium text-green-600'
                                        "
                                    >
                                        {{ estimatedUsage.toLocaleString() }} /
                                        {{
                                            usage.limit === 'unlimited'
                                                ? '∞'
                                                : usage.limit.toLocaleString()
                                        }}
                                    </span>
                                </div>
                                <Alert
                                    v-if="willExceedQuota"
                                    class="mt-2"
                                    variant="destructive"
                                >
                                    <AlertCircle class="h-4 w-4" />
                                    <AlertDescription>
                                        <strong>{{
                                            t(
                                                'campaigns.quota_exceeded',
                                                'This campaign exceeds your quota!',
                                            )
                                        }}</strong>
                                        <br />
                                        {{
                                            t(
                                                'campaigns.quota_exceeded_desc',
                                                'Please upgrade your plan or reduce the number of recipients.',
                                            )
                                        }}
                                    </AlertDescription>
                                </Alert>
                            </div>
                        </AlertDescription>
                    </Alert>

                    <!-- Message Preview -->
                    <div class="space-y-3">
                        <h3
                            class="flex items-center gap-2 text-lg font-semibold"
                        >
                            <MessageSquare class="h-5 w-5" />
                            {{
                                t(
                                    'campaigns.message_preview',
                                    'Message Preview',
                                )
                            }}
                        </h3>
                        <div
                            :dir="isRTL() ? 'rtl' : 'ltr'"
                            class="rounded-lg border bg-white p-4 shadow-sm dark:bg-gray-900"
                        >
                            <p class="text-sm whitespace-pre-wrap">
                                {{ form.message_content }}
                            </p>
                            <div
                                v-if="form.media || selectedTemplate?.media_url"
                                class="mt-3"
                            >
                                <Badge class="bg-[#25D366] hover:bg-[#128C7E]">
                                    {{ form.message_type }}
                                    {{
                                        t(
                                            'campaigns.media_attached',
                                            'attached',
                                        )
                                    }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Navigation Buttons -->
            <div
                class="sticky bottom-0 flex items-center justify-between rounded-t-lg border-t bg-background p-4 shadow-lg"
            >
                <Button
                    v-if="currentStep > 1"
                    :disabled="isSubmitting"
                    size="lg"
                    variant="outline"
                    @click="prevStep"
                >
                    <ArrowLeft
                        v-if="!isRTL()"
                        :class="isRTL() ? 'ml-2' : 'mr-2'"
                        class="h-4 w-4"
                    />
                    {{ t('common.previous', 'Previous') }}
                    <ArrowRight
                        v-if="isRTL()"
                        :class="isRTL() ? 'mr-2' : 'ml-2'"
                        class="h-4 w-4"
                    />
                </Button>
                <div v-else></div>

                <div class="flex gap-3">
                    <Button
                        v-if="currentStep < 4"
                        :disabled="
                            (currentStep === 1 && !canProceedStep1) ||
                            (currentStep === 2 && !canProceedStep2) ||
                            (currentStep === 3 && !canProceedStep3)
                        "
                        class="bg-[#25D366] hover:bg-[#128C7E]"
                        size="lg"
                        @click="nextStep"
                    >
                        <ArrowLeft
                            v-if="isRTL()"
                            :class="isRTL() ? 'ml-2' : 'mr-2'"
                            class="h-4 w-4"
                        />
                        {{ t('common.next', 'Next') }}
                        <ArrowRight
                            v-if="!isRTL()"
                            :class="isRTL() ? 'mr-2' : 'ml-2'"
                            class="h-4 w-4"
                        />
                    </Button>

                    <Button
                        v-else
                        :disabled="isSubmitting || willExceedQuota"
                        class="bg-[#25D366] hover:bg-[#128C7E]"
                        size="lg"
                        @click="submit"
                    >
                        <CheckCircle
                            v-if="!isSubmitting"
                            :class="isRTL() ? 'ml-2' : 'mr-2'"
                            class="h-4 w-4"
                        />
                        <div
                            v-else
                            :class="isRTL() ? 'ml-2' : 'mr-2'"
                            class="h-4 w-4 animate-spin rounded-full border-b-2 border-white"
                        ></div>
                        {{
                            isSubmitting
                                ? t('common.creating', 'Creating...')
                                : t(
                                      'campaigns.create_campaign',
                                      'Create Campaign',
                                  )
                        }}
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Smooth transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
