<?php namespace Uit\Formyaml\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateUitFormyamlSubmits extends Migration
{
    public function up()
    {
        Schema::create('uit_formyaml_submits', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('event');
            $table->integer('user_id')->nullable();
            $table->text('content')->nullable();
            $table->text('info')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('uit_formyaml_submits');
    }
}
