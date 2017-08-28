<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntitiesWithSpecificDatabaseTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::connection('other')->create('sortable_entity_with_specific_databases', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('position')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::connection('other')->table('sortable_entity_with_specific_databases', function (Blueprint $table) {
            $table->drop();
        });
    }
}
