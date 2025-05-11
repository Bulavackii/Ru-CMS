<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('address')->nullable()->after('name');
            $table->string('phone', 50)->nullable()->after('address');
            $table->string('telegram', 50)->nullable()->after('phone');
            $table->string('whatsapp', 50)->nullable()->after('telegram');
            $table->string('vk')->nullable()->after('whatsapp');
            $table->string('zip', 20)->nullable()->after('vk');

            $table->boolean('is_company')->default(false)->after('zip');
            $table->string('company_name')->nullable()->after('is_company');
            $table->string('inn', 20)->nullable()->after('company_name');
            $table->string('ogrn', 20)->nullable()->after('inn');
            $table->string('ceo')->nullable()->after('ogrn');
            $table->string('address_legal')->nullable()->after('ceo');
            $table->string('address_actual')->nullable()->after('address_legal');
            $table->string('okato', 20)->nullable()->after('address_actual');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'address', 'phone', 'telegram', 'whatsapp', 'vk', 'zip',
                'is_company', 'company_name', 'inn', 'ogrn',
                'ceo', 'address_legal', 'address_actual', 'okato'
            ]);
        });
    }
};
