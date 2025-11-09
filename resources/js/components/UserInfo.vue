<script lang="ts" setup>
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import type { User } from '@/types';
import { computed } from 'vue';

interface Props {
    user: User;
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

// Get user initials from first_name and last_name or name
const userInitials = computed(() => {
    if (!props.user) return '??';

    // Try first_name + last_name
    if (props.user.first_name && props.user.last_name) {
        return `${props.user.first_name.charAt(0)}${props.user.last_name.charAt(0)}`.toUpperCase();
    }

    // Fall back to name
    if (props.user.name) {
        const parts = props.user.name.split(' ');
        if (parts.length >= 2) {
            return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.toUpperCase();
        }
        return props.user.name.substring(0, 2).toUpperCase();
    }

    return '??';
});

// Get full name
const fullName = computed(() => {
    if (!props.user) return 'Guest';

    if (props.user.full_name) {
        return props.user.full_name;
    }

    if (props.user.first_name && props.user.last_name) {
        return `${props.user.first_name} ${props.user.last_name}`;
    }

    return props.user.name || 'Guest';
});
</script>

<template>
    <div v-if="user" class="flex items-center gap-3">
        <Avatar class="size-8">
            <AvatarImage
                v-if="user.avatar"
                :alt="fullName"
                :src="user.avatar"
            />
            <AvatarFallback class="bg-[#25D366] text-xs text-white">
                {{ userInitials }}
            </AvatarFallback>
        </Avatar>
        <div class="flex flex-col">
            <span class="text-sm leading-none font-medium">{{ fullName }}</span>
            <span v-if="showEmail" class="text-xs text-muted-foreground">{{
                user.email
            }}</span>
        </div>
    </div>
</template>
