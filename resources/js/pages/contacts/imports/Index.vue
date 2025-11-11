<script lang="ts" setup>
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    destroy as deleteImport,
    index as contactsIndex,
    process as processImport,
    template,
    upload,
} from '@/routes/dashboard/contacts/imports';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    Check,
    Download,
    Eye,
    FileText,
    MoreVertical,
    Trash2,
    Upload,
} from 'lucide-vue-next';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { Country } from '@/types';
import axios from 'axios';

interface Tag {
    id: number;
    name: string;
    color: string;
}

interface ImportRecord {
    id: number;
    filename: string;
    status: string;
    total_rows: number;
    valid_contacts: number;
    invalid_contacts: number;
    duplicate_contacts: number;
    created_at: string;
}

interface ImportProgress {
    total: number;
    processed: number;
    valid: number;
    invalid: number;
    duplicates: number;
    current_row: number;
    status: string;
}

interface Props {
    imports: {
        data: ImportRecord[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    tags: Tag[];
    countries: Country[];
}

const defaultCountryId = ref<string>('none');
const props = defineProps<Props>();
const { t, isRTL } = useTranslation();
const page = usePage();

// Multi-step wizard state
const currentStep = ref(1);
const uploadedFile = ref<File | null>(null);
const parseResult = ref<any>(null);
const columnMapping = ref<Record<string, string>>({
    first_name: '',
    last_name: '',
    phone_number: '',
    email: '',
});
const validateWhatsApp = ref(false);
const selectedTagId = ref<string>('none');
const isProcessing = ref(false);
const importSummary = ref<any>(null);

// Progress tracking
const importProgress = ref<ImportProgress | null>(null);
let progressInterval: number | null = null;

// File upload
const fileInput = ref<HTMLInputElement | null>(null);

const contactFields = [
    {
        value: 'first_name',
        label: t('contacts.fields.first_name'),
        required: true,
    },
    {
        value: 'last_name',
        label: t('contacts.fields.last_name'),
        required: false,
    },
    {
        value: 'phone_number',
        label: t('contacts.fields.phone_number'),
        required: true,
    },
    { value: 'email', label: t('contacts.fields.email'), required: false },
];

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        uploadFile(target.files[0]);
    }
};

const handleDrop = (event: DragEvent) => {
    if (event.dataTransfer && event.dataTransfer.files.length > 0) {
        uploadFile(event.dataTransfer.files[0]);
    }
};

const uploadFile = (file: File) => {
    uploadedFile.value = file;
    const formData = new FormData();
    formData.append('file', file);

    router.post(upload(), formData, {
        onError: (errors) => {
            console.error('Upload failed:', errors);
            uploadedFile.value = null;
        },
    });
};

const startImport = () => {
    if (!parseResult.value || !parseResult.value.import_id) return;

    isProcessing.value = true;
    currentStep.value = 3; // Show processing step

    // Start polling for progress
    startProgressPolling(parseResult.value.import_id);

    router.post(
        processImport(parseResult.value.import_id),
        {
            column_mapping: columnMapping.value,
            validate_whatsapp: validateWhatsApp.value,
            tag_id: selectedTagId.value !== 'none' ? selectedTagId.value : null,
            default_country_id: defaultCountryId.value !== 'none' ? defaultCountryId.value : null,
        },
        {
            onFinish: () => {
                isProcessing.value = false;
                stopProgressPolling();
            },
            onError: () => {
                currentStep.value = 2; // Go back to mapping on error
                stopProgressPolling();
            },
        },
    );
};

// Progress polling functions
const startProgressPolling = (importId: number) => {
    // Poll every 500ms for real-time updates
    progressInterval = window.setInterval(async () => {
        try {
            const response = await axios.get(`/dashboard/contacts/imports/${importId}/progress`);
            importProgress.value = response.data;

            // Stop polling if completed
            if (response.data.status === 'completed') {
                stopProgressPolling();
            }
        } catch (error) {
            console.error('Failed to fetch progress:', error);
        }
    }, 500);
};

const stopProgressPolling = () => {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }
};

const resetImport = () => {
    currentStep.value = 1;
    uploadedFile.value = null;
    parseResult.value = null;
    columnMapping.value = {
        first_name: '',
        last_name: '',
        phone_number: '',
        email: '',
    };
    validateWhatsApp.value = false;
    selectedTagId.value = 'none';
    defaultCountryId.value = 'none';
    importSummary.value = null;
    importProgress.value = null;
};

