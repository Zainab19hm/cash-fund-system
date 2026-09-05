<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Feature tests for RegisterController.
 *
 * Covers:
 *  (1) A new user is created with is_active = false.
 *  (2) The user cannot log in immediately after registering.
 *  (3) Duplicate username / email are rejected.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /** Minimal valid registration payload. */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name'                  => 'مستخدم جديد',
            'username'              => 'new_user_test',
            'email'                 => 'newuser@example.com',
            'password'              => 'Secret1234',
            'password_confirmation' => 'Secret1234',
            'role'                  => 'client',
        ], $overrides);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /register
    // ─────────────────────────────────────────────────────────────

    public function test_register_page_loads_for_guests(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('إنشاء حساب جديد');
    }

    public function test_register_page_has_rtl_and_arabic(): void
    {
        $response = $this->get(route('register'));

        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
    }

    public function test_register_page_contains_role_select(): void
    {
        $response = $this->get(route('register'));

        $response->assertSee('<select', false);
        $response->assertSee('name="role"', false);
        $response->assertSee('مدير');
        $response->assertSee('مستثمر');
        $response->assertSee('عميل');
    }

    public function test_register_page_has_link_back_to_login(): void
    {
        $response = $this->get(route('register'));

        $response->assertSee(route('login'));
        $response->assertSee('سجّل الدخول');
    }

    public function test_login_page_has_link_to_register(): void
    {
        $response = $this->get(route('login'));

        $response->assertSee(route('register'));
        $response->assertSee('إنشاء حساب جديد');
    }

    // ─────────────────────────────────────────────────────────────
    // (1) New user is created with is_active = false
    // ─────────────────────────────────────────────────────────────

    public function test_successful_registration_creates_user_with_is_active_false(): void
    {
        $response = $this->post(route('register'), $this->validPayload());

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', [
            'username'  => 'new_user_test',
            'is_active' => false,
        ]);

        $user = User::where('username', 'new_user_test')->first();
        $this->assertNotNull($user);
        $this->assertFalse((bool) $user->is_active);
    }

    public function test_is_active_forced_to_false_even_if_truthy_input_sent(): void
    {
        // Even if someone crafts a request with is_active = true / 1 / "true",
        // the controller must ignore it and store false.
        $payload = $this->validPayload();
        $payload['is_active'] = true;   // attempted injection

        $this->post(route('register'), $payload);

        $user = User::where('username', 'new_user_test')->first();
        $this->assertNotNull($user);
        $this->assertFalse((bool) $user->is_active);
    }

    public function test_registration_redirects_to_login_with_success_message(): void
    {
        $response = $this->post(route('register'), $this->validPayload());

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
    }

    public function test_registration_stores_correct_role(): void
    {
        foreach (['admin', 'investor', 'client'] as $role) {
            $payload = $this->validPayload([
                'username' => "user_{$role}_reg",
                'email'    => "{$role}@example.com",
                'role'     => $role,
            ]);

            $this->post(route('register'), $payload);

            $this->assertDatabaseHas('users', [
                'username' => "user_{$role}_reg",
                'role'     => $role,
                'is_active' => false,
            ]);
        }
    }

    public function test_registration_hashes_password(): void
    {
        $this->post(route('register'), $this->validPayload());

        $user = User::where('username', 'new_user_test')->first();
        $this->assertNotNull($user);
        $this->assertNotEquals('Secret1234', $user->password);
        $this->assertTrue(password_verify('Secret1234', $user->password));
    }

    // ─────────────────────────────────────────────────────────────
    // (2) User cannot log in immediately after registering
    // ─────────────────────────────────────────────────────────────

    public function test_newly_registered_user_cannot_log_in(): void
    {
        // Step 1: register
        $this->post(route('register'), $this->validPayload());

        // Confirm is_active is false
        $user = User::where('username', 'new_user_test')->first();
        $this->assertFalse((bool) $user->is_active);

        // Step 2: try to log in
        $loginResponse = $this->post(route('login'), [
            'username' => 'new_user_test',
            'password' => 'Secret1234',
        ]);

        // Should NOT be authenticated
        $this->assertFalse(Auth::check());

        // Should get an error (either a session error or a redirect back)
        // The LoginController logs them in first, then checks is_active and logs them out,
        // returning an error.
        $loginResponse->assertRedirect();

        // Ensure the user is still not logged in after the response
        $this->assertGuest();
    }

    public function test_inactive_user_login_shows_arabic_error_message(): void
    {
        // Register (creates inactive user)
        $this->post(route('register'), $this->validPayload());

        // Attempt login
        $response = $this->post(route('login'), [
            'username' => 'new_user_test',
            'password' => 'Secret1234',
        ]);

        // The LoginController returns an Arabic error about the account being suspended
        $response->assertSessionHasErrors(['username']);

        $errors = session('errors');
        $errorMessage = $errors ? $errors->first('username') : '';
        $this->assertNotEmpty($errorMessage);
        // Verify the response redirects back (not to a dashboard)
        $response->assertRedirect();
        $this->assertGuest();
    }

    public function test_registered_user_is_still_inactive_in_database_after_login_attempt(): void
    {
        $this->post(route('register'), $this->validPayload());

        // Attempt to log in
        $this->post(route('login'), [
            'username' => 'new_user_test',
            'password' => 'Secret1234',
        ]);

        // is_active must still be false — login attempt must not flip it
        $this->assertDatabaseHas('users', [
            'username'  => 'new_user_test',
            'is_active' => false,
        ]);
    }

    public function test_registered_user_can_login_after_being_activated(): void
    {
        // Register (inactive)
        $this->post(route('register'), $this->validPayload());

        // Admin activates the user
        User::where('username', 'new_user_test')->update(['is_active' => true]);

        // Now login should succeed
        $response = $this->post(route('login'), [
            'username' => 'new_user_test',
            'password' => 'Secret1234',
        ]);

        $this->assertTrue(Auth::check());
        $response->assertRedirect(); // redirect to dashboard
    }

    // ─────────────────────────────────────────────────────────────
    // (3) Duplicate username / email are rejected
    // ─────────────────────────────────────────────────────────────

    public function test_duplicate_username_is_rejected(): void
    {
        // Create a user with the username we'll try to register again
        User::create([
            'name'      => 'Existing User',
            'username'  => 'new_user_test',
            'password'  => 'password',
            'role'      => 'client',
            'is_active' => true,
        ]);

        $response = $this->post(route('register'), $this->validPayload([
            'username' => 'new_user_test',   // duplicate
            'email'    => 'different@example.com',
        ]));

        $response->assertSessionHasErrors(['username']);

        // Only the original user exists — no new record created
        $this->assertEquals(1, User::where('username', 'new_user_test')->count());
    }

    public function test_duplicate_email_is_rejected(): void
    {
        // Create a user with the email we'll try to register again
        User::create([
            'name'      => 'Existing User',
            'username'  => 'existing_user',
            'email'     => 'newuser@example.com',
            'password'  => 'password',
            'role'      => 'client',
            'is_active' => true,
        ]);

        $response = $this->post(route('register'), $this->validPayload([
            'username' => 'completely_different',
            'email'    => 'newuser@example.com',  // duplicate
        ]));

        $response->assertSessionHasErrors(['email']);

        // No new user should have been created
        $this->assertEquals(0, User::where('username', 'completely_different')->count());
    }

    public function test_duplicate_username_does_not_create_any_record(): void
    {
        User::create([
            'name'      => 'Original',
            'username'  => 'taken_username',
            'password'  => 'password',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $countBefore = User::count();

        $this->post(route('register'), $this->validPayload([
            'username' => 'taken_username',
        ]));

        $this->assertEquals($countBefore, User::count());
    }

    public function test_duplicate_email_does_not_create_any_record(): void
    {
        User::create([
            'name'      => 'Original',
            'username'  => 'original_user',
            'email'     => 'taken@example.com',
            'password'  => 'password',
            'role'      => 'investor',
            'is_active' => true,
        ]);

        $countBefore = User::count();

        $this->post(route('register'), $this->validPayload([
            'username' => 'brand_new_username',
            'email'    => 'taken@example.com',
        ]));

        $this->assertEquals($countBefore, User::count());
    }

    // ─────────────────────────────────────────────────────────────
    // Validation — required fields
    // ─────────────────────────────────────────────────────────────

    public function test_name_is_required(): void
    {
        $response = $this->post(route('register'), $this->validPayload(['name' => '']));
        $response->assertSessionHasErrors(['name']);
    }

    public function test_username_is_required(): void
    {
        $response = $this->post(route('register'), $this->validPayload(['username' => '']));
        $response->assertSessionHasErrors(['username']);
    }

    public function test_password_minimum_length_8(): void
    {
        $response = $this->post(route('register'), $this->validPayload([
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]));
        $response->assertSessionHasErrors(['password']);
    }

    public function test_password_confirmation_must_match(): void
    {
        $response = $this->post(route('register'), $this->validPayload([
            'password'              => 'Secret1234',
            'password_confirmation' => 'DifferentPass',
        ]));
        $response->assertSessionHasErrors(['password']);
    }

    public function test_invalid_role_is_rejected(): void
    {
        $response = $this->post(route('register'), $this->validPayload(['role' => 'superuser']));
        $response->assertSessionHasErrors(['role']);
    }

    public function test_role_is_required(): void
    {
        $response = $this->post(route('register'), $this->validPayload(['role' => '']));
        $response->assertSessionHasErrors(['role']);
    }

    public function test_invalid_email_format_is_rejected(): void
    {
        $response = $this->post(route('register'), $this->validPayload(['email' => 'not-an-email']));
        $response->assertSessionHasErrors(['email']);
    }

    public function test_email_is_optional(): void
    {
        $payload = $this->validPayload();
        unset($payload['email']);

        $response = $this->post(route('register'), $payload);
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', [
            'username' => 'new_user_test',
            'is_active' => false,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Guest middleware — authenticated users redirected
    // ─────────────────────────────────────────────────────────────

    public function test_authenticated_user_cannot_access_register_page(): void
    {
        $user = User::create([
            'name'      => 'Auth User',
            'username'  => 'auth_user',
            'password'  => 'password',
            'role'      => 'client',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('register'));

        // Guest middleware redirects authenticated users away
        $response->assertRedirect();
        $this->assertNotEquals(200, $response->status());
    }

    // ─────────────────────────────────────────────────────────────
    // Route registration check
    // ─────────────────────────────────────────────────────────────

    public function test_register_routes_are_registered(): void
    {
        $this->assertNotEmpty(route('register'));
    }
}
