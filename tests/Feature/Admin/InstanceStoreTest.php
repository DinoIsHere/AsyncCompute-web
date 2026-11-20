<?php

namespace Tests\Feature\Admin;

use App\Models\GpuModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesPlans;
use Tests\TestCase;

class InstanceStoreTest extends TestCase
{
    use CreatesPlans;
    use RefreshDatabase;

    /**
     * A valid 1x1 PNG. gd is unavailable here, so UploadedFile::fake()->image()
     * cannot be used; real bytes are needed for the "image" validation rule.
     */
    private function pngFixture(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'HGX H200',
            'vram_gb' => 141,
            'vcpus' => 96,
            'ram_gb' => 640,
            'storage' => '2x 3.84 TB NVMe',
            'price_per_gpu_hour' => 8.25,
            'description' => 'Blackwell test plan',
            'is_active' => 1,
            'offerings' => [
                ['gpu_count' => 1, 'region' => 'us-east-1', 'stock' => 5],
            ],
        ], $overrides);
    }

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
        $this->assertDatabaseCount('instance_offerings', 0);
    }

    public function test_store_rejects_duplicate_name(): void
    {
        $this->createPlan(['name' => 'Taken Plan']);
        $this->actingAs(User::factory()->create());

        $this->post('/admin/instances', $this->validPayload(['name' => 'Taken Plan']))
            ->assertSessionHasErrors('name');

        $this->assertSame(1, GpuModel::count());
    }

    public function test_store_rejects_oversized_or_wrong_type_image(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->post('/admin/instances', $this->validPayload([
            'image' => UploadedFile::fake()->createWithContent('notes.txt', 'not an image'),
        ]))->assertSessionHasErrors('image');

        $this->assertSame(0, GpuModel::count());
    }

    public function test_store_creates_plan_offerings_and_image(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $png = $this->pngFixture();
        $this->assertNotFalse(getimagesizefromstring($png)); // guards the fixture itself

        $this->post('/admin/instances', $this->validPayload([
            'image' => UploadedFile::fake()->createWithContent('gpu.png', $png),
            'offerings' => [
                ['gpu_count' => 1, 'region' => 'us-east-1', 'stock' => 5],
                ['gpu_count' => 8, 'region' => 'us-east-1', 'stock' => 0],
            ],
        ]))->assertRedirect('/admin/instances');

        $this->assertDatabaseHas('gpu_models', [
            'name' => 'HGX H200', 'slug' => 'hgx-h200', 'is_active' => 1,
        ]);

        $plan = GpuModel::where('name', 'HGX H200')->firstOrFail();
        $this->assertSame(2, $plan->offerings()->count());
        $this->assertSame([0, 1], array_map('intval', $plan->offerings()->orderBy('sort_order')->pluck('sort_order')->all()));
        $this->assertNotNull($plan->image_path);
        Storage::disk('public')->assertExists($plan->image_path);
    }

    public function test_store_saves_inactive_plan_when_is_active_is_zero(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/admin/instances', $this->validPayload([
            'name' => 'Quiet Plan',
            'vram_gb' => 8,
            'vcpus' => 8,
            'ram_gb' => 64,
            'storage' => '1x 960 GB NVMe',
            'price_per_gpu_hour' => 0.5,
            'is_active' => 0,
        ]))->assertRedirect('/admin/instances');

        $this->assertDatabaseHas('gpu_models', ['name' => 'Quiet Plan', 'is_active' => 0]);
    }

    public function test_guest_cannot_reach_admin_catalogue_routes(): void
    {
        $this->get('/admin/instances')->assertRedirect('/admin/login');
        $this->get('/admin/instances/create')->assertRedirect('/admin/login');
        $this->post('/admin/instances', $this->validPayload())->assertRedirect('/admin/login');
        $this->assertSame(0, GpuModel::count());
    }

    public function test_index_lists_plans_with_offering_counts(): void
    {
        $plan = $this->createPlan([
            'name' => 'Counted Plan',
            'offerings' => [
                ['gpu_count' => 1, 'region' => 'us-east-1', 'stock' => 1],
                ['gpu_count' => 2, 'region' => 'us-east-1', 'stock' => 1],
            ],
        ]);

        $response = $this->actingAs(User::factory()->create())->get('/admin/instances');
        $response->assertOk();

        $page = $response->viewData('page');
        $this->assertSame('Admin/Instances/Index', $page['component']);
        $this->assertCount(1, $page['props']['plans']);
        $this->assertSame($plan->id, $page['props']['plans'][0]['id']);
        $this->assertSame(2, (int) $page['props']['plans'][0]['offerings_count']);
    }

    public function test_create_page_renders_the_form(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/admin/instances/create');
        $response->assertOk();
        $this->assertSame('Admin/Instances/Create', $response->viewData('page')['component']);
    }
}
