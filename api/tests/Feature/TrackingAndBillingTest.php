<?php

namespace Tests\Feature;

use App\Events\WorkspacePlanUpdated;
use App\Models\Ad;
use App\Models\Campaign;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use App\Services\MercadoPagoClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrackingAndBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_pixel_records_an_impression(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
        $workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        $campaign = Campaign::create(['workspace_id' => $workspace->id, 'created_by' => $user->id, 'name' => 'Site', 'kind' => 'standard', 'status' => 'active', 'public_id' => Str::uuid()]);
        $ad = Ad::create(['campaign_id' => $campaign->id, 'name' => 'Hero', 'status' => 'active', 'tracking_key' => Str::uuid()]);

        $this->get('/t/'.$ad->tracking_key.'.gif', ['User-Agent' => 'Mozilla Chrome Windows'])->assertOk()->assertHeader('Content-Type', 'image/gif');
        $this->assertDatabaseHas('ad_impressions', ['ad_id' => $ad->id, 'browser' => 'Chrome', 'source' => 'tracking_pixel']);
    }

    public function test_payment_webhooks_are_idempotent_and_activate_premium(): void
    {
        Event::fake([WorkspacePlanUpdated::class]);
        $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
        config(['mercadopago.access_token' => 'TEST-token']);
        Http::fake(['https://api.mercadopago.com/v1/payments/123' => Http::response([
            'id' => 123, 'status' => 'approved', 'external_reference' => 'workspace:'.$workspace->id,
            'metadata' => ['workspace_id' => $workspace->id, 'billing_cycle' => 'annual'],
        ])]);
        $payload = ['id' => 'notification_123', 'type' => 'payment', 'data' => ['id' => '123']];

        $this->postJson('/api/webhooks/mercadopago', $payload)->assertOk();
        $this->postJson('/api/webhooks/mercadopago', $payload)->assertOk();

        $this->assertDatabaseCount('payment_webhook_events', 1);
        $this->assertDatabaseHas('subscriptions', ['provider_subscription_id' => '123', 'status' => 'active']);
        $this->assertDatabaseHas('subscriptions', ['provider_subscription_id' => '123', 'provider_plan_id' => 'annual']);
        $this->assertTrue(now()->addMonths(11)->lt($workspace->subscriptions()->first()->current_period_end));
        $this->assertSame('premium', $workspace->fresh()->planCode());
        Event::assertDispatched(WorkspacePlanUpdated::class, fn (WorkspacePlanUpdated $event) => $event->workspace->is($workspace));
        Http::assertSentCount(1);
    }

    public function test_owner_can_poll_a_pix_payment_and_refresh_the_workspace_plan(): void
    {
        Event::fake([WorkspacePlanUpdated::class]);
        config(['mercadopago.access_token' => 'TEST-token']);
        $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
        $user = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        Subscription::create([
            'workspace_id' => $workspace->id,
            'provider' => 'mercadopago',
            'provider_subscription_id' => '789',
            'provider_plan_id' => 'monthly',
            'plan_code' => 'premium',
            'status' => 'pending',
        ]);
        Http::fake(['https://api.mercadopago.com/v1/payments/789' => Http::response([
            'id' => 789,
            'status' => 'approved',
            'external_reference' => 'workspace:'.$workspace->id,
            'metadata' => ['workspace_id' => $workspace->id, 'billing_cycle' => 'monthly'],
        ])]);

        $this->actingAs($user)
            ->getJson('/api/billing/payments/789/status')
            ->assertOk()
            ->assertJsonPath('payment_id', '789')
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('plan', 'premium')
            ->assertJsonPath('limits.realtime', true);

        $this->assertDatabaseHas('subscriptions', ['provider_subscription_id' => '789', 'status' => 'active']);
        Event::assertDispatched(WorkspacePlanUpdated::class);
        Http::assertSentCount(1);
    }

    public function test_member_cannot_poll_a_workspace_payment(): void
    {
        $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
        $user = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $workspace->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);
        Subscription::create([
            'workspace_id' => $workspace->id,
            'provider' => 'mercadopago',
            'provider_subscription_id' => '790',
            'provider_plan_id' => 'monthly',
            'plan_code' => 'premium',
            'status' => 'pending',
        ]);

        $this->actingAs($user)->getJson('/api/billing/payments/790/status')->assertForbidden();
    }

    public function test_workspace_members_can_authorize_the_private_billing_channel(): void
    {
        $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $workspace->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
        $payload = ['socket_id' => '1234.5678', 'channel_name' => 'private-workspaces.'.$workspace->id.'.billing'];

        $this->actingAs($member)->postJson('/broadcasting/auth', $payload)->assertOk();
        $this->actingAs($outsider)->postJson('/broadcasting/auth', $payload)->assertForbidden();
    }

    public function test_transparent_checkout_creates_a_payment_without_trusting_the_frontend_amount(): void
    {
        config([
            'mercadopago.base_url' => 'https://api.mercadopago.com',
            'mercadopago.access_token' => 'TEST-token',
            'mercadopago.premium_plans.annual.amount' => 9.90,
            'mercadopago.premium_plans.annual.duration_months' => 12,
            'mercadopago.premium_plans.annual.max_installments' => 1,
            'mercadopago.premium_plans.annual.label' => 'Anual',
            'mercadopago.notification_url' => 'https://app.example.test/api/webhooks/mercadopago',
        ]);
        Http::fake(['https://api.mercadopago.com/v1/payments' => Http::response(['id' => 456, 'status' => 'pending', 'payment_method_id' => 'pix'])]);
        $workspace = Workspace::create(['name' => 'Acme', 'slug' => 'acme']);
        $user = User::factory()->create(['email' => 'owner@example.test']);

        app(MercadoPagoClient::class)->createPayment($workspace, $user, [
            'transaction_amount' => 0.01,
            'payment_method_id' => 'pix',
            'payer' => ['email' => 'buyer@example.test'],
        ], 'annual', 'request-uuid');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/v1/payments'
            && $request->header('Authorization')[0] === 'Bearer TEST-token'
            && $request->header('X-Idempotency-Key')[0] === 'request-uuid'
            && $request['transaction_amount'] === 9.90
            && $request['payment_method_id'] === 'pix'
            && $request['external_reference'] === 'workspace:'.$workspace->id
            && $request['metadata']['billing_cycle'] === 'annual'
            && $request['metadata']['duration_months'] === 12
            && $request['payer']['email'] === 'buyer@example.test'
        );
    }

    public function test_local_notification_url_is_not_sent_to_mercado_pago(): void
    {
        config(['mercadopago.notification_url' => 'http://127.0.0.1:8080/api/webhooks/mercadopago']);
        $this->assertNull(app(MercadoPagoClient::class)->publicNotificationUrl());
    }

    public function test_mercado_pago_webhook_rejects_an_invalid_signature_when_configured(): void
    {
        config(['mercadopago.webhook_secret' => 'webhook-secret']);
        $this->postJson('/api/webhooks/mercadopago?data.id=123', [
            'id' => 'notification_123', 'type' => 'payment', 'data' => ['id' => '123'],
        ], ['x-request-id' => 'request-123', 'x-signature' => 'ts=123,v1=invalid'])->assertUnauthorized();
    }
}
