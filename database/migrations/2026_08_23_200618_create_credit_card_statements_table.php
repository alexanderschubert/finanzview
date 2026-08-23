<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_card_statements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('credit_card_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            $table->date('due_date')->nullable();

            $table->decimal('amount', 14, 2);

            $table->enum('status', [
                'open',
                'issued',
                'paid',
                'overdue',
            ])->default('open');

            $table->timestamps();

            $table->unique([
                'credit_card_id',
                'period_start',
                'period_end',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_card_statements');
    }
};