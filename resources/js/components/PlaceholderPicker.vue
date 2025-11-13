<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslation } from '@/composables/useTranslation';
import type { Placeholder } from '@/types/template';
import { Copy } from 'lucide-vue-next';

interface Props {
    placeholders: Placeholder[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    insert: [value: string];
}>();

const { t, isRTL } = useTranslation();

const handleInsert = (placeholder: string) => {
    emit('insert', placeholder);
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle :class="isRTL() ? 'text-right' : 'text-left'">
                {{
                    t(
                        'templates.available_placeholders',
                        'Available Placeholders',
                    )
                }}
            </CardTitle>
            <CardDescription :class="isRTL() ? 'text-right' : 'text-left'">
                {{ t('templates.click_to_insert', 'Click to insert') }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div class="space-y-2">
                <Button
                    v-for="placeholder in placeholders"
                    :key="placeholder.value"
                    :class="isRTL() ? 'flex-row-reverse' : ''"
                    class="w-full justify-between"
                    size="sm"
                    variant="outline"
                    @click="handleInsert(placeholder.value)"
                >
                    <div
                        :class="isRTL() ? 'text-right' : 'text-left'"
                        class="flex-1"
                    >
                        <div class="font-medium">{{ placeholder.label }}</div>
                        <div class="text-xs text-muted-foreground">
                            {{ placeholder.description }}
                        </div>
                    </div>
                    <div
                        :class="isRTL() ? 'mr-0 ml-auto' : 'mr-auto ml-0'"
                        class="flex items-center gap-2"
                    >
                        <Badge variant="secondary">{{
                            placeholder.value
                        }}</Badge>
                        <Copy
                            :class="isRTL() ? 'ml-2' : 'mr-2'"
                            class="size-4"
                        />
                    </div>
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
