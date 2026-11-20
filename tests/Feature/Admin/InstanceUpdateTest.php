<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesPlans;
use Tests\TestCase;

class InstanceUpdateTest extends TestCase
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
        // SQLite returns uncast numeric columns as strings: compare loosely.
        $this->assertEquals(2, $plan->fresh()->offerings()->first()->gpu_count);
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
        $this->actingAs(User::factory()->create());

        $this->put("/admin/instances/{$plan->id}", [
            'name' => 'Image Plan',
            'vram_gb' => 8,
            'vcpus' => 8,
            'ram_gb' => 64,
            'storage' => 'NVMe',
            'price_per_gpu_hour' => 1,
            'is_active' => 1,
            'image' => UploadedFile::fake()->createWithContent('new.png', $this->pngFixture()),
            'offerings' => [['gpu_count' => 1, 'region' => 'us-east-1', 'stock' => 1]],
        ])->assertRedirect('/admin/instances');

        $this->assertNotSame('gpu-models/old.png', $plan->fresh()->image_path);
        Storage::disk('public')->assertExists($plan->fresh()->image_path);
    }
}
