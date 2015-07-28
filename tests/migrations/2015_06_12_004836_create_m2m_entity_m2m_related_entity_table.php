<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateM2mEntityM2mRelatedEntityTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('m2m_entity_m2m_related_entity', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('m2m_related_entity_position')->unsigned();

            $table->integer('m2m_entity_id')->unsigned()->index();
            $table->foreign('m2m_entity_id')->references('id')->on('m2m_entity')->onDelete('cascade');
            $table->integer('m2m_related_entity_id')->unsigned()->index();
            $table->foreign('m2m_related_entity_id')->references('id')->on('m2m_related_entity')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('m2m_entity_m2m_related_entity', function (Blueprint $table) {
            $table->drop();
        });
    }
}
