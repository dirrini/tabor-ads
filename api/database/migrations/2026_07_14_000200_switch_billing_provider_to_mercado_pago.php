<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['billing_customers', 'subscriptions', 'payment_webhook_events'] as $table) {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN provider SET DEFAULT 'mercadopago'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['billing_customers', 'subscriptions', 'payment_webhook_events'] as $table) {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN provider SET DEFAULT 'pagarme'");
        }
    }
};
