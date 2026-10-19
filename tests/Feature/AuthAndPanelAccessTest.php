<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Identity\Livewire\Auth\Login;
use App\Modules\Identity\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthAndPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        Role::firstOrCreate(['name' => Role::SUPER_ADMIN], ['label' => 'Super Administrator']);
        Role::firstOrCreate(['name' => Role::SERVER_ADMIN], ['label' => 'Server Administrator']);
        Role::firstOrCreate(['name' => Role::RESELLER], ['label' => 'Reseller']);
        Role::firstOrCreate(['name' => Role::CUSTOMER], ['label' => 'Customer']);
    }

    public function test_guest_is_redirected_to_login_page(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');

        $loginResponse = $this->get('/login');
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('VORTAN');
    }

    public function test_user_can_authenticate_via_livewire_login(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@vortan.local',
            'password' => bcrypt('SecretPassword123!'),
        ]);
        $user->assignRole(Role::SUPER_ADMIN);

        Livewire::test(Login::class)
            ->set('email', 'admin@vortan.local')
            ->set('password', 'SecretPassword123!')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);

        // Verify audit log was recorded
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event' => 'user.logged_in',
        ]);
    }

    public function test_failed_login_records_audit_log_and_shows_error(): void
    {
        $user = User::factory()->create([
            'email' => 'victim@vortan.local',
            'password' => bcrypt('CorrectPassword123!'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'victim@vortan.local')
            ->set('password', 'WrongPassword!')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user.login_failed',
        ]);
    }

    public function test_customer_cannot_access_admin_panel(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::CUSTOMER);

        $this->actingAs($customer);

        // Attempt to access admin panel
        $response = $this->get('/admin');
        $response->assertStatus(403);

        // Customer can access customer panel
        $customerPanelResponse = $this->get('/app');
        $customerPanelResponse->assertStatus(200);
        $customerPanelResponse->assertSee('Hosting Management');
    }

    public function test_super_admin_can_access_all_panels(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        $this->actingAs($admin);

        $this->get('/admin')->assertStatus(200)->assertSee('Infrastructure Overview');
        $this->get('/reseller')->assertStatus(200)->assertSee('Reseller Resource Hub');
        $this->get('/app')->assertStatus(200)->assertSee('Hosting Management');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
