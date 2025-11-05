<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

// Get user initials from first_name and last_name
const userInitials = computed(() => {
    if (!user.value) return '??';

    const firstName = user.value.first_name || '';
    const lastName = user.value.last_name || '';

    return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase();
});

// Get full name
const fullName = computed(() => {
    if (!user.value) return 'Guest';
    return user.value.full_name || `${user.value.first_name} ${user.value.last_name}`;
});
</script>

<template>
    <div v-if="user" class="flex items-center gap-3">
        <Avatar class="size-8">
            <AvatarFallback class="text-xs">
                {{ userInitials }}
            </AvatarFallback>
        </Avatar>
        <div class="flex flex-col">
            <span class="text-sm font-medium leading-none">{{ fullName }}</span>
            <span class="text-xs text-muted-foreground">{{ user.email }}</span>
        </div>
    </div>
</template>
