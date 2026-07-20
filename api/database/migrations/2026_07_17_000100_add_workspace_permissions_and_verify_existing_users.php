<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_members', function (Blueprint $table) {
            $table->boolean('can_create_campaigns')->default(true)->after('role');
            $table->boolean('can_view_metrics')->default(true)->after('can_create_campaigns');
        });

        Schema::table('workspace_invitations', function (Blueprint $table) {
            $table->boolean('can_create_campaigns')->default(true)->after('role');
            $table->boolean('can_view_metrics')->default(true)->after('can_create_campaigns');
        });

        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('workspace_members', function (Blueprint $table) {
            $table->dropColumn(['can_create_campaigns', 'can_view_metrics']);
        });

        Schema::table('workspace_invitations', function (Blueprint $table) {
            $table->dropColumn(['can_create_campaigns', 'can_view_metrics']);
        });
    }
};
