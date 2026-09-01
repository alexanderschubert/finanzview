<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_providers', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->enum('type', [
                'bank',
                'payment',
                'card',
                'lender',
                'other',
            ])->default('other');

            $table->string('logo')->nullable();
            $table->string('emoji', 20)->nullable();
            $table->string('color', 20)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_providers');
    }
};
