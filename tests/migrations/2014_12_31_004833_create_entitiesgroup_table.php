<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntitiesGroupTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sortable_entities_group', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('position')->default(1);
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
