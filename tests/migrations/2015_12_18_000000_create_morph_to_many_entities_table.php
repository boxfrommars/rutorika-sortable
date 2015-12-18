<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMorphToManyEntitiesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('morph_to_many_entity_ones', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('morph_to_many_entity_twos', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('morph_to_many_entity_ones', function (Blueprint $table) {
            $table->drop();
        });

        Schema::table('morph_to_many_entity_twos', function (Blueprint $table) {
            $table->drop();
        });
    }
}
