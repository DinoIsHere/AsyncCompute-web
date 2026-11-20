<?php

namespace Tests\Feature\Admin;

use App\Models\GpuModel;
use App\Models\InstanceOffering;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_dashboard_renders_catalogue_counts(): void
    {
        $this->seed();
        GpuModel::firstOrFail()->update(['is_active' => false]);

        $response = $this->actingAs(User::factory()->create())->get('/admin');
        $response->assertOk();

        $page = $response->viewData('page');
        $this->assertSame('Admin/Dashboard', $page['component']);
        $this->assertSame(6, $page['props']['plansCount']);
        $this->assertSame(InstanceOffering::count(), $page['props']['offeringsCount']);
        $this->assertSame(1, $page['props']['inactiveCount']);
    }
}
