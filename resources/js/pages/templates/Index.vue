<script lang="ts" setup>
import Heading from '@/components/Heading.vue';
import TemplateUsageIndicator from '@/components/TemplateUsageIndicator.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    create,
    destroy,
    edit,
    index,
    show,
} from '@/routes/dashboard/templates';
import type { Template, TemplateFilters } from '@/types/template';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Edit as EditIcon,
    Eye,
    FileText,
    Image as ImageIcon,
    MessageSquare,
    MoreVertical,
    Plus,
    Search,
    Trash2,
    Video,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface UsageStats {
    used: number;
    limit: number | string;
    remaining: number | string;
}

interface Props {
    templates: {
        data: Template[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: TemplateFilters;
    usage?: UsageStats;
}

const props = defineProps<Props>();
const page = usePage();

const { t, isRTL } = useTranslation();

const searchQuery = ref(props.filters.search || '');
const selectedType = ref<string>(props.filters.type || 'all');
const sortBy = ref(props.filters.sort_by || 'created_at');

const showDeleteDialog = ref(false);
const templateToDelete = ref<Template | null>(null);
const showSuccessAlert = ref(!!page.props.flash?.success);

const hasTemplates = computed(() => props.templates.data.length > 0);
const successMessage = computed(() => page.props.flash?.success as string);

const canCreateTemplate = computed(() => {
    if (!props.usage) return true;
    if (props.usage.limit === 'unlimited' || props.usage.limit === '∞') {
        return true;
    }
    return props.usage.used < (props.usage.limit as number);
});

const applyFilters = () => {
    router.get(
        index().url,
        {
            search: searchQuery.value || undefined,
            type: selectedType.value === 'all' ? undefined : selectedType.value,
            sort_by: sortBy.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedType.value = 'all';
    sortBy.value = 'created_at';
    applyFilters();
};

const confirmDelete = (template: Template) => {
    templateToDelete.value = template;
    showDeleteDialog.value = true;
};

const deleteTemplate = () => {
    if (!templateToDelete.value) return;

    router.delete(destroy(templateToDelete.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            templateToDelete.value = null;
            showSuccessAlert.value = true;
        },
    });
};

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

const formatDate = (dateString: string | null) => {
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
</script>

<template>
    <AppLayout>
        <Head :title="t('templates.title', 'Templates')" />

        <div
            :class="isRTL() ? 'text-right' : 'text-left'"
            class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6"
        >
            <!-- Success Alert -->
            <Alert
                v-if="showSuccessAlert && successMessage"
                class="border-[#25D366]/20 bg-[#25D366]/5"
            >
                <CheckCircle2 class="h-4 w-4 text-[#25D366]" />
                <AlertDescription class="flex items-center justify-between">
                    <span class="font-medium text-[#25D366]">{{
                        successMessage
                    }}</span>
                    <button
                        class="text-[#25D366] hover:text-[#128C7E]"
                        @click="showSuccessAlert = false"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </AlertDescription>
            </Alert>

            <!-- Header -->
            <div
                :class="isRTL() ? 'flex-row-reverse' : ''"
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    :description="
                        t(
                            'templates.description',
                            'Manage your message templates',
                        )
                    "
                    :title="t('templates.title', 'Templates')"
                />
                <Button
                    v-if="canCreateTemplate"
                    as-child
                    class="bg-[#25D366] hover:bg-[#128C7E]"
                >
                    <Link :href="create()">
                        <Plus
                            :class="isRTL() ? 'ml-2' : 'mr-2'"
                            class="h-4 w-4"
                        />
                        {{ t('templates.create_template', 'Create Template') }}
                    </Link>
                </Button>
                <Button v-else disabled variant="outline">
                    <Plus :class="isRTL() ? 'ml-2' : 'mr-2'" class="h-4 w-4" />
                    {{ t('templates.create_template', 'Create Template') }}
                </Button>
            </div>

            <!-- Usage Indicator -->
            <TemplateUsageIndicator v-if="usage" :usage="usage" />

            <!-- Filters -->
            <div class="flex flex-col gap-4 lg:flex-row">
                <!-- Search -->
                <div class="relative flex-1">
                    <Search
                        :class="isRTL() ? 'right-3' : 'left-3'"
                        class="absolute top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="searchQuery"
                        :class="isRTL() ? 'pr-10' : 'pl-10'"
                        :placeholder="
                            t('templates.search', 'Search templates...')
                        "
                        class="focus-visible:ring-[#25D366]"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <!-- Filter Controls -->
                <div class="flex flex-wrap gap-2">
                    <!-- Type Filter -->
                    <Select
                        v-model="selectedType"
                        @update:model-value="applyFilters"
                    >
                        <SelectTrigger class="w-[180px]">
                            <SelectValue
                                :placeholder="
                                    t('templates.all_types', 'All Types')
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {{ t('templates.all_types', 'All Types') }}
                            </SelectItem>
                            <SelectItem value="text">
                                {{ t('templates.text_only', 'Text Only') }}
                            </SelectItem>
                            <SelectItem value="text_image">
                                {{ t('templates.text_image', 'Text + Image') }}
                            </SelectItem>
                            <SelectItem value="text_video">
                                {{ t('templates.text_video', 'Text + Video') }}
                            </SelectItem>
                            <SelectItem value="text_document">
                                {{
                                    t(
                                        'templates.text_document',
                                        'Text + Document',
                                    )
                                }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Sort By -->
                    <Select v-model="sortBy" @update:model-value="applyFilters">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="created_at">
                                {{ t('templates.sort.newest', 'Newest First') }}
                            </SelectItem>
                            <SelectItem value="name">
                                {{ t('templates.sort.name', 'Name') }}
                            </SelectItem>
                            <SelectItem value="usage">
                                {{ t('templates.sort.most_used', 'Most Used') }}
                            </SelectItem>
                            <SelectItem value="last_used">
                                {{
                                    t(
                                        'templates.sort.recently_used',
                                        'Recently Used',
                                    )
                                }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Button size="sm" variant="ghost" @click="clearFilters">
                        Clear
                    </Button>
                </div>
            </div>

            <!-- Templates Table -->
            <div class="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                {{
                                    t(
                                        'templates.template_name',
                                        'Template Name',
                                    )
                                }}
                            </TableHead>
                            <TableHead
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                {{ t('templates.template_type', 'Type') }}
                            </TableHead>
                            <TableHead
                                :class="isRTL() ? 'text-right' : 'text-left'"
                                class="hidden md:table-cell"
                            >
                                {{ t('templates.template_content', 'Content') }}
                            </TableHead>
                            <TableHead
                                :class="isRTL() ? 'text-right' : 'text-left'"
                                class="hidden lg:table-cell"
                            >
                                {{ t('templates.usage_count', 'Usage') }}
                            </TableHead>
                            <TableHead
                                :class="isRTL() ? 'text-right' : 'text-left'"
                                class="hidden lg:table-cell"
                            >
                                {{ t('templates.last_used', 'Last Used') }}
                            </TableHead>
                            <TableHead
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                {{ t('common.actions', 'Actions') }}
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <!-- Empty State -->
                        <TableRow v-if="!hasTemplates">
                            <TableCell class="py-12 text-center" colspan="6">
                                <div class="flex flex-col items-center gap-2">
                                    <div
                                        class="mb-2 flex items-center justify-center rounded-full bg-[#25D366]/10 p-4"
                                    >
                                        <MessageSquare
                                            class="h-12 w-12 text-[#25D366]"
                                        />
                                    </div>
                                    <p class="font-medium">
                                        {{
                                            t(
                                                'templates.no_templates',
                                                'No templates found',
                                            )
                                        }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{
                                            t(
                                                'templates.create_first_template',
                                                'Create your first template to get started',
                                            )
                                        }}
                                    </p>
                                </div>
                            </TableCell>
                        </TableRow>

                        <!-- Template Rows -->
                        <TableRow
                            v-for="template in templates.data"
                            :key="template.id"
                            class="hover:bg-muted/50"
                        >
                            <!-- Name -->
                            <TableCell
                                :class="isRTL() ? 'text-right' : 'text-left'"
                                class="font-medium"
                            >
                                <div class="flex items-center gap-2">
                                    <component
                                        :is="getTypeIcon(template.type)"
                                        class="h-4 w-4 text-[#25D366]"
                                    />
                                    {{ template.name }}
                                </div>
                            </TableCell>

                            <!-- Type -->
                            <TableCell
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                <Badge
                                    :class="getTypeBadgeColor(template.type)"
                                    class="text-xs text-white"
                                >
                                    {{ getTypeLabel(template.type) }}
                                </Badge>
                            </TableCell>

                            <!-- Content Preview -->
                            <TableCell
                                :class="isRTL() ? 'text-right' : 'text-left'"
                                class="hidden md:table-cell"
                            >
                                <p
                                    class="line-clamp-2 max-w-md text-sm text-muted-foreground"
                                >
                                    {{ template.content }}
                                </p>
                            </TableCell>

                            <!-- Usage Count -->
                            <TableCell
                                :class="isRTL() ? 'text-right' : 'text-left'"
                                class="hidden lg:table-cell"
                            >
                                <span class="font-semibold text-[#25D366]">
                                    {{ template.usage_count }}
                                </span>
                            </TableCell>

                            <!-- Last Used -->
                            <TableCell
                                :class="isRTL() ? 'text-right' : 'text-left'"
                                class="hidden lg:table-cell"
                            >
                                <span class="text-sm text-muted-foreground">
                                    {{ formatDate(template.last_used_at) }}
                                </span>
                            </TableCell>

                            <!-- Actions -->
                            <TableCell
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button size="sm" variant="ghost">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            @click="
                                                router.visit(show(template.id))
                                            "
                                        >
                                            <Eye
                                                :class="
                                                    isRTL() ? 'ml-2' : 'mr-2'
                                                "
                                                class="h-4 w-4"
                                            />
                                            {{ t('common.view', 'View') }}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="
                                                router.visit(edit(template.id))
                                            "
                                        >
                                            <EditIcon
                                                :class="
                                                    isRTL() ? 'ml-2' : 'mr-2'
                                                "
                                                class="h-4 w-4"
                                            />
                                            {{ t('common.edit', 'Edit') }}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="text-destructive"
                                            @click="confirmDelete(template)"
                                        >
                                            <Trash2
                                                :class="
                                                    isRTL() ? 'ml-2' : 'mr-2'
                                                "
                                                class="h-4 w-4"
                                            />
                                            {{ t('common.delete', 'Delete') }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div
                v-if="hasTemplates && templates.total > templates.per_page"
                class="flex items-center justify-between"
            >
                <div class="text-sm text-muted-foreground">
                    {{ t('common.showing', 'Showing') }}
                    <span class="font-medium">{{
                        (templates.current_page - 1) * templates.per_page + 1
                    }}</span>
                    {{ t('common.to', 'to') }}
                    <span class="font-medium">{{
                        Math.min(
                            templates.current_page * templates.per_page,
                            templates.total,
                        )
                    }}</span>
                    {{ t('common.of', 'of') }}
                    <span class="font-medium">{{ templates.total }}</span>
                    {{ t('common.results', 'results') }}
                </div>

                <nav class="flex gap-1">
                    <Link
                        v-for="link in templates.links"
                        :key="link.label"
                        :class="[
                            'rounded-md px-3 py-2 text-sm transition-colors',
                            link.active
                                ? 'bg-[#25D366] text-white'
                                : 'bg-background text-foreground hover:bg-muted',
                            !link.url ? 'cursor-not-allowed opacity-50' : '',
                        ]"
                        :href="link.url || '#'"
                        preserve-scroll
                        v-html="link.label"
                    />
                </nav>
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
