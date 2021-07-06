<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediumItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medium_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('medium_id');
            $table->morphs('media_item');

            $table
                ->foreign('medium_id')
                ->references('id')
                ->on('media')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('medium_items');
    }
}
