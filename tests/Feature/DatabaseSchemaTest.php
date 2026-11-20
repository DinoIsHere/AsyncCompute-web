<?php

namespace Tests\Feature;

use App\Models\GpuModel;
use App\Models\InstanceOffering;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertStringContainsString('/images/gpu-models/', $plan->image_url);
    }
}
