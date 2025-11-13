<script lang="ts" setup>
import Heading from '@/components/Heading.vue';
import TemplatePreview from '@/components/TemplatePreview.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy, edit } from '@/routes/dashboard/templates';
import type { Placeholder, Template } from '@/types/template';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    Edit as EditIcon,
    FileText,
    Hash,
    Image as ImageIcon,
    MessageSquare,
    Trash2,
    Video,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    template: Template;
    placeholders: Placeholder[];
    samplePreview: string;
}

const props = defineProps<Props>();

const { t, isRTL } = useTranslation();

const showDeleteDialog = ref(false);

const getTypeIcon = (type: string) => {
    const icons: Record<string, any> = {
        text: MessageSquare,
        text_image: ImageIcon,
        text_video: Video,
        text_document: FileText,
    };
    return icons[type] || MessageSquare;
};

const getTypeBadgeColor = (type: string) => {
    const colors: Record<string, string> = {
        text: 'bg-[#25D366] hover:bg-[#128C7E]',
        text_image: 'bg-blue-500 hover:bg-blue-600',
        text_video: 'bg-purple-500 hover:bg-purple-600',
        text_document: 'bg-orange-500 hover:bg-orange-600',
    };
    return colors[type] || 'bg-gray-500';
};

const getTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        text: t('templates.text_only', 'Text Only'),
        text_image: t('templates.text_image', 'Text + Image'),
        text_video: t('templates.text_video', 'Text + Video'),
        text_document: t('templates.text_document', 'Text + Document'),
    };
    return labels[type] || type;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};

const formatLastUsed = (dateString: string | null) => {
    if (!dateString) return t('templates.never_used', 'Never Used');

    const date = new Date(dateString);
    const now = new Date();
    const diffInDays = Math.floor(
        (now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24),
    );

    if (diffInDays === 0) return t('common.today', 'Today');
    if (diffInDays === 1) return t('common.yesterday', 'Yesterday');
    if (diffInDays < 7)
        return `${diffInDays} ${t('common.days_ago', 'days ago')}`;

    return date.toLocaleDateString();
};

const usedPlaceholders = computed(() => {
    const content = props.template.content + (props.template.caption || '');
    return props.placeholders.filter((p) => content.includes(p.value));
});

const confirmDelete = () => {
    showDeleteDialog.value = true;
};

const deleteTemplate = () => {
    router.delete(destroy(props.template.id));
};
</script>

