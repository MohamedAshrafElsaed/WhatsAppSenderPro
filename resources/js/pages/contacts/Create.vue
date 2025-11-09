<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Form } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import { usePhoneNumber } from '@/composables/usePhoneNumber';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index } from '@/routes/contacts';
import { store } from '@/routes/contacts';

interface Country {
    id: number;
    name_en: string;
    name_ar: string;
    phone_code: string;
}

interface Tag {
    id: number;
    name: string;
    color: string;
}

interface Props {
    countries: Country[];
    tags: Tag[];
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();

const selectedCountry = ref<number | null>(null);
const selectedTags = ref<number[]>([]);
const validateWhatsApp = ref(false);

const { mobileNumber, handlePhoneInput } = usePhoneNumber(
    selectedCountry,
    props.countries
);

const toggleTag = (tagId: number) => {
    const index = selectedTags.value.indexOf(tagId);
    if (index > -1) {
        selectedTags.value.splice(index, 1);
    } else {
        selectedTags.value.push(tagId);
    }
};

const isTagSelected = (tagId: number) => selectedTags.value.includes(tagId);

// Watch mobile number to update hidden input
watch(mobileNumber, (newValue) => {
    const input = document.querySelector('input[name="phone_number"]') as HTMLInputElement;
    if (input) {
        input.value = newValue;
    }
});

// Watch country to update hidden input
watch(selectedCountry, (newValue) => {
    const input = document.querySelector('input[name="country_id"]') as HTMLInputElement;
    if (input && newValue) {
        input.value = newValue.toString();
    }
});

// Watch tags to update hidden input
watch(selectedTags, (newValue) => {
    newValue.forEach((tagId, index) => {
        const input = document.querySelector(`input[name="tags[${index}]"]`) as HTMLInputElement;
        if (input) {
            input.value = tagId.toString();
        }
    });
}, { deep: true });
</script>

<template>
    <AppLayout>
        <Head :title="t('contacts.add')" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6" :class="isRTL() ? 'text-right' : 'text-left'">
            <div class="max-w-3xl mx-auto w-full space-y-6">
                <Heading
                    :title="t('contacts.add')"
                    :description="t('contacts.description')"
                />

                <Form
                    v-bind="store.form()"
                    :reset-on-success="false"
                    #default="{ errors, processing }"
                    class="space-y-6"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ t('contacts.fields.contact_info', 'Contact Information') }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- First Name -->
                            <div>
                                <Label for="first_name">
                                    {{ t('contacts.fields.first_name') }}
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="first_name"
                                    name="first_name"
                                    type="text"
                                    required
                                    autofocus
                                    :disabled="processing"
                                />
                                <InputError :message="errors.first_name" />
                            </div>

                            <!-- Last Name -->
                            <div>
                                <Label for="last_name">{{ t('contacts.fields.last_name') }}</Label>
                                <Input
                                    id="last_name"
                                    name="last_name"
                                    type="text"
                                    :disabled="processing"
                                />
                                <InputError :message="errors.last_name" />
                            </div>

                            <!-- Country -->
                            <div>
                                <Label for="country">{{ t('contacts.fields.country') }}</Label>
                                <Select v-model="selectedCountry" :disabled="processing">
                                    <SelectTrigger>
                                        <SelectValue :placeholder="t('common.select_country', 'Select country')" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="country in countries"
                                            :key="country.id"
                                            :value="country.id.toString()"
                                        >
                                            {{ isRTL() ? country.name_ar : country.name_en }} (+{{ country.phone_code }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <input type="hidden" name="country_id" :value="selectedCountry || ''" />
                                <InputError :message="errors.country_id" />
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <Label for="phone_number">
                                    {{ t('contacts.fields.phone_number') }}
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="phone_number_display"
                                    v-model="mobileNumber"
                                    @input="handlePhoneInput"
                                    type="tel"
                                    required
                                    :disabled="processing"
                                    placeholder="+201234567890"
                                />
                                <input type="hidden" name="phone_number" :value="mobileNumber" />
                                <p v-if="selectedCountry" class="text-xs text-muted-foreground mt-1">
                                    {{ t('contacts.phone_hint', 'Enter number without country code') }}
                                </p>
                                <InputError :message="errors.phone_number" />
                            </div>

                            <!-- Email -->
                            <div>
                                <Label for="email">{{ t('contacts.fields.email') }}</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    :disabled="processing"
                                />
                                <InputError :message="errors.email" />
                            </div>

                            <!-- Tags -->
                            <div>
                                <Label>{{ t('contacts.fields.tags') }}</Label>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <Badge
                                        v-for="tag in tags"
                                        :key="tag.id"
                                        :variant="isTagSelected(tag.id) ? 'default' : 'outline'"
                                        :style="isTagSelected(tag.id) ? { backgroundColor: tag.color, color: 'white' } : {}"
                                        class="cursor-pointer"
                                        @click="toggleTag(tag.id)"
                                    >
                                        {{ tag.name }}
                                    </Badge>
                                </div>
                                <input
                                    v-for="(tagId, index) in selectedTags"
                                    :key="tagId"
                                    type="hidden"
                                    :name="`tags[${index}]`"
                                    :value="tagId"
                                />
                            </div>

                            <!-- Notes -->
                            <div>
                                <Label for="notes">{{ t('contacts.fields.notes') }}</Label>
                                <Textarea
                                    id="notes"
                                    name="notes"
                                    :disabled="processing"
                                    rows="3"
                                />
                            </div>

                            <!-- Validate WhatsApp -->
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    id="validate_whatsapp"
                                    v-model:checked="validateWhatsApp"
                                    name="validate_whatsapp"
                                    :disabled="processing"
                                    :value="validateWhatsApp ? '1' : '0'"
                                />
                                <Label for="validate_whatsapp" class="cursor-pointer font-normal">
                                    {{ t('contacts.validation.validate_now') }}
                                </Label>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Actions -->
                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="$inertia.visit(index())"
                            :disabled="processing"
                        >
                            {{ t('common.cancel', 'Cancel') }}
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ processing ? t('common.saving', 'Saving...') : t('common.save', 'Save') }}
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
