<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('visual_fragments', function (Blueprint $t) {
      $t->string('zone')->nullable()->index()->after('type'); // header|footer|hero|custom
    });
  }
  public function down(): void {
    Schema::table('visual_fragments', function (Blueprint $t) {
      $t->dropColumn('zone');
    });
  }
};
