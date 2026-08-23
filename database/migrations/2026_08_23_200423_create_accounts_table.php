<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('institution')->nullable();

            $table->enum('type', [
                'checking',
                'savings',
                'credit_card',
                'paypal',
                'cash',
                'investment',
                'loan',
                'other',
            ])->default('checking');

            $table->string('currency', 3)->default('EUR');

            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('credit_limit', 14, 2)->nullable();

            $table->string('iban')->nullable();
            $table->string('account_number')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('include_in_total')->default(true);

            $table->string('color')->nullable();
            $table->string('icon')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};