# AsyncCompute Web Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the PT. Asyncindo demo: a Lambda-style public GPU instances page plus a session-authenticated admin catalogue (CRUD, validation, uploads) on Laravel 9 + Vue 3 + Inertia + SQLite.

**Architecture:** Laravel routes and controllers stay the source of truth. Controllers pass Eloquent models/collections to Vue page components as Inertia props; the browser never fetches the catalogue itself. Admin writes go through FormRequests, run in a DB transaction, and store uploads on Laravel's `public` disk. One app, one Vite build.

**Tech Stack:** PHP 8.0.30, Laravel 9.x, `inertiajs/inertia-laravel` 1.3.4, Vue 3.4, `@inertiajs/vue3` 1.x, Vite 5 + `laravel-vite-plugin` 0.8, Tailwind CSS 3.4, SQLite, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-11-20-asyncindo-compute-design.md`

## Global Constraints

- PHP **8.0.30** at `C:/xampp/php/php.exe`; Composer **2.8.12**; Node **22.20.0**.
- PHP 8.0 caps Laravel at **9.x** and `inertiajs/inertia-laravel` at **1.3.4**. Vue 3 with `@inertiajs/vue3` 1.x.
- `pdo_sqlite` present; **`gd` absent** — uploads are stored, never resized; never call `UploadedFile::fake()->image()` in tests.
- PowerShell execution policy blocks `npm.ps1`; use **`npm.cmd`** for all npm commands.
- Use local images in `public/images/`; never hotlink Lambda's site. Asyncindo branding/copy throughout.
- No billing, provisioning, registration, or public REST API. No customer-facing write routes.
- Vue pages use literal paths (`/admin/instances`, `/admin/logout`, …) — **no Ziggy** route helper is installed.
- The `GpuModel` model appends an `image_url` attribute so Vue never has to know whether an image came from `public/` or `Storage::disk('public')`.
- Tests use SQLite `:memory:` + `RefreshDatabase`, and read Inertia props via `$response->viewData('page')['props']` (no `AssertableInertia` dependency).

## Review Focus

These are the inputs/conditions the spec leaves implicit that are most likely to break the build. Each is pinned to a test in the owning task.

- **Unchecked `is_active` checkbox sends no field** → a `required|boolean` rule rejects a perfectly valid "inactive" save. Pin: `test_store_saves_inactive_plan_when_is_active_is_zero` (Task 5) plus the hidden `is_active=0` input in `Form.vue` (Task 5).
- **Uploads with no `gd`** → `UploadedFile::fake()->image()` throws before validation runs, and a naive fake fails the `image|mimes` rule. Pin: `test_store_creates_plan_offerings_and_image` uses `createWithContent()` with real 1×1 PNG bytes (Task 5).
- **Invalid offering rows** (`gpu_count=3`, negative stock, empty region) must be rejected inline, not partially written. Pin: `test_store_rejects_invalid_payload` asserts the exact `offerings.*.*` error keys and `assertDatabaseCount('gpu_models', 0)` (Task 5).
- **Unique name on update** falsely rejects keeping the same plan name. Pin: `test_update_allows_keeping_same_name_and_replaces_offerings` (Task 6).
- **Deleting a plan with offerings / replacing offerings** leaves orphans if the transaction or cascade is wrong. Pin: `test_delete_removes_plan_and_offerings` and the offering-count assertion in the update test (Task 6).

For the visual-only requirements (tab filtering, empty state at a GPU count, responsive breakpoints), the owning tasks include a manual browser verification step — there is no JS unit-test runner in the stack.

---

## File Structure

Created/modified files, grouped by the task that owns them:

```
routes/web.php                                   ( Tasks 1, 3, 4, 5, 6 — routes appended per task )
app/Http/Kernel.php                              ( Task 1 — register HandleInertiaRequests )
app/Http/Middleware/HandleInertiaRequests.php    ( Task 1 )
resources/views/app.blade.php                    ( Task 1 )
resources/js/app.js                              ( Task 1 )
resources/css/app.css                            ( Task 1 )
package.json, vite.config.js, tailwind.config.js, postcss.config.js  ( Task 1 )
.env, database/database.sqlite, public/storage   ( Task 1 )

