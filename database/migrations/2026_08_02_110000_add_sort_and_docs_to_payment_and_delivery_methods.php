<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Порядок вывода и ссылка на документацию у методов оплаты и доставки.
 *
 * docs_url нужен сидеру: метод создаётся без ключей, и владельцу надо
 * показать, где эти ключи вообще берутся. Хранить ссылку в коде вьюхи
 * нельзя — методы можно заводить руками.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_methods', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('active');
            }
            if (! Schema::hasColumn('payment_methods', 'docs_url')) {
                $table->string('docs_url')->nullable()->after('settings');
            }
        });

        Schema::table('delivery_methods', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_methods', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
            if (! Schema::hasColumn('delivery_methods', 'docs_url')) {
                $table->string('docs_url')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'docs_url']);
        });

        Schema::table('delivery_methods', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'docs_url']);
        });
    }
};
