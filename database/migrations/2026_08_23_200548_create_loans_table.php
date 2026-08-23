<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('account_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');

            $table->decimal('principal_amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);

            $table->decimal('interest_rate', 6, 3)->nullable();

            $table->decimal('installment_amount', 14, 2);

            $table->unsignedInteger('total_installments')->nullable();
            $table->unsignedInteger('paid_installments')->default(0);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->enum('type', [
                'loan',
                'installment',
                'paypal_installment',
                'other',
            ])->default('loan');

            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};