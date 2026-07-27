<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Payments\PaymentStatusClient;
use App\Data\PaymentWebhookData;
use App\Mail\OrderSuccess;
use App\Models\Jersey;
use App\Models\League;
use App\Models\Order;
use App\Models\User;
use App\Services\Payments\PaymentInitializationService;
use App\Services\Payments\PaymentWebhookService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Mockery;
use RuntimeException;
use Tests\Fakes\FakePaymentStatusClient;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    private const SERVER_KEY = 'sandbox-server-key-for-tests';

    private User $customer;

    private Jersey $jersey;

    private Order $order;

    private FakePaymentStatusClient $statusClient;

    private string $transactionId = 'payment-transaction-0001';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('midtrans.server_key', self::SERVER_KEY);
        config()->set('midtrans.verify_terminal_status', true);
        Mail::fake();

        $this->statusClient = new FakePaymentStatusClient;
        $this->app->instance(PaymentStatusClient::class, $this->statusClient);

        $this->customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
        ]);
        $league = League::factory()->create(['created_by' => $this->customer->id]);
        $this->jersey = Jersey::factory()->create([
            'league_id' => $league->id,
            'created_by' => $this->customer->id,
            'stock' => ['S' => 2, 'M' => 2, 'L' => 2, 'XL' => 2],
            'sold' => 0,
            'price' => 250000,
        ]);
        $this->order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'subtotal' => 250000,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_price' => 250000,
            'status' => Order::STATUS_IN_CART,
        ]);
        $this->createOrderItem($this->order, $this->jersey);
    }

    public function test_browser_return_never_changes_order_state(): void
    {
        $this->actingAs($this->customer)
            ->get(route('checkout.payment.finish', [
                'order_id' => $this->order->invoice_number,
                'transaction_status' => 'settlement',
            ]))
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => Order::STATUS_IN_CART,
            'provider_transaction_id' => null,
        ]);
    }

    public function test_malformed_callback_returns_a_generic_unauthorized_response(): void
    {
        Log::spy();

        $this->postJson(route('payment.webhook'), ['order_id' => $this->order->invoice_number])
            ->assertUnauthorized()
            ->assertJsonStructure(['message', 'support_reference'])
            ->assertJsonMissingValidationErrors(['transaction_id']);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Malformed payment notification rejected.'
                    && $context['order_id'] === $this->order->invoice_number
                    && $context['error_code'] === 'PAYMENT_WEBHOOK_VALIDATION_FAILED'
                    && ! array_key_exists('transaction_id', $context)
                    && ! array_key_exists('exception_message', $context);
            });

        $this->assertOrderUnchanged();
    }

    public function test_cash_on_delivery_order_rejects_payment_provider_callbacks(): void
    {
        $this->order->forceFill([
            'status' => Order::STATUS_PENDING,
            'transaction_status' => 'order_placed',
            'payment_type' => 'cash_on_delivery',
            'payment_method' => 'cash_on_delivery',
        ])->save();

        $this->postJson(route('payment.webhook'), $this->payload('settlement'))
            ->assertUnauthorized();

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => Order::STATUS_PENDING,
            'transaction_status' => 'order_placed',
            'payment_method' => 'cash_on_delivery',
            'provider_transaction_id' => null,
        ]);
        $this->assertSame(0, $this->statusClient->fetchCount);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        Log::spy();
        $payload = $this->payload('settlement');
        $payload['signature_key'] = str_repeat('0', 128);

        $this->postJson(route('payment.webhook'), $payload)
            ->assertUnauthorized()
            ->assertJsonStructure(['message', 'support_reference']);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Rejected payment notification.'
                    && $context['order_id'] === $this->order->invoice_number
                    && $context['error_code'] === 'PAYMENT_SIGNATURE_INVALID'
                    && ! array_key_exists('transaction_id', $context)
                    && ! array_key_exists('reason', $context)
                    && ! array_key_exists('exception_message', $context);
            });

        $this->assertOrderUnchanged();
        $this->assertSame(0, $this->statusClient->fetchCount);
    }

    public function test_unexpected_webhook_failure_uses_safe_logging_and_a_generic_response(): void
    {
        Log::spy();
        $webhooks = Mockery::mock(PaymentWebhookService::class);
        $webhooks->shouldReceive('handle')
            ->once()
            ->andThrow(new RuntimeException('provider payload contained a secret token'));
        $this->app->instance(PaymentWebhookService::class, $webhooks);

        $this->postJson(route('payment.webhook'), $this->payload('pending', statusCode: '201'))
            ->assertStatus(500)
            ->assertJsonStructure(['message', 'support_reference']);

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Payment notification processing failed.'
                    && $context['order_id'] === $this->order->invoice_number
                    && $context['error_code'] === 'PAYMENT_WEBHOOK_PROCESSING_FAILED'
                    && ! array_key_exists('transaction_id', $context)
                    && ! array_key_exists('exception_message', $context);
            });

        $this->assertOrderUnchanged();
    }

    public function test_forged_amount_is_rejected_even_with_a_valid_signature(): void
    {
        $payload = $this->payload('settlement', grossAmount: '1.00');

        $this->postJson(route('payment.webhook'), $payload)->assertUnauthorized();

        $this->assertOrderUnchanged();
    }

    public function test_capture_accept_marks_order_successful(): void
    {
        $this->postProviderCallback('capture', '200', 'credit_card', 'accept')
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_SUCCESS);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => Order::STATUS_SUCCESS,
            'fraud_status' => 'accept',
        ]);
        $this->assertSame(1, (int) $this->jersey->fresh()->stock['M']);
        Mail::assertQueued(OrderSuccess::class, 1);
    }

    public function test_capture_challenge_remains_pending(): void
    {
        $this->postProviderCallback('capture', '201', 'credit_card', 'challenge')
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_PENDING);

        $this->assertSame(2, (int) $this->jersey->fresh()->stock['M']);
        Mail::assertNothingQueued();
    }

    public function test_capture_deny_marks_order_failed(): void
    {
        $this->postProviderCallback('capture', '202', 'credit_card', 'deny')
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_FAILED);

        $this->assertSame(2, (int) $this->jersey->fresh()->stock['M']);
        Mail::assertNothingQueued();
    }

    public function test_capture_without_fraud_status_never_defaults_to_success(): void
    {
        $this->postProviderCallback('capture', '200', 'credit_card', null)
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_REVIEW);

        $this->assertSame(2, (int) $this->jersey->fresh()->stock['M']);
        Mail::assertNothingQueued();
    }

    public function test_inconsistent_status_code_and_capture_state_is_rejected(): void
    {
        $payload = $this->payload('capture', statusCode: '201', paymentType: 'credit_card', fraudStatus: 'accept');
        $this->configureProvider('capture', '200', 'credit_card', 'accept');

        $this->postJson(route('payment.webhook'), $payload)->assertUnauthorized();

        $this->assertOrderUnchanged();
        $this->assertSame(0, $this->statusClient->fetchCount);
    }

    public function test_duplicate_capture_callback_is_idempotent(): void
    {
        $payload = $this->payload('capture', statusCode: '200', paymentType: 'credit_card', fraudStatus: 'accept');
        $this->configureProvider('capture', '200', 'credit_card', 'accept');

        $this->postJson(route('payment.webhook'), $payload)
            ->assertOk()
            ->assertJsonPath('duplicate', false);
        $this->postJson(route('payment.webhook'), $payload)
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $this->assertSame(1, (int) $this->jersey->fresh()->stock['M']);
        $this->assertSame(1, (int) $this->jersey->fresh()->sold);
        Mail::assertQueued(OrderSuccess::class, 1);
    }

    public function test_changed_transaction_id_on_capture_callback_is_rejected(): void
    {
        $this->postProviderCallback('capture', '201', 'credit_card', 'challenge')->assertOk();

        $this->transactionId = 'payment-transaction-replay';
        $this->configureProvider('capture', '200', 'credit_card', 'accept');

        $this->postJson(
            route('payment.webhook'),
            $this->payload('capture', statusCode: '200', paymentType: 'credit_card', fraudStatus: 'accept'),
        )->assertUnauthorized();

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => Order::STATUS_PENDING,
            'provider_transaction_id' => 'payment-transaction-0001',
            'fraud_status' => 'challenge',
        ]);
    }

    public function test_duplicate_settlement_callback_is_idempotent(): void
    {
        $payload = $this->payload('settlement');
        $this->configureProvider('settlement');

        $this->postJson(route('payment.webhook'), $payload)
            ->assertOk()
            ->assertJsonPath('duplicate', false);
        $this->postJson(route('payment.webhook'), $payload)
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $this->assertSame(1, (int) $this->jersey->fresh()->stock['M']);
        $this->assertSame(1, (int) $this->jersey->fresh()->sold);
        Mail::assertQueued(OrderSuccess::class, 1);
    }

    public function test_server_side_status_is_authoritative_for_terminal_transition(): void
    {
        $callback = $this->payload('settlement');
        $this->configureProvider('deny', '202');

        $this->postJson(route('payment.webhook'), $callback)
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_FAILED);

        $this->assertSame(2, (int) $this->jersey->fresh()->stock['M']);
    }

    public function test_pending_callback_does_not_reduce_stock(): void
    {
        $this->postProviderCallback('pending', '201')
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_PENDING);

        $this->assertSame(2, (int) $this->jersey->fresh()->stock['M']);
        Mail::assertNothingQueued();
    }

    public function test_denied_callback_marks_order_failed(): void
    {
        $this->assertFailedStatus('deny');
    }

    public function test_expired_callback_marks_order_failed(): void
    {
        $this->assertFailedStatus('expire');
    }

    public function test_canceled_callback_marks_order_failed(): void
    {
        $this->assertFailedStatus('cancel');
    }

    public function test_late_success_after_reservation_expiry_is_reconciled_safely(): void
    {
        app(PaymentInitializationService::class)->begin($this->order);
        $this->order->forceFill(['reservation_expires_at' => now()->subMinute()])->save();

        $this->artisan('inventory:release-expired-reservations')->assertSuccessful();

        $this->assertSame(Order::STATUS_PENDING, $this->order->fresh()->status);
        $this->assertSame(2, (int) $this->jersey->fresh()->stock['M']);

        $this->postProviderCallback('settlement')
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_SUCCESS);

        $this->assertSame(1, (int) $this->jersey->fresh()->stock['M']);
        $this->assertNotNull($this->order->items()->firstOrFail()->stock_consumed_at);
    }

    public function test_successful_callback_consumes_existing_reservation_without_double_decrement(): void
    {
        app(PaymentInitializationService::class)->begin($this->order);
        $this->assertSame(1, (int) $this->jersey->fresh()->stock['M']);

        $this->postProviderCallback('settlement')
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_SUCCESS);

        $this->assertSame(1, (int) $this->jersey->fresh()->stock['M']);
        $this->assertSame(1, (int) $this->jersey->fresh()->sold);
        $this->assertNotNull($this->order->items()->firstOrFail()->stock_consumed_at);
    }

    public function test_successful_callback_reduces_stock_inside_the_processed_order(): void
    {
        $this->postProviderCallback('settlement')
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_SUCCESS);

        $this->assertSame(1, (int) $this->jersey->fresh()->stock['M']);
        $this->assertNotNull($this->order->fresh()->processed_at);
        $this->assertNotNull($this->order->fresh()->paid_at);
    }

    public function test_insufficient_locked_stock_moves_paid_order_to_manual_review(): void
    {
        $stock = $this->jersey->stock;
        $stock['M'] = 0;
        $this->jersey->update(['stock' => $stock]);

        $this->postProviderCallback('settlement')
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_REVIEW);

        $this->assertSame(0, (int) $this->jersey->fresh()->stock['M']);
        $this->assertNotNull($this->order->fresh()->paid_at);
        Mail::assertNothingQueued();
    }

    public function test_success_callback_for_an_empty_order_is_held_for_manual_review(): void
    {
        $this->order->items()->delete();

        $this->postProviderCallback('settlement')
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_REVIEW);

        $this->assertNotNull($this->order->fresh()->paid_at);
        $this->assertSame(2, (int) $this->jersey->fresh()->stock['M']);
        Mail::assertNothingQueued();
    }

    public function test_refund_notification_moves_successful_order_to_review_without_restocking(): void
    {
        $this->postProviderCallback('settlement')->assertOk();
        $this->postProviderCallback('refund')->assertOk()->assertJsonPath('status', Order::STATUS_REVIEW);

        $this->assertSame(1, (int) $this->jersey->fresh()->stock['M']);
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => Order::STATUS_REVIEW,
            'transaction_status' => 'refund',
        ]);
    }

    public function test_replay_with_a_different_transaction_id_is_rejected(): void
    {
        $this->postProviderCallback('pending', '201')->assertOk();
        $this->transactionId = 'payment-transaction-replay';
        $this->configureProvider('settlement');

        $this->postJson(route('payment.webhook'), $this->payload('settlement'))->assertUnauthorized();

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => Order::STATUS_PENDING,
            'provider_transaction_id' => 'payment-transaction-0001',
        ]);
    }

    public function test_invalid_order_is_rejected(): void
    {
        $payload = $this->payload('settlement');
        $payload['order_id'] = 'SA-MISSING-ORDER';
        $payload['signature_key'] = $this->signature($payload);

        $this->postJson(route('payment.webhook'), $payload)->assertUnauthorized();
    }

    private function assertFailedStatus(string $transactionStatus): void
    {
        $this->postProviderCallback($transactionStatus, '202')->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => Order::STATUS_FAILED,
            'transaction_status' => $transactionStatus,
        ]);
        $this->assertSame(2, (int) $this->jersey->fresh()->stock['M']);
    }

    private function postProviderCallback(
        string $transactionStatus,
        string $statusCode = '200',
        string $paymentType = 'bank_transfer',
        ?string $fraudStatus = null,
    ): TestResponse {
        $this->configureProvider($transactionStatus, $statusCode, $paymentType, $fraudStatus);

        return $this->postJson(
            route('payment.webhook'),
            $this->payload($transactionStatus, statusCode: $statusCode, paymentType: $paymentType, fraudStatus: $fraudStatus),
        );
    }

    private function configureProvider(
        string $transactionStatus,
        string $statusCode = '200',
        string $paymentType = 'bank_transfer',
        ?string $fraudStatus = null,
    ): void {
        $this->statusClient->respondWith(new PaymentWebhookData(
            orderId: $this->order->invoice_number,
            transactionId: $this->transactionId,
            transactionStatus: $transactionStatus,
            statusCode: $statusCode,
            grossAmount: '250000.00',
            signatureKey: '',
            paymentType: $paymentType,
            fraudStatus: $fraudStatus,
        ));
    }

    /** @return array<string, string> */
    private function payload(
        string $transactionStatus,
        string $grossAmount = '250000.00',
        string $statusCode = '200',
        string $paymentType = 'bank_transfer',
        ?string $fraudStatus = null,
    ): array {
        $payload = [
            'order_id' => $this->order->invoice_number,
            'transaction_id' => $this->transactionId,
            'transaction_status' => $transactionStatus,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'payment_type' => $paymentType,
        ];

        if ($fraudStatus !== null) {
            $payload['fraud_status'] = $fraudStatus;
        }

        $payload['signature_key'] = $this->signature($payload);

        return $payload;
    }

    /** @param array<string, string> $payload */
    private function signature(array $payload): string
    {
        return hash(
            'sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].self::SERVER_KEY,
        );
    }

    private function assertOrderUnchanged(): void
    {
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => Order::STATUS_IN_CART,
            'provider_transaction_id' => null,
        ]);
        $this->assertSame(2, (int) $this->jersey->fresh()->stock['M']);
    }
}
