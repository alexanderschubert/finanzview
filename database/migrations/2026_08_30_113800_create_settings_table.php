<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('currency', 3)
                ->default('EUR');

            $table->unsignedTinyInteger('decimal_places')
                ->default(2);

            $table->string('date_format')
                ->default('d.m.Y');

            $table->unsignedTinyInteger('first_day_of_week')
                ->default(1);

            $table->unsignedBigInteger('default_account_id')
                ->nullable();

            $table->unsignedBigInteger('default_category_id')
                ->nullable();

            $table->unsignedTinyInteger('month_start_day')
                ->default(1);

            $table->timestamps();

            $table->foreign('default_account_id')
                ->references('id')
                ->on('accounts')
                ->nullOnDelete();

            $table->foreign('default_category_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