database/migrations/*_create_gpu_models_table.php       ( Task 2 )
database/migrations/*_create_instance_offerings_table.php( Task 2 )
app/Models/GpuModel.php                                  ( Task 2 )
app/Models/InstanceOffering.php                          ( Task 2 )
database/seeders/DatabaseSeeder.php                      ( Task 2 )
database/seeders/UserSeeder.php                          ( Task 2 )
database/seeders/GpuModelSeeder.php                      ( Task 2 )
public/images/gpu-models/*.svg                           ( Task 2 )
tests/Concerns/CreatesPlans.php                          ( Task 2 )

app/Http/Controllers/InstanceController.php              ( Task 3 )
resources/js/Pages/Public/Instances.vue                  ( Task 3 )
resources/js/Components/Public/SiteHeader.vue            ( Task 3 )
resources/js/Components/Public/SiteFooter.vue            ( Task 3 )
resources/js/Components/Public/PricingTable.vue          ( Task 3 )
tests/Feature/PublicInstancesTest.php                    ( Task 3 )

app/Http/Controllers/Admin/AuthController.php            ( Task 4 )
app/Http/Controllers/Admin/DashboardController.php       ( Task 4 )
resources/js/Layouts/AdminLayout.vue                     ( Task 4 )
resources/js/Pages/Admin/Login.vue                       ( Task 4 )
resources/js/Pages/Admin/Dashboard.vue                   ( Task 4 )
tests/Feature/Admin/AuthTest.php                         ( Task 4 )

app/Http/Requests/Admin/StoreGpuModelRequest.php         ( Task 5 )
app/Http/Controllers/Admin/InstanceController.php        ( Tasks 5, 6 )
resources/js/Pages/Admin/Instances/Index.vue             ( Task 5 )
resources/js/Pages/Admin/Instances/Create.vue            ( Task 5 )
resources/js/Pages/Admin/Instances/Form.vue              ( Tasks 5, 6 )
tests/Feature/Admin/InstanceStoreTest.php                ( Task 5 )

app/Http/Requests/Admin/UpdateGpuModelRequest.php        ( Task 6 )
resources/js/Pages/Admin/Instances/Edit.vue              ( Task 6 )
tests/Feature/Admin/InstanceUpdateTest.php               ( Task 6 )
```

---

### Task 1: Scaffold Laravel 9 + Inertia/Vue/Tailwind toolchain

**Files:**
- Create: `developer-created` Laravel scaffold merged into repo root (keep `.git` and `docs/`)
- Create: `package.json`, `vite.config.js`, `tailwind.config.js`, `postcss.config.js`
- Create: `resources/views/app.blade.php`, `resources/js/app.js`, `resources/css/app.css`
- Modify: `app/Http/Kernel.php`, `routes/web.php`, `.env`
- Test: `tests/Feature/ToolchainTest.php`

**Interfaces:**
- Produces: working `php artisan`, working `npm.cmd run build`, `public/build/manifest.json`, `GET /` → 302 `/instances`, `GpuModel`/`InstanceOffering` not yet defined.
- Produces: Tailwind color tokens `canvas #0A0A0A`, `panel #151518`, `hairline #2A2A2E`, `acid #B4FF39` for all later Vue files.
- Produces: Inertia pages resolved from `resources/js/Pages/**/*.vue` by name (e.g. `Public/Instances`).

- [ ] **Step 1: Scaffold into a temp folder and merge, preserving `.git` and `docs/`**

```powershell
composer create-project laravel/laravel:^9.0 _scaffold --prefer-dist
```
Then copy every entry of `_scaffold` (including dotfiles) into the repo root without overwriting `.git` or `docs`, and delete `_scaffold`. Verify `composer.json`, `artisan`, `phpunit.xml` exist at root.

- [ ] **Step 2: Install Inertia, publish middleware, register it**

```powershell
composer require inertiajs/inertia-laravel:1.3.4
php artisan inertia:middleware
```
If the artisan command is unavailable in 1.3.4, copy `vendor/inertiajs/inertia-laravel/stubs/HandleInertiaRequests.php` to `app/Http/Middleware/HandleInertiaRequests.php` instead. Add `\App\Http\Middleware\HandleInertiaRequests::class` to the `web` middleware group in `app/Http/Kernel.php`. In `share()`, add `auth.user` as `{ name }` or `null` using a lazy closure.

- [ ] **Step 3: Write the frontend toolchain config and entry files**

`package.json`: type `module`; scripts `dev`/`build`; dependencies `vue ^3.4`, `@inertiajs/vue3 ^1.0`; devDependencies `vite ^5`, `laravel-vite-plugin ^0.8`, `@vitejs/plugin-vue ^5`, `tailwindcss ^3.4`, `postcss ^8`, `autoprefixer ^10`.

`vite.config.js`: `laravel({ input: ['resources/css/app.css','resources/js/app.js'], refresh: true })` plus `vue()`; alias `@` → `resources/js`.

`tailwind.config.js`: content globs for `resources/**/*.{blade.php,js,vue}`; extend colors with the four tokens above; keep Tailwind's default palette intact (name the lime token `acid`, not `lime`).

`postcss.config.js`: tailwindcss + autoprefixer.

`resources/css/app.css`: `@tailwind base/components/utilities` plus a small `@layer base` rule setting `body { @apply bg-canvas text-zinc-100 antialiased; }` and a `.container-page` utility (`@apply mx-auto w-full max-w-7xl px-5 sm:px-8`).

`resources/views/app.blade.php`: `<meta name="csrf-token">`, `@vite([...])`, `@inertiaHead` in `<head>`; `<body class="bg-canvas text-zinc-100 antialiased">@inertia</body>`.

`resources/js/app.js`: `createInertiaApp` with `import.meta.glob('./Pages/**/*.vue', { eager: true })`, mount with `createApp({ render: () => h(App, props) }).use(plugin).mount(el)`, and import `../css/app.css`.

- [ ] **Step 4: Configure the runtime**

`.env`: `DB_CONNECTION=sqlite`, `DB_DATABASE` pointing at `database/database.sqlite`, `APP_URL=http://localhost:8000`. Create the empty `database/database.sqlite` file. Run `php artisan key:generate`, `php artisan storage:link`, `npm.cmd install`, `npm.cmd run build`. Confirm `public/build/manifest.json` exists.

- [ ] **Step 5: Write the failing smoke test**

```php
public function test_root_redirects_to_instances_and_build_assets_exist(): void
{
    $this->get('/')->assertRedirect('/instances');
    $this->assertFileExists(public_path('build/manifest.json'));
}
```

- [ ] **Step 6: Run it to verify it fails**

Run: `php artisan test --filter=ToolchainTest`
Expected: FAIL — `/` returns 404 (route not defined yet).

- [ ] **Step 7: Add the root redirect**

In `routes/web.php`: `Route::get('/', fn () => redirect('/instances'));`

- [ ] **Step 8: Run it to verify it passes**

Run: `php artisan test --filter=ToolchainTest`
Expected: PASS (2 assertions).

- [ ] **Step 9: Commit**

```powershell
git add -A
git commit -m "chore: scaffold Laravel 9 + Inertia/Vue/Tailwind toolchain"
```

---

### Task 2: Database schema, Eloquent models, and seed data

**Files:**
- Create: `database/migrations/*_create_gpu_models_table.php`, `database/migrations/*_create_instance_offerings_table.php`
- Create: `app/Models/GpuModel.php`, `app/Models/InstanceOffering.php`
- Modify: `database/seeders/DatabaseSeeder.php`; Create: `UserSeeder.php`, `GpuModelSeeder.php`
- Create: `public/images/gpu-models/{b200,h100,a100-80,a100-40,gh200,v100}.svg`
- Create: `tests/Concerns/CreatesPlans.php`
- Test: `tests/Feature/DatabaseSchemaTest.php`

**Interfaces:**
- Consumes: Task 1 toolchain; `Storage` facade; `asset()`.
- Produces: `GpuModel` with `$fillable`, `$appends = ['image_url']`, cast `is_active => boolean`, `getImageUrlAttribute(): ?string` returning `Storage::url($path)` when `image_path` starts with `gpu-models/`, otherwise `asset($path)`, `null` when empty. Relation `offerings(): HasMany`.
- Produces: `InstanceOffering` with `$fillable`, relation `gpuModel(): BelongsTo`.
- Produces: `CreatesPlans::createPlan(array $overrides = []): GpuModel` test helper that creates a plan and, unless overridden, one `gpu_count=1` offering.
- Produces: seeded admin `admin@asyncindo.test` / `password` and six plans named exactly `HGX B200`, `HGX H100`, `HGX A100 80GB`, `HGX A100 40GB`, `GH200`, `Tesla V100`, each with 2–4 offerings and a local SVG `image_path`.

- [ ] **Step 1: Write the failing seeder/schema test**

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\GpuModel;
use App\Models\InstanceOffering;
use App\Models\User;

