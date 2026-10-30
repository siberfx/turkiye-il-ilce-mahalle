<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('turkiye-package.neighborhoods_table', 'neighborhoods');

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            // mahalle, koy or bagli (mevkii, mezra, ... attached to a köy)
            $table->string('type', 16)->default('mahalle')->index();

            // The köy a bağlı belongs to
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->foreign('parent_id')
                ->references('id')
                ->on($tableName)
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        $tableName = config('turkiye-package.neighborhoods_table', 'neighborhoods');

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['type']);
            $table->dropColumn(['parent_id', 'type']);
        });
    }
};