<template>
    <AppLayout>
        <Head :title="template.name" />

        <div
            :class="isRTL() ? 'text-right' : 'text-left'"
            class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6"
        >
            <!-- Header -->
            <div
                :class="isRTL() ? 'flex-row-reverse' : ''"
                class="flex items-center justify-between"
            >
                <Heading :title="template.name" />
                <div
                    :class="isRTL() ? 'flex-row-reverse' : ''"
                    class="flex gap-2"
                >
                    <Button as-child class="bg-[#25D366] hover:bg-[#128C7E]">
                        <Link :href="edit(template.id)">
                            <EditIcon
                                :class="isRTL() ? 'ml-2' : 'mr-2'"
                                class="h-4 w-4"
                            />
                            {{ t('common.edit', 'Edit') }}
                        </Link>
                    </Button>
                    <Button variant="destructive" @click="confirmDelete">
                        <Trash2
                            :class="isRTL() ? 'ml-2' : 'mr-2'"
                            class="h-4 w-4"
                        />
                        {{ t('common.delete', 'Delete') }}
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content (2 columns) -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Template Details -->
                    <Card>
                        <CardHeader>
                            <div
                                :class="isRTL() ? 'flex-row-reverse' : ''"
                                class="flex items-center justify-between"
                            >
                                <CardTitle
                                    :class="
                                        isRTL() ? 'text-right' : 'text-left'
                                    "
                                >
                                    {{
                                        t(
                                            'templates.template_details',
                                            'Template Details',
                                        )
                                    }}
                                </CardTitle>
                                <Badge
                                    :class="getTypeBadgeColor(template.type)"
                                    class="text-white"
                                >
                                    <component
                                        :is="getTypeIcon(template.type)"
                                        :class="isRTL() ? 'ml-1' : 'mr-1'"
                                        class="h-3 w-3"
                                    />
                                    {{ getTypeLabel(template.type) }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Content -->
                            <div>
                                <h4
                                    :class="
                                        isRTL() ? 'text-right' : 'text-left'
                                    "
                                    class="mb-2 font-medium text-[#25D366]"
                                >
                                    {{
                                        t(
                                            'templates.template_content',
                                            'Message Content',
                                        )
                                    }}
                                </h4>
                                <div
                                    :class="
                                        isRTL() ? 'text-right' : 'text-left'
                                    "
                                    class="rounded-lg border bg-muted/50 p-4 whitespace-pre-wrap"
                                >
                                    {{ template.content }}
                                </div>
                            </div>

                            <!-- Caption (if present) -->
                            <div v-if="template.caption">
                                <h4
                                    :class="
                                        isRTL() ? 'text-right' : 'text-left'
                                    "
                                    class="mb-2 font-medium text-[#25D366]"
                                >
                                    {{
                                        t(
                                            'templates.media_caption',
                                            'Media Caption',
                                        )
                                    }}
                                </h4>
                                <div
                                    :class="
                                        isRTL() ? 'text-right' : 'text-left'
                                    "
                                    class="rounded-lg border bg-muted/50 p-4 whitespace-pre-wrap"
                                >
                                    {{ template.caption }}
                                </div>
                            </div>

                            <!-- Media (if present) -->
                            <div v-if="template.media_url">
                                <h4
                                    :class="
                                        isRTL() ? 'text-right' : 'text-left'
                                    "
                                    class="mb-2 font-medium text-[#25D366]"
                                >
                                    {{
                                        t('templates.media_file', 'Media File')
                                    }}
                                </h4>
                                <div class="overflow-hidden rounded-lg border">
                                    <img
                                        v-if="template.type === 'text_image'"
                                        :src="template.media_url"
                                        alt="Template media"
                                        class="w-full object-cover"
                                    />
                                    <video
                                        v-else-if="
                                            template.type === 'text_video'
                                        "
                                        :src="template.media_url"
                                        class="w-full"
                                        controls
                                    />
                                    <div
                                        v-else-if="
                                            template.type === 'text_document'
                                        "
                                        class="flex items-center gap-4 bg-orange-50 p-4 dark:bg-orange-950"
                                    >
                                        <FileText
                                            class="h-12 w-12 text-orange-500"
                                        />
                                        <div class="flex-1">
                                            <p class="font-medium">
                                                {{
                                                    t(
                                                        'templates.document_file',
                                                        'Document file',
                                                    )
                                                }}
                                            </p>
                                            <p
                                                class="text-sm text-muted-foreground"
                                            >
                                                {{
                                                    template.media_url
                                                        .split('/')
                                                        .pop()
                                                }}
                                            </p>
                                            <a
                                                :href="template.media_url"
                                                class="text-sm font-medium text-[#25D366] hover:underline"
                                                download
                                                target="_blank"
                                            >
                                                {{
                                                    t(
                                                        'common.download',
                                                        'Download',
                                                    )
                                                }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Used Placeholders -->
                            <div v-if="usedPlaceholders.length > 0">
                                <Separator class="my-4" />
                                <h4
                                    :class="
                                        isRTL() ? 'text-right' : 'text-left'
                                    "
                                    class="mb-2 font-medium text-[#25D366]"
                                >
                                    {{
                                        t(
                                            'templates.used_placeholders',
                                            'Used Placeholders',
                                        )
                                    }}
                                </h4>
                                <div class="flex flex-wrap gap-2">
                                    <Badge
                                        v-for="placeholder in usedPlaceholders"
                                        :key="placeholder.value"
                                        class="border-[#25D366]/20 bg-[#25D366]/10 text-[#25D366]"
                                    >
                                        {{ placeholder.value }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Preview -->
                    <TemplatePreview
                        :caption="template.caption"
                        :content="template.content"
                        :media-url="template.media_url"
                        :type="template.type"
                    />
                </div>

                <!-- Sidebar (1 column) -->
                <div class="space-y-6">
                    <!-- Statistics -->
                    <Card class="border-[#25D366]/20">
                        <CardHeader>
                            <CardTitle
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                {{ t('templates.statistics', 'Statistics') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Usage Count -->
                            <div
                                :class="
                                    isRTL()
                                        ? 'flex-row-reverse text-right'
                                        : 'text-left'
                                "
                                class="flex items-start gap-3"
                            >
                                <div class="rounded-lg bg-[#25D366]/10 p-2">
                                    <Hash class="h-5 w-5 text-[#25D366]" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-muted-foreground">
                                        {{
                                            t(
                                                'templates.usage_count',
                                                'Usage Count',
                                            )
                                        }}
                                    </p>
                                    <p
                                        class="text-xl font-semibold text-[#25D366]"
                                    >
                                        {{ template.usage_count }}
                                    </p>
                                </div>
                            </div>

                            <Separator />

                            <!-- Last Used -->
                            <div
                                :class="
                                    isRTL()
                                        ? 'flex-row-reverse text-right'
                                        : 'text-left'
                                "
                                class="flex items-start gap-3"
                            >
                                <div class="rounded-lg bg-[#25D366]/10 p-2">
                                    <Calendar class="h-5 w-5 text-[#25D366]" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-muted-foreground">
                                        {{
                                            t(
                                                'templates.last_used',
                                                'Last Used',
                                            )
                                        }}
                                    </p>
                                    <p class="font-medium">
                                        {{
                                            formatLastUsed(
                                                template.last_used_at,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Metadata -->
                    <Card>
                        <CardHeader>
                            <CardTitle
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                {{ t('templates.metadata', 'Metadata') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div :class="isRTL() ? 'text-right' : 'text-left'">
                                <p class="text-muted-foreground">
                                    {{ t('common.created_at', 'Created At') }}
                                </p>
                                <p class="font-medium">
                                    {{ formatDate(template.created_at) }}
                                </p>
                            </div>
                            <Separator />
                            <div :class="isRTL() ? 'text-right' : 'text-left'">
                                <p class="text-muted-foreground">
                                    {{ t('common.updated_at', 'Updated At') }}
                                </p>
                                <p class="font-medium">
                                    {{ formatDate(template.updated_at) }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Available Placeholders Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                {{
                                    t(
                                        'templates.available_placeholders',
                                        'Available Placeholders',
                                    )
                                }}
                            </CardTitle>
                            <CardDescription
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                {{
                                    t(
                                        'templates.placeholders_info',
                                        'You can use these placeholders in your templates',
                                    )
                                }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div
                                    v-for="placeholder in placeholders"
                                    :key="placeholder.value"
                                    :class="
                                        isRTL() ? 'text-right' : 'text-left'
                                    "
                                >
                                    <Badge
                                        class="border-[#25D366]/20 bg-[#25D366]/10 text-[#25D366]"
                                    >
                                        {{ placeholder.value }}
                                    </Badge>
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{ placeholder.description }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle :class="isRTL() ? 'text-right' : 'text-left'">
                        {{ t('templates.delete_template', 'Delete Template') }}
                    </DialogTitle>
                    <DialogDescription
                        :class="isRTL() ? 'text-right' : 'text-left'"
                    >
                        {{
                            t(
                                'templates.delete_template_confirm',
                                'Are you sure you want to delete this template?',
                            )
                        }}
                        {{
                            t(
                                'common.cannot_undo',
                                'This action cannot be undone.',
                            )
                        }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        {{ t('common.cancel', 'Cancel') }}
                    </Button>
                    <Button variant="destructive" @click="deleteTemplate">
                        {{ t('common.delete', 'Delete') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
