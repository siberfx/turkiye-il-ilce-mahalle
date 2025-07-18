<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $config = config('turkiye-adresler');
        $tableName = $config['neighborhoods_table'] ?? 'neighborhoods';
        $districtsTable = $config['districts_table'] ?? 'districts';
        $citiesTable = $config['cities_table'] ?? 'cities';

        $relationCityName = Str::singular($citiesTable) . '_id';
        $relationDistrictName = Str::singular($districtsTable) . '_id';


        Schema::create($tableName, function (Blueprint $table) use ($districtsTable, $citiesTable, $relationCityName, $relationDistrictName) {
            $table->id();
            $table->unsignedBigInteger($relationCityName);
            $table->unsignedBigInteger($relationDistrictName);
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('city_id');
            $table->index('district_id');
            $table->index('slug');
            $table->index('is_active');

            // Foreign key constraints
            $table->foreign('city_id')
                  ->references('id')
                  ->on($citiesTable)
                  ->onDelete('cascade');

            $table->foreign('district_id')
                  ->references('id')
                  ->on($districtsTable)
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        $config = config('turkiye-adresler');
        $tableName = $config['neighborhoods_table'] ?? 'neighborhoods';

        Schema::dropIfExists($tableName);
    }
};
