<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEntitiesGroupTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sortable_entities_group', function (Blueprint $table) {
            $table->increments('id');
            $table->string('position')->default('U');
            $table->string('category');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('sortable_entities_group', function (Blueprint $table) {
            $table->drop();
        });
    }
}
