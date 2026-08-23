<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->decimal('amount', 14, 2);

            $table->date('start_date');
            $table->date('end_date');

            $table->enum('period', [
                'monthly',
                'yearly',
                'custom',
            ])->default('monthly');

            $table->boolean('is_active')->default(true);

            $table->string('color')->nullable();
            $table->string('icon')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};