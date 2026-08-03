<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\News;
use Tests\TestCase;

class NewsRatingFieldTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User { return User::factory()->create(['is_admin' => true]); }

    public function test_rating_is_saved_for_gaming_template(): void
    {
        $this->actingAs($this->admin())->post('/admin/news', [
            'title' => 'Обзор игры', 'content' => 'Текст обзора.',
            'template' => 'gaming', 'rating' => '8.5', 'published' => '1',
        ])->assertSessionHasNoErrors();

        $news = News::where('title', 'Обзор игры')->firstOrFail();

        $this->assertSame('8.5', (string) $news->rating);
        // Обзор не должен становиться товаром.
        $this->assertNull($news->price);
    }

    public function test_rating_is_cleared_for_other_templates(): void
    {
        // Иначе значение осталось бы висеть после смены шаблона.
        $this->actingAs($this->admin())->post('/admin/news', [
            'title' => 'Обычная новость', 'content' => 'Текст.',
            'template' => 'default', 'rating' => '7', 'published' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull(News::where('title', 'Обычная новость')->firstOrFail()->rating);
    }

    public function test_rating_can_be_edited(): void
    {
        $news = News::create([
            'title' => 'Обзор', 'content' => 'Текст.', 'slug' => 'obzor-test',
            'template' => 'gaming', 'rating' => 5, 'published' => true,
        ]);

        $this->actingAs($this->admin())->put('/admin/news/' . $news->id, [
            'title' => 'Обзор', 'content' => 'Текст.', 'slug' => 'obzor-test',
            'template' => 'gaming', 'rating' => '9.2', 'published' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('9.2', (string) $news->fresh()->rating);
    }
}
