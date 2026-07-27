<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\PaymentWebhookData;
use App\Exceptions\InvalidPaymentNotification;
use App\Http\Requests\MidtransWebhookRequest;
use App\Models\Order;
use App\Services\Payments\PaymentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(private readonly PaymentWebhookService $webhooks) {}

    public function webhook(MidtransWebhookRequest $request): JsonResponse
    {
        $supportReference = (string) Str::uuid();

        try {
            $result = $this->webhooks->handle(PaymentWebhookData::fromArray($request->validated()));

            return response()->json([
                'message' => 'Payment notification accepted.',
                'status' => $result['status'],
                'duplicate' => $result['duplicate'],
            ]);
        } catch (InvalidPaymentNotification $exception) {
            Log::warning('Rejected payment notification.', [
                'support_reference' => $supportReference,
                'order_id' => $request->input('order_id'),
                'error_code' => $exception->errorCode(),
            ]);

            return response()->json([
                'message' => 'Payment notification could not be verified.',
                'support_reference' => $supportReference,
            ], 401);
        } catch (Throwable) {
            Log::error('Payment notification processing failed.', [
                'support_reference' => $supportReference,
                'order_id' => $request->input('order_id'),
                'error_code' => 'PAYMENT_WEBHOOK_PROCESSING_FAILED',
            ]);

            return response()->json([
                'message' => 'Payment notification could not be processed.',
                'support_reference' => $supportReference,
            ], 500);
        }
    }

    public function success(Request $request, Order $order): View
    {
        abort_unless(
            $order->user_id === $request->user()->getKey()
            && $order->status !== Order::STATUS_IN_CART,
            404,
        );

        $order->loadMissing(['items', 'user']);

        return view('checkout.checkout-success', compact('order'));
    }

    public function finish(Request $request): View|RedirectResponse
    {
        return $this->browserResult($request);
    }

    public function unfinish(Request $request): View|RedirectResponse
    {
        return $this->browserResult($request);
    }

    public function error(Request $request): View|RedirectResponse
    {
        return $this->browserResult($request);
    }

    private function browserResult(Request $request): View|RedirectResponse
    {
        $invoiceNumber = $request->string('order_id')->trim()->value();

        if ($invoiceNumber === '') {
            return to_route('order-history')
                ->with('status', 'Your payment status will appear in order history after provider verification.');
        }

        $order = $request->user()
            ->orders()
            ->where('invoice_number', $invoiceNumber)
            ->first();

        if ($order === null) {
            return to_route('order-history')
                ->with('status', 'The requested order could not be found.');
        }

        return match ($order->status) {
            Order::STATUS_SUCCESS => view('checkout.checkout-success', compact('order')),
            Order::STATUS_FAILED => view('checkout.checkout-failed', compact('order')),
            Order::STATUS_REVIEW => view('checkout.checkout-review', compact('order')),
            default => view('checkout.checkout-pending', compact('order')),
        };
    }
}
