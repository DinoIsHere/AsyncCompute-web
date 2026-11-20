# Motion-v Animation Layer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add subtle `motion-v` animations to pricing tabs, table filtering, feature cards, and admin feedback without changing data flow or validation.

**Architecture:** Register MotionPlugin once in `app.js`, centralize timing in a small shared constant, and apply `motion` elements in the existing Vue components. No props, routes, controllers, or backend tests change.

**Tech Stack:** Vue 3.5, `motion-v` 2.13.0, `@vueuse/core` >=10, Vite 5, Tailwind 3.4.

**Spec:** `docs/superpowers/specs/2026-11-20-motion-v-animation-design.md`

## Global Constraints

- Vue 3.5 / Vite 5; `npm.cmd` only (PowerShell blocks `npm.ps1`).
- `motion-v` 2.13.0 requires `@vueuse/core >=10`; install both.
- Tailwind tokens unchanged: `canvas #0A0A0A`, `panel #151518`, `hairline #2A2A2E`, `acid #B4FF39`.
- Durations 150–250 ms; respect `prefers-reduced-motion`.
- No changes to routes, controllers, request rules, seeders, or Inertia props.

## Review Focus

- `motion-v` components must not break SSR-less Inertia mount or table markup — verify `npm.cmd run build` and a browser load of `/instances` and `/admin/instances`.
- Reduced-motion users must still see content (no invisible elements) — keep opacity targets reachable even when transforms are disabled.
- Filtering to a GPU count with zero plans must not throw — existing empty-state row stays.
- Admin inline errors must still render while animating — motion wrappers cannot swallow error text.
- File uploads in `Form.vue` must keep working with `forceFormData` after wrapping.

---

### Task 1: Wire motion-v into public and admin UI

**Files:**
- Modify: `package.json`, `package-lock.json`, `resources/js/app.js`
- Create: `resources/js/utils/motion.js`
- Modify: `resources/js/Components/Public/PricingTable.vue`, `resources/js/Pages/Public/Instances.vue`
- Modify: `resources/js/Pages/Admin/Instances/Index.vue`, `resources/js/Pages/Admin/Instances/Form.vue`, `resources/js/Layouts/AdminLayout.vue`
- Test: verification via `npm.cmd run build`, `php artisan test`, and browser load

**Interfaces:**
- Consumes: existing Vue components and Tailwind tokens from the base app.
- Produces: `resources/js/utils/motion.js` exporting `MOTION = { duration: 0.2, ease: 'easeOut', enter: { opacity: 0, y: 8 }, visible: { opacity: 1, y: 0 } }` and `prefersReducedMotion(): boolean`.

- [ ] **Step 1: Install dependencies**

Run `npm.cmd install motion-v@^2.13.0 @vueuse/core@^10`. Confirm both appear in `package.json`.

- [ ] **Step 2: Register the plugin**

In `resources/js/app.js`, import `MotionPlugin` from `motion-v` and add `.use(MotionPlugin)` to the Inertia app setup chain. Verify no other app wiring changes.

- [ ] **Step 3: Add the shared motion constants**

Create `resources/js/utils/motion.js` with the exact exports in Interfaces. Use `window.matchMedia('(prefers-reduced-motion: reduce)').matches` guarded for SSR-less browser use.

- [ ] **Step 4: Animate the public pricing table**

In `PricingTable.vue`, give the active tab pill a shared `layoutId` so it slides between counts; wrap filtered rows in `AnimatePresence` and render each row as a `motion.tr` keyed by plan id with the enter/visible variants from `MOTION`. Keep the existing empty-state row and stock badge logic unchanged.

- [ ] **Step 5: Animate feature cards**

In `Instances.vue`, wrap the three feature cards in `motion.div` with a short staggered entrance using `MOTION`; keep copy and layout identical.

- [ ] **Step 6: Animate admin feedback**

In `AdminLayout.vue`, `Index.vue`, and `Form.vue`, add hover/tap scale on primary buttons and an opacity/y transition on error blocks and removed rows, using `MOTION`. Do not change form fields, `useForm` state, or submit URLs.

- [ ] **Step 7: Verify**

Run `npm.cmd run build` and `C:\xampp\php\php.exe artisan test`. Expected: build succeeds; all tests pass. Load `/instances` and `/admin/instances` in a browser and confirm tabs animate, rows re-render, and no console errors.

- [ ] **Step 8: Commit**

```powershell
git add -A
git commit -m "feat: add motion-v animations to public and admin UI"
```
