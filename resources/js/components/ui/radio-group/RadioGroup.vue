<script lang="ts" setup>
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
        :class="cn('grid gap-2', props.class)"
        :default-value="defaultValue"
        :disabled="disabled"
        :model-value="modelValue"
        :name="name"
        @update:model-value="handleChange"
    >
        <slot />
    </RadioGroup>
</template>
