# Motion-v animation layer — short design

Date: 2026-11-20

## Intent and why

The demo site currently switches pricing tabs, shows validation errors, and mutates admin lists with instant Vue state changes. Add an animation library so those interactions feel responsive and polished, matching the Lambda-inspired visual style, without changing data flow, validation, or the public/admin split.

## Goals

- Install `motion-v` (with its `@vueuse/core` peer) and register it in `resources/js/app.js`.
- Animate the public pricing experience: tab indicator/selection, row changes when switching GPU count, and entrance of hero feature cards.
- Animate admin feedback: hover/tap on buttons, inline validation error appearance, and row removal on delete.
- Keep motion subtle (150–250 ms) and respect `prefers-reduced-motion`.

## Non-goals

- No new pages, routes, props, or data-fetching changes.
- No change to validation rules, uploads, seed data, or styling tokens.
- No page-transition router integration.

## Constraints

- Vue 3.5, Vite 5, `npm.cmd` only; `motion-v` 2.13.0 requires `@vueuse/core >=10`.
- Inertia pages resolve from `resources/js/Pages/**/*.vue`; no Ziggy.
- Existing Tailwind tokens must stay unchanged: `canvas #0A0A0A`, `panel #151518`, `hairline #2A2A2E`, `acid #B4FF39`.

## Approaches

1. **`motion-v` (chosen)** — active Motion library for Vue; component-based `motion` elements and layout animations handle tab/row transitions cleanly. Costs a dependency plus `@vueuse/core`.
2. `@vueuse/motion` — directive-based and smaller to wire in, but fewer layout-transition capabilities for the tab table. Rejected.
3. CSS-only transitions — no framework, does not satisfy the request. Rejected.

## Design

- Add `motion-v` and `@vueuse/core` to `package.json`; `app.use(MotionPlugin)` in `app.js`.
- In `PricingTable.vue`, wrap the tab bar in a motion layout group so the active pill slides, and animate table rows on GPU-count change with a short fade/translate so filtering reads as a transition rather than a jump.
- In `Instances.vue`, give hero/feature cards a staggered entrance; keep copy and layout identical.
- In admin pages, add hover/tap feedback to primary buttons and a small fade/slide for error messages and row removal, using the same duration tokens.
- Centralize duration/easing in a tiny JS constant so values stay consistent; honor `prefers-reduced-motion` by disabling transforms when set.

## What's left to the builder

- Exact motion component/directive syntax per file and the shared constant's location.
- Verify the production build and full test suite still pass.
