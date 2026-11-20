// Shared, subtle motion tokens. All timings stay in the 150–250 ms range.
export const MOTION = {
    duration: 0.2,
    ease: 'easeOut',
    enter: { opacity: 0, y: 8 },
    visible: { opacity: 1, y: 0 },
    exit: { opacity: 0, y: -4 },
};

export function prefersReducedMotion() {
    return (
        typeof window !== 'undefined' &&
        typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

// v-bind this on buttons for a small hover/tap response.
export function hoverProps() {
    return prefersReducedMotion()
        ? {}
        : { whileHover: { scale: 1.02 }, whileTap: { scale: 0.98 }, transition: { duration: 0.12 } };
}

// v-bind this on any motion element that should fade/slide into view.
export function enterProps(options = {}) {
    const { delay = 0, ...transition } = options;

    if (prefersReducedMotion()) {
        return { initial: false, animate: MOTION.visible, transition: { duration: 0 } };
    }

    return {
        initial: MOTION.enter,
        animate: MOTION.visible,
        transition: { duration: MOTION.duration, ease: MOTION.ease, delay, ...transition },
    };
}
