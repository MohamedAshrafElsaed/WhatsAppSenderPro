<script lang="ts" setup>
import UserInfo from '@/components/UserInfo.vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { useTranslation } from '@/composables/useTranslation';
import { logout } from '@/routes';
import { edit } from '@/routes/dashboard/settings/profile';
import type { User } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings } from 'lucide-vue-next';

interface Props {
    user: User;
}

defineProps<Props>();

const { t } = useTranslation();

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :show-email="true" :user="user" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link :href="edit()" as="button" class="block w-full" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                {{ t('nav.settings', 'Settings') }}
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            :href="logout()"
            as="button"
            class="block w-full"
            data-test="logout-button"
            @click="handleLogout"
        >
            <LogOut class="mr-2 h-4 w-4" />
            {{ t('auth.logout', 'Log out') }}
        </Link>
    </DropdownMenuItem>
</template>