const deleteImportRecord = (importId: number) => {
    if (
        confirm(
            t(
                'imports.messages.confirm_delete',
                'Are you sure you want to delete this import?',
            ),
        )
    ) {
        router.delete(deleteImport(importId));
    }
};

const viewImportDetails = (importRecord: ImportRecord) => {
    importSummary.value = {
        import_id: importRecord.id,
        total: importRecord.total_rows,
        valid: importRecord.valid_contacts,
        invalid: importRecord.invalid_contacts,
        duplicates: importRecord.duplicate_contacts,
        phone_normalized: 0,
        errors: [],
    };
    currentStep.value = 4;
};

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        pending: 'secondary',
        processing: 'default',
        completed: 'default',
        failed: 'destructive',
    };
    return colors[status] || 'secondary';
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const progressPercentage = computed(() => {
    if (currentStep.value === 3 && importProgress.value) {
        return Math.round((importProgress.value.processed / importProgress.value.total) * 100);
    }
    return (currentStep.value / 4) * 100;
});

// Watch for import_preview from flash data (after upload)
watch(
    () => page.props.import_preview,
    (newPreview) => {
        if (newPreview) {
            parseResult.value = newPreview as any;
            currentStep.value = 2;
        }
    },
    { immediate: true }
);

// Watch for import_summary from flash data (after processing)
watch(
    () => page.props.import_summary,
    (newSummary) => {
        if (newSummary) {
            importSummary.value = newSummary as any;
            currentStep.value = 4;
        }
    },
    { immediate: true }
);

// Check on mount for any flash data
onMounted(() => {
    if (page.props.import_preview) {
        parseResult.value = page.props.import_preview as any;
        currentStep.value = 2;
    }
    if (page.props.import_summary) {
        importSummary.value = page.props.import_summary as any;
        currentStep.value = 4;
    }
});

// Cleanup on unmount
onUnmounted(() => {
    stopProgressPolling();
});

</script>

