<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('account_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->date('transaction_date');

            $table->string('description');
            $table->text('notes')->nullable();

            $table->decimal('amount', 14, 2);

            $table->enum('type', [
                'income',
                'expense',
                'transfer',
            ]);

            $table->string('currency', 3)->default('EUR');

            $table->string('merchant')->nullable();
            $table->string('reference')->nullable();

            $table->boolean('is_pending')->default(false);
            $table->boolean('is_recurring')->default(false);

            $table->string('external_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'user_id',
                'transaction_date',
            ]);

            $table->index([
                'account_id',
                'transaction_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};