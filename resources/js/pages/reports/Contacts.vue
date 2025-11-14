<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as dashboard } from '@/routes/dashboard';
import { index as reportsIndex } from '@/routes/dashboard/reports';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircle,
    Download,
    FileUp,
    Tag,
    TrendingUp,
    UserCheck,
    UserPlus,
    Users,
    XCircle,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface ContactStats {
    total: number;
    validated: number;
    invalid: number;
    validation_percentage: number;
    new_this_period: number;
    growth_rate: number;
}

interface ContactSource {
    source: string;
    count: number;
    percentage: number;
}

interface TopTag {
    name: string;
    count: number;
}

interface Props {
    stats: ContactStats;
    sources: ContactSource[];
    topTags: TopTag[];
    dateRange: {
        start: string;
        end: string;
    };
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('dashboard.title', 'Dashboard'), href: dashboard().url },
    { title: t('reports.title', 'Reports & Analytics'), href: reportsIndex().url },
    { title: t('reports.contacts', 'Contact Reports') },
];

const startDate = ref(props.dateRange.start);
const endDate = ref(props.dateRange.end);

const applyDateFilter = () => {
    router.get(
        location.pathname,
        {
            start_date: startDate.value,
            end_date: endDate.value,
        },
        {
            preserveState: true,
        },
    );
};

const exportReport = () => {
    window.location.href = `/dashboard/reports/export?type=contacts&start_date=${startDate.value}&end_date=${endDate.value}`;
};

const formatNumber = (num: number): string => {
    return new Intl.NumberFormat().format(num);
};

const getSourceIcon = (source: string) => {
    switch (source.toLowerCase()) {
        case 'import':
        case 'csv':
        case 'excel':
            return FileUp;
        case 'manual':
            return UserPlus;
        default:
            return Users;
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('reports.contacts', 'Contact Reports')" />

        <div
            :class="isRTL() ? 'text-right' : 'text-left'"
            class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6"
        >
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <Heading
                    :description="t('reports.contacts_description', 'Analyze your contact database and growth metrics')"
                    :title="t('reports.contacts', 'Contact Reports')"
                />

                <Button class="bg-[#25D366] hover:bg-[#128C7E]" @click="exportReport">
                    <Download :class="isRTL() ? 'ml-2' : 'mr-2'" class="h-4 w-4" />
                    {{ t('common.export', 'Export') }}
                </Button>
            </div>

            <!-- Date Range Filter -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('reports.date_range', 'Date Range') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex flex-wrap gap-4">
                        <div class="flex-1">
                            <Label>{{ t('reports.start_date', 'Start Date') }}</Label>
                            <Input v-model="startDate" type="date" />
                        </div>
                        <div class="flex-1">
                            <Label>{{ t('reports.end_date', 'End Date') }}</Label>
                            <Input v-model="endDate" type="date" />
                        </div>
                        <div class="flex items-end">
                            <Button @click="applyDateFilter">
                                {{ t('common.apply', 'Apply') }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Contact Stats Overview -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Contacts -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('contacts.total_contacts', 'Total Contacts') }}
                        </CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-[#25D366]">
                            {{ formatNumber(stats.total) }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('contacts.in_database', 'In your database') }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Validated Contacts -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('contacts.validated', 'Validated') }}
                        </CardTitle>
                        <UserCheck class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">
                            {{ formatNumber(stats.validated) }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ stats.validation_percentage }}% {{ t('contacts.validated', 'validated') }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Invalid Contacts -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('contacts.invalid', 'Invalid') }}
                        </CardTitle>
                        <XCircle class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">
                            {{ formatNumber(stats.invalid) }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ Math.round((stats.invalid / stats.total) * 100) }}% {{ t('contacts.invalid', 'invalid') }}
                        </p>
                    </CardContent>
                </Card>

                <!-- New Contacts -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('contacts.new_contacts', 'New Contacts') }}
                        </CardTitle>
                        <UserPlus class="h-4 w-4 text-blue-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">
                            {{ formatNumber(stats.new_this_period) }}
                        </div>
                        <div class="flex items-center gap-1 text-xs text-muted-foreground">
                            <TrendingUp
                                :class="stats.growth_rate >= 0 ? 'text-green-600' : 'text-red-600'"
                                class="h-3 w-3"
                            />
                            <span>{{ stats.growth_rate >= 0 ? '+' : '' }}{{ stats.growth_rate }}% {{ t('reports.growth', 'growth') }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Validation Progress -->
            <Card class="border-t-4 border-t-[#25D366]">
                <CardHeader>
                    <CardTitle>{{ t('contacts.validation_status', 'Validation Status') }}</CardTitle>
                    <CardDescription>
                        {{ t('contacts.validation_desc', 'WhatsApp number validation progress') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl font-bold text-green-600">
                                {{ stats.validation_percentage }}%
                            </span>
                            <Badge variant="default" class="text-sm">
                                {{ formatNumber(stats.validated) }} / {{ formatNumber(stats.total) }}
                                {{ t('contacts.validated', 'validated') }}
                            </Badge>
                        </div>
                        <Progress :model-value="stats.validation_percentage" class="bg-green-600" />
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="h-3 w-3 rounded-full bg-green-600"></div>
                                <span>{{ formatNumber(stats.validated) }} {{ t('contacts.valid', 'Valid') }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-3 w-3 rounded-full bg-red-600"></div>
                                <span>{{ formatNumber(stats.invalid) }} {{ t('contacts.invalid', 'Invalid') }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Contact Sources -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('contacts.sources', 'Contact Sources') }}</CardTitle>
                    <CardDescription>
                        {{ t('contacts.sources_desc', 'Where your contacts are coming from') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="source in sources"
                            :key="source.source"
                            class="flex items-center justify-between rounded-lg border p-4"
                        >
                            <div class="flex items-center gap-3">
                                <component :is="getSourceIcon(source.source)" class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="font-medium capitalize">{{ source.source }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ formatNumber(source.count) }} {{ t('contacts.title', 'contacts') }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-[#25D366]">
                                    {{ source.percentage }}%
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="sources.length === 0"
                            class="py-8 text-center text-muted-foreground"
                        >
                            {{ t('contacts.no_sources', 'No contact sources available') }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Top Tags -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('contacts.top_tags', 'Top Tags') }}</CardTitle>
                    <CardDescription>
                        {{ t('contacts.top_tags_desc', 'Most commonly used contact tags') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-2">
                        <Badge
                            v-for="tag in topTags"
                            :key="tag.name"
                            variant="secondary"
                            class="text-sm px-3 py-1"
                        >
                            {{ tag.name }}
                            <span class="ml-2 text-xs opacity-70">({{ formatNumber(tag.count) }})</span>
                        </Badge>

                        <div
                            v-if="topTags.length === 0"
                            class="w-full py-8 text-center text-muted-foreground"
                        >
                            {{ t('contacts.no_tags', 'No tags available') }}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
