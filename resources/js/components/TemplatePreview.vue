<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslation } from '@/composables/useTranslation';
import { FileText } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    content: string;
    type?: string;
    caption?: string | null;
    mediaUrl?: string | null;
    useSampleData?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    caption: null,
    mediaUrl: null,
    useSampleData: true,
});

const { t, isRTL } = useTranslation();

const sampleData = {
    first_name: 'Ahmed',
    last_name: 'Mohamed',
    phone: '+201234567890',
    custom_field_1: 'Sample data',
    custom_field_2: 'Sample data',
    custom_field_3: 'Sample data',
};

const mergedContent = computed(() => {
    if (!props.useSampleData) {
        return props.content;
    }

    let result = props.content;
    const placeholders = {
        '{{first_name}}': sampleData.first_name,
        '{{last_name}}': sampleData.last_name,
        '{{phone}}': sampleData.phone,
        '{{custom_field_1}}': sampleData.custom_field_1,
        '{{custom_field_2}}': sampleData.custom_field_2,
        '{{custom_field_3}}': sampleData.custom_field_3,
    };

    Object.entries(placeholders).forEach(([placeholder, value]) => {
        result = result.replace(new RegExp(placeholder, 'g'), value);
    });

    return result;
});

const mergedCaption = computed(() => {
    if (!props.caption || !props.useSampleData) {
        return props.caption;
    }

    let result = props.caption;
    const placeholders = {
        '{{first_name}}': sampleData.first_name,
        '{{last_name}}': sampleData.last_name,
        '{{phone}}': sampleData.phone,
        '{{custom_field_1}}': sampleData.custom_field_1,
        '{{custom_field_2}}': sampleData.custom_field_2,
        '{{custom_field_3}}': sampleData.custom_field_3,
    };

    Object.entries(placeholders).forEach(([placeholder, value]) => {
        result = result.replace(new RegExp(placeholder, 'g'), value);
    });

    return result;
});

const typeLabel = computed(() => {
    const labels: Record<string, string> = {
        text: t('templates.text_only', 'Text Only'),
        text_image: t('templates.text_image', 'Text + Image'),
        text_video: t('templates.text_video', 'Text + Video'),
        text_document: t('templates.text_document', 'Text + Document'),
    };
    return labels[props.type] || props.type;
});

const typeBadgeColor = computed(() => {
    const colors: Record<string, string> = {
        text: 'bg-blue-500',
        text_image: 'bg-green-500',
        text_video: 'bg-purple-500',
        text_document: 'bg-orange-500',
    };
    return colors[props.type] || 'bg-gray-500';
});
</script>

<template>
    <Card>
        <CardHeader>
            <div
                :class="isRTL() ? 'flex-row-reverse' : ''"
                class="flex items-center justify-between"
            >
                <CardTitle :class="isRTL() ? 'text-right' : 'text-left'">
                    {{ t('templates.template_preview', 'Template Preview') }}
                </CardTitle>
                <Badge :class="typeBadgeColor" class="text-white">
                    {{ typeLabel }}
                </Badge>
            </div>
            <CardDescription
                v-if="useSampleData"
                :class="isRTL() ? 'text-right' : 'text-left'"
            >
                {{ t('templates.sample_preview', 'Preview with Sample Data') }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <!-- Media Preview -->
                <div
                    v-if="mediaUrl && type === 'text_image'"
                    class="overflow-hidden rounded-lg border"
                >
                    <img
                        :src="mediaUrl"
                        alt="Template media"
                        class="w-full object-cover"
                    />
                </div>

                <div
                    v-if="mediaUrl && type === 'text_video'"
                    class="overflow-hidden rounded-lg border"
                >
                    <video :src="mediaUrl" class="w-full" controls />
                </div>

                <div
                    v-if="mediaUrl && type === 'text_document'"
                    class="flex items-center gap-4 rounded-lg border p-4"
                >
                    <FileText class="size-8 text-muted-foreground" />
                    <div>
                        <p class="font-medium">
                            {{ t('templates.document_file', 'Document file') }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ mediaUrl.split('/').pop() }}
                        </p>
                    </div>
                </div>

                <!-- Caption (for media types) -->
                <div
                    v-if="mergedCaption && type !== 'text'"
                    :class="isRTL() ? 'text-right' : 'text-left'"
                    class="rounded-lg border bg-muted/50 p-4"
                >
                    <p class="whitespace-pre-wrap">{{ mergedCaption }}</p>
                </div>

                <!-- Content -->
                <div
                    :class="isRTL() ? 'text-right' : 'text-left'"
                    class="rounded-lg border bg-muted/50 p-4"
                >
                    <p class="whitespace-pre-wrap">{{ mergedContent }}</p>
                </div>

                <!-- Sample Data Info -->
                <div
                    v-if="useSampleData"
                    :class="isRTL() ? 'text-right' : 'text-left'"
                    class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800 dark:bg-blue-950 dark:text-blue-200"
                >
                    <p class="font-medium">
                        {{
                            t('templates.sample_data_used', 'Sample data used:')
                        }}
                    </p>
                    <ul
                        :class="isRTL() ? 'pr-4' : 'pl-4'"
                        class="mt-1 list-disc space-y-1"
                    >
                        <li>
                            {{ t('contacts.fields.first_name', 'First Name') }}:
                            {{ sampleData.first_name }}
                        </li>
                        <li>
                            {{ t('contacts.fields.last_name', 'Last Name') }}:
                            {{ sampleData.last_name }}
                        </li>
                        <li>
                            {{ t('contacts.fields.phone_number', 'Phone') }}:
                            {{ sampleData.phone }}
                        </li>
                    </ul>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
