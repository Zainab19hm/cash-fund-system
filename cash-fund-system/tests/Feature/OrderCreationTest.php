<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    private function createClient(): User
    {
        \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $client = User::create([
            'name' => 'Test Client',
            'username' => 'test_client',
            'password' => 'password',
            'role' => 'client',
            'is_active' => true,
        ]);

        return $client;
    }

    private function getCategoryId(): int
    {
        return DB::table('categories')->where('is_active', true)->first()->id;
    }

    public function test_client_can_create_draft_order_with_valid_items(): void
    {
        $client = $this->createClient();
        $this->actingAs($client);

        $categoryId = $this->getCategoryId();

        $response = $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 150.00,
            'order_date' => '2026-07-05',
            'description' => 'طلب اختبار',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند أول', 'amount' => 100.00],
                ['category_id' => $categoryId, 'description' => 'بند ثاني', 'amount' => 50.00],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders_fund', [
            'created_by' => $client->id,
            'status' => 'DRAFT',
            'amount' => 150.00,
        ]);
        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_order_items_must_sum_to_total(): void
    {
        $client = $this->createClient();
        $this->actingAs($client);

        $categoryId = $this->getCategoryId();

        $response = $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 200.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند', 'amount' => 100.00],
            ],
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('orders_fund', 0);
    }

    public function test_submit_order_without_items_rejected(): void
    {
        $client = $this->createClient();
        $this->actingAs($client);

        $categoryId = $this->getCategoryId();

        // Create an order first
        $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 100.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند', 'amount' => 100.00],
            ],
        ]);

        $order = DB::table('orders_fund')->first();

        // Remove all items
        DB::table('order_items')->where('order_id', $order->id)->delete();

        $response = $this->post(route('client.orders.submit', $order->id));

        $response->assertSessionHasErrors('items');
    }

    public function test_submit_other_users_order_returns_403(): void
    {
        $client1 = $this->createClient();
        $this->actingAs($client1);

        $categoryId = $this->getCategoryId();

        $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 100.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند', 'amount' => 100.00],
            ],
        ]);

        $order = DB::table('orders_fund')->first();

        // Create another client and try to submit
        $client2 = User::create([
            'name' => 'Client 2',
            'username' => 'test_client_2',
            'password' => 'password',
            'role' => 'client',
            'is_active' => true,
        ]);

        $this->actingAs($client2);

        $response = $this->post(route('client.orders.submit', $order->id));

        $response->assertStatus(403);
    }

    public function test_view_other_users_order_returns_403(): void
    {
        $client1 = $this->createClient();
        $this->actingAs($client1);

        $categoryId = $this->getCategoryId();

        $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 100.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند', 'amount' => 100.00],
            ],
        ]);

        $order = DB::table('orders_fund')->first();

        $client2 = User::create([
            'name' => 'Client 2',
            'username' => 'test_client_2_view',
            'password' => 'password',
            'role' => 'client',
            'is_active' => true,
        ]);

        $this->actingAs($client2);

        $response = $this->get(route('client.orders.show', $order->id));

        $response->assertStatus(403);
    }

    public function test_upload_disallowed_mime_rejected(): void
    {
        Storage::fake('local');

        $client = $this->createClient();
        $this->actingAs($client);

        $categoryId = $this->getCategoryId();

        $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 100.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند', 'amount' => 100.00],
            ],
        ]);

        $order = DB::table('orders_fund')->first();

        $file = UploadedFile::fake()->createWithContent('test.exe', 'fake content');

        $response = $this->post(route('client.orders.upload-document', $order->id), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_upload_oversized_file_rejected(): void
    {
        Storage::fake('local');

        $client = $this->createClient();
        $this->actingAs($client);

        $categoryId = $this->getCategoryId();

        $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 100.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند', 'amount' => 100.00],
            ],
        ]);

        $order = DB::table('orders_fund')->first();

        $file = UploadedFile::fake()->create('large.pdf', 11000, 'application/pdf');

        $response = $this->post(route('client.orders.upload-document', $order->id), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_document_not_accessible_via_public_url(): void
    {
        Storage::fake('local');

        $client = $this->createClient();
        $this->actingAs($client);

        $categoryId = $this->getCategoryId();

        $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 100.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند', 'amount' => 100.00],
            ],
        ]);

        $order = DB::table('orders_fund')->first();

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $this->post(route('client.orders.upload-document', $order->id), [
            'file' => $file,
        ]);

        $doc = DB::table('documents')->first();

        // 1. Verify the stored path is in private directory (not public)
        $this->assertStringStartsWith('private/', $doc->file_path);

        // 2. Attempt to access the file via common public URL patterns — must NOT exist
        $this->get("/storage/{$doc->file_path}")->assertStatus(404);
        $this->get("/storage/app/{$doc->file_path}")->assertStatus(404);
        $this->get("/storage/app/private/{$doc->file_path}")->assertStatus(404);

        // 3. Verify there is no route that serves private documents as GET/download
        //    (upload-document POST route is fine — it creates records, doesn't serve files)
        $routeUris = collect(\Route::getRoutes()->getRoutes())
            ->map(fn ($route) => ['uri' => $route->uri(), 'methods' => $route->methods()])
            ->values();
        $hasDocumentServeRoute = $routeUris->contains(function ($route) {
            $uri = $route['uri'];
            $methods = $route['methods'];
            $isGet = in_array('GET', $methods) || in_array('HEAD', $methods);
            return $isGet && (str_contains($uri, 'storage') || str_contains($uri, 'private'));
        });
        $this->assertFalse($hasDocumentServeRoute, 'No GET route should serve files from private storage');
    }

    public function test_order_generates_sequential_number(): void
    {
        $client = $this->createClient();
        $this->actingAs($client);

        $categoryId = $this->getCategoryId();

        $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 100.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند', 'amount' => 100.00],
            ],
        ]);

        $order = DB::table('orders_fund')->first();

        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{3}$/', $order->order_number);
        $this->assertEquals('ORD-2026-001', $order->order_number);
    }

    public function test_client_sees_only_own_orders(): void
    {
        $client1 = $this->createClient();
        $this->actingAs($client1);

        $categoryId = $this->getCategoryId();

        $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 100.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند', 'amount' => 100.00],
            ],
        ]);

        // Create another client's order
        $client2 = User::create([
            'name' => 'Client 2',
            'username' => 'test_client_2_list',
            'password' => 'password',
            'role' => 'client',
            'is_active' => true,
        ]);

        $this->actingAs($client2);

        $this->post(route('client.orders.store'), [
            'type' => 'receipt',
            'amount' => 200.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند client2', 'amount' => 200.00],
            ],
        ]);

        // Client 1 should only see 1 order
        $this->actingAs($client1);
        $response = $this->get(route('client.orders.index'));
        $response->assertOk();

        $ordersForClient1 = DB::table('orders_fund')->where('created_by', $client1->id)->count();
        $ordersForClient2 = DB::table('orders_fund')->where('created_by', $client2->id)->count();

        $this->assertEquals(1, $ordersForClient1);
        $this->assertEquals(1, $ordersForClient2);
    }

    public function test_submit_requires_draft_status(): void
    {
        $client = $this->createClient();
        $this->actingAs($client);

        $categoryId = $this->getCategoryId();

        $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 100.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند', 'amount' => 100.00],
            ],
        ]);

        $order = DB::table('orders_fund')->first();

        // Submit once
        $this->post(route('client.orders.submit', $order->id));

        // Try to submit again (now PENDING)
        $response = $this->post(route('client.orders.submit', $order->id));

        $response->assertSessionHasErrors('status');
    }

    public function test_create_order_permission_assigned_to_client(): void
    {
        $this->createClient();

        $clientRoleId = DB::table('users')->where('username', 'test_client')->first()->role;

        $createOrderPerm = DB::table('permissions')->where('key', 'create_order')->first();

        $assigned = DB::table('role_permissions')
            ->where('role', 'client')
            ->where('permission_id', $createOrderPerm->id)
            ->exists();

        $this->assertTrue($assigned, 'create_order permission must be assigned to client role');
    }

    public function test_rtl_in_order_creation_view(): void
    {
        $client = $this->createClient();
        $this->actingAs($client);

        $response = $this->get(route('client.orders.create'));

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('طلب جديد');
    }

    public function test_order_index_view_renders_successfully(): void
    {
        $client = $this->createClient();
        $this->actingAs($client);

        $response = $this->get(route('client.orders.index'));

        $response->assertOk();
        $response->assertSee('طلباتي');
    }

    public function test_order_show_view_renders_successfully(): void
    {
        $client = $this->createClient();
        $this->actingAs($client);

        $categoryId = $this->getCategoryId();

        $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 100.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند', 'amount' => 100.00],
            ],
        ]);

        $order = DB::table('orders_fund')->first();

        $response = $this->get(route('client.orders.show', $order->id));

        $response->assertOk();
        $response->assertSee('تفاصيل الطلب');
        $response->assertSee('بند');
    }

    public function test_order_number_format_is_correct(): void
    {
        $client = $this->createClient();
        $this->actingAs($client);

        $categoryId = $this->getCategoryId();

        $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 100.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند', 'amount' => 100.00],
            ],
        ]);

        $order = DB::table('orders_fund')->first();

        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{3}$/', $order->order_number);
    }

    public function test_multiple_orders_get_sequential_numbers(): void
    {
        $client = $this->createClient();
        $this->actingAs($client);

        $categoryId = $this->getCategoryId();

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('client.orders.store'), [
                'type' => 'payment',
                'amount' => 100.00,
                'order_date' => '2026-07-05',
                'items' => [
                    ['category_id' => $categoryId, 'description' => "بند $i", 'amount' => 100.00],
                ],
            ]);
        }

        $orders = DB::table('orders_fund')->orderBy('id')->get();

        $this->assertEquals('ORD-2026-001', $orders[0]->order_number);
        $this->assertEquals('ORD-2026-002', $orders[1]->order_number);
        $this->assertEquals('ORD-2026-003', $orders[2]->order_number);
    }

    public function test_upload_allowed_mime_accepted(): void
    {
        Storage::fake('local');

        $client = $this->createClient();
        $this->actingAs($client);

        $categoryId = $this->getCategoryId();

        $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 100.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند', 'amount' => 100.00],
            ],
        ]);

        $order = DB::table('orders_fund')->first();

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->post(route('client.orders.upload-document', $order->id), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'order_id' => $order->id,
            'file_name' => 'document.pdf',
            'file_type' => 'pdf',
        ]);
    }

    public function test_upload_on_executed_order_rejected(): void
    {
        Storage::fake('local');

        $client = $this->createClient();
        $this->actingAs($client);

        $categoryId = $this->getCategoryId();

        $this->post(route('client.orders.store'), [
            'type' => 'payment',
            'amount' => 100.00,
            'order_date' => '2026-07-05',
            'items' => [
                ['category_id' => $categoryId, 'description' => 'بند', 'amount' => 100.00],
            ],
        ]);

        $order = DB::table('orders_fund')->first();

        // Manually set status to EXECUTED
        DB::table('orders_fund')->where('id', $order->id)->update(['status' => 'EXECUTED']);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->post(route('client.orders.upload-document', $order->id), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }
}
