<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Categories\Console\Commands\SeedDefaultCategoriesCommand as Categories;
use Tests\TestCase;

/**
 * Категории по умолчанию и раскладка по ним материалов.
 *
 * Раньше категории заводились прямо в мастере установки и доставались только
 * новостям двух шаблонов из пяти; страницы и медиатека оставались без них
 * вовсе. Здесь закреплено обратное: после установки категория есть у каждого
 * материала, а повторный прогон ничего не задваивает.
 */
class DefaultCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private function news(string $template, string $slug): int
    {
        return DB::table('news')->insertGetId([
            'title'      => 'Материал ' . $slug,
            'slug'       => $slug,
            'content'    => '<p>x</p>',
            'template'   => $template,
            'published'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function page(string $slug): int
    {
        return DB::table('pages')->insertGetId([
            'title'      => 'Страница ' . $slug,
            'slug'       => $slug,
            'content'    => '<p>x</p>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function file(string $name): int
    {
        return DB::table('files')->insertGetId([
            'name'          => $name,
            'original_name' => $name,
            'path'          => 'files/' . $name,
            'mime_type'     => 'application/octet-stream',
            'size'          => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    private function categoryId(string $slug): int
    {
        return (int) DB::table('categories')->where('slug', $slug)->value('id');
    }

    public function test_news_are_spread_across_categories_by_template(): void
    {
        $magazine = $this->news('magazine', 'zhurnal-1');
        $clinic   = $this->news('clinic', 'klinika-1');
        $gaming   = $this->news('gaming', 'igry-1');

        Categories::seed();

        foreach ([[$magazine, 'magazine'], [$clinic, 'clinic'], [$gaming, 'gaming']] as [$newsId, $slug]) {
            $this->assertDatabaseHas('news_category', [
                'news_id'     => $newsId,
                'category_id' => $this->categoryId($slug),
            ]);
        }
    }

    public function test_news_without_template_go_to_the_news_category(): void
    {
        // Шаблон записывается то как «default», то пустой строкой, то NULL —
        // три состояния одной группы, и все три обязаны попасть в «Новости».
        $ids = [$this->news('default', 'a'), $this->news('', 'b')];

        DB::table('news')->where('id', $ids[1])->update(['template' => null]);

        Categories::seed();

        foreach ($ids as $id) {
            $this->assertDatabaseHas('news_category', [
                'news_id'     => $id,
                'category_id' => $this->categoryId('news'),
            ]);
        }
    }

    public function test_pages_and_files_get_categories_too(): void
    {
        $page = $this->page('o-proekte');
        $logo = $this->file('ru-cms-logo.svg');
        $clip = $this->file('sample-5s.mp4');

        Categories::seed();

        $this->assertDatabaseHas('page_category', [
            'page_id'     => $page,
            'category_id' => $this->categoryId('info'),
        ]);
        $this->assertDatabaseHas('files', ['id' => $logo, 'category_id' => $this->categoryId('site-design')]);
        $this->assertDatabaseHas('files', ['id' => $clip, 'category_id' => $this->categoryId('media')]);
    }

    public function test_seeding_twice_changes_nothing(): void
    {
        $this->news('magazine', 'zhurnal-1');
        $this->page('kontakty');
        $this->file('photo.jpg');

        Categories::seed();

        $second = Categories::seed();

        $this->assertSame(0, $second['news'], 'Повторный прогон задваивает привязку новостей');
        $this->assertSame(0, $second['pages'], 'Повторный прогон задваивает привязку страниц');
        $this->assertSame(0, $second['files'], 'Повторный прогон переприкрепляет файлы');
    }

    public function test_manual_choices_are_never_overwritten(): void
    {
        $own = DB::table('categories')->insertGetId([
            'title' => 'Моя', 'slug' => 'moya', 'type' => 'file',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $file = $this->file('logo.svg');
        DB::table('files')->where('id', $file)->update(['category_id' => $own]);

        $news = $this->news('magazine', 'zhurnal-2');
        DB::table('news_category')->insert(['news_id' => $news, 'category_id' => $own]);

        Categories::seed();

        // У файла категория одна: перезапись стёрла бы выбор владельца.
        $this->assertDatabaseHas('files', ['id' => $file, 'category_id' => $own]);

        // У новости категорий может быть несколько, но раз владелец уже
        // проставил свою — второй, «правильной» по шаблону, не навязываем.
        $this->assertSame(1, DB::table('news_category')->where('news_id', $news)->count());
    }

    public function test_existing_categories_keep_their_titles_but_gain_icons(): void
    {
        // «Новости» и «Товары» существуют с самых первых установок — без
        // иконки. Название владелец мог поменять под себя, поэтому трогаем
        // только пустое.
        DB::table('categories')->insert([
            'title' => 'Моя лента', 'slug' => 'news', 'type' => 'news',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        Categories::seed();

        $category = DB::table('categories')->where('slug', 'news')->first();

        $this->assertSame('Моя лента', $category->title, 'Название владельца переписано');
        $this->assertNotEmpty($category->icon, 'Пустая иконка не дозаполнена');
    }
}
