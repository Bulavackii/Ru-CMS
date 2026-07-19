<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use Modules\News\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    /** @test */
    public function user_can_list_news()
    {
        News::factory()->count(5)->create(['published' => true]);

        $response = $this->getJson('/api/v1/news');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items',
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                    ],
                ],
            ]);
    }

    /** @test */
    public function user_can_view_single_news()
    {
        $news = News::factory()->create(['published' => true]);

        $response = $this->getJson("/api/v1/news/{$news->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'content',
                ],
            ]);
    }

    /** @test */
    public function authenticated_user_can_create_news()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/admin/news', [
                'title' => 'Test News',
                'content' => 'Test content',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);

        $this->assertDatabaseHas('news', [
            'title' => 'Test News',
        ]);
    }

    /** @test */
    public function authenticated_user_can_update_news()
    {
        $news = News::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/admin/news/{$news->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function authenticated_user_can_delete_news()
    {
        $news = News::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/admin/news/{$news->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('news', [
            'id' => $news->id,
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_news()
    {
        $response = $this->postJson('/api/v1/admin/news', [
            'title' => 'Test News',
            'content' => 'Test content',
        ]);

        $response->assertStatus(401);
    }
}

