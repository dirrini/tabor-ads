<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('provider_user_id');
            $table->string('avatar_url')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_user_id']);
        });

        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('plan_override')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_workspace_id')->nullable()->after('status')->constrained('workspaces')->nullOnDelete();
        });

        Schema::create('workspace_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->unique(['workspace_id', 'user_id']);
        });

        Schema::create('workspace_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 20)->default('member');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'email']);
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('kind', 20)->default('standard');
            $table->string('status', 20)->default('draft');
            $table->uuid('public_id')->unique();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'status']);
        });

        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status', 20)->default('active');
            $table->uuid('tracking_key')->unique();
            $table->text('destination_url')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->index(['campaign_id', 'status']);
        });

        if (Schema::hasTable('ad_impressions')) {
            Schema::rename('ad_impressions', 'legacy_ad_impressions');
        }

        Schema::create('ad_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20)->default('tracking_pixel');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('browser', 50)->default('Other');
            $table->string('platform', 50)->default('Other');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['ad_id', 'created_at']);
            $table->index(['browser', 'created_at']);
        });

        Schema::create('billing_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->default('mercadopago');
            $table->string('provider_customer_id')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'provider']);
            $table->unique(['provider', 'provider_customer_id']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->default('mercadopago');
            $table->string('provider_subscription_id')->nullable();
            $table->string('provider_plan_id')->nullable();
            $table->string('plan_code', 30)->default('premium');
            $table->string('status', 30)->default('pending');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('grace_until')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_subscription_id']);
            $table->index(['workspace_id', 'status']);
        });

        Schema::create('payment_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('provider_invoice_id');
            $table->unsignedBigInteger('amount_cents')->default(0);
            $table->string('status', 30);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['subscription_id', 'provider_invoice_id']);
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30)->default('mercadopago');
            $table->string('provider_event_id');
            $table->string('type');
            $table->json('payload');
            $table->string('status', 20)->default('received');
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payment_invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('billing_customers');
        Schema::dropIfExists('ad_impressions');
        if (Schema::hasTable('legacy_ad_impressions')) {
            Schema::rename('legacy_ad_impressions', 'ad_impressions');
        }
        Schema::dropIfExists('ads');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('workspace_invitations');
        Schema::dropIfExists('workspace_members');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_workspace_id');
        });
        Schema::dropIfExists('workspaces');
        Schema::dropIfExists('oauth_identities');
    }
};
