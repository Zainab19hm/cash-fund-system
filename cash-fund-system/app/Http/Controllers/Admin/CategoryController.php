<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeManageCategories();

        $query = Category::query();

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        $categories = $query->latest()->paginate(15)->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $this->authorizeManageCategories();

        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $this->authorizeManageCategories();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->where(fn ($q) => $q->where('type', $request->type)),
            ],
            'type' => 'required|in:payment,receipt,both',
        ]);

        $validated['is_active'] = true;

        $category = Category::create($validated);

        $this->logAudit('create', $category->id);

        return redirect()->route('admin.categories.index')
            ->with('success', 'تم إنشاء التصنيف بنجاح');
    }

    public function edit(Category $category)
    {
        $this->authorizeManageCategories();

        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeManageCategories();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->where(fn ($q) => $q->where('type', $request->type))->ignore($category->id),
            ],
            'type' => 'required|in:payment,receipt,both',
        ]);

        $category->update($validated);

        $this->logAudit('update', $category->id);

        return redirect()->route('admin.categories.index')
            ->with('success', 'تم تعديل التصنيف بنجاح');
    }

    public function toggleStatus(Category $category)
    {
        $this->authorizeManageCategories();

        $category->update(['is_active' => !$category->is_active]);

        $this->logAudit('update', $category->id, 'toggle_status');

        return redirect()->route('admin.categories.index')
            ->with('success', $category->is_active
                ? 'تم تفعيل التصنيف بنجاح'
                : 'تم إيقاف التصنيف بنجاح');
    }

    private function authorizeManageCategories(): void
    {
        $userId = auth()->id();
        $userRole = auth()->user()->role;

        $permission = DB::table('permissions')
            ->where('key', 'manage_categories')
            ->first();

        if (!$permission) {
            abort(403, 'غير مصرح لك بهذه العملية.');
        }

        $has = DB::table('role_permissions')
            ->where('role', $userRole)
            ->where('permission_id', $permission->id)
            ->exists();

        if (!$has) {
            abort(403, 'غير مصرح لك بهذه العملية.');
        }
    }

    private function logAudit(string $action, int $entityId, ?string $notes = null): void
    {
        DB::table('log_audit')->insert([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'entity_type' => 'categories',
            'entity_id'   => $entityId,
            'notes'       => $notes,
            'created_at'  => now(),
        ]);
    }
}
