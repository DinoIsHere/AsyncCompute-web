<script setup>
import { onMounted, ref, watch } from 'vue';

/**
 * CountUp — animates the numeric portion of a formatted value
 * (e.g. "$12,345" or "99.9%") from 0 to the target using rAF.
 * Falls back to static text when reduced motion is preferred.
 */
const props = defineProps({
    value: {
        type: [String, Number],
        required: true,
    },
    duration: {
        type: Number,
        default: 0.9,
    },
});

const PREFIX_RE = /^([^\d]*)([\d,.]+)(.*)$/;

const prefix = ref('');
const suffix = ref('');
const display = ref('');

function formatNumber(num) {
    const target = props.value.match(PREFIX_RE);
    const decimals = target ? (target[2].split('.')[1] ?? '').length : 0;
    return num.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

function parse() {
    const match = String(props.value).match(PREFIX_RE);
    if (!match) return null;
    const num = parseFloat(match[2].replace(/,/g, ''));
    return Number.isNaN(num) ? null : { prefix: match[1], num, suffix: match[3] };
}

function animate() {
    const parsed = parse();
    if (!parsed) {
        display.value = String(props.value);
        return;
    }

    prefix.value = parsed.prefix;
    suffix.value = parsed.suffix;

    const reduced =
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduced) {
        display.value = formatNumber(parsed.num);
        return;
    }

    const start = performance.now();
    const durationMs = props.duration * 1000;

    function tick(now) {
        const t = Math.min(1, (now - start) / durationMs);
        const eased = 1 - Math.pow(1 - t, 3); // easeOutCubic
        display.value = formatNumber(parsed.num * eased);
        if (t < 1) requestAnimationFrame(tick);
    }

    requestAnimationFrame(tick);
}

onMounted(animate);
watch(() => props.value, animate);
</script>

<template>
    <span class="tabular-nums">{{ prefix }}{{ display }}{{ suffix }}</span>
</template>
