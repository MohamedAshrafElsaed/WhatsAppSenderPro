<script setup lang="ts">
import { Head, Link, Form } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import LandingLayout from '@/layouts/LandingLayout.vue';
import { useTranslation } from '@/composables/useTranslation';
import { usePhoneNumber } from '@/composables/usePhoneNumber';
import { Loader2 } from 'lucide-vue-next';
import { login } from '@/routes';
import { store } from '@/routes/register';

interface Country {
    id: number;
    name: string;
    phone_code: string;
    iso_code: string;
}

interface Industry {
    id: number;
    name: string;
    slug: string;
}

interface Props {
    countries: Country[];
    industries: Industry[];
}

const props = defineProps<Props>();
const { t } = useTranslation();

// Form field refs
const selectedCountry = ref<number | null>(null);
const selectedIndustry = ref<number | null>(null);

// Phone number handling with auto country code removal
const { mobileNumber, handlePhoneInput } = usePhoneNumber(
    selectedCountry,
    props.countries
);

// Transform countries for SearchableSelect
const countryOptions = computed(() =>
    props.countries.map((country) => ({
        value: country.id.toString(),
        label: `${country.name} (+${country.phone_code})`,
    }))
);

// Transform industries for SearchableSelect
const industryOptions = computed(() =>
    props.industries.map((industry) => ({
        value: industry.id.toString(),
        label: industry.name,
    }))
);

// Handle mobile number input with auto country code removal
const onMobileInput = () => {
    // The v-model automatically updates mobileNumber.value
    // We just need to clean it
    mobileNumber.value = handlePhoneInput(mobileNumber.value);
};

// Watch country changes to update hidden input
watch(selectedCountry, (newValue) => {
    const countryInput = document.querySelector('input[name="country_id"]') as HTMLInputElement;
    if (countryInput && newValue) {
        countryInput.value = newValue.toString();
    }
});

// Watch industry changes to update hidden input
watch(selectedIndustry, (newValue) => {
    const industryInput = document.querySelector('input[name="industry_id"]') as HTMLInputElement;
    if (industryInput && newValue) {
        industryInput.value = newValue.toString();
    }
});

// Watch mobile number to update hidden input
watch(mobileNumber, (newValue) => {
    const mobileInput = document.querySelector('input[name="mobile_number"]') as HTMLInputElement;
    if (mobileInput) {
        mobileInput.value = newValue;
    }
});
</script>

