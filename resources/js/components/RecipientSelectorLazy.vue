<script lang="ts" setup>
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useTranslation } from '@/composables/useTranslation';
import { usePage } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Loader2, Search, Users } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface Contact {
    id: number;
    first_name: string;
    last_name: string | null;
    phone_number: string;
}

interface PaginationInfo {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface Props {
    modelValue: number[];
    totalContactsCount?: number;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    totalContactsCount: 0,
});

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

const { t, isRTL } = useTranslation();

// Primary brand color
const PRIMARY_COLOR = '#25D366';

// State
const searchQuery = ref('');
const contacts = ref<Contact[]>([]);
const selectedContacts = ref(new Set<number>(props.modelValue));
const isLoading = ref(false);
const isSelectingAll = ref(false);
const pagination = ref<PaginationInfo>({
    current_page: 1,
    last_page: 1,
    per_page: 50,
    total: 0,
    from: null,
    to: null,
});
const selectedNotInPage = ref<Contact[]>([]);
const currentPage = ref(1);

// Computed
const selectedCount = computed(() => selectedContacts.value.size);
const isAllCurrentPageSelected = computed(() => {
    if (contacts.value.length === 0) return false;
    return contacts.value.every((c) => selectedContacts.value.has(c.id));
});
const isIndeterminate = computed(() => {
    if (contacts.value.length === 0) return false;
    const selected = contacts.value.filter((c) =>
        selectedContacts.value.has(c.id),
    );
    return selected.length > 0 && selected.length < contacts.value.length;
});

// Methods
const searchContacts = async (page = 1) => {
    isLoading.value = true;

    try {
        const params = new URLSearchParams({
            page: page.toString(),
            per_page: '50',
            search: searchQuery.value,
        });

        // Include selected IDs to maintain selection state
        const selectedArray = Array.from(selectedContacts.value);
        if (selectedArray.length > 0) {
            params.append('selected_ids', JSON.stringify(selectedArray));
        }

        const response = await fetch(
            `/dashboard/campaigns/contacts/search?${params}`,
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            },
        );

        const data = await response.json();

        contacts.value = data.contacts;
        selectedNotInPage.value = data.selected_not_in_page || [];
        pagination.value = data.pagination;
        currentPage.value = page;
    } catch (error) {
        console.error('Failed to search contacts:', error);
    } finally {
        isLoading.value = false;
    }
};

// Debounced search
const debouncedSearch = debounce(() => {
    searchContacts(1);
}, 300);

// Toggle individual contact
const toggleContact = (contactId: number) => {
    const newSelected = new Set(selectedContacts.value);
    if (newSelected.has(contactId)) {
        newSelected.delete(contactId);
    } else {
        newSelected.add(contactId);
    }
    selectedContacts.value = newSelected;
    emit('update:modelValue', Array.from(newSelected));
};

// Toggle all contacts on current page
const toggleAllCurrentPage = () => {
    const newSelected = new Set(selectedContacts.value);

    if (isAllCurrentPageSelected.value) {
        // Deselect all on current page
        contacts.value.forEach((c) => newSelected.delete(c.id));
    } else {
        // Select all on current page
        contacts.value.forEach((c) => newSelected.add(c.id));
    }

    selectedContacts.value = newSelected;
    emit('update:modelValue', Array.from(newSelected));
};

// Select all contacts (across all pages)
const selectAllContacts = async () => {
    isSelectingAll.value = true;

    try {
        const response = await fetch(
            '/dashboard/campaigns/contacts/select-all',
            {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': usePage().props.csrf_token as string,
                },
                body: JSON.stringify({
                    search: searchQuery.value,
                }),
            },
        );

        const data = await response.json();

        selectedContacts.value = new Set(data.contact_ids);
        emit('update:modelValue', data.contact_ids);
    } catch (error) {
        console.error('Failed to select all contacts:', error);
    } finally {
        isSelectingAll.value = false;
    }
};

// Clear selection
const clearSelection = () => {
    selectedContacts.value = new Set();
    emit('update:modelValue', []);
};

// Load next page
const loadNextPage = () => {
    if (currentPage.value < pagination.value.last_page) {
        searchContacts(currentPage.value + 1);
    }
};

