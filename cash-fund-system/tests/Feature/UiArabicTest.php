<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UiArabicTest extends TestCase
{
    use RefreshDatabase;

    private function seedAndLogin(): User
    {
        \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin);
        return $admin;
    }

    public function test_layout_file_has_rtl_attributes(): void
    {
        $content = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('dir="rtl"', $content);
        $this->assertStringContainsString('lang="ar"', $content);
    }

    public function test_category_index_view_has_arabic_title(): void
    {
        $content = file_get_contents(resource_path('views/admin/categories/index.blade.php'));
        $this->assertStringContainsString('إدارة التصنيفات', $content);
    }

    public function test_category_create_view_has_arabic_title(): void
    {
        $content = file_get_contents(resource_path('views/admin/categories/create.blade.php'));
        $this->assertStringContainsString('تصنيف جديد', $content);
    }

    public function test_category_edit_view_has_arabic_title(): void
    {
        $content = file_get_contents(resource_path('views/admin/categories/edit.blade.php'));
        $this->assertStringContainsString('تعديل التصنيف', $content);
    }

    public function test_toggle_status_has_confirm_dialog_pattern(): void
    {
        $content = file_get_contents(resource_path('views/admin/categories/index.blade.php'));
        $this->assertStringContainsString('confirm', $content);
        $this->assertStringContainsString('هل أنت متأكد', $content);
    }

    public function test_category_types_displayed_in_arabic_in_view(): void
    {
        $content = file_get_contents(resource_path('views/admin/categories/index.blade.php'));
        $this->assertStringContainsString('صرف', $content);
        $this->assertStringContainsString('قبض', $content);
        $this->assertStringContainsString('صرف وقبض', $content);
    }

    public function test_status_labels_displayed_in_arabic_in_view(): void
    {
        $content = file_get_contents(resource_path('views/admin/categories/index.blade.php'));
        $this->assertStringContainsString('نشط', $content);
        $this->assertStringContainsString('موقوف', $content);
    }

    public function test_category_form_validation_messages_use_arabic_placeholders(): void
    {
        $createContent = file_get_contents(resource_path('views/admin/categories/create.blade.php'));
        $this->assertStringContainsString('اسم التصنيف', $createContent);
        $this->assertStringContainsString('النوع', $createContent);
    }

    public function test_admin_nav_contains_category_link_in_arabic(): void
    {
        $content = file_get_contents(resource_path('views/components/admin-nav.blade.php'));
        $this->assertStringContainsString('admin.categories', $content);
        $this->assertStringContainsString('إدارة التصنيفات', $content);
    }

    public function test_admin_nav_links_use_consistent_svg_patterns(): void
    {
        $content = file_get_contents(resource_path('views/components/admin-nav.blade.php'));
        $this->assertStringContainsString('svg', $content);
        $this->assertStringContainsString('stroke-linecap="round"', $content);
        $this->assertStringContainsString('rounded-lg', $content);
    }

    public function test_category_index_view_uses_alpine_js_for_toggle(): void
    {
        $content = file_get_contents(resource_path('views/admin/categories/index.blade.php'));
        $this->assertStringContainsString('x-data', $content);
        $this->assertStringContainsString('@submit.prevent', $content);
    }
}