public function test_seeder_creates_six_active_plans_with_related_offerings(): void
{
    $this->seed();

    $this->assertSame(1, User::count());
    $this->assertTrue(User::first()->email === 'admin@asyncindo.test');
    $this->assertSame(6, GpuModel::count());
    $this->assertGreaterThan(0, InstanceOffering::count());

    foreach (['HGX B200','HGX H100','HGX A100 80GB','HGX A100 40GB','GH200','Tesla V100'] as $name) {
        $this->assertDatabaseHas('gpu_models', ['name' => $name, 'is_active' => 1]);
    }

    $plan = GpuModel::where('name', 'HGX H100')->firstOrFail();
    $this->assertGreaterThanOrEqual(2, $plan->offerings()->count());
    $this->assertSame($plan->id, $plan->offerings()->first()->gpuModel->id);
    $this->assertStringStartsWith('/images/gpu-models/', $plan->image_url);
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --filter=DatabaseSchemaTest`
Expected: FAIL — no `gpu_models` table / no seeder output.

- [ ] **Step 3: Create the two migrations**

`gpu_models`: `id`, `name` unique, `slug` unique, `vram_gb` decimal(8,2), `vcpus` unsignedInteger, `ram_gb` unsignedInteger, `storage` string, `price_per_gpu_hour` decimal(8,2), `description` text nullable, `image_path` string nullable, `is_active` boolean default true, timestamps.

`instance_offerings`: `id`, `gpu_model_id` `foreignId()->constrained()->cascadeOnDelete()`, `gpu_count` unsignedTinyInteger, `region` string, `stock` unsignedInteger default 0, `sort_order` unsignedInteger default 0, timestamps.

Ensure `phpunit.xml` sets `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` (add if Laravel 9's default lacks them); SQLite foreign keys stay enabled.

- [ ] **Step 4: Create the models**

Implement exactly the interfaces listed above. Slug is derived from name with `Str::slug()`; keep derivation in the controller/seeders, not a model mutator, so the request validation owns `name`.

- [ ] **Step 5: Create the seeders and placeholder images**

`DatabaseSeeder` calls `UserSeeder` then `GpuModelSeeder`. `UserSeeder` creates one user with `Hash::make('password')`. `GpuModelSeeder` creates the six plans using a local array of specs/offerings, `Str::slug($name)` for slug, and `images/gpu-models/<file>.svg` for `image_path`. Include at least one offering with `stock = 0` so the out-of-stock badge is exercised. Create six minimal dark-background SVGs with lime labels in `public/images/gpu-models/`.

Suggested spec values (price = `price_per_gpu_hour`): B200 180/128/1152/10.50, H100 80/104/768/4.99, A100 80GB 80/96/512/2.49, A100 40GB 40/96/512/1.89, GH200 96/72/480/3.75, V100 16/32/256/0.99. Storage strings are free text like `2x 3.84 TB NVMe`.

- [ ] **Step 6: Create the `CreatesPlans` test trait**

Implement the signature in Interfaces. This trait is consumed by Task 5 and Task 6 tests.

- [ ] **Step 7: Run the test to verify it passes**

Run: `php artisan test --filter=DatabaseSchemaTest`
Expected: PASS.

- [ ] **Step 8: Commit**

```powershell
git add -A
git commit -m "feat: gpu schema, relations, and seed data"
```

---

### Task 3: Public instances page

**Files:**
- Create: `app/Http/Controllers/InstanceController.php`
- Create: `resources/js/Pages/Public/Instances.vue`, `resources/js/Components/Public/{SiteHeader,SiteFooter,PricingTable}.vue`
- Modify: `routes/web.php`
- Test: `tests/Feature/PublicInstancesTest.php`

**Interfaces:**
- Consumes: `GpuModel::with('offerings')->where('is_active', true)->orderBy('price_per_gpu_hour')->get()` from Task 2; `$appends` `image_url`.
- Produces: `Inertia::render('Public/Instances', ['plans' => $plans])` where each plan is `{ id, name, slug, vram_gb, vcpus, ram_gb, storage, price_per_gpu_hour, description, image_url, is_active, offerings: [{ id, gpu_count, region, stock, sort_order }] }`. No client-side fetching.
- Produces: `SiteHeader` links — "Log in" → `/admin/login`; "Get started" and "Launch instance" → `/instances#pricing`; "Compare" → `#` placeholder. Hamburger below `md`.
- Produces: `PricingTable` props `plans`; tabs `[8,4,2,1]`; active plans = plans having an offering for the selected count; one row per plan; availability badge from that plan's offering for the selected count.

- [ ] **Step 1: Write the failing feature test**

```php
public function test_instances_page_renders_seeded_plans_from_controller_props(): void
{
    $this->seed();
    $response = $this->get('/instances');
    $response->assertOk();

    $page = $response->viewData('page');
    $this->assertSame('Public/Instances', $page['component']);
    $this->assertArrayHasKey('plans', $page['props']);

    $plans = $page['props']['plans'];
    $this->assertCount(6, $plans);
    foreach (['HGX B200','HGX H100','HGX A100 80GB','HGX A100 40GB','GH200','Tesla V100'] as $name) {
        $this->assertContains($name, collect($plans)->pluck('name')->all());
    }
    $this->assertSame('Tesla V100', $plans[0]['name']); // cheapest first
    $this->assertArrayHasKey('offerings', $plans[0]);
    $this->assertArrayHasKey('stock', $plans[0]['offerings'][0]);
}

public function test_inactive_plans_are_not_exposed_publicly(): void
{
    $plan = $this->createPlan(['name' => 'Hidden Plan', 'is_active' => false]);
    $response = $this->get('/instances');
    $this->assertNotContains('Hidden Plan', collect($response->viewData('page')['props']['plans'])->pluck('name')->all());
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --filter=PublicInstancesTest`
Expected: FAIL — `/instances` 404 / no Inertia page.

- [ ] **Step 3: Implement controller and route**

Add `Route::get('/instances', [InstanceController::class, 'index'])->name('instances.index')`. Implement `index()` exactly as the Interfaces block specifies. Append `use Illuminate\Support\Facades\Route;` and the controller import.

- [ ] **Step 4: Build the Vue page and components**

`Instances.vue`: `SiteHeader`, hero ("Compute on demand" + "Train, fine-tune, and serve on 1–8 GPU instances" + Launch CTA), three feature cards (Launch in minutes / Multi-GPU instances / Use UI, API, or CLI), `<PricingTable :plans="plans" id="pricing">`, three value props, bottom CTA, `SiteFooter`. Use `<Head>` from `@inertiajs/vue3` for the title.

`PricingTable.vue`: `ref(8)` selected count; computed filtered plans; tab buttons `[8,4,2,1]`; table columns `PLAN / VRAM/GPU / vCPUs / RAM / STORAGE / PRICE/GPU/HR / AVAILABILITY`; availability badge: `stock > 0` → "In stock · N" in `text-acid`, else "Out of stock" in muted grey; empty state row when no plan has that count. `overflow-x-auto` wrapper, `sticky left-0 bg-panel` first column, dark `border-hairline` card styling, lime `bg-acid text-canvas` buttons.

`SiteFooter.vue`: product/docs/company anchors and `© 2026 PT. Asyncindo`.

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=PublicInstancesTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Verify visually in a browser**

Run `php artisan serve` and `npm.cmd run dev`, open `/instances`, confirm: all six rows render, switching 8x/4x/2x/1x filters rows and updates the stock badge, at least one tab shows the empty state, and at phone width the table scrolls horizontally with a sticky first column while the header shows a hamburger.

- [ ] **Step 7: Commit**

```powershell
git add -A
git commit -m "feat: public instances page with GPU-count tabs"
```

---

### Task 4: Admin authentication, layout, and dashboard

**Files:**
- Create: `app/Http/Controllers/Admin/AuthController.php`, `app/Http/Controllers/Admin/DashboardController.php`
- Create: `resources/js/Layouts/AdminLayout.vue`, `resources/js/Pages/Admin/Login.vue`, `resources/js/Pages/Admin/Dashboard.vue`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/AuthTest.php`

**Interfaces:**
- Consumes: seeded user from Task 2; Inertia shared `auth.user` from Task 1.
- Produces: routes `GET/POST /admin/login`, `POST /admin/logout`, `GET /admin` (auth middleware). Dashboard props `{ plansCount, offeringsCount, inactiveCount }` as integers.
- Produces: `AdminLayout` with dark sidebar (`Dashboard`, `Instances`, `Log out`) that becomes a top bar below `md`; logout is `router.post('/admin/logout')`. `Login.vue` uses `useForm({ email, password })` and `form.post('/admin/login')`.
- Produces: failed login returns `back()->withErrors(['email' => ...])->onlyInput('email')` so Inertia renders the error inline.

- [ ] **Step 1: Write the failing auth tests**

```php
public function test_guest_is_redirected_to_login(): void
{
    $this->get('/admin')->assertRedirect('/admin/login');
}

public function test_seeded_admin_can_log_in_and_log_out(): void
{
    $this->seed(UserSeeder::class);
    $this->post('/admin/login', ['email' => 'admin@asyncindo.test', 'password' => 'password'])
        ->assertRedirect('/admin');
    $this->assertAuthenticated();
    $this->get('/admin')->assertOk();
    $this->post('/admin/logout')->assertRedirect('/admin/login');
    $this->assertGuest();
}

public function test_invalid_credentials_do_not_authenticate(): void
{
    $this->seed(UserSeeder::class);
    $this->from('/admin/login')
        ->post('/admin/login', ['email' => 'admin@asyncindo.test', 'password' => 'wrong'])
        ->assertRedirect('/admin/login')
        ->assertSessionHasErrors('email');
    $this->assertGuest();
}
```

Add a fourth test asserting `Dashboard` props counts after seeding: `plansCount === 6`, `offeringsCount === InstanceOffering::count()`, `inactiveCount === 0` (toggle one plan inactive first if desired).

- [ ] **Step 2: Run them to verify they fail**

Run: `php artisan test --filter=AuthTest`
Expected: FAIL — login routes missing.

- [ ] **Step 3: Implement auth controller and routes**

`showLogin()` renders `Admin/Login`. `login()` validates `email|required|email` and `password|required|string`, uses `Auth::attempt`, regenerates the session, redirects `intended('/admin')`, else returns back with the email error. `logout()` calls `Auth::logout()`, invalidates/regenerates the session, redirects `/admin/login`. Wrap all admin routes in `Route::middleware('auth')->prefix('admin')->name('admin.')`.

- [ ] **Step 4: Implement dashboard controller and Vue pages**

`DashboardController@index` renders `Admin/Dashboard` with the three counts from `GpuModel`/`InstanceOffering`. `AdminLayout.vue` receives implicit `auth.user` from shared props. `Dashboard.vue` shows three stat cards wrapped in `AdminLayout`. `Login.vue` is standalone (no admin layout) with a centered dark card, email/password fields, and inline `errors.email`.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=AuthTest`
Expected: PASS (4 tests).

- [ ] **Step 6: Commit**

```powershell
git add -A
git commit -m "feat: admin session auth, layout, and dashboard"
```

---

### Task 5: Admin catalogue index and create (with validation + upload)

**Files:**
- Create: `app/Http/Requests/Admin/StoreGpuModelRequest.php`
- Create: `app/Http/Controllers/Admin/InstanceController.php`
- Create: `resources/js/Pages/Admin/Instances/{Index,Create,Form}.vue`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/InstanceStoreTest.php`

**Interfaces:**
- Consumes: `CreatesPlans` trait; `GpuModel`; `Storage::disk('public')`; `AdminLayout`.
- Produces: routes `GET /admin/instances`, `GET /admin/instances/create`, `POST /admin/instances` (all auth-guarded, names `admin.instances.*`).
- Produces: index props `{ plans }` where each plan is the model plus appended `offerings_count` (from `withCount('offerings')`).
- Produces: `StoreGpuModelRequest::rules()` returning the rules listed in the spec, including `image => nullable|image|mimes:jpg,jpeg,png,webp|max:2048` and `offerings.*.gpu_count => required|in:1,2,4,8`.
- Produces: `InstanceController::index()`, `create()`, `store(StoreGpuModelRequest $request)`; `store()` derives `slug` with `Str::slug`, stores the image via `Storage::disk('public')->putFile('gpu-models', $file)`, writes the plan and all offerings inside `DB::transaction()`, sets `sort_order` from row index, and redirects to `/admin/instances`.
- Produces: `Form.vue` props `{ plan: Object|null }`; `useForm` fields `name, vram_gb, vcpus, ram_gb, storage, price_per_gpu_hour, description, is_active, image, offerings[]`; a hidden `is_active=0` immediately before the checkbox; an "Add offering" button; per-field error spans reading `usePage().props.errors`; create submit `form.post('/admin/instances', { forceFormData: true })`.

- [ ] **Step 1: Write the failing tests (validation, upload, inactive default)**

```php
public function test_store_rejects_invalid_payload_without_writing_anything(): void
{
    $this->actingAs(User::factory()->create());
    $response = $this->post('/admin/instances', [
        'name' => '', 'vram_gb' => 0, 'vcpus' => 0, 'ram_gb' => 0,
        'storage' => '', 'price_per_gpu_hour' => -1, 'is_active' => 1,
        'offerings' => [['gpu_count' => 3, 'region' => '', 'stock' => -1]],
    ]);
    $response->assertSessionHasErrors([
        'name', 'vram_gb', 'vcpus', 'ram_gb', 'storage', 'price_per_gpu_hour',
        'offerings.0.gpu_count', 'offerings.0.region', 'offerings.0.stock',
    ]);
    $this->assertDatabaseCount('gpu_models', 0);
}
```

Also add `test_store_rejects_duplicate_name` (seed a plan, post the same name, assert error on `name`) and `test_store_rejects_oversized_or_wrong_type_image` (post a `.txt` file and assert error on `image`). Then add these two success tests:

```php
public function test_store_creates_plan_offerings_and_image(): void
{
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
    // gd is unavailable: use real PNG bytes instead of UploadedFile::fake()->image()
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==');
    $this->assertNotFalse(getimagesizefromstring($png)); // guards the fixture itself

    $this->post('/admin/instances', [
        'name' => 'HGX H200', 'vram_gb' => 141, 'vcpus' => 96, 'ram_gb' => 640,
        'storage' => '2x 3.84 TB NVMe', 'price_per_gpu_hour' => 8.25, 'is_active' => 1,
        'image' => UploadedFile::fake()->createWithContent('gpu.png', $png),
        'offerings' => [
            ['gpu_count' => 1, 'region' => 'us-east-1', 'stock' => 5],
            ['gpu_count' => 8, 'region' => 'us-east-1', 'stock' => 0],
        ],
    ])->assertRedirect('/admin/instances');

    $this->assertDatabaseHas('gpu_models', [
        'name' => 'HGX H200', 'slug' => 'hgx-h200', 'is_active' => 1,
    ]);
    $plan = GpuModel::where('name', 'HGX H200')->firstOrFail();
    $this->assertSame(2, $plan->offerings()->count());
    $this->assertNotNull($plan->image_path);
    Storage::disk('public')->assertExists($plan->image_path);
}

public function test_store_saves_inactive_plan_when_is_active_is_zero(): void
{
    $this->actingAs(User::factory()->create());
    $this->post('/admin/instances', [
        'name' => 'Quiet Plan', 'vram_gb' => 8, 'vcpus' => 8, 'ram_gb' => 64,
        'storage' => '1x 960 GB NVMe', 'price_per_gpu_hour' => 0.5, 'is_active' => 0,
        'offerings' => [['gpu_count' => 1, 'region' => 'us-east-1', 'stock' => 1]],
    ])->assertRedirect('/admin/instances');
    $this->assertDatabaseHas('gpu_models', ['name' => 'Quiet Plan', 'is_active' => 0]);
}
```

- [ ] **Step 2: Run all of them to verify they fail**

Run: `php artisan test --filter=InstanceStoreTest`
Expected: FAIL — request class / routes missing.

- [ ] **Step 3: Implement the FormRequest, routes, index, create, and store action**

Write `StoreGpuModelRequest` with the exact rules from the spec. Add the `admin.instances.index/create/store` routes. In `store()`, wrap `GpuModel::create()` + `offerings()->createMany()` in `DB::transaction()`; derive `slug`; store upload first from `$request->file('image')` when present. `index()` uses `withCount('offerings')` and `orderBy('name')`.

- [ ] **Step 4: Run the full store test file to verify it passes**

Run: `php artisan test --filter=InstanceStoreTest`
Expected: PASS (5 tests).

- [ ] **Step 5: Build the admin index and create UI**

`Index.vue` (wrapped in `AdminLayout`): table with image thumbnail (`plan.image_url`, a neutral placeholder when null), name, `price_per_gpu_hour`, `offerings_count`, Edit link to `/admin/instances/{id}/edit`, and a Delete button using `router.delete('/admin/instances/{id}', { onFinish: () => router.reload() })`. `Create.vue` renders `<Form :plan="null" />` inside `AdminLayout`. `Form.vue` implements the two-column layout (plan fields + image on the left, repeatable offering rows on the right), inline errors, and the submit behaviour in Interfaces.

- [ ] **Step 6: Verify visually in a browser**

Log in at `/admin/login`, open `/admin/instances/create`, submit an empty form and confirm inline errors appear next to each field and values are retained; add/remove an offering row; confirm the image input accepts a small PNG. At phone width confirm the sidebar becomes a top bar and form columns stack.

- [ ] **Step 7: Commit**

```powershell
git add -A
git commit -m "feat: admin catalogue index and create with validation/uploads"
```

---

### Task 6: Admin edit, update, delete, and final verification

**Files:**
- Create: `app/Http/Requests/Admin/UpdateGpuModelRequest.php`
- Modify: `app/Http/Controllers/Admin/InstanceController.php`
- Create: `resources/js/Pages/Admin/Instances/Edit.vue`
- Test: `tests/Feature/Admin/InstanceUpdateTest.php`

**Interfaces:**
- Consumes: `StoreGpuModelRequest` rules (Task 5), `CreatesPlans` trait, `Form.vue`.
- Produces: routes `GET /admin/instances/{gpuModel}/edit`, `PUT /admin/instances/{gpuModel}`, `DELETE /admin/instances/{gpuModel}` with implicit route-model binding to `GpuModel`.
- Produces: `UpdateGpuModelRequest` rules identical to store except `name` uses `Rule::unique('gpu_models','name')->ignore($this->route('gpuModel')->id)`.
- Produces: controller methods `edit(GpuModel $gpuModel)`, `update(UpdateGpuModelRequest $request, GpuModel $gpuModel)`, `destroy(GpuModel $gpuModel)`. Update rewrites the plan's scalar fields, deletes and recreates all offerings from the submitted rows inside `DB::transaction()`, replaces the image only when a new file was uploaded, and redirects to `/admin/instances`. Delete relies on the FK cascade and keeps uploaded files on disk (documented choice).
- Produces: `Edit.vue` renders `<Form :plan="plan" />`; `Form.vue` submit path becomes `form.post('/admin/instances/{id}', { _method: 'put', forceFormData: true })` when `plan` is present, and shows the current `plan.image_url` with a "Replace image" input.

- [ ] **Step 1: Write the failing tests**

```php
public function test_update_allows_keeping_same_name_and_replaces_offerings(): void
{
    $plan = $this->createPlan(['name' => 'Stable Plan', 'price_per_gpu_hour' => 1.00]);
    $this->actingAs(User::factory()->create());

    $this->put("/admin/instances/{$plan->id}", [
        'name' => 'Stable Plan', 'vram_gb' => 40, 'vcpus' => 64, 'ram_gb' => 512,
        'storage' => '2x 1.92 TB NVMe', 'price_per_gpu_hour' => 1.25, 'is_active' => 1,
        'offerings' => [['gpu_count' => 2, 'region' => 'eu-west-1', 'stock' => 9]],
    ])->assertRedirect('/admin/instances');

    $this->assertSame(1, $plan->fresh()->offerings()->count());
    $this->assertSame(2, $plan->fresh()->offerings()->first()->gpu_count);
    $this->assertSame('stable-plan', $plan->fresh()->slug);
}

public function test_update_rejects_another_plans_name(): void
{
    $this->createPlan(['name' => 'Taken']);
    $plan = $this->createPlan(['name' => 'Editable']);
    $this->actingAs(User::factory()->create());
    $this->put("/admin/instances/{$plan->id}", [
        'name' => 'Taken', 'vram_gb' => 8, 'vcpus' => 8, 'ram_gb' => 64,
        'storage' => 'NVMe', 'price_per_gpu_hour' => 1, 'is_active' => 1,
        'offerings' => [['gpu_count' => 1, 'region' => 'us-east-1', 'stock' => 1]],
    ])->assertSessionHasErrors('name');
}

public function test_delete_removes_plan_and_offerings(): void
{
    $plan = $this->createPlan(['name' => 'Disposable']);
    $this->actingAs(User::factory()->create());
    $offeringId = $plan->offerings()->first()->id;

    $this->delete("/admin/instances/{$plan->id}")->assertRedirect('/admin/instances');

    $this->assertDatabaseMissing('gpu_models', ['id' => $plan->id]);
    $this->assertDatabaseMissing('instance_offerings', ['id' => $offeringId]);
}

public function test_update_replaces_image_when_uploaded(): void
{
    Storage::fake('public');
    $plan = $this->createPlan(['image_path' => 'gpu-models/old.png']);
    // use the same base64 PNG fixture as Task 5
    $this->actingAs(User::factory()->create());
    $this->put("/admin/instances/{$plan->id}", [
        ... valid fields ..., 'is_active' => 1,
        'image' => UploadedFile::fake()->createWithContent('new.png', $png),
        'offerings' => [['gpu_count' => 1, 'region' => 'us-east-1', 'stock' => 1]],
    ])->assertRedirect('/admin/instances');
    $this->assertNotSame('gpu-models/old.png', $plan->fresh()->image_path);
    Storage::disk('public')->assertExists($plan->fresh()->image_path);
}
```

- [ ] **Step 2: Run them to verify they fail**

Run: `php artisan test --filter=InstanceUpdateTest`
Expected: FAIL — update/delete routes missing.

- [ ] **Step 3: Implement update and delete**

Write `UpdateGpuModelRequest`; add the three routes; implement controller methods per Interfaces. Keep offering re-sync inside the same `DB::transaction()` as the plan update.

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --filter=InstanceUpdateTest`
Expected: PASS (4 tests).

- [ ] **Step 5: Verify the edit UI in a browser**

Open an existing plan's edit page: current values are prefilled, current image is shown, changing name to a name used by another plan shows an inline error, add/remove offering rows, save, and confirm the index reflects the change. Delete a test plan and confirm it disappears with no orphan rows.

- [ ] **Step 6: Full verification**

Run: `php artisan test`; `npm.cmd run build`; `php artisan route:list | Select-String "admin|instances"`. Expected: all tests PASS, build succeeds with no errors, and the expected route list is present. Confirm responsive behaviour once more at phone width on both public and admin pages.

- [ ] **Step 7: Commit**

```powershell
git add -A
git commit -m "feat: admin edit/update/delete and final verification"
```

---

## Self-Review Notes

- Every spec section has an owning task: toolchain/constraints (1), data model + relations + seeders (2), public page + tabs + layout (3), admin auth (4), admin CRUD + validation + uploads (5, 6), responsive behaviour (3, 5, 6 manual checks), tests (2–6).
- Spec values copied verbatim: PHP 8.0.30, Composer 2.8.12, Node 22.20.0, Laravel 9, inertia-laravel 1.3.4, Tailwind 3.4, upload rule `nullable|image|mimes:jpg,jpeg,png,webp|max:2048`, `in:1,2,4,8`, admin credentials, six plan names, colors `#0A0A0A/#151518/#2A2A2E/#B4FF39`, six plans clears the five-plan bar.
- Builder decisions locked in the plan: `image_url` appended attribute; seed images are local SVGs; offering re-sync = delete + recreate in a transaction; files are kept on plan delete but replaced on upload; no Ziggy; PHPUnit reads props from `viewData('page')`; upload test uses `createWithContent` because `gd` is absent.
