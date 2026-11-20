<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * Dependency-free WebGL background: an animated flowing "aurora" of soft
 * light blobs, in the spirit of lambda.design-style hero backdrops.
 * Renders at a capped resolution, pauses when offscreen/hidden, and is
 * static when the user prefers reduced motion.
 */
const canvasRef = ref(null);

let gl = null;
let rafId = null;
let resizeObserver = null;
let program = null;
let uniforms = {};
let running = false;
let startTime = 0;

const VERT_SRC = `
attribute vec2 aPosition;
void main() {
    gl_Position = vec4(aPosition, 0.0, 1.0);
}
`;

// Fragment shader: domain-warped fbm producing smooth drifting color fields.
const FRAG_SRC = `
precision highp float;

uniform vec2 uResolution;
uniform float uTime;

// Cheap 2D hash + value noise
float hash(vec2 p) {
    p = fract(p * vec2(234.34, 435.345));
    p += dot(p, p + 34.23);
    return fract(p.x * p.y);
}

float noise(vec2 p) {
    vec2 i = floor(p);
    vec2 f = fract(p);
    vec2 u = f * f * (3.0 - 2.0 * f);
    return mix(
        mix(hash(i), hash(i + vec2(1.0, 0.0)), u.x),
        mix(hash(i + vec2(0.0, 1.0)), hash(i + vec2(1.0, 1.0)), u.x),
        u.y
    );
}

float fbm(vec2 p) {
    float value = 0.0;
    float amplitude = 0.5;
    for (int i = 0; i < 5; i++) {
        value += amplitude * noise(p);
        p = p * 2.03 + vec2(17.3, 9.1);
        amplitude *= 0.5;
    }
    return value;
}

void main() {
    vec2 uv = gl_FragCoord.xy / uResolution.xy;
    vec2 p = uv * vec2(uResolution.x / uResolution.y, 1.0);

    float t = uTime * 0.05;

    // Domain warp: flow the noise field through itself for organic motion
    vec2 q = vec2(fbm(p * 1.6 + t), fbm(p * 1.6 - t * 0.7 + 4.7));
    vec2 r = vec2(fbm(p * 2.2 + q * 1.4 + vec2(1.7, 9.2) + t * 1.3),
                  fbm(p * 2.2 + q * 1.4 + vec2(8.3, 2.8) - t * 0.9));
    float f = fbm(p * 1.8 + r * 1.6);

    // Palette: near-black canvas -> deep green-teal -> acid highlight
    vec3 base = vec3(0.039, 0.039, 0.039);        // #0A0A0A
    vec3 deep = vec3(0.031, 0.118, 0.086);        // deep green
    vec3 mid  = vec3(0.086, 0.294, 0.235);        // teal-green
    vec3 acid = vec3(0.706, 1.0, 0.224);          // #B4FF39

    vec3 color = base;
    color = mix(color, deep, smoothstep(0.15, 0.55, f) * 0.9);
    color = mix(color, mid, smoothstep(0.45, 0.8, f) * 0.55);

    // Sparse acid highlights where the warp field peaks
    float glow = smoothstep(0.62, 0.95, f) * smoothstep(0.95, 0.7, r.x);
    color = mix(color, acid, glow * 0.35);

    // Concentrate light toward the top of the hero, fading down
    color *= mix(0.35, 1.15, 1.0 - uv.y * 0.6);

    // Subtle vignette so edges melt into the canvas background
    vec2 c = uv - 0.5;
    float vignette = 1.0 - dot(c, c) * 0.9;
    color *= vignette;

    // Keep overall brightness low; this is a backdrop, not a light show
    color = mix(base, color, 0.9);

    gl_FragColor = vec4(color, 1.0);
}
`;

function compileShader(type, src) {
    const shader = gl.createShader(type);
    gl.shaderSource(shader, src);
    gl.compileShader(shader);
    if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
        console.error('WebGL shader error:', gl.getShaderInfoLog(shader));
        return null;
    }
    return shader;
}

function resize() {
    const canvas = canvasRef.value;
    if (!canvas || !gl) return;
    // Cap resolution for performance; blur-smooth content hides the scaling
    const scale = Math.min(window.devicePixelRatio || 1, 1.5) * 0.5;
    const width = Math.max(1, Math.floor(canvas.clientWidth * scale));
    const height = Math.max(1, Math.floor(canvas.clientHeight * scale));
    if (canvas.width !== width || canvas.height !== height) {
        canvas.width = width;
        canvas.height = height;
        gl.viewport(0, 0, width, height);
    }
}

function frame(now) {
    if (!running) return;
    gl.uniform2f(uniforms.uResolution, gl.drawingBufferWidth, gl.drawingBufferHeight);
    gl.uniform1f(uniforms.uTime, (now - startTime) / 1000);
    gl.drawArrays(gl.TRIANGLES, 0, 6);
    rafId = requestAnimationFrame(frame);
}

function start() {
    if (running || !gl) return;
    running = true;
    startTime = performance.now();
    rafId = requestAnimationFrame(frame);
}

function stop() {
    running = false;
    if (rafId) cancelAnimationFrame(rafId);
    rafId = null;
}

onMounted(() => {
    const canvas = canvasRef.value;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    gl = canvas.getContext('webgl', { antialias: false, alpha: false, powerPreference: 'low-power' });
    if (!gl) return; // Graceful fallback: static gradient beneath stays visible

    const vert = compileShader(gl.VERTEX_SHADER, VERT_SRC);
    const frag = compileShader(gl.FRAGMENT_SHADER, FRAG_SRC);
    if (!vert || !frag) return;

    program = gl.createProgram();
    gl.attachShader(program, vert);
    gl.attachShader(program, frag);
    gl.linkProgram(program);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
        console.error('WebGL program error:', gl.getProgramInfoLog(program));
        return;
    }
    gl.useProgram(program);

    // Fullscreen triangle pair
    const buffer = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
    gl.bufferData(
        gl.ARRAY_BUFFER,
        new Float32Array([-1, -1, 1, -1, -1, 1, -1, 1, 1, -1, 1, 1]),
        gl.STATIC_DRAW
    );
    const loc = gl.getAttribLocation(program, 'aPosition');
    gl.enableVertexAttribArray(loc);
    gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);

    uniforms = {
        uResolution: gl.getUniformLocation(program, 'uResolution'),
        uTime: gl.getUniformLocation(program, 'uTime'),
    };

    resize();
    resizeObserver = new ResizeObserver(resize);
    resizeObserver.observe(canvas);

    if (reducedMotion) {
        // Draw a single static frame
        gl.uniform2f(uniforms.uResolution, gl.drawingBufferWidth, gl.drawingBufferHeight);
        gl.uniform1f(uniforms.uTime, 12.0);
        gl.drawArrays(gl.TRIANGLES, 0, 6);
    } else {
        // Pause when the tab is hidden
        document.addEventListener('visibilitychange', () => {
            document.hidden ? stop() : start();
        });
        start();
    }
});

onBeforeUnmount(() => {
    stop();
    if (resizeObserver) resizeObserver.disconnect();
});
</script>

<template>
    <div class="absolute inset-0" aria-hidden="true">
        <!-- Static gradient fallback (and underlay) -->
        <div
            class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(180,255,57,0.10),transparent_55%)]"
        ></div>
        <canvas ref="canvasRef" class="absolute inset-0 h-full w-full"></canvas>
    </div>
</template>
