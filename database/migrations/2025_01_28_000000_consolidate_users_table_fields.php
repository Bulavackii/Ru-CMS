<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🔄 Объединенная миграция для таблицы users
 * 
 * Объединяет следующие миграции:
 * - add_russian_fields_to_users_table
 * - add_2fa_and_security_fields_to_users_table
 * - add_localization_fields_to_users_table
 * - create_admin_features (поле settings)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Settings (из create_admin_features)
            if (!Schema::hasColumn('users', 'settings')) {
                $table->json('settings')->nullable()->after('is_admin');
            }

            // 2FA и безопасность
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('two_factor_recovery_codes');
            }
            
            // email_verified_at уже есть в базовой миграции, но проверяем на всякий случай
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('two_factor_enabled');
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }

            // Российские поля
            if (!Schema::hasColumn('users', 'postal_code')) {
                $table->string('postal_code', 10)->nullable()->after('zip');
            }
            if (!Schema::hasColumn('users', 'region')) {
                $table->string('region', 100)->nullable()->after('postal_code');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city', 100)->nullable()->after('region');
            }

            // Локализация
            if (!Schema::hasColumn('users', 'country_code')) {
                $table->string('country_code', 2)->nullable()->after('settings')
                    ->comment('Код страны (ISO 3166-1 alpha-2)');
            }
            if (!Schema::hasColumn('users', 'locale')) {
                $table->string('locale', 10)->nullable()->after('country_code')
                    ->comment('Локаль пользователя (ru, en, de, fi и т.д.)');
            }
        });

        // Добавляем индекс для country_code
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'country_code')) {
            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->index('country_code', 'idx_users_country_code');
                } catch (\Throwable $e) {
                    // Индекс уже существует
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Удаление локализации
            if (Schema::hasColumn('users', 'locale')) {
                $table->dropColumn('locale');
            }
            if (Schema::hasColumn('users', 'country_code')) {
                try {
                    $table->dropIndex(['country_code']);
                } catch (\Throwable $e) {}
                $table->dropColumn('country_code');
            }

            // Удаление российских полей
            if (Schema::hasColumn('users', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('users', 'region')) {
                $table->dropColumn('region');
            }
            if (Schema::hasColumn('users', 'postal_code')) {
                $table->dropColumn('postal_code');
            }

            // Удаление полей безопасности
            $securityColumns = [
                'last_login_ip',
                'last_login_at',
                'two_factor_enabled',
                'two_factor_recovery_codes',
                'two_factor_secret',
            ];
            
            foreach ($securityColumns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // email_verified_at не удаляем, так как это стандартное поле Laravel

            // Удаление settings
            if (Schema::hasColumn('users', 'settings')) {
                $table->dropColumn('settings');
            }
        });
    }
};




