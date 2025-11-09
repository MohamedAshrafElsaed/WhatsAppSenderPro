<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Plus,
    Search,
    Filter,
    MoreVertical,
    Eye,
    Edit,
    Trash2,
    CheckCircle2,
    XCircle,
    Clock,
    Upload,
} from 'lucide-vue-next';
import { index as importsIndex } from '@/routes/contacts/imports';
import { index, create, show, edit, destroy } from '@/routes/contacts';

interface Contact {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    phone_number: string;
    email: string;
    country: { id: number; name: string } | null;
    tags: Array<{ id: number; name: string; color: string }>;
    source: string;
    is_whatsapp_valid: boolean;
    validated_at: string | null;
    last_message_at: string | null;
    created_at: string;
}

interface Tag {
    id: number;
    name: string;
    color: string;
}

interface Country {
    id: number;
    name_en: string;
    name_ar: string;
}

interface Props {
    contacts: {
        data: Contact[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    tags: Tag[];
    countries: Country[];
    filters: {
        search?: string;
        source?: string;
        tag_id?: number;
        country_id?: number;
        validation_status?: string;
    };
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();

// Search and filters - use 'all' as default to avoid empty string
const searchQuery = ref(props.filters.search || '');
const selectedSource = ref(props.filters.source || 'all');
const selectedTag = ref(props.filters.tag_id?.toString() || 'all');
const selectedCountry = ref(props.filters.country_id?.toString() || 'all');
const selectedStatus = ref(props.filters.validation_status || 'all');

// Selection
const selectedContacts = ref<number[]>([]);
const showDeleteDialog = ref(false);
const contactToDelete = ref<Contact | null>(null);

// Apply filters
const applyFilters = () => {
    router.get(
        index(),
        {
            search: searchQuery.value || undefined,
            source: selectedSource.value === 'all' ? undefined : selectedSource.value,
            tag_id: selectedTag.value === 'all' ? undefined : selectedTag.value,
            country_id: selectedCountry.value === 'all' ? undefined : selectedCountry.value,
            validation_status: selectedStatus.value === 'all' ? undefined : selectedStatus.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

// Clear filters
const clearFilters = () => {
    searchQuery.value = '';
    selectedSource.value = 'all';
    selectedTag.value = 'all';
    selectedCountry.value = 'all';
    selectedStatus.value = 'all';
    applyFilters();
};

// Selection handlers
const toggleSelectAll = () => {
    if (selectedContacts.value.length === props.contacts.data.length) {
        selectedContacts.value = [];
    } else {
        selectedContacts.value = props.contacts.data.map(c => c.id);
    }
};

const toggleSelect = (contactId: number) => {
    const index = selectedContacts.value.indexOf(contactId);
    if (index > -1) {
        selectedContacts.value.splice(index, 1);
    } else {
        selectedContacts.value.push(contactId);
    }
};

const isSelected = (contactId: number) => selectedContacts.value.includes(contactId);
const allSelected = computed(() =>
    props.contacts.data.length > 0 &&
    selectedContacts.value.length === props.contacts.data.length
);

// Delete handlers
const confirmDelete = (contact: Contact) => {
    contactToDelete.value = contact;
    showDeleteDialog.value = true;
};

const deleteContact = () => {
    if (contactToDelete.value) {
        router.delete(destroy(contactToDelete.value.id), {
            onSuccess: () => {
                showDeleteDialog.value = false;
                contactToDelete.value = null;
            },
        });
    }
};

// Bulk validate
const bulkValidate = () => {
    router.post('/contacts/bulk-validate', {
        contact_ids: selectedContacts.value,
    }, {
        onSuccess: () => {
            selectedContacts.value = [];
        },
    });
};

// Get validation status badge
const getStatusBadge = (contact: Contact) => {
    if (contact.is_whatsapp_valid) {
        return { variant: 'default' as const, icon: CheckCircle2, text: t('contacts.validation.valid') };
    } else if (contact.validated_at) {
        return { variant: 'destructive' as const, icon: XCircle, text: t('contacts.validation.invalid') };
    }
    return { variant: 'secondary' as const, icon: Clock, text: t('contacts.validation.pending') };
};

// Get source badge color
const getSourceColor = (source: string): string => {
    const colors: Record<string, string> = {
        manual: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        csv_import: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        excel_import: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        api: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    };
    return colors[source] || colors.manual;
};
</script>

<template>
    <AppLayout>
        <Head :title="t('contacts.title')" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6" :class="isRTL() ? 'text-right' : 'text-left'">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <Heading
                    :title="t('contacts.title')"
                    :description="t('contacts.description')"
                />
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        @click="router.visit(importsIndex())"
                    >
                        <Upload :class="isRTL() ? 'ml-2' : 'mr-2'" class="h-4 w-4" />
                        {{ t('contacts.import') }}
                    </Button>
                    <Button @click="router.visit(create())">
                        <Plus :class="isRTL() ? 'ml-2' : 'mr-2'" class="h-4 w-4" />
                        {{ t('contacts.add') }}
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col lg:flex-row gap-4">
                <!-- Search -->
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="searchQuery"
                        :placeholder="t('contacts.search')"
                        class="pl-10"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap gap-2">
                    <Select v-model="selectedSource" @update:model-value="applyFilters">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue :placeholder="t('contacts.filters.all_sources')" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{{ t('contacts.filters.all_sources') }}</SelectItem>
                            <SelectItem value="manual">{{ t('contacts.sources.manual') }}</SelectItem>
                            <SelectItem value="csv_import">{{ t('contacts.sources.csv_import') }}</SelectItem>
                            <SelectItem value="excel_import">{{ t('contacts.sources.excel_import') }}</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="selectedStatus" @update:model-value="applyFilters">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue :placeholder="t('contacts.filters.all_status')" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{{ t('contacts.filters.all_status') }}</SelectItem>
                            <SelectItem value="valid">{{ t('contacts.validation.valid') }}</SelectItem>
                            <SelectItem value="invalid">{{ t('contacts.validation.invalid') }}</SelectItem>
                        </SelectContent>
                    </Select>

                    <Button variant="ghost" size="sm" @click="clearFilters">
                        Clear
                    </Button>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div
                v-if="selectedContacts.length > 0"
                class="flex items-center justify-between p-4 bg-muted rounded-lg"
            >
                <span class="text-sm font-medium">
                    {{ t('contacts.selected', { count: selectedContacts.length }) }}
                </span>
                <div class="flex gap-2">
                    <Button size="sm" variant="outline" @click="bulkValidate">
                        {{ t('contacts.validate_selected') }}
                    </Button>
                    <Button size="sm" variant="destructive">
                        {{ t('contacts.delete_selected') }}
                    </Button>
                </div>
            </div>

            <!-- Table -->
            <div class="border rounded-lg">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-12">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    @change="toggleSelectAll"
                                    class="rounded border-gray-300"
                                />
                            </TableHead>
                            <TableHead>{{ t('contacts.fields.first_name') }}</TableHead>
                            <TableHead>{{ t('contacts.fields.phone_number') }}</TableHead>
                            <TableHead>{{ t('contacts.fields.email') }}</TableHead>
                            <TableHead>{{ t('contacts.fields.tags') }}</TableHead>
                            <TableHead>{{ t('contacts.fields.source') }}</TableHead>
                            <TableHead>{{ t('contacts.fields.whatsapp_status') }}</TableHead>
                            <TableHead>{{ t('contacts.fields.actions') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="contacts.data.length === 0">
                            <TableCell colspan="8" class="text-center py-12">
                                <div class="flex flex-col items-center gap-2">
                                    <p class="text-muted-foreground">{{ t('contacts.no_contacts') }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ t('contacts.import_to_start') }}
                                    </p>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="contact in contacts.data" :key="contact.id">
                            <TableCell>
                                <input
                                    type="checkbox"
                                    :checked="isSelected(contact.id)"
                                    @change="toggleSelect(contact.id)"
                                    class="rounded border-gray-300"
                                />
                            </TableCell>
                            <TableCell class="font-medium">{{ contact.full_name }}</TableCell>
                            <TableCell>{{ contact.phone_number }}</TableCell>
                            <TableCell>{{ contact.email || '-' }}</TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="tag in contact.tags"
                                        :key="tag.id"
                                        :style="{ backgroundColor: tag.color }"
                                        class="text-white"
                                    >
                                        {{ tag.name }}
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :class="getSourceColor(contact.source)" variant="outline">
                                    {{ t(`contacts.sources.${contact.source}`) }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="getStatusBadge(contact).variant">
                                    <component
                                        :is="getStatusBadge(contact).icon"
                                        class="w-3 h-3 mr-1"
                                    />
                                    {{ getStatusBadge(contact).text }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="sm">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem @click="router.visit(show(contact.id))">
                                            <Eye class="mr-2 h-4 w-4" />
                                            View
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="router.visit(edit(contact.id))">
                                            <Edit class="mr-2 h-4 w-4" />
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="text-destructive"
                                            @click="confirmDelete(contact)"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="showDeleteDialog">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{{ t('contacts.messages.confirm_delete') }}</DialogTitle>
                        <DialogDescription>
                            This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="showDeleteDialog = false">
                            Cancel
                        </Button>
                        <Button variant="destructive" @click="deleteContact">
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
