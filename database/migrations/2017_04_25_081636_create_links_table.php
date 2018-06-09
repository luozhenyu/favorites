<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('links', function (Blueprint $table) {
            $table->increments('id');
            $table->text('url');
            $table->text('title')->nullable();
            $table->text('cover')->nullable();
            $table->text('abstract')->nullable();
            $table->mediumText('content')->nullable();
            $table->text('tags')->nullable();
            $table->timestamps();

            $table->unsignedInteger('user_id');
            $table->unsignedInteger('category_id')->nullable();
        });

        DB::statement('ALTER TABLE `links` ADD FULLTEXT INDEX `ft_index`(`title`,`tags`,`content`) WITH PARSER ngram;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('links');
    }
}
