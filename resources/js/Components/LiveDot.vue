<script setup>
/**
 * LiveDot — pulsing status dot for "live" indicators.
 * Pure CSS animation; static under reduced motion.
 */
defineProps({
    color: {
        type: String,
        default: '#B4FF39',
    },
});
</script>

<template>
    <span class="relative inline-flex h-2 w-2" aria-hidden="true">
        <span
            class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-60"
            :style="{ backgroundColor: color }"
        ></span>
        <span
            class="relative inline-flex h-2 w-2 rounded-full"
            :style="{ backgroundColor: color }"
        ></span>
    </span>
</template>
