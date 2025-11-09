<script lang="ts" setup>
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { usePhoneNumber } from '@/composables/usePhoneNumber';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store } from '@/routes/dashboard/contacts';
import { Form, Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

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
    props.countries,
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
    const input = document.querySelector(
        'input[name="phone_number"]',
    ) as HTMLInputElement;
    if (input) {
        input.value = newValue;
    }
});

// Watch country to update hidden input
watch(selectedCountry, (newValue) => {
    const input = document.querySelector(
        'input[name="country_id"]',
    ) as HTMLInputElement;
    if (input && newValue) {
        input.value = newValue.toString();
    }
});

// Watch tags to update hidden input
watch(
    selectedTags,
    (newValue) => {
        newValue.forEach((tagId, index) => {
            const input = document.querySelector(
                `input[name="tags[${index}]"]`,
            ) as HTMLInputElement;
            if (input) {
                input.value = tagId.toString();
            }
        });
    },
    { deep: true },
);
</script>

<template>
    <AppLayout>
        <Head :title="t('contacts.add')" />

        <div
            :class="isRTL() ? 'text-right' : 'text-left'"
            class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6"
        >
            <div class="mx-auto w-full max-w-3xl space-y-6">
                <Heading
                    :description="t('contacts.description')"
                    :title="t('contacts.add')"
                />

                <Form
                    #default="{ errors, processing }"
                    :reset-on-success="false"
                    class="space-y-6"
                    v-bind="store.form()"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle>{{
                                t(
                                    'contacts.fields.contact_info',
                                    'Contact Information',
                                )
                            }}</CardTitle>
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
                                    :disabled="processing"
                                    autofocus
                                    name="first_name"
                                    required
                                    type="text"
                                />
                                <InputError :message="errors.first_name" />
                            </div>

                            <!-- Last Name -->
                            <div>
                                <Label for="last_name">{{
                                    t('contacts.fields.last_name')
                                }}</Label>
                                <Input
                                    id="last_name"
                                    :disabled="processing"
                                    name="last_name"
                                    type="text"
                                />
                                <InputError :message="errors.last_name" />
                            </div>

                            <!-- Country -->
                            <div>
                                <Label for="country">{{
                                    t('contacts.fields.country')
                                }}</Label>
                                <Select
                                    v-model="selectedCountry"
                                    :disabled="processing"
                                >
                                    <SelectTrigger>
                                        <SelectValue
                                            :placeholder="
                                                t(
                                                    'common.select_country',
                                                    'Select country',
                                                )
                                            "
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="country in countries"
                                            :key="country.id"
                                            :value="country.id.toString()"
                                        >
                                            {{
                                                isRTL()
                                                    ? country.name_ar
                                                    : country.name_en
                                            }}
                                            (+{{ country.phone_code }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <input
                                    :value="selectedCountry || ''"
                                    name="country_id"
                                    type="hidden"
                                />
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
                                    :disabled="processing"
                                    placeholder="+201234567890"
                                    required
                                    type="tel"
                                    @input="handlePhoneInput"
                                />
                                <input
                                    :value="mobileNumber"
                                    name="phone_number"
                                    type="hidden"
                                />
                                <p
                                    v-if="selectedCountry"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{
                                        t(
                                            'contacts.phone_hint',
                                            'Enter number without country code',
                                        )
                                    }}
                                </p>
                                <InputError :message="errors.phone_number" />
                            </div>

                            <!-- Email -->
                            <div>
                                <Label for="email">{{
                                    t('contacts.fields.email')
                                }}</Label>
                                <Input
                                    id="email"
                                    :disabled="processing"
                                    name="email"
                                    type="email"
                                />
                                <InputError :message="errors.email" />
                            </div>

                            <!-- Tags -->
                            <div>
                                <Label>{{ t('contacts.fields.tags') }}</Label>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <Badge
                                        v-for="tag in tags"
                                        :key="tag.id"
                                        :style="
                                            isTagSelected(tag.id)
                                                ? {
                                                      backgroundColor:
                                                          tag.color,
                                                      color: 'white',
                                                  }
                                                : {}
                                        "
                                        :variant="
                                            isTagSelected(tag.id)
                                                ? 'default'
                                                : 'outline'
                                        "
                                        class="cursor-pointer"
                                        @click="toggleTag(tag.id)"
                                    >
                                        {{ tag.name }}
                                    </Badge>
                                </div>
                                <input
                                    v-for="(tagId, index) in selectedTags"
                                    :key="tagId"
                                    :name="`tags[${index}]`"
                                    :value="tagId"
                                    type="hidden"
                                />
                            </div>

                            <!-- Notes -->
                            <div>
                                <Label for="notes">{{
                                    t('contacts.fields.notes')
                                }}</Label>
                                <Textarea
                                    id="notes"
                                    :disabled="processing"
                                    name="notes"
                                    rows="3"
                                />
                            </div>

                            <!-- Validate WhatsApp -->
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    id="validate_whatsapp"
                                    v-model:checked="validateWhatsApp"
                                    :disabled="processing"
                                    :value="validateWhatsApp ? '1' : '0'"
                                    name="validate_whatsapp"
                                />
                                <Label
                                    class="cursor-pointer font-normal"
                                    for="validate_whatsapp"
                                >
                                    {{ t('contacts.validation.validate_now') }}
                                </Label>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Actions -->
                    <div class="flex justify-end gap-2">
                        <Button
                            :disabled="processing"
                            type="button"
                            variant="outline"
                            @click="$inertia.visit(index())"
                        >
                            {{ t('common.cancel', 'Cancel') }}
                        </Button>
                        <Button :disabled="processing" type="submit">
                            {{
                                processing
                                    ? t('common.saving', 'Saving...')
                                    : t('common.save', 'Save')
                            }}
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
