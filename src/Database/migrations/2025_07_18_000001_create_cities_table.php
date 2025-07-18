<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $config = config('turkiye-adresler');
        $tableName = $config['cities_table'] ?? 'cities';

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('slug');
            $table->index('is_active');
        });
    }

    public function down()
    {
        $config = config('turkiye-adresler');
        $tableName = $config['cities_table'] ?? 'cities';

        Schema::dropIfExists($tableName);
    }
};
