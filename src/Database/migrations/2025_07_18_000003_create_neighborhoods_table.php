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

        $relationCityName = $config['cities_relation_id'] ?? 'city_id';
        $relationDistrictName = $config['districts_relation_id'] ?? 'district_id';


        Schema::create($tableName, function (Blueprint $table) use ($districtsTable, $citiesTable, $relationCityName, $relationDistrictName) {
            $table->id();
            $table->unsignedBigInteger($relationCityName);
            $table->unsignedBigInteger($relationDistrictName);
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);

            // Indexes
            $table->index($relationCityName);
            $table->index($relationDistrictName);
            $table->index('slug');
            $table->index('is_active');

            // Foreign key constraints
            $table->foreign($relationCityName)
                  ->references('id')
                  ->on($citiesTable)
                  ->onDelete('cascade');

            $table->foreign($relationDistrictName)
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
