<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEntitiesWithChangedFieldTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sortable_entity_with_changed_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('somefield')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('sortable_entity_with_changed_fields', function (Blueprint $table) {
            $table->drop();
        });
    }
}
