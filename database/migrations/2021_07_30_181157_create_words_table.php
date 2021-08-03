<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWordsTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create( 'words', function ( Blueprint $table ) {
            $table->bigIncrements( 'id' );
            $table->string( 'title' );
            $table->string( 'slug' );
            $table->string( 'british_title' )->nullable();
            $table->string( 'british_slug' )->nullable();
            $table->unsignedBigInteger( 'category_id' )->nullable();
            $table->integer( 'ordering' )->nullable();
            $table->string( 'tag_ids', 1024 )->nullable();
            $table->longText( 'content' )->nullable();
            $table->string( 'author_name' )->nullable();
            $table->unsignedBigInteger( 'author_id' );
            $table->unsignedBigInteger( 'image_id' )->nullable();
            $table->string( 'keywords' )->nullable();
            $table->integer( 'hits' )->default( 0 );
            $table->integer( 'comments_count' )->default( 0 );
            $table->unsignedBigInteger( 'created_by' );
            $table->unsignedBigInteger( 'updated_by' );
            $table->integer( 'status' );
            $table->timestamps();
            $table->softDeletes();

            $table->foreign( 'category_id' )
                  ->references( 'id' )
                  ->on( 'categories' )
                  ->onDelete( 'SET NULL' );
            $table->foreign( 'author_id' )
                  ->references( 'id' )
                  ->on( 'users' );
            $table->foreign( 'created_by' )
                  ->references( 'id' )
                  ->on( 'users' );
            $table->foreign( 'updated_by' )
                  ->references( 'id' )
                  ->on( 'users' );
            $table->foreign( 'image_id' )
                  ->references( 'id' )
                  ->on( 'media' );

        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
//        Schema::dropIfExists( 'posts' );
    }
}