// Load previous page
const loadPrevPage = () => {
    if (currentPage.value > 1) {
        searchContacts(currentPage.value - 1);
    }
};

// Remove from selected (for selected not in page)
const removeFromSelected = (contactId: number) => {
    const newSelected = new Set(selectedContacts.value);
    newSelected.delete(contactId);
    selectedContacts.value = newSelected;
    emit('update:modelValue', Array.from(newSelected));

    // Remove from selectedNotInPage
    selectedNotInPage.value = selectedNotInPage.value.filter(
        (c) => c.id !== contactId,
    );
};

// Watch for search query changes
watch(searchQuery, () => {
    debouncedSearch();
});

// Initialize
onMounted(() => {
    searchContacts(1);
});
</script>

<template>
    <div class="space-y-4">
        <!-- Header with stats -->
        <div
            :class="isRTL() ? 'flex-row-reverse' : ''"
            class="flex items-center justify-between"
        >
            <div :class="isRTL() ? 'text-right' : 'text-left'">
                <p class="text-sm text-muted-foreground">
                    {{ t('campaigns.total_contacts', 'Total Contacts') }}:
                    <span class="font-medium">{{
                        totalContactsCount.toLocaleString()
                    }}</span>
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ t('campaigns.selected_contacts', 'Selected') }}:
                    <Badge
                        :style="`background-color: ${PRIMARY_COLOR}`"
                        class="ml-2"
                    >
                        {{ selectedCount.toLocaleString() }}
                    </Badge>
                </p>
            </div>

            <div
                :class="isRTL() ? 'flex-row-reverse gap-2' : 'gap-2'"
                class="flex"
            >
                <Button
                    :disabled="selectedCount === 0"
                    size="sm"
                    variant="outline"
                    @click="clearSelection"
                >
                    {{ t('campaigns.clear_selection', 'Clear Selection') }}
                </Button>
                <Button
                    :disabled="isSelectingAll"
                    :style="`background-color: ${PRIMARY_COLOR}; &:hover { background-color: ${PRIMARY_COLOR}e6; }`"
                    class="hover:opacity-90"
                    size="sm"
                    @click="selectAllContacts"
                >
                    <Loader2
                        v-if="isSelectingAll"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    {{ t('campaigns.select_all_contacts', 'Select All') }}
                    ({{ totalContactsCount.toLocaleString() }})
                </Button>
            </div>
        </div>

        <!-- Search Box -->
        <div class="relative">
            <Search
                :class="isRTL() ? 'right-3' : 'left-3'"
                class="absolute top-3 h-4 w-4 text-muted-foreground"
            />
            <Input
                v-model="searchQuery"
                :class="isRTL() ? 'pr-10' : 'pl-10'"
                :dir="isRTL() ? 'rtl' : 'ltr'"
                :placeholder="
                    t(
                        'campaigns.search_contacts',
                        'Search contacts by name or phone...',
                    )
                "
            />
        </div>

        <!-- Selected contacts not in current page -->
        <div
            v-if="selectedNotInPage.length > 0"
            class="rounded-lg border bg-muted/50 p-3"
        >
            <p
                :class="isRTL() ? 'text-right' : 'text-left'"
                class="mb-2 text-sm font-medium"
            >
                {{
                    t(
                        'campaigns.selected_from_other_pages',
                        'Selected from other pages',
                    )
                }}:
            </p>
            <div class="flex flex-wrap gap-2">
                <Badge
                    v-for="contact in selectedNotInPage"
                    :key="contact.id"
                    class="cursor-pointer"
                    variant="secondary"
                    @click="removeFromSelected(contact.id)"
                >
                    {{ contact.first_name }} {{ contact.last_name }}
                    <span class="ml-1">×</span>
                </Badge>
            </div>
        </div>

        <!-- Contacts List -->
        <div class="rounded-lg border">
            <!-- Select All Header -->
            <div class="border-b bg-muted/50 p-3">
                <div
                    :class="isRTL() ? 'flex-row-reverse' : ''"
                    class="flex items-center justify-between"
                >
                    <label
                        :class="isRTL() ? 'flex-row-reverse' : ''"
                        class="flex cursor-pointer items-center gap-2"
                    >
                        <Checkbox
                            :checked="isAllCurrentPageSelected"
                            :indeterminate="isIndeterminate"
                            @click="toggleAllCurrentPage"
                        />
                        <span class="text-sm font-medium">
                            {{
                                t(
                                    'campaigns.select_all_page',
                                    'Select all on this page',
                                )
                            }}
                        </span>
                    </label>

                    <div class="text-sm text-muted-foreground">
                        {{ t('campaigns.showing', 'Showing') }}
                        {{ pagination.from || 0 }}-{{ pagination.to || 0 }}
                        {{ t('campaigns.of', 'of') }}
                        {{ pagination.total.toLocaleString() }}
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex h-48 items-center justify-center">
                <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
            </div>

            <!-- Empty State -->
            <div
                v-else-if="contacts.length === 0"
                class="flex h-48 items-center justify-center"
            >
                <div
                    :class="isRTL() ? 'text-right' : 'text-left'"
                    class="text-center"
                >
                    <Users
                        class="mx-auto mb-2 h-12 w-12 text-muted-foreground"
                    />
                    <p class="text-sm text-muted-foreground">
                        {{
                            searchQuery
                                ? t(
                                      'campaigns.no_contacts_found',
                                      'No contacts found',
                                  )
                                : t(
                                      'campaigns.no_contacts',
                                      'No contacts available',
                                  )
                        }}
                    </p>
                </div>
            </div>

            <!-- Contacts Grid -->
            <ScrollArea v-else class="h-96">
                <div class="grid gap-2 p-3 md:grid-cols-2">
                    <label
                        v-for="contact in contacts"
                        :key="contact.id"
                        :class="[
                            'flex cursor-pointer items-center gap-2 rounded-lg border p-3 transition-colors hover:bg-muted/50',
                            isRTL() ? 'flex-row-reverse' : '',
                            selectedContacts.has(contact.id)
                                ? 'border-[#25D366] bg-[#25D366]/10'
                                : '',
                        ]"
                    >
                        <Checkbox
                            :checked="selectedContacts.has(contact.id)"
                            @click="toggleContact(contact.id)"
                        />
                        <div
                            :class="isRTL() ? 'text-right' : 'text-left'"
                            class="flex-1"
                        >
                            <p class="font-medium">
                                {{ contact.first_name }} {{ contact.last_name }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ contact.phone_number }}
                            </p>
                        </div>
                    </label>
                </div>
            </ScrollArea>

            <!-- Pagination -->
            <div
                v-if="pagination.last_page > 1"
                class="border-t bg-muted/50 p-3"
            >
                <div
                    :class="isRTL() ? 'flex-row-reverse' : ''"
                    class="flex items-center justify-between"
                >
                    <Button
                        :disabled="currentPage === 1 || isLoading"
                        size="sm"
                        variant="outline"
                        @click="loadPrevPage"
                    >
                        {{ t('common.previous', 'Previous') }}
                    </Button>

                    <span class="text-sm text-muted-foreground">
                        {{ t('campaigns.page', 'Page') }} {{ currentPage }} /
                        {{ pagination.last_page }}
                    </span>

                    <Button
                        :disabled="
                            currentPage === pagination.last_page || isLoading
                        "
                        size="sm"
                        variant="outline"
                        @click="loadNextPage"
                    >
                        {{ t('common.next', 'Next') }}
                    </Button>
                </div>
            </div>
        </div>

        <!-- Warning for large selections -->
        <Alert
            v-if="selectedCount > 1000"
            class="border-yellow-500 bg-yellow-50 dark:bg-yellow-950"
        >
            <AlertDescription>
                {{
                    t(
                        'campaigns.large_selection_warning',
                        'You have selected a large number of contacts. This may take longer to process.',
                    )
                }}
            </AlertDescription>
        </Alert>
    </div>
</template>

<style scoped>
/* Custom checkbox styles for brand color */
:deep(.checkbox[data-state='checked']) {
    background-color: #25d366;
    border-color: #25d366;
}

:deep(.checkbox[data-state='indeterminate']) {
    background-color: #25d366;
    border-color: #25d366;
}
</style>
