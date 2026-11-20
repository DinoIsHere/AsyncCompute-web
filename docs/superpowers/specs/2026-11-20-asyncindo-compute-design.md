# AsyncCompute Web — Design

Date: 2026-11-20
Repo: `D:/Dino/UTS/AsyncCompute-web`

## Intent and why

Build a fictional demo site for **PT. Asyncindo**, a compute-renting company. A visitor should land on a page that feels like Lambda's Instances page — browse GPU plans, compare specs and price per GPU-hour — while an admin can maintain that catalogue from a separate, private area.

This is a showcase/demo, not a live business. PT. Asyncindo is fictional. There is no real money, no real provisioning, and no customer accounts. The point is to demonstrate a complete Laravel + Vue app: relational data flowing from controllers, seeded content, validated forms, uploads, public/admin views, all responsive.

## Goals

- Public instances page modelled on `lambda.com/instances`, using Lambda's visual language (near-black background, neon-lime accent, tight type, card-and-table layout) with Asyncindo branding.
- Admin area to create, edit, delete, and upload images for GPU plans and their GPU-count variants.
- SQLite database with at least two related tables, read through Eloquent in controllers.
- Seed at least five plans so the public page is populated from a fresh `migrate --seed`.
- Server-side validation on every write form, with errors shown inline in Vue.
- Responsive from phone to desktop.

## Non-goals

- No billing, checkout, payments, invoices, or usage metering.
- No real instance provisioning, SSH keys, or cloud API integration.
- No customer registration or dashboards.
- No public REST API.

## Anti-goals

- An empty default page, or a public page that only admins can populate by hand.
- Splitting the UI into Blade on one side and Vue on the other in a way that makes the public page feel stitched together.
- Storing uploads without validation or outside Laravel's storage abstraction.

## Constraints

- PHP 8.0.30 (XAMPP, `C:/xampp/php/php.exe`); Composer 2.8.12; Node 22.20.0.
- PHP 8.0 caps Laravel at **9.x** and `inertiajs/inertia-laravel` at **1.3.4** (its last release supporting PHP ~8.0 and Laravel 9). Vue 3 with `@inertiajs/vue3` 1.x and Vite/Laravel 9 plugin.
- `pdo_sqlite` is present; `gd` is not. Uploads are stored, not resized.
- PowerShell execution policy blocks `npm.ps1`; use `npm.cmd` (or `cmd /c npm`) when running scripts from a shell here.

## Approaches

1. **Inertia + Vue 3 (chosen).** Laravel routes and controllers remain the source of truth. Controllers pass Eloquent models/collections as props to Vue page components; Inertia handles navigation. One app and one Vite build. This is the shortest path to "Eloquent relation taking data from controller" and keeps validation, auth, and uploads in idiomatic Laravel.
2. **Separate Vue SPA + JSON API.** A decoupled Vue app calls Laravel API routes. Cleaner boundary, but needs two dev servers, a Vite proxy, CORS/sanctum decisions, and substantially more code for the same visible result. Rejected as overkill for a demo.
3. **Blade public page + Vue islands.** Least JavaScript, but the tabbed pricing table, upload form, and admin CRUD would be split across two rendering systems and would not feel like one coherent Lambda-style experience. Rejected.

## Data model (SQLite)

**`gpu_models`** — one row per GPU plan.
`id`, `name` (unique), `slug` (unique), `vram_gb` (decimal), `vcpus` (integer), `ram_gb` (integer), `storage` (string), `price_per_gpu_hour` (decimal 8,2), `description` (text), `image_path` (string, nullable), `is_active` (boolean, default true), timestamps.

**`instance_offerings`** — the 1x / 2x / 4x / 8x variants of a plan.
`id`, `gpu_model_id` (foreign key → `gpu_models.id`, cascade delete), `gpu_count` (tinyint, one of 1/2/4/8), `region` (string), `stock` (integer), `sort_order` (integer), timestamps.

Relations:
- `GpuModel::offerings()` → `hasMany(InstanceOffering::class)`
- `InstanceOffering::gpuModel()` → `belongsTo(GpuModel::class)`

**`users`** — a single seeded admin account for the private area. `name`, `email`, `password`, timestamps. This is a third table; the two-table requirement is met by the first two.

## Pages and data flow

Public:
- `GET /` redirects to `GET /instances`.
- `InstanceController@index` loads `GpuModel::with('offerings')->where('is_active', true)->orderBy('price_per_gpu_hour')->get()` and passes it to the `Public/Instances` Vue page as props. No client-side fetching of the catalogue.
- **Tab behaviour (my call):** the Vue page builds a map of plans that have an offering for each of 1x/2x/4x/8x. Selecting a tab filters the table to plans with an offering at that GPU count. Each row shows the plan's specs and `price_per_gpu_hour` from `gpu_models`, plus a stock/availability badge taken from that plan's offering for the selected count. The table stays one row per plan; `image_path` is used in the admin list and edit form only, not in the public table.

