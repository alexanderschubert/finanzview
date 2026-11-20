<?php

use App\Support\ProviderCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ProviderCatalog::sync();
    }

    public function down(): void
    {
        // Anbieter bleiben erhalten, sie können verknüpft sein.
    }
};
