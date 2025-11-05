<script setup lang="ts">
import { ref, computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Search } from 'lucide-vue-next';

interface Option {
    value: string;
    label: string;
}

interface Props {
    options: Option[];
    modelValue?: string | number | null;
    placeholder?: string;
    searchPlaceholder?: string;
    name?: string;
    required?: boolean;
    tabindex?: number;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:modelValue': [value: string | number | null];
}>();

const searchQuery = ref('');
const isOpen = ref(false);

const filteredOptions = computed(() => {
    if (!searchQuery.value) {
        return props.options;
    }

    const query = searchQuery.value.toLowerCase();
    return props.options.filter(option =>
        option.label.toLowerCase().includes(query)
    );
});

const handleOpenChange = (open: boolean) => {
    isOpen.value = open;
    if (!open) {
        // Reset search when closing
        searchQuery.value = '';
    }
};

const handleValueChange = (value: string) => {
    emit('update:modelValue', value);
};
</script>

<template>
    <Select
        :model-value="modelValue?.toString()"
        @update:model-value="handleValueChange"
        @open-change="handleOpenChange"
        :name="name"
        :required="required"
    >
        <SelectTrigger :tabindex="tabindex" class="w-full">
            <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent class="w-full">
            <!-- Search Input -->
            <div class="sticky top-0 z-10 bg-background p-2 pb-1">
                <div class="relative">
                    <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="searchQuery"
                        :placeholder="searchPlaceholder || 'Search...'"
                        class="h-9 pl-8"
                        @keydown.enter.prevent
                        @keydown.space.stop
                    />
                </div>
            </div>

            <!-- Options List -->
            <div class="max-h-[300px] overflow-y-auto">
                <SelectItem
                    v-for="option in filteredOptions"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </SelectItem>

                <!-- No Results -->
                <div
                    v-if="filteredOptions.length === 0"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    No results found
                </div>
            </div>
        </SelectContent>
    </Select>
</template>
