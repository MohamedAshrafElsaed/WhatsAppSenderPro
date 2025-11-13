<script lang="ts" setup>
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/composables/useTranslation';
import type { TemplateType } from '@/types/template';
import {
    FileText,
    Image as ImageIcon,
    Trash2,
    Upload,
    Video,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    type: TemplateType;
    existingMediaUrl?: string | null;
    error?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:file': [file: File | null];
    remove: [];
}>();

const { t, isRTL } = useTranslation();

const fileInput = ref<HTMLInputElement>();
const selectedFile = ref<File | null>(null);
const previewUrl = ref<string | null>(null);

const mediaConfig = computed(() => {
    const configs = {
        text_image: {
            accept: 'image/jpeg,image/png,image/gif',
            maxSize: '5MB',
            types: 'JPG, PNG, GIF',
            icon: ImageIcon,
        },
        text_video: {
            accept: 'video/mp4',
            maxSize: '16MB',
            types: 'MP4',
            icon: Video,
        },
        text_document: {
            accept: '.pdf,.doc,.docx,.xls,.xlsx',
            maxSize: '10MB',
            types: 'PDF, DOC, DOCX, XLS, XLSX',
            icon: FileText,
        },
    };
    return configs[props.type] || null;
});

const showUploader = computed(() => {
    return props.type !== 'text' && mediaConfig.value;
});

const hasMedia = computed(() => {
    return selectedFile.value || props.existingMediaUrl;
});

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        handleFile(target.files[0]);
    }
};

const handleDrop = (event: DragEvent) => {
    if (event.dataTransfer && event.dataTransfer.files.length > 0) {
        handleFile(event.dataTransfer.files[0]);
    }
};

const handleFile = (file: File) => {
    selectedFile.value = file;
    emit('update:file', file);

    // Create preview URL for images and videos
    if (props.type === 'text_image' || props.type === 'text_video') {
        previewUrl.value = URL.createObjectURL(file);
    }
};

const removeMedia = () => {
    selectedFile.value = null;
    previewUrl.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    emit('remove');
};

const getFileIcon = () => {
    if (!mediaConfig.value) return Upload;
    return mediaConfig.value.icon;
};

const getFileName = computed(() => {
    if (selectedFile.value) {
        return selectedFile.value.name;
    }
    if (props.existingMediaUrl) {
        return props.existingMediaUrl.split('/').pop() || 'Media file';
    }
    return '';
});
</script>

<template>
    <div v-if="showUploader" class="space-y-4">
        <!-- Upload Area (show if no media) -->
        <Card v-if="!hasMedia">
            <CardContent class="p-6">
                <div
                    class="cursor-pointer space-y-4 rounded-lg border-2 border-dashed p-8 text-center transition-colors hover:border-primary"
                    @click="fileInput?.click()"
                    @drop.prevent="handleDrop"
                    @dragover.prevent
                >
                    <component
                        :is="getFileIcon()"
                        class="mx-auto h-12 w-12 text-muted-foreground"
                    />
                    <div>
                        <p class="text-lg font-medium">
                            {{ t('templates.upload_media', 'Upload Media') }}
                        </p>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{
                                t(
                                    'templates.drag_drop',
                                    'Drag and drop or click to browse',
                                )
                            }}
                        </p>
                        <p
                            v-if="mediaConfig"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            {{ mediaConfig.types }} -
                            {{ t('templates.max_size', 'Max size') }}:
                            {{ mediaConfig.maxSize }}
                        </p>
                    </div>
                    <input
                        ref="fileInput"
                        :accept="mediaConfig?.accept"
                        hidden
                        type="file"
                        @change="handleFileSelect"
                    />
                </div>
            </CardContent>
        </Card>

        <!-- Media Preview -->
        <Card v-if="hasMedia">
            <CardContent class="p-6">
                <div class="space-y-4">
                    <!-- Image Preview -->
                    <div v-if="type === 'text_image'" class="relative">
                        <img
                            v-if="previewUrl"
                            :src="previewUrl"
                            alt="Preview"
                            class="w-full rounded-lg object-cover"
                        />
                        <img
                            v-else-if="existingMediaUrl"
                            :src="existingMediaUrl"
                            alt="Current media"
                            class="w-full rounded-lg object-cover"
                        />
                    </div>

                    <!-- Video Preview -->
                    <div v-if="type === 'text_video'" class="relative">
                        <video
                            v-if="previewUrl"
                            :src="previewUrl"
                            class="w-full rounded-lg"
                            controls
                        />
                        <video
                            v-else-if="existingMediaUrl"
                            :src="existingMediaUrl"
                            class="w-full rounded-lg"
                            controls
                        />
                    </div>

                    <!-- Document Preview -->
                    <div
                        v-if="type === 'text_document'"
                        class="flex items-center gap-4"
                    >
                        <div class="rounded-lg border p-4">
                            <FileText class="size-12 text-muted-foreground" />
                        </div>
                        <div class="flex-1">
                            <p class="font-medium">{{ getFileName }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    t(
                                        'templates.document_file',
                                        'Document file',
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Remove Button -->
                    <div
                        :class="isRTL() ? 'flex-row-reverse' : ''"
                        class="flex gap-2"
                    >
                        <Button
                            size="sm"
                            variant="destructive"
                            @click="removeMedia"
                        >
                            <Trash2
                                :class="isRTL() ? 'ml-2' : 'mr-2'"
                                class="size-4"
                            />
                            {{ t('common.remove', 'Remove') }}
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="fileInput?.click()"
                        >
                            <Upload
                                :class="isRTL() ? 'ml-2' : 'mr-2'"
                                class="size-4"
                            />
                            {{ t('templates.replace_media', 'Replace Media') }}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Error Message -->
        <InputError v-if="error" :message="error" />
    </div>
</template>
