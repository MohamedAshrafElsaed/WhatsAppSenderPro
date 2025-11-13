<script lang="ts" setup>
import TemplateController from '@/actions/App/Http/Controllers/TemplateController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import MediaUploader from '@/components/MediaUploader.vue';
import PlaceholderPicker from '@/components/PlaceholderPicker.vue';
import TemplatePreview from '@/components/TemplatePreview.vue';
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
import { index } from '@/routes/dashboard/templates';
import type { Placeholder, TemplateType } from '@/types/template';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    FileText,
    Image as ImageIcon,
    MessageSquare,
    Video,
} from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';

interface Props {
    placeholders: Placeholder[];
}

const props = defineProps<Props>();

const { t, isRTL } = useTranslation();

const form = useForm({
    name: '',
    type: 'text' as TemplateType,
    content: '',
    caption: '',
    media: null as File | null,
});

const contentTextarea = ref<HTMLTextAreaElement>();
const captionTextarea = ref<HTMLTextAreaElement>();
const previewMediaUrl = ref<string | null>(null);

const requiresMedia = computed(() => {
    return form.type !== 'text';
});

const insertPlaceholder = (placeholder: string) => {
    const textarea = contentTextarea.value;
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = form.content;

    form.content = text.substring(0, start) + placeholder + text.substring(end);

    setTimeout(() => {
        textarea.focus();
        const newPosition = start + placeholder.length;
        textarea.setSelectionRange(newPosition, newPosition);
    }, 0);
};

const handleMediaUpdate = (file: File | null) => {
    form.media = file;

    if (previewMediaUrl.value) {
        URL.revokeObjectURL(previewMediaUrl.value);
        previewMediaUrl.value = null;
    }

    if (file) {
        previewMediaUrl.value = URL.createObjectURL(file);
    }
};

const handleMediaRemove = () => {
    form.media = null;

    if (previewMediaUrl.value) {
        URL.revokeObjectURL(previewMediaUrl.value);
        previewMediaUrl.value = null;
    }
};

watch(
    () => form.type,
    (newType) => {
        if (newType === 'text') {
            form.media = null;
            form.caption = '';
            if (previewMediaUrl.value) {
                URL.revokeObjectURL(previewMediaUrl.value);
                previewMediaUrl.value = null;
            }
        }
    },
);

onUnmounted(() => {
    if (previewMediaUrl.value) {
        URL.revokeObjectURL(previewMediaUrl.value);
    }
});

