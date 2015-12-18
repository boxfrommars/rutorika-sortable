<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMorphablesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('morphables', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('morph_to_many_related_entity_id')->unsigned();
            $table->integer('morph_to_many_related_entity_position')->unsigned();
            $table->integer('morphable_id')->unsigned()->index();
            $table->string('morphable_type')->index();
            $table->foreign('morph_to_many_related_entity_id')->references('id')->on('morph_to_many_related_entity')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('morphables', function (Blueprint $table) {
            $table->drop();
        });
    }
}
