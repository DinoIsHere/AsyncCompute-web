<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPlans;
use Tests\TestCase;

class PublicInstancesTest extends TestCase
{
    use CreatesPlans;
    use RefreshDatabase;

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
}
