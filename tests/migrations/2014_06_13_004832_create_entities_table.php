<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEntitiesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sortable_entities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('position')->default('U');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('sortable_entities', function (Blueprint $table) {
            $table->drop();
        });
    }
}