Admin (behind session auth):
- `GET /admin/login`, `POST /admin/login`, `POST /admin/logout`.
- `GET /admin` — dashboard with counts of plans, offerings, and inactive plans.
- `GET /admin/instances` — table of plans with edit/delete.
- `GET /admin/instances/create`, `POST /admin/instances` — create form with an `is_active` checkbox, image upload, and a repeatable offering rows editor.
- `GET /admin/instances/{gpuModel}/edit`, `PUT/PATCH`, `DELETE` — edit/delete. Store and update run in a DB transaction so a plan and its offerings are written together.

## Public page layout (mirrors lambda.com/instances)

```
┌──────────────────────────────────────────────────────────────┐
│ ▲ asyncindo   Instances  Compare  Docs  Company   Log in  Get started │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│   Compute on demand                          [ Launch instance ]│
│   Train, fine-tune, and serve on 1–8 GPU instances            │
│                                                              │
│   ┌──────────────┐ ┌──────────────┐ ┌──────────────┐        │
│   │ Launch in    │ │ Multi-GPU    │ │ Use UI, API, │        │
│   │ minutes      │ │ instances    │ │ or CLI       │        │
│   └──────────────┘ └──────────────┘ └──────────────┘        │
│                                                              │
│   Pay by the minute                                          │
│   [ 8x ] [ 4x ] [ 2x ] [ 1x ]                                │
│   ┌────────────────────────────────────────────────────────┐ │
│   │ PLAN | VRAM/GPU | vCPUs | RAM | STORAGE | PRICE/GPU/HR │ │
│   │ ... rows rendered from gpu_models + offering stock ... │ │
│   └────────────────────────────────────────────────────────┘ │
│                                                              │
│   Purpose-built for AI  ·  Private networking  ·  Observability │
│                                                              │
│   Ready to get started?  [ Launch instance ]                 │
├──────────────────────────────────────────────────────────────┤
│ asyncindo   Products   Docs   Company   © 2026 PT. Asyncindo │
└──────────────────────────────────────────────────────────────┘
```

Visual direction: near-black `#0A0A0A` page, dark `#151518` cards with a thin `#2A2A2E` border, lime `#B4FF39` primary buttons and accents, white headings, muted grey body text. Use local copies of reference imagery in `public/images/` rather than hotlinking Lambda's site. Asyncindo wordmark and copy replace Lambda's; the structure and interaction pattern are what is being copied.

**Header links (my call):** "Log in" goes to `/admin/login`. "Get started" and "Launch instance" scroll to the pricing table at `/instances#pricing`. "Compare" is a placeholder anchor for the demo — there is no compare page.

## Admin page layout

Dark sidebar (Dashboard, Instances, Log out) plus a content area. Dashboard shows three stat cards. Instances index is a table with plan image thumbnail, name, price, variant count, and edit/delete actions. Create/edit is a two-column form: plan fields and image upload on the left, repeatable offering rows (`gpu_count`, `region`, `stock`) on the right, with inline field errors and a save button.

## Uploads and validation

- Image upload field on the plan form: `nullable|image|mimes:jpg,jpeg,png,webp|max:2048`, stored with `Storage::disk('public')->putFile('gpu-models', $file)`; only the relative path goes in `image_path`. Edit form shows the current image and allows replacement. Deleting a plan does not auto-delete the file (builder's call to keep or clean up).
- `StoreGpuModelRequest` / `UpdateGpuModelRequest` validate: `name` required, max 120, unique (ignoring current on update); `slug` derived from name; `vram_gb` required numeric min 1; `vcpus` required integer min 1; `ram_gb` required integer min 1; `storage` required string; `price_per_gpu_hour` required numeric min 0; `is_active` required boolean (my call — this is the control for public visibility, and it makes the dashboard's inactive count real); `offerings` required array min 1; `offerings.*.gpu_count` required `in:1,2,4,8`; `offerings.*.region` required string; `offerings.*.stock` required integer min 0.
- Inertia shares validation errors with Vue; the form renders per-field messages and keeps submitted values on failure.

## Seeding

`DatabaseSeeder` calls `UserSeeder` (one admin: `admin@asyncindo.test` / `password`) and `GpuModelSeeder`. The seeder creates six plans — HGX B200, HGX H100, HGX A100 80GB, HGX A100 40GB, GH200, Tesla V100 — with the same spec/price shape as the reference table, each with two to four offerings across 1x/2x/4x/8x and a local placeholder image path. Six plans clears the "at least five" bar.

## Responsive behaviour

Tailwind CSS 3.4 with mobile-first breakpoints. Header collapses to a hamburger below `md`. Feature cards stack on small screens. The pricing table scrolls horizontally on narrow viewports with a sticky first column. Admin sidebar becomes a top bar on mobile; form columns stack.

## What's left to the builder

- Exact Tailwind tokens, component files, and asset filenames.
- The precise marketing copy for the remaining sections, following the reference page's structure.
- Choice of local placeholder images vs downloaded reference images for the seeded plans.
- Tests: at minimum a feature test that seeds the database, loads `/instances`, and confirms five-plus plan names appear, plus validation and upload tests for the admin form.
