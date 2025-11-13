<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/composables/useTranslation';
import { Check, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Contact {
    id: number;
    first_name: string;
    last_name: string | null;
    phone_number: string;
}

interface Props {
    contacts: Contact[];
    modelValue: number[];
}

interface Emits {
    (e: 'update:modelValue', value: number[]): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { t, isRTL } = useTranslation();

const searchQuery = ref('');

const selectedIds = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const filteredContacts = computed(() => {
    if (!searchQuery.value) return props.contacts;

    const query = searchQuery.value.toLowerCase();
    return props.contacts.filter((contact) => {
        const name =
            `${contact.first_name} ${contact.last_name || ''}`.toLowerCase();
        const phone = contact.phone_number.toLowerCase();
        return name.includes(query) || phone.includes(query);
    });
});

const isSelected = (contactId: number) => {
    return selectedIds.value.includes(contactId);
};

const toggleContact = (contactId: number) => {
    if (isSelected(contactId)) {
        selectedIds.value = selectedIds.value.filter((id) => id !== contactId);
    } else {
        selectedIds.value = [...selectedIds.value, contactId];
    }
};

const selectAll = () => {
    selectedIds.value = filteredContacts.value.map((c) => c.id);
};

const deselectAll = () => {
    selectedIds.value = [];
};

const selectedCount = computed(() => selectedIds.value.length);
</script>

<template>
    <div class="space-y-4">
        <!-- Search and Actions -->
        <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex gap-2">
            <div class="relative flex-1">
                <Search
                    :class="isRTL() ? 'right-3' : 'left-3'"
                    class="absolute top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    :class="isRTL() ? 'pr-10' : 'pl-10'"
                    :placeholder="
                        t('campaigns.search_contacts', 'Search contacts...')
                    "
                />
            </div>
            <Button size="sm" variant="outline" @click="selectAll">
                {{ t('common.select_all', 'Select All') }}
            </Button>
            <Button size="sm" variant="outline" @click="deselectAll">
                {{ t('common.clear', 'Clear') }}
            </Button>
        </div>

        <!-- Selected Count Badge -->
        <div :class="isRTL() ? 'justify-end' : 'justify-start'" class="flex">
            <Badge class="bg-[#25D366] text-white">
                {{
                    t(
                        'campaigns.contacts_selected',
                        '{count} contacts selected',
                    ).replace('{count}', selectedCount.toString())
                }}
            </Badge>
        </div>

        <!-- Contacts List -->
        <div class="max-h-96 space-y-2 overflow-y-auto rounded-lg border p-4">
            <div
                v-for="contact in filteredContacts"
                :key="contact.id"
                :class="[
                    'flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-accent',
                    isSelected(contact.id)
                        ? 'border-[#25D366] bg-[#25D366]/10'
                        : '',
                    isRTL() ? 'flex-row-reverse' : '',
                ]"
            >
                <Checkbox
                    :checked="isSelected(contact.id)"
                    @update:checked="() => toggleContact(contact.id)"
                />
                <div
                    :class="isRTL() ? 'text-right' : 'text-left'"
                    class="flex-1"
                >
                    <div class="font-medium">
                        {{ contact.first_name }} {{ contact.last_name }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        {{ contact.phone_number }}
                    </div>
                </div>
                <Check
                    v-if="isSelected(contact.id)"
                    class="h-5 w-5 text-[#25D366]"
                />
            </div>

            <div
                v-if="filteredContacts.length === 0"
                class="py-8 text-center text-muted-foreground"
            >
                {{ t('campaigns.no_contacts_found', 'No contacts found') }}
            </div>
        </div>
    </div>
</template>
