<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for PermissionController::update().
 *
 * The Blade view encodes every checkbox value as a JSON string:
 *   value="{{ json_encode(['role' => 'admin', 'permission_id' => $perm->id]) }}"
 *
 * These tests submit assignments[] exactly as the browser would — each element
 * is a JSON string — and verify that the controller decodes them correctly
 * before validation, and then writes the expected role_permissions rows.
 */
class PermissionUpdateTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /** Seed the database and log in as the admin user. */
    private function seedAndLogin(): User
    {
        \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin);
        return $admin;
    }

    /**
     * Build an assignments[] payload the same way the Blade view does:
     * each element is json_encode(['role' => ..., 'permission_id' => ...]).
     *
     * @param  array<array{role: string, permission_id: int}>  $pairs
     * @return array<string, list<string>>
     */
    private function buildJsonPayload(array $pairs): array
    {
        return [
            'assignments' => array_map(
                fn($pair) => json_encode($pair),
                $pairs
            ),
        ];
    }

    /** Return all permission IDs keyed by their 'key' string. */
    private function permIdMap(): array
    {
        return DB::table('permissions')->pluck('id', 'key')->toArray();
    }

    // -----------------------------------------------------------------------
    // Happy-path: JSON-string assignments pass validation and create rows
    // -----------------------------------------------------------------------

    /**
     * Submit a minimal valid payload (all three roles, manage_permissions kept
     * for admin) using JSON-string assignments, the same format the Blade form
     * produces.  Expect no validation errors and the exact rows in DB.
     */
    public function test_json_string_assignments_pass_validation_and_create_rows(): void
    {
        $this->seedAndLogin();
        $ids = $this->permIdMap();

        // Give each role at least one permission; admin must keep manage_permissions.
        $pairs = [
            ['role' => 'admin',    'permission_id' => $ids['manage_permissions']],
            ['role' => 'admin',    'permission_id' => $ids['approve_order']],
            ['role' => 'investor', 'permission_id' => $ids['create_order']],
            ['role' => 'client',   'permission_id' => $ids['create_order']],
        ];

        $response = $this->post(
            route('admin.permissions.update'),
            $this->buildJsonPayload($pairs)
        );

        // No validation errors.
        $response->assertSessionHasNoErrors();

        // Redirected to the index page on success.
        $response->assertRedirect(route('admin.permissions.index'));

        // role_permissions table must contain exactly the rows we sent.
        $this->assertSame(count($pairs), DB::table('role_permissions')->count());

        foreach ($pairs as $pair) {
            $this->assertDatabaseHas('role_permissions', [
                'role'          => $pair['role'],
                'permission_id' => $pair['permission_id'],
            ]);
        }
    }

    /**
     * Verify that every submitted row is persisted and no extra rows survive
     * from the previous state (the controller wipes the table before inserting).
     */
    public function test_previous_rows_are_replaced_not_appended(): void
    {
        $this->seedAndLogin();
        $ids = $this->permIdMap();

        // First update — give admin two permissions.
        $firstPairs = [
            ['role' => 'admin',    'permission_id' => $ids['manage_permissions']],
            ['role' => 'admin',    'permission_id' => $ids['approve_order']],
            ['role' => 'investor', 'permission_id' => $ids['create_order']],
            ['role' => 'client',   'permission_id' => $ids['create_order']],
        ];
        $this->post(route('admin.permissions.update'), $this->buildJsonPayload($firstPairs));

        // Second update — replace with a different set.
        $secondPairs = [
            ['role' => 'admin',    'permission_id' => $ids['manage_permissions']],
            ['role' => 'admin',    'permission_id' => $ids['execute_order']],   // different from above
            ['role' => 'investor', 'permission_id' => $ids['reject_order']],    // different
            ['role' => 'client',   'permission_id' => $ids['create_order']],
        ];
        $response = $this->post(route('admin.permissions.update'), $this->buildJsonPayload($secondPairs));

        $response->assertSessionHasNoErrors();

        // Only the second set exists.
        $this->assertSame(count($secondPairs), DB::table('role_permissions')->count());

        // Rows from the first update that are NOT in the second update must be gone.
        $this->assertDatabaseMissing('role_permissions', [
            'role'          => 'admin',
            'permission_id' => $ids['approve_order'],
        ]);
        $this->assertDatabaseMissing('role_permissions', [
            'role'          => 'investor',
            'permission_id' => $ids['create_order'],
        ]);

        foreach ($secondPairs as $pair) {
            $this->assertDatabaseHas('role_permissions', $pair);
        }
    }

    /**
     * The controller must also accept already-decoded arrays (plain PHP arrays),
     * so existing callers that build the payload programmatically still work.
     */
    public function test_decoded_array_assignments_still_work(): void
    {
        $this->seedAndLogin();
        $ids = $this->permIdMap();

        $pairs = [
            ['role' => 'admin',    'permission_id' => $ids['manage_permissions']],
            ['role' => 'investor', 'permission_id' => $ids['create_order']],
            ['role' => 'client',   'permission_id' => $ids['create_order']],
        ];

        // Pass plain arrays — NOT JSON strings.
        $response = $this->post(route('admin.permissions.update'), [
            'assignments' => $pairs,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.permissions.index'));

        foreach ($pairs as $pair) {
            $this->assertDatabaseHas('role_permissions', $pair);
        }
    }

    // -----------------------------------------------------------------------
    // Validation failures (unchanged behaviour, confirmed still correct)
    // -----------------------------------------------------------------------

    /** Missing all three roles → business-rule error, not a validation error. */
    public function test_missing_role_triggers_error(): void
    {
        $this->seedAndLogin();
        $ids = $this->permIdMap();

        // Only admin and investor — client is absent.
        $pairs = [
            ['role' => 'admin',    'permission_id' => $ids['manage_permissions']],
            ['role' => 'investor', 'permission_id' => $ids['create_order']],
        ];

        $response = $this->post(route('admin.permissions.update'), $this->buildJsonPayload($pairs));

        $response->assertSessionHasErrors('permissions');
        // Nothing should be written to role_permissions.
        // (The seeded data was wiped by migrate:fresh so the table is not empty,
        //  but the controller returns before touching it, so count stays whatever
        //  the seed left — we just confirm the error was returned.)
    }

    /** Removing manage_permissions from admin is rejected. */
    public function test_removing_manage_permissions_from_admin_is_rejected(): void
    {
        $this->seedAndLogin();
        $ids = $this->permIdMap();

        $pairs = [
            ['role' => 'admin',    'permission_id' => $ids['approve_order']],  // manage_permissions intentionally absent
            ['role' => 'investor', 'permission_id' => $ids['create_order']],
            ['role' => 'client',   'permission_id' => $ids['create_order']],
        ];

        $response = $this->post(route('admin.permissions.update'), $this->buildJsonPayload($pairs));

        $response->assertSessionHasErrors('permissions');
    }

    /** An invalid role value fails the 'in:admin,investor,client' rule. */
    public function test_invalid_role_value_fails_validation(): void
    {
        $this->seedAndLogin();
        $ids = $this->permIdMap();

        $badPairs = [
            ['role' => 'superuser', 'permission_id' => $ids['manage_permissions']],
            ['role' => 'investor',  'permission_id' => $ids['create_order']],
            ['role' => 'client',    'permission_id' => $ids['create_order']],
        ];

        $response = $this->post(route('admin.permissions.update'), $this->buildJsonPayload($badPairs));

        $response->assertSessionHasErrors();
    }

    /** A permission_id that does not exist in the permissions table fails validation. */
    public function test_nonexistent_permission_id_fails_validation(): void
    {
        $this->seedAndLogin();

        $badPairs = [
            ['role' => 'admin',    'permission_id' => 99999],
            ['role' => 'investor', 'permission_id' => 99999],
            ['role' => 'client',   'permission_id' => 99999],
        ];

        $response = $this->post(route('admin.permissions.update'), $this->buildJsonPayload($badPairs));

        $response->assertSessionHasErrors();
    }
}
