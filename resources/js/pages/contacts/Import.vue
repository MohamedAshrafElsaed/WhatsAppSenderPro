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
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    destroy as deleteImport,
    index,
    process as processImport,
    template,
    upload,
} from '@/routes/dashboard/contacts/imports';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    Check,
    Download,
    FileText,
    Trash2,
    Upload,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

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

interface Props {
    imports: {
        data: ImportRecord[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    tags: Tag[];
}

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
    country: '',
});
const validateWhatsApp = ref(false);
const selectedTagId = ref<string>('none');
const isProcessing = ref(false);
const importSummary = ref<any>(null);

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
        preserveState: true,
        onSuccess: () => {
            const preview = page.props.import_preview as any;
            if (preview) {
                parseResult.value = preview;
                currentStep.value = 2;
            }
        },
        onError: (errors) => {
            console.error('Upload failed:', errors);
            uploadedFile.value = null;
        },
    });
};

const startImport = () => {
    if (!parseResult.value?.id) return;

    isProcessing.value = true;

    router.post(
        processImport(parseResult.value.id),
        {
            column_mapping: columnMapping.value,
            validate_whatsapp: validateWhatsApp.value,
            tag_id: selectedTagId.value === 'none' ? null : selectedTagId.value,
        },
        {
            preserveState: true,
            onSuccess: () => {
                const summary = page.props.import_summary as any;
                if (summary) {
                    importSummary.value = summary;
                    currentStep.value = 4;
                } else {
                    currentStep.value = 3;
                }
                isProcessing.value = false;
            },
            onError: (errors) => {
                console.error('Import processing failed:', errors);
                isProcessing.value = false;
            },
        },
    );
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
        country: '',
    };
    validateWhatsApp.value = false;
    selectedTagId.value = 'none';
    importSummary.value = null;
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

const progressPercentage = computed(() => (currentStep.value / 4) * 100);

// Watch for import summary in page props
watch(
    () => page.props.import_summary,
    (newSummary) => {
        if (newSummary) {
            importSummary.value = newSummary;
            if (currentStep.value === 3) {
                currentStep.value = 4;
            }
        }
    },
    { deep: true },
);
</script>

<template>
    <AppLayout>
        <Head :title="t('imports.title')" />

        <div class="mx-auto max-w-6xl space-y-6">
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
                                            v-for="(
                                                header, index
                                            ) in parseResult.headers"
                                            :key="index"
                                        >
                                            {{ header }}
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow
                                        v-for="(
                                            row, rowIndex
                                        ) in parseResult.preview.slice(0, 3)"
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
                                        v-for="(
                                            header, index
                                        ) in parseResult?.headers"
                                        :key="index"
                                        :value="header"
                                    >
                                        {{ header }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="space-y-4">
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

                        <div>
                            <Label for="tag">{{
                                t('imports.mapping.assign_tag')
                            }}</Label>
                            <Select v-model="selectedTagId">
                                <SelectTrigger>
                                    <SelectValue
                                        :placeholder="
                                            t('imports.mapping.select_tag')
                                        "
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
                                            :style="{
                                                backgroundColor: tag.color,
                                            }"
                                            class="text-white"
                                        >
                                            {{ tag.name }}
                                        </Badge>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="flex justify-between">
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

            <!-- Step 3: Processing -->
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
                    <!-- Statistics -->
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
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
                    </div>

                    <!-- Errors -->
                    <div
                        v-if="
                            importSummary.errors &&
                            importSummary.errors.length > 0
                        "
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
                                        v-for="(
                                            error, index
                                        ) in importSummary.errors.slice(0, 10)"
                                        :key="index"
                                    >
                                        <TableCell>{{ error.row }}</TableCell>
                                        <TableCell>{{ error.error }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <Button variant="outline" @click="resetImport">
                            {{ t('imports.summary.import_another') }}
                        </Button>
                        <Button @click="$inertia.visit(index())">
                            {{ t('imports.summary.view_contacts') }}
                        </Button>
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
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            @click="
                                                deleteImportRecord(
                                                    importRecord.id,
                                                )
                                            "
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
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
