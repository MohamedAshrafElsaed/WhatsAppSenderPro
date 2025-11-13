<script setup lang="ts">
import { RadioGroup } from '@headlessui/vue';
import { cn } from '@/lib/utils';

interface Props {
    modelValue?: any;
    defaultValue?: any;
    disabled?: boolean;
    name?: string;
    required?: boolean;
    class?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: any];
}>();

const handleChange = (value: any) => {
    emit('update:modelValue', value);
};
</script>

<template>
    <RadioGroup
        :model-value="modelValue"
        :default-value="defaultValue"
        :disabled="disabled"
        :name="name"
        :class="cn('grid gap-2', props.class)"
        @update:model-value="handleChange"
    >
        <slot />
    </RadioGroup>
</template>
