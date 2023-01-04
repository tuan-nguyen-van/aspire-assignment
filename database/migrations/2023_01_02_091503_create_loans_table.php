<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onUpdate('restrict')
                ->onDelete('cascade');
            $table->unsignedInteger('amount');
            $table->decimal('remained_principle', 15, 2, true);
            $table->unsignedSmallInteger('term');
            $table->enum('payment_period', ['weekly', 'monthly']);
            $table->date('start_date');
            $table->enum('state', ['approved', 'pending', 'paid'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loans');
    }
};
