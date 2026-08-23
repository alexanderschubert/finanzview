<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('account_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');

            $table->string('issuer')->nullable();

            $table->string('last_four', 4)->nullable();

            $table->decimal('credit_limit', 14, 2)->nullable();

            $table->decimal('current_balance', 14, 2)->default(0);

            $table->unsignedTinyInteger('billing_day')->nullable();
            $table->unsignedTinyInteger('payment_due_day')->nullable();

            $table->string('color')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_cards');
    }
};