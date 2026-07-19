<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\Categories\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем админ-пользователя для тестов
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    /** @test */
    public function admin_can_view_categories_index()
    {
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.categories.index'));

        $response->assertStatus(200);
        $response->assertViewIs('Categories::admin.index');
    }

    /** @test */
    public function admin_can_create_category()
    {
        $categoryData = [
            'title' => 'Test Category',
            'type' => 'test',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), $categoryData);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['title' => 'Test Category']);
    }

    /** @test */
    public function slug_is_auto_generated_from_title()
    {
        $categoryData = [
            'title' => 'Test Category Name',
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), $categoryData);

        $this->assertDatabaseHas('categories', [
            'title' => 'Test Category Name',
            'slug' => 'test-category-name',
        ]);
    }

    /** @test */
    public function admin_can_update_category()
    {
        $category = Category::factory()->create(['title' => 'Old Title']);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.categories.update', $category->id), [
                'title' => 'New Title',
            ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'title' => 'New Title',
        ]);
    }

    /** @test */
    public function admin_can_delete_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $category->id));

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    /** @test */
    public function category_can_have_parent()
    {
        $parent = Category::factory()->create(['title' => 'Parent']);
        $child = Category::factory()->create([
            'title' => 'Child',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertTrue($parent->children->contains($child));
    }

    /** @test */
    public function category_cannot_be_parent_of_itself()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put(route('admin.categories.update', $category->id), [
                'title' => $category->title,
                'parent_id' => $category->id,
            ]);

        $response->assertSessionHasErrors(['parent_id']);
    }

    /** @test */
    public function can_filter_categories_by_type()
    {
        Category::factory()->create(['type' => 'news']);
        Category::factory()->create(['type' => 'product']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.categories.index', ['type' => 'news']));

        $response->assertStatus(200);
        $categories = $response->viewData('categories');
        $this->assertTrue($categories->every(fn($cat) => $cat->type === 'news'));
    }

    /** @test */
    public function can_filter_categories_by_activity()
    {
        Category::factory()->create(['is_active' => true]);
        Category::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.categories.index', ['is_active' => '1']));

        $response->assertStatus(200);
        $categories = $response->viewData('categories');
        $this->assertTrue($categories->every(fn($cat) => $cat->is_active === true));
    }

    /** @test */
    public function bulk_delete_removes_categories()
    {
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();
        $cat3 = Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.categories.bulkDelete'), [
                'category_ids' => [$cat1->id, $cat2->id],
            ]);

        $response->assertJson(['success' => true]);
        $this->assertSoftDeleted('categories', ['id' => $cat1->id]);
        $this->assertSoftDeleted('categories', ['id' => $cat2->id]);
        $this->assertDatabaseHas('categories', ['id' => $cat3->id]);
    }
}




