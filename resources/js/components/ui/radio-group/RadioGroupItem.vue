<script lang="ts" setup>
import { RadioGroupOption } from '@headlessui/vue';
import { Circle } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

interface Props {
    value: any;
    disabled?: boolean;
    class?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <RadioGroupOption
        v-slot="{ checked, disabled: isDisabled }"
        :class="cn('cursor-pointer', props.class)"
        :disabled="disabled"
        :value="value"
    >
        <span
            :class="cn(
                'relative flex h-4 w-4 items-center justify-center rounded-full border border-input',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
                'disabled:cursor-not-allowed disabled:opacity-50',
                'data-[state=checked]:border-[#25D366] data-[state=checked]:bg-[#25D366]',
                checked && 'border-[#25D366] bg-[#25D366]',
                (disabled || isDisabled) && 'cursor-not-allowed opacity-50'
            )"
            :data-state="checked ? 'checked' : 'unchecked'"
        >
            <Circle
                v-if="checked"
                class="h-2.5 w-2.5 fill-white text-white"
            />
        </span>
    </RadioGroupOption>
</template>
