<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up()
    {
        $config = config('turkiye-adresler');
        $tableName = $config['districts_table'] ?? 'districts';
        $citiesTable = $config['cities_table'] ?? 'cities';

        $relationName = $config['cities_relation_id'] ?? 'city_id';

        Schema::create($tableName, function (Blueprint $table) use ($citiesTable, $relationName) {
            $table->id();
            $table->unsignedBigInteger($relationName);
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);

            // Indexes
            $table->index($relationName);
            $table->index('slug');
            $table->index('is_active');

            // Foreign key constraints
            $table->foreign($relationName)
                ->references('id')
                ->on($citiesTable)
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        $config = config('turkiye-adresler');
        $tableName = $config['districts_table'] ?? 'districts';

        Schema::dropIfExists($tableName);
    }
};
