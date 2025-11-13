<script lang="ts" setup>
import { computed, type HTMLAttributes, onMounted, onUnmounted, ref } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class']
    orientation?: 'vertical' | 'horizontal' | 'both'
    hideScrollbar?: boolean
}>();

const scrollContainer = ref<HTMLElement>();
const showScrollbar = ref(false);
const scrollbarThumbHeight = ref(0);
const scrollbarThumbTop = ref(0);
const isScrolling = ref(false);
let scrollTimeout: ReturnType<typeof setTimeout>;

const scrollbarStyles = computed(() => {
    if (props.hideScrollbar || !showScrollbar.value) {
        return { display: 'none' };
    }
    return {};
});

const thumbStyles = computed(() => ({
    height: `${scrollbarThumbHeight.value}%`,
    top: `${scrollbarThumbTop.value}%`
}));

const handleScroll = () => {
    if (!scrollContainer.value) return;

    const { scrollTop, scrollHeight, clientHeight } = scrollContainer.value;

    // Show scrollbar when scrolling
    showScrollbar.value = scrollHeight > clientHeight;
    isScrolling.value = true;

    // Calculate thumb size and position
    const scrollPercentage = (scrollTop / (scrollHeight - clientHeight)) * 100;
    const thumbHeight = (clientHeight / scrollHeight) * 100;

    scrollbarThumbHeight.value = Math.max(thumbHeight, 20); // Minimum thumb height
    scrollbarThumbTop.value = (scrollPercentage / 100) * (100 - scrollbarThumbHeight.value);

    // Hide scrollbar after scrolling stops
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(() => {
        isScrolling.value = false;
    }, 1000);
};

const checkScrollable = () => {
    if (!scrollContainer.value) return;
    const { scrollHeight, clientHeight } = scrollContainer.value;
    showScrollbar.value = scrollHeight > clientHeight;
    handleScroll();
};

onMounted(() => {
    checkScrollable();
    window.addEventListener('resize', checkScrollable);

    // Check scrollable after content loads
    setTimeout(checkScrollable, 100);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkScrollable);
    clearTimeout(scrollTimeout);
});
</script>

<template>
    <div :class="cn('relative overflow-hidden', props.class)">
        <div
            ref="scrollContainer"
            class="h-full w-full overflow-auto scrollbar-hide"
            @scroll="handleScroll"
        >
            <slot />
        </div>

        <!-- Custom Scrollbar -->
        <div
            v-if="props.orientation !== 'horizontal'"
            :class="cn(
        'absolute right-0 top-0 z-10 h-full w-2 transition-opacity',
        isScrolling ? 'opacity-100' : 'opacity-0 hover:opacity-100'
      )"
            :style="scrollbarStyles"
        >
            <div
                :style="thumbStyles"
                class="absolute right-0 top-0 w-full bg-gray-200 dark:bg-gray-700 rounded-full transition-all duration-200"
            />
        </div>
    </div>
</template>

<style scoped>
/* Hide default scrollbar */
.scrollbar-hide {
    -ms-overflow-style: none; /* IE and Edge */
    scrollbar-width: none; /* Firefox */
}

.scrollbar-hide::-webkit-scrollbar {
    display: none; /* Chrome, Safari and Opera */
}
</style>