const submit = () => {
    form.post(TemplateController.store.url(), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="t('templates.create_template', 'Create Template')" />

        <div
            :class="isRTL() ? 'text-right' : 'text-left'"
            class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6"
        >
            <!-- Header -->
            <Heading
                :description="
                    t(
                        'templates.create_description',
                        'Create a new message template with placeholders',
                    )
                "
                :title="t('templates.create_template', 'Create Template')"
            >
                <template #icon>
                    <div
                        class="flex items-center justify-center rounded-lg bg-[#25D366] p-2"
                    >
                        <MessageSquare class="h-5 w-5 text-white" />
                    </div>
                </template>
            </Heading>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Form Section (2 columns) -->
                <div class="lg:col-span-2">
                    <form class="space-y-6" @submit.prevent="submit">
                        <!-- Basic Information -->
                        <Card>
                            <CardHeader>
                                <CardTitle
                                    :class="
                                        isRTL() ? 'text-right' : 'text-left'
                                    "
                                >
                                    {{
                                        t(
                                            'templates.basic_info',
                                            'Basic Information',
                                        )
                                    }}
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Template Name -->
                                <div>
                                    <Label
                                        :class="
                                            isRTL() ? 'text-right' : 'text-left'
                                        "
                                        for="name"
                                    >
                                        {{
                                            t(
                                                'templates.template_name',
                                                'Template Name',
                                            )
                                        }}
                                    </Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        :placeholder="
                                            t(
                                                'templates.name_placeholder',
                                                'Enter template name',
                                            )
                                        "
                                        class="mt-1 focus-visible:ring-[#25D366]"
                                        type="text"
                                    />
                                    <InputError
                                        :message="form.errors.name"
                                        class="mt-1"
                                    />
                                </div>

                                <!-- Template Type -->
                                <div>
                                    <Label
                                        :class="
                                            isRTL() ? 'text-right' : 'text-left'
                                        "
                                        for="type"
                                    >
                                        {{
                                            t(
                                                'templates.template_type',
                                                'Template Type',
                                            )
                                        }}
                                    </Label>
                                    <Select v-model="form.type" class="mt-1">
                                        <SelectTrigger
                                            class="focus:ring-[#25D366]"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="text">
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <MessageSquare
                                                        class="h-4 w-4 text-[#25D366]"
                                                    />
                                                    {{
                                                        t(
                                                            'templates.text_only',
                                                            'Text Only',
                                                        )
                                                    }}
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="text_image">
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <ImageIcon
                                                        class="h-4 w-4 text-[#25D366]"
                                                    />
                                                    {{
                                                        t(
                                                            'templates.text_image',
                                                            'Text + Image',
                                                        )
                                                    }}
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="text_video">
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <Video
                                                        class="h-4 w-4 text-[#25D366]"
                                                    />
                                                    {{
                                                        t(
                                                            'templates.text_video',
                                                            'Text + Video',
                                                        )
                                                    }}
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="text_document">
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <FileText
                                                        class="h-4 w-4 text-[#25D366]"
                                                    />
                                                    {{
                                                        t(
                                                            'templates.text_document',
                                                            'Text + Document',
                                                        )
                                                    }}
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :message="form.errors.type"
                                        class="mt-1"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Media Upload (for media types) -->
                        <MediaUploader
                            v-if="requiresMedia"
                            :error="form.errors.media"
                            :type="form.type"
                            @remove="handleMediaRemove"
                            @update:file="handleMediaUpdate"
                        />

                        <!-- Caption (for media types) -->
                        <Card v-if="requiresMedia">
                            <CardHeader>
                                <CardTitle
                                    :class="
                                        isRTL() ? 'text-right' : 'text-left'
                                    "
                                >
                                    {{
                                        t(
                                            'templates.media_caption',
                                            'Media Caption',
                                        )
                                    }}
                                </CardTitle>
                                <CardDescription
                                    :class="
                                        isRTL() ? 'text-right' : 'text-left'
                                    "
                                >
                                    {{
                                        t(
                                            'templates.caption_description',
                                            'Optional text to accompany the media',
                                        )
                                    }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Textarea
                                    ref="captionTextarea"
                                    v-model="form.caption"
                                    :placeholder="
                                        t(
                                            'templates.caption_placeholder',
                                            'Enter caption...',
                                        )
                                    "
                                    class="focus-visible:ring-[#25D366]"
                                    rows="3"
                                />
                                <InputError
                                    :message="form.errors.caption"
                                    class="mt-1"
                                />
                            </CardContent>
                        </Card>

                        <!-- Message Content -->
                        <Card>
                            <CardHeader>
                                <CardTitle
                                    :class="
                                        isRTL() ? 'text-right' : 'text-left'
                                    "
                                >
                                    {{
                                        t(
                                            'templates.template_content',
                                            'Message Content',
                                        )
                                    }}
                                </CardTitle>
                                <CardDescription
                                    :class="
                                        isRTL() ? 'text-right' : 'text-left'
                                    "
                                >
                                    {{
                                        t(
                                            'templates.content_description',
                                            'Use placeholders to personalize messages',
                                        )
                                    }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Textarea
                                    ref="contentTextarea"
                                    v-model="form.content"
                                    :placeholder="
                                        t(
                                            'templates.content_placeholder',
                                            'Enter message content...',
                                        )
                                    "
                                    class="focus-visible:ring-[#25D366]"
                                    rows="8"
                                />
                                <InputError
                                    :message="form.errors.content"
                                    class="mt-1"
                                />
                            </CardContent>
                        </Card>

                        <!-- Form Actions -->
                        <div
                            :class="isRTL() ? 'flex-row-reverse' : ''"
                            class="flex gap-4"
                        >
                            <Button
                                :disabled="form.processing"
                                class="bg-[#25D366] hover:bg-[#128C7E]"
                                type="submit"
                            >
                                {{ t('common.create', 'Create') }}
                            </Button>
                            <Button as-child variant="outline">
                                <Link :href="index()">
                                    {{ t('common.cancel', 'Cancel') }}
                                </Link>
                            </Button>
                        </div>
                    </form>
                </div>

                <!-- Sidebar Section (1 column) -->
                <div class="space-y-6">
                    <!-- Placeholder Picker -->
                    <PlaceholderPicker
                        :placeholders="placeholders"
                        @insert="insertPlaceholder"
                    />

                    <!-- Preview -->
                    <TemplatePreview
                        :caption="form.caption"
                        :content="form.content"
                        :media-url="previewMediaUrl"
                        :type="form.type"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
