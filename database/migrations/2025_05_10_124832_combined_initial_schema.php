<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Таблица users
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Добавление поля is_admin
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_admin')->default(false)->after('password');
            });
        }

        // Таблица password_reset_tokens
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // Таблица sessions
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        // Таблица cache
        if (!Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        // Таблица cache_locks
        if (!Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        // Таблица jobs
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        // Таблица job_batches
        if (!Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        // Таблица failed_jobs
        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        // Таблица personal_access_tokens
        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        // Таблица categories
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->timestamps();
            });
        }

        // Таблица news
        if (!Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('content')->nullable();
                $table->boolean('published')->default(false);
                $table->string('template')->nullable();
                $table->timestamps();
            });
        }

        // Добавление полей SEO, price, stock, is_promo
        Schema::table('news', function (Blueprint $table) {
            if (!Schema::hasColumn('news', 'meta_title')) $table->string('meta_title')->nullable();
            if (!Schema::hasColumn('news', 'meta_description')) $table->string('meta_description')->nullable();
            if (!Schema::hasColumn('news', 'meta_keywords')) $table->string('meta_keywords')->nullable();
            if (!Schema::hasColumn('news', 'meta_header')) $table->string('meta_header')->nullable();
            if (!Schema::hasColumn('news', 'price')) $table->decimal('price', 10, 2)->nullable();
            if (!Schema::hasColumn('news', 'stock')) $table->integer('stock')->nullable();
            if (!Schema::hasColumn('news', 'is_promo')) $table->boolean('is_promo')->default(false);
        });

        // Таблица news_category
        if (!Schema::hasTable('news_category')) {
            Schema::create('news_category', function (Blueprint $table) {
                $table->id();
                $table->foreignId('news_id')->constrained('news')->onDelete('cascade');
                $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // Таблица modules
        if (!Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('version');
                $table->boolean('active')->default(false);
                $table->timestamps();
            });
        }

        // Таблица slideshows
        if (!Schema::hasTable('slideshows')) {
            Schema::create('slideshows', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('published')->default(false);
                $table->timestamps();
            });
        }

        // Добавление поля position
        if (Schema::hasTable('slideshows') && !Schema::hasColumn('slideshows', 'position')) {
            Schema::table('slideshows', function (Blueprint $table) {
                $table->string('position')->default('top');
            });
        }

        // Таблица slides
        if (!Schema::hasTable('slides')) {
            Schema::create('slides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('slideshow_id')->constrained('slideshows')->onDelete('cascade');
                $table->string('media_path');
                $table->enum('media_type', ['image', 'video']);
                $table->text('caption')->nullable();
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        // Таблица slideshow_items (если используется)
        if (Schema::hasTable('slideshow_items')) {
            Schema::table('slideshow_items', function (Blueprint $table) {
                if (!Schema::hasColumn('slideshow_items', 'caption')) {
                    $table->string('caption')->nullable()->after('media_type');
                }
                if (!Schema::hasColumn('slideshow_items', 'order')) {
                    $table->integer('order')->default(0);
                }
            });
        }

        // Таблица notifications
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('notifications', 'enabled')) {
                    $table->boolean('enabled')->default(true)->after('cookie_key');
                }
                if (!Schema::hasColumn('notifications', 'bg_color')) {
                    $table->string('bg_color', 20)->nullable()->after('icon');
                }
                if (!Schema::hasColumn('notifications', 'text_color')) {
                    $table->string('text_color', 20)->nullable()->after('bg_color');
                }
            });
        }
    }

    public function down(): void
    {
        // Здесь можно добавить откат по необходимости
    }
};
