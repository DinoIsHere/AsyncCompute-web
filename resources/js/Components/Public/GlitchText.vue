<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * GlitchText — splits text into per-character spans and applies a
 * radial-proximity glitch effect around the cursor: RGB channel split,
 * jitter, and random character scrambling that fades with distance.
 *
 * Respects prefers-reduced-motion (renders plain text).
 */
const props = defineProps({
    text: {
        type: String,
        required: true,
    },
    /** Effect radius in px */
    radius: {
        type: Number,
        default: 160,
    },
});

const rootRef = ref(null);
const charRefs = ref([]);

const GLITCH_CHARS = '01<>_/\\|#{}[]$%&@!?;:~^*+=';
const REDUCED_MOTION =
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

let rafId = null;
let running = false;
let pointer = { x: -99999, y: -99999 };
let resizeObserver = null;
let scrollListener = null;
let moveListener = null;
let states = [];

const chars = props.text.split('').map((ch, i) => ({
    ch,
    key: i,
    isSpace: ch === ' ',
}));

function measure() {
    const root = rootRef.value;
    if (!root) return;
    for (const state of states) {
        const r = state.el.getBoundingClientRect();
        state.cx = r.left + r.width / 2;
        state.cy = r.top + r.height / 2;
    }
}

function resetChar(state) {
    state.el.textContent = state.original;
    state.el.style.textShadow = '';
    state.el.style.transform = '';
    state.scrambled = false;
}

function frame() {
    if (!running) return;

    for (const state of states) {
        const { el, cx, cy } = state;
        const dx = pointer.x - cx;
        const dy = pointer.y - cy;
        const dist = Math.sqrt(dx * dx + dy * dy);
        // Intensity 0..1, eased, with a small soft core around the cursor
        let t = 1 - dist / props.radius;
        if (t <= 0) {
            if (state.scrambled) resetChar(state);
            else {
                el.style.textShadow = '';
                el.style.transform = '';
            }
            continue;
        }
        const intensity = t * t * (3 - 2 * t); // smoothstep

        // --- Character scrambling -------------------------------------
        if (intensity > 0.35 && Math.random() < intensity * 0.12) {
            el.textContent =
                GLITCH_CHARS[(Math.random() * GLITCH_CHARS.length) | 0];
            state.scrambled = true;
        } else if (state.scrambled && Math.random() < 0.25) {
            el.textContent = state.original;
            if (Math.random() < 0.6) state.scrambled = false;
        }

        // --- RGB channel split ----------------------------------------
        const angle = Math.atan2(dy, dx);
        const shift = intensity * 5;
        const sx = Math.cos(angle) * shift;
        const alpha = (intensity * 0.85).toFixed(2);
        el.style.textShadow =
            `${sx.toFixed(1)}px 0 rgba(255, 42, 82, ${alpha}), ` +
            `${(-sx).toFixed(1)}px 0 rgba(64, 255, 218, ${alpha}), ` +
            `0 0 ${(intensity * 14).toFixed(1)}px rgba(180, 255, 57, ` +
            `${(intensity * 0.5).toFixed(2)})`;

        // --- Jitter displacement --------------------------------------
        const jx = (Math.random() - 0.5) * intensity * 4;
        const jy = (Math.random() - 0.5) * intensity * 3;
        el.style.transform = `translate(${jx.toFixed(1)}px, ${jy.toFixed(1)}px)`;
    }

    rafId = requestAnimationFrame(frame);
}

function start() {
    if (running || REDUCED_MOTION) return;
    running = true;
    measure();
    rafId = requestAnimationFrame(frame);
}

function stop() {
    running = false;
    if (rafId) cancelAnimationFrame(rafId);
    rafId = null;
    for (const state of states) {
        if (state.scrambled) state.el.textContent = state.original;
        state.el.style.textShadow = '';
        state.el.style.transform = '';
        state.scrambled = false;
    }
}

function onMove(e) {
    pointer.x = e.clientX;
    pointer.y = e.clientY;
}

onMounted(() => {
    if (REDUCED_MOTION) return;

    states = charRefs.value.filter(Boolean).map((el) => ({
        el,
        original: el.textContent,
        cx: 0,
        cy: 0,
        scrambled: false,
    }));

    measure();

    resizeObserver = new ResizeObserver(measure);
    resizeObserver.observe(rootRef.value);

    // Re-measure on scroll since rects are viewport-relative
    scrollListener = () => measure();
    window.addEventListener('scroll', scrollListener, { passive: true });

    moveListener = onMove;
    window.addEventListener('pointermove', moveListener, { passive: true });

    // Only run the loop while the heading is on screen
    const observer = new IntersectionObserver(
        ([entry]) => (entry.isIntersecting ? start() : stop()),
        { threshold: 0 }
    );
    observer.observe(rootRef.value);

    document.addEventListener('visibilitychange', () => {
        document.hidden ? stop() : start();
    });
});

onBeforeUnmount(() => {
    stop();
    if (resizeObserver) resizeObserver.disconnect();
    if (scrollListener) window.removeEventListener('scroll', scrollListener);
    if (moveListener) window.removeEventListener('pointermove', moveListener);
});
</script>

<template>
    <span ref="rootRef" class="inline-block" aria-label="text">
        <template v-for="(c, i) in chars" :key="i">
            <span v-if="c.isSpace">&nbsp;</span>
            <span v-else ref="charRefs" class="inline-block will-change-transform">{{
                c.ch
            }}</span>
        </template>
    </span>
</template>
