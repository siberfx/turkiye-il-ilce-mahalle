<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Siberfx\TurkiyePackage\Support\CityOrder;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('turkiye-package.cities_table', 'cities');

        Schema::table($tableName, function (Blueprint $table) {
            // Featured cities first (config: featured_cities), then Turkish alphabetical order
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
        });

        // Existing installations already have cities: order them right away.
        CityOrder::apply();
    }

    public function down(): void
    {
        $tableName = config('turkiye-package.cities_table', 'cities');

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