<template>
    <AppLayout>
        <Head :title="t('imports.title')" />

        <!-- Dimmed overlay when processing -->
        <div
            v-if="isProcessing"
            class="fixed inset-0 z-40 bg-black/50"
            aria-hidden="true"
        ></div>

        <div class="mx-auto max-w-6xl space-y-6" :class="{ 'relative z-50': isProcessing }">
            <Heading
                :description="
                    t(
                        'imports.description',
                        'Import contacts from CSV or Excel files',
                    )
                "
                :title="t('imports.title')"
            />

            <!-- Step Indicator -->
            <div class="mb-8 flex items-center justify-center gap-4">
                <div
                    v-for="step in 4"
                    :key="step"
                    :class="[
                        'flex items-center gap-2',
                        currentStep >= step
                            ? 'text-primary'
                            : 'text-muted-foreground',
                    ]"
                >
                    <div
                        :class="[
                            'flex h-10 w-10 items-center justify-center rounded-full font-semibold',
                            currentStep >= step
                                ? 'bg-primary text-white'
                                : 'bg-muted',
                        ]"
                    >
                        <Check v-if="currentStep > step" class="h-5 w-5" />
                        <span v-else>{{ step }}</span>
                    </div>
                    <span class="hidden font-medium sm:inline">
                        {{
                            step === 1
                                ? t('imports.upload_file')
                                : step === 2
                                    ? t('imports.map_columns')
                                    : step === 3
                                        ? t('imports.processing')
                                        : t('imports.summary')
                        }}
                    </span>
                </div>
            </div>

            <Progress :model-value="progressPercentage" class="h-2" />

            <!-- Step 1: Upload -->
            <Card v-show="currentStep === 1">
                <CardContent class="p-12">
                    <div
                        class="cursor-pointer space-y-4 rounded-lg border-2 border-dashed p-12 text-center transition-colors hover:border-primary"
                        @click="fileInput?.click()"
                        @drop.prevent="handleDrop"
                        @dragover.prevent
                    >
                        <Upload
                            class="mx-auto h-12 w-12 text-muted-foreground"
                        />
                        <div>
                            <p class="text-lg font-medium">
                                {{ t('imports.upload.drag_drop') }}
                            </p>
                            <p class="mt-2 text-sm text-muted-foreground">
                                {{ t('imports.upload.req_format') }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ t('imports.upload.req_size') }}
                            </p>
                        </div>
                        <input
                            ref="fileInput"
                            accept=".csv,.xlsx,.xls"
                            hidden
                            type="file"
                            @change="handleFileSelect"
                        />
                    </div>

                    <!-- Download Template -->
                    <div class="mt-6 text-center">
                        <a :href="template()" download>
                            <Button variant="outline">
                                <Download
                                    :class="isRTL() ? 'ml-2' : 'mr-2'"
                                    class="h-4 w-4"
                                />
                                {{ t('imports.upload.download_template') }}
                            </Button>
                        </a>
                    </div>
                </CardContent>
            </Card>

            <!-- Step 2: Column Mapping -->
            <Card v-show="currentStep === 2" class="space-y-6">
                <CardHeader>
                    <CardTitle>{{ t('imports.mapping.title') }}</CardTitle>
                    <p class="text-sm text-muted-foreground">
                        {{ t('imports.mapping.description') }}
                    </p>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Preview -->
                    <div v-if="parseResult">
                        <h3 class="mb-2 font-semibold">
                            {{ t('imports.mapping.preview') }}
                        </h3>
                        <div class="overflow-x-auto rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead
                                            v-for="(header, index) in parseResult.headers"
                                            :key="index"
                                        >
                                            {{ header }}
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow
                                        v-for="(row, rowIndex) in parseResult.preview.slice(0, 3)"
                                        :key="rowIndex"
                                    >
                                        <TableCell
                                            v-for="(cell, cellIndex) in row"
                                            :key="cellIndex"
                                        >
                                            {{ cell || '-' }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{
                                t(
                                    'imports.showing_rows',
                                    'Showing 3 of {total} rows',
                                    { total: parseResult.total_rows },
                                )
                            }}
                        </p>
                    </div>

                    <!-- Column Mapping -->
                    <div class="space-y-4">
                        <h3 class="font-semibold">
                            {{ t('imports.mapping.title') }}
                        </h3>
                        <div
                            v-for="field in contactFields"
                            :key="field.value"
                            class="grid grid-cols-2 items-center gap-4"
                        >
                            <Label>
                                {{ field.label }}
                                <Badge
                                    v-if="field.required"
                                    class="ml-2"
                                    variant="destructive"
                                >
                                    {{ t('imports.mapping.required') }}
                                </Badge>
                                <Badge v-else class="ml-2" variant="secondary">
                                    {{ t('imports.mapping.optional') }}
                                </Badge>
                            </Label>
                            <Select v-model="columnMapping[field.value]">
                                <SelectTrigger>
                                    <SelectValue
                                        :placeholder="
                                t(
                                    'imports.mapping.select_column',
                                    'Select column',
                                )
                            "
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="(header, index) in parseResult?.headers"
                                        :key="index"
                                        :value="header"
                                    >
                                        {{ header }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Phone Normalization Section -->
                    <div class="space-y-4 border-t pt-6 mt-6">
                        <div class="space-y-2">
                            <Label for="default_country">
                                {{ t('imports.default_country', 'Default Country for Phone Normalization') }}
                            </Label>
                            <Select v-model="defaultCountryId">
                                <SelectTrigger>
                                    <SelectValue
                                        :placeholder="t('imports.select_country', 'Select default country')"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        {{ t('imports.no_default_country', 'No default country') }}
                                    </SelectItem>
                                    <SelectItem
                                        v-for="country in countries"
                                        :key="country.id"
                                        :value="country.id.toString()"
                                    >
                                        {{ isRTL() ? country.name_ar : country.name_en }}
                                        (+{{ country.phone_code }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    t(
                                        'imports.default_country_hint',
                                        'Used to normalize phone numbers that don\'t include a country code'
                                    )
                                }}
                            </p>
                        </div>

                        <Alert>
                            <AlertCircle class="h-4 w-4" />
                            <AlertDescription>
                                <p class="font-semibold mb-2">
                                    {{ t('imports.phone_normalization_info', 'Phone Number Normalization') }}
                                </p>
                                <p class="text-sm text-muted-foreground mb-2">
                                    {{ t('imports.auto_country_detection', 'Country will be automatically detected from phone numbers') }}
                                </p>
                            </AlertDescription>
                        </Alert>
                    </div>

                    <!-- Import Options -->
                    <div class="space-y-4 border-t pt-6">
                        <h3 class="font-semibold">
                            {{ t('imports.options', 'Import Options') }}
                        </h3>

                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="validate_whatsapp"
                                v-model:checked="validateWhatsApp"
                            />
                            <Label
                                class="cursor-pointer font-normal"
                                for="validate_whatsapp"
                            >
                                {{ t('imports.mapping.validate_whatsapp') }}
                            </Label>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('imports.whatsapp_validation_note', 'Only validates if you have a connected WhatsApp session') }}
                        </p>

                        <div>
                            <Label for="tag">{{
                                    t('imports.mapping.assign_tag')
                                }}</Label>
                            <Select v-model="selectedTagId">
                                <SelectTrigger>
                                    <SelectValue
                                        :placeholder="t('imports.mapping.select_tag')"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">{{
                                            t('common.none', 'None')
                                        }}</SelectItem>
                                    <SelectItem
                                        v-for="tag in tags"
                                        :key="tag.id"
                                        :value="tag.id.toString()"
                                    >
                                        <Badge
                                            :style="{ backgroundColor: tag.color }"
                                            class="text-white"
                                        >
                                            {{ tag.name }}
                                        </Badge>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-between border-t pt-6">
                        <Button variant="outline" @click="resetImport">
                            {{ t('common.cancel', 'Cancel') }}
                        </Button>
                        <Button
                            :disabled="
                    !columnMapping.first_name ||
                    !columnMapping.phone_number
                "
                            @click="startImport"
                        >
                            {{ t('imports.mapping.start_import') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Step 3: Processing with Progress -->
            <Card v-show="currentStep === 3" class="p-12 text-center">
                <div class="space-y-6">
                    <div class="flex justify-center">
                        <div
                            class="h-16 w-16 animate-spin rounded-full border-4 border-primary border-t-transparent"
                        ></div>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">
                            {{ t('imports.processing') }}
                        </h3>
                        <p class="mt-2 text-muted-foreground">
                            {{
                                t(
                                    'imports.processing_desc',
                                    'Please wait while we process your contacts...',
                                )
                            }}
                        </p>
                    </div>

                    <!-- Real-time Progress -->
                    <div v-if="importProgress" class="space-y-4">
                        <Progress
                            :model-value="progressPercentage"
                            class="h-3"
                        />

                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div class="rounded-lg bg-muted p-3">
                                <p class="text-xs text-muted-foreground">Total</p>
                                <p class="text-lg font-bold">{{ importProgress.total }}</p>
                            </div>
                            <div class="rounded-lg bg-green-50 p-3 dark:bg-green-950">
                                <p class="text-xs text-muted-foreground">Valid</p>
                                <p class="text-lg font-bold text-green-600">{{ importProgress.valid }}</p>
                            </div>
                            <div class="rounded-lg bg-red-50 p-3 dark:bg-red-950">
                                <p class="text-xs text-muted-foreground">Invalid</p>
                                <p class="text-lg font-bold text-red-600">{{ importProgress.invalid }}</p>
                            </div>
                            <div class="rounded-lg bg-yellow-50 p-3 dark:bg-yellow-950">
                                <p class="text-xs text-muted-foreground">Duplicates</p>
                                <p class="text-lg font-bold text-yellow-600">{{ importProgress.duplicates }}</p>
                            </div>
                        </div>

                        <p class="text-sm text-muted-foreground">
                            Processing row {{ importProgress.current_row }} of {{ importProgress.total }}
                            ({{ progressPercentage }}%)
                        </p>
                    </div>
                </div>
            </Card>

            <!-- Step 4: Summary -->
            <Card v-if="importSummary" v-show="currentStep === 4">
                <CardHeader>
                    <CardTitle>{{
                            t('imports.summary.title', 'Import Complete')
                        }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Statistics Grid -->
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                        <div class="rounded-lg border p-4">
                            <p class="text-sm text-muted-foreground">
                                {{ t('imports.summary.total_processed') }}
                            </p>
                            <p class="text-2xl font-bold">
                                {{ importSummary.total }}
                            </p>
                        </div>

                        <div
                            class="rounded-lg border border-green-200 bg-green-50 p-4 dark:bg-green-950"
                        >
                            <p class="text-sm text-muted-foreground">
                                {{ t('imports.summary.valid_imported') }}
                            </p>
                            <p class="text-2xl font-bold text-green-600">
                                {{ importSummary.valid }}
                            </p>
                        </div>

                        <div
                            class="rounded-lg border border-red-200 bg-red-50 p-4 dark:bg-red-950"
                        >
                            <p class="text-sm text-muted-foreground">
                                {{ t('imports.summary.invalid_skipped') }}
                            </p>
                            <p class="text-2xl font-bold text-red-600">
                                {{ importSummary.invalid }}
                            </p>
                        </div>

                        <div
                            class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:bg-yellow-950"
                        >
                            <p class="text-sm text-muted-foreground">
                                {{ t('imports.summary.duplicates') }}
                            </p>
                            <p class="text-2xl font-bold text-yellow-600">
                                {{ importSummary.duplicates }}
                            </p>
                        </div>

                        <div
                            class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:bg-blue-950"
                        >
                            <p class="text-sm text-muted-foreground">
                                {{ t('imports.summary.phone_normalized', 'Phone Numbers Normalized') }}
                            </p>
                            <p class="text-2xl font-bold text-blue-600">
                                {{ importSummary.phone_normalized || 0 }}
                            </p>
                        </div>
                    </div>

                    <!-- Errors Table -->
                    <div
                        v-if="importSummary.errors && importSummary.errors.length > 0"
                    >
                        <Alert variant="destructive">
                            <AlertCircle class="h-4 w-4" />
                            <AlertDescription>
                                {{
                                    t(
                                        'imports.summary.errors_found',
                                        '{count} errors found',
                                        { count: importSummary.errors.length },
                                    )
                                }}
                            </AlertDescription>
                        </Alert>

                        <div class="mt-4 overflow-hidden rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{{
                                                t('imports.errors.row', 'Row')
                                            }}</TableHead>
                                        <TableHead>{{
                                                t('imports.errors.error', 'Error')
                                            }}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow
                                        v-for="(error, index) in importSummary.errors.slice(0, 10)"
                                        :key="index"
                                    >
                                        <TableCell>{{ error.row }}</TableCell>
                                        <TableCell>{{ error.error }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <p v-if="importSummary.errors.length > 10" class="mt-2 text-sm text-muted-foreground">
                            {{ t('imports.showing_first_errors', 'Showing first 10 of {total} errors', { total: importSummary.errors.length }) }}
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-between">
                        <Button variant="outline" @click="resetImport">
                            {{ t('imports.summary.import_another') }}
                        </Button>
                        <div class="flex gap-2">
                            <Button variant="outline" @click="currentStep = 1">
                                {{ t('imports.history.title', 'Import History') }}
                            </Button>
                            <Button @click="$inertia.visit(contactsIndex())">
                                {{ t('imports.summary.view_contacts') }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Import History -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('imports.history.title') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="imports.data.length === 0"
                        class="py-8 text-center text-muted-foreground"
                    >
                        <FileText class="mx-auto mb-2 h-12 w-12" />
                        <p>{{ t('imports.history.no_imports') }}</p>
                    </div>
                    <div v-else class="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{{
                                            t('imports.history.filename')
                                        }}</TableHead>
                                    <TableHead>{{
                                            t('imports.history.date')
                                        }}</TableHead>
                                    <TableHead>{{
                                            t('imports.history.total')
                                        }}</TableHead>
                                    <TableHead>{{
                                            t('imports.history.valid')
                                        }}</TableHead>
                                    <TableHead>{{
                                            t('imports.history.invalid')
                                        }}</TableHead>
                                    <TableHead>{{
                                            t('imports.history.status')
                                        }}</TableHead>
                                    <TableHead>{{
                                            t('common.actions', 'Actions')
                                        }}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="importRecord in imports.data"
                                    :key="importRecord.id"
                                >
                                    <TableCell class="font-medium">{{
                                            importRecord.filename
                                        }}</TableCell>
                                    <TableCell>{{
                                            formatDate(importRecord.created_at)
                                        }}</TableCell>
                                    <TableCell>{{
                                            importRecord.total_rows
                                        }}</TableCell>
                                    <TableCell class="text-green-600">{{
                                            importRecord.valid_contacts
                                        }}</TableCell>
                                    <TableCell class="text-red-600">{{
                                            importRecord.invalid_contacts
                                        }}</TableCell>
                                    <TableCell>
                                        <Badge
                                            :variant="
                                                getStatusColor(
                                                    importRecord.status,
                                                )
                                            "
                                        >
                                            {{
                                                t(
                                                    `imports.status.${importRecord.status}`,
                                                )
                                            }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button size="sm" variant="ghost">
                                                    <MoreVertical class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem
                                                    v-if="importRecord.status === 'completed'"
                                                    @click="viewImportDetails(importRecord)"
                                                >
                                                    <Eye :class="isRTL() ? 'ml-2' : 'mr-2'" class="h-4 w-4" />
                                                    {{ t('common.view', 'View') }}
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    class="text-destructive"
                                                    @click="deleteImportRecord(importRecord.id)"
                                                >
                                                    <Trash2 :class="isRTL() ? 'ml-2' : 'mr-2'" class="h-4 w-4" />
                                                    {{ t('common.delete', 'Delete') }}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
