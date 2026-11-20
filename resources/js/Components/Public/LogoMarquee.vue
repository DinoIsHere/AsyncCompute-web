<script setup>
import { computed } from 'vue';

/**
 * LogoMarquee — classic "Trusted by teams at" strip with an infinite
 * rolling brand row. Pure CSS animation (duplicated track), pauses on
 * hover, and renders as a static wrapped row when the user prefers
 * reduced motion.
 */
const props = defineProps({
    brands: {
        type: Array,
        required: true,
    },
    /** Duration of one full loop, in seconds */
    duration: {
        type: Number,
        default: 28,
    },
});

const reducedMotion =
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Duplicate the list so the -50% translate loops seamlessly.
const track = computed(() => [...props.brands, ...props.brands]);

const trackStyle = reducedMotion
    ? {}
    : {
          animation: `marquee-scroll ${props.duration}s linear infinite`,
      };

function onPauseToggle(e) {
    if (reducedMotion) return;
    e.currentTarget.style.animationPlayState =
        e.type === 'mouseenter' ? 'paused' : 'running';
}
</script>

<template>
    <section class="border-b border-hairline py-10 sm:py-12" aria-label="Trusted by">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <p
                class="text-center text-xs font-semibold uppercase tracking-[0.3em] text-zinc-500"
            >
                Trusted by teams building with GPUs
            </p>

            <div
                class="marquee-mask relative mt-8 overflow-hidden"
                @mouseenter="onPauseToggle"
                @mouseleave="onPauseToggle"
            >
                <div class="marquee-track flex w-max items-center" :style="trackStyle">
                    <div
                        v-for="(brand, i) in track"
                        :key="`${brand.name}-${i}`"
                        class="flex shrink-0 items-center gap-2.5 px-7 sm:px-10"
                    >
                        <!-- Inline SVG glyph -->
                        <span
                            class="grid h-6 w-6 place-items-center text-zinc-500"
                            v-html="brand.icon"
                        ></span>
                        <span
                            class="whitespace-nowrap text-sm font-semibold tracking-wide text-zinc-500 transition-colors duration-200 hover:text-zinc-200"
                        >
                            {{ brand.name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style>
@keyframes marquee-scroll {
    from {
        transform: translateX(0);
    }
    to {
        transform: translateX(-50%);
    }
}

/* Fade edges so logos melt into the page instead of hard-clipping */
.marquee-mask {
    mask-image: linear-gradient(
        to right,
        transparent,
        black 12%,
        black 88%,
        transparent
    );
    -webkit-mask-image: linear-gradient(
        to right,
        transparent,
        black 12%,
        black 88%,
        transparent
    );
}

@media (prefers-reduced-motion: reduce) {
    .marquee-track {
        animation: none !important;
        flex-wrap: wrap;
        justify-content: center;
        width: auto;
    }
}
</style>