<template>
    <LandingLayout>
        <Head :title="t('auth.register.title', 'Register')" />

        <div class="mx-auto max-w-2xl px-4 py-16">
            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ t('auth.register.title', 'Create an account') }}
                </h1>
                <p class="mt-2 text-muted-foreground">
                    {{
                        t(
                            'auth.register.description',
                            'Enter your details to get started'
                        )
                    }}
                </p>
            </div>

            <!-- Register Form -->
            <div class="rounded-lg border bg-card p-8 shadow-sm">
                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                    class="flex flex-col gap-6"
                >
                    <div class="grid gap-6">
                        <!-- First Name & Last Name (Side by Side) -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="first_name">
                                    {{
                                        t(
                                            'auth.register.first_name',
                                            'First Name'
                                        )
                                    }}
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="first_name"
                                    type="text"
                                    name="first_name"
                                    required
                                    autofocus
                                    :tabindex="1"
                                    autocomplete="given-name"
                                    :placeholder="
                                        t('auth.placeholders.first_name', 'John')
                                    "
                                    :class="{
                                        'border-destructive': errors.first_name,
                                    }"
                                />
                                <InputError :message="errors.first_name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="last_name">
                                    {{
                                        t('auth.register.last_name', 'Last Name')
                                    }}
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="last_name"
                                    type="text"
                                    name="last_name"
                                    required
                                    :tabindex="2"
                                    autocomplete="family-name"
                                    :placeholder="
                                        t('auth.placeholders.last_name', 'Doe')
                                    "
                                    :class="{
                                        'border-destructive': errors.last_name,
                                    }"
                                />
                                <InputError :message="errors.last_name" />
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="grid gap-2">
                            <Label for="email">
                                {{
                                    t(
                                        'auth.register.email',
                                        'Email Address'
                                    )
                                }}
                                <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                :tabindex="3"
                                autocomplete="email"
                                :placeholder="
                                    t(
                                        'auth.placeholders.email',
                                        'email@example.com'
                                    )
                                "
                                :class="{ 'border-destructive': errors.email }"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <!-- Country & Mobile Number -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="country_id">
                                    {{ t('auth.register.country', 'Country') }}
                                    <span class="text-destructive">*</span>
                                </Label>
                                <SearchableSelect
                                    v-model="selectedCountry"
                                    :options="countryOptions"
                                    :placeholder="
                                        t(
                                            'auth.placeholders.select_country',
                                            'Select country'
                                        )
                                    "
                                    :search-placeholder="
                                        t('common.search', 'Search...')
                                    "
                                    name="country_id_display"
                                    required
                                    :tabindex="4"
                                />
                                <!-- Hidden input for form submission -->
                                <input
                                    type="hidden"
                                    name="country_id"
                                    :value="selectedCountry || ''"
                                />
                                <InputError :message="errors.country_id" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="mobile_number">
                                    {{
                                        t(
                                            'auth.register.mobile',
                                            'Mobile Number'
                                        )
                                    }}
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="mobile_number_display"
                                    type="tel"
                                    v-model="mobileNumber"
                                    @input="onMobileInput"
                                    required
                                    :tabindex="5"
                                    autocomplete="tel"
                                    :placeholder="
                                        t(
                                            'auth.placeholders.mobile',
                                            '1234567890'
                                        )
                                    "
                                    :class="{
                                        'border-destructive':
                                            errors.mobile_number,
                                    }"
                                />
                                <!-- Hidden input for form submission -->
                                <input
                                    type="hidden"
                                    name="mobile_number"
                                    :value="mobileNumber"
                                />
                                <p
                                    v-if="selectedCountry"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{
                                        t(
                                            'auth.register.mobile_hint',
                                            'Enter number without country code'
                                        )
                                    }}
                                </p>
                                <InputError :message="errors.mobile_number" />
                            </div>
                        </div>

                        <!-- Industry -->
                        <div class="grid gap-2">
                            <Label for="industry_id">
                                {{ t('auth.register.industry', 'Industry') }}
                                <span class="text-destructive">*</span>
                            </Label>
                            <SearchableSelect
                                v-model="selectedIndustry"
                                :options="industryOptions"
                                :placeholder="
                                    t(
                                        'auth.placeholders.select_industry',
                                        'Select industry'
                                    )
                                "
                                :search-placeholder="
                                    t('common.search', 'Search...')
                                "
                                name="industry_id_display"
                                required
                                :tabindex="6"
                            />
                            <!-- Hidden input for form submission -->
                            <input
                                type="hidden"
                                name="industry_id"
                                :value="selectedIndustry || ''"
                            />
                            <InputError :message="errors.industry_id" />
                        </div>

                        <!-- Password -->
                        <div class="grid gap-2">
                            <Label for="password">
                                {{ t('auth.register.password', 'Password') }}
                                <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                :tabindex="7"
                                autocomplete="new-password"
                                :placeholder="
                                    t(
                                        'auth.placeholders.password',
                                        'Min 8 characters'
                                    )
                                "
                                :class="{
                                    'border-destructive': errors.password,
                                }"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <!-- Password Confirmation -->
                        <div class="grid gap-2">
                            <Label for="password_confirmation">
                                {{
                                    t(
                                        'auth.register.password_confirmation',
                                        'Confirm Password'
                                    )
                                }}
                                <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                :tabindex="8"
                                autocomplete="new-password"
                                :placeholder="
                                    t(
                                        'auth.placeholders.password',
                                        'Confirm password'
                                    )
                                "
                                :class="{
                                    'border-destructive':
                                        errors.password_confirmation,
                                }"
                            />
                            <InputError
                                :message="errors.password_confirmation"
                            />
                        </div>

                        <!-- Submit Button -->
                        <Button
                            type="submit"
                            class="mt-2 w-full bg-[#25D366] hover:bg-[#128C7E]"
                            :tabindex="9"
                            :disabled="processing"
                            data-test="register-user-button"
                        >
                            <Loader2
                                v-if="processing"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            {{
                                t('auth.register.submit', 'Create Account')
                            }}
                        </Button>
                    </div>

                    <!-- Login Link -->
                    <div class="text-center text-sm text-muted-foreground">
                        {{
                            t(
                                'auth.register.have_account',
                                'Already have an account?'
                            )
                        }}
                        <Link
                            :href="login()"
                            :tabindex="10"
                            class="font-medium text-[#25D366] hover:text-[#128C7E] hover:underline"
                        >
                            {{ t('auth.register.login', 'Log in') }}
                        </Link>
                    </div>
                </Form>
            </div>
        </div>
    </LandingLayout>
</template>
