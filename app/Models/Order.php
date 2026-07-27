<?php

declare(strict_types=1);

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_IN_CART = 'IN CART';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_SUCCESS = 'SUCCESS';

    public const STATUS_FAILED = 'FAILED';

    public const STATUS_REVIEW = 'PAYMENT REVIEW';

    public const FULFILLMENT_PENDING = 'PENDING';

    public const FULFILLMENT_CONFIRMED = 'CONFIRMED';

    public const FULFILLMENT_PROCESSING = 'PROCESSING';

    public const FULFILLMENT_PACKED = 'PACKED';

    public const FULFILLMENT_DISPATCHED = 'DISPATCHED';

    public const FULFILLMENT_OUT_FOR_DELIVERY = 'OUT FOR DELIVERY';

    public const FULFILLMENT_DELIVERED = 'DELIVERED';

    public const FULFILLMENT_CANCELLED = 'CANCELLED';

    public const FULFILLMENT_REFUNDED = 'REFUNDED';

    /** @var array<int, string> */
    public static array $status = [
        self::STATUS_IN_CART,
        self::STATUS_PENDING,
        self::STATUS_SUCCESS,
        self::STATUS_FAILED,
        self::STATUS_REVIEW,
    ];

    /** @var array<int, string> */
    public static array $fulfillmentStatuses = [
        self::FULFILLMENT_PENDING,
        self::FULFILLMENT_CONFIRMED,
        self::FULFILLMENT_PROCESSING,
        self::FULFILLMENT_PACKED,
        self::FULFILLMENT_DISPATCHED,
        self::FULFILLMENT_OUT_FOR_DELIVERY,
        self::FULFILLMENT_DELIVERED,
        self::FULFILLMENT_CANCELLED,
        self::FULFILLMENT_REFUNDED,
    ];

    /** @var array<int, string> */
    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_email',
        'invoice_number',
        'subtotal',
        'shipping_amount',
        'tax_amount',
        'discount_amount',
        'total_price',
        'courier_services',
        'shipping_phone',
        'shipping_address',
        'shipping_province',
        'shipping_city',
        'shipping_postal_code',
        'status',
        'fulfillment_status',
        'fulfillment_status_updated_at',
        'provider_transaction_id',
        'transaction_status',
        'payment_type',
        'payment_method',
        'status_code',
        'fraud_status',
        'reservation_expires_at',
        'processed_at',
        'paid_at',
        'placed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'courier_services' => 'array',
        'subtotal' => 'integer',
        'shipping_amount' => 'integer',
        'tax_amount' => 'integer',
        'discount_amount' => 'integer',
        'total_price' => 'integer',
        'fulfillment_status_updated_at' => 'datetime',
        'reservation_expires_at' => 'datetime',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
        'placed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Order $order): void {
            if ($order->status !== self::STATUS_IN_CART) {
                throw new DomainException('Historical orders cannot be deleted.');
            }
        });
    }

    /** @return array<string, string> */
    public static function fulfillmentStatusOptions(): array
    {
        return collect(self::$fulfillmentStatuses)
            ->mapWithKeys(fn (string $status): array => [$status => self::fulfillmentStatusLabel($status)])
            ->all();
    }

    public static function fulfillmentStatusLabel(?string $status): string
    {
        return match ($status) {
            self::FULFILLMENT_PENDING => trans('Pending'),
            self::FULFILLMENT_CONFIRMED => trans('Confirmed'),
            self::FULFILLMENT_PROCESSING => trans('Processing'),
            self::FULFILLMENT_PACKED => trans('Packed'),
            self::FULFILLMENT_DISPATCHED => trans('Dispatched'),
            self::FULFILLMENT_OUT_FOR_DELIVERY => trans('Out for Delivery'),
            self::FULFILLMENT_DELIVERED => trans('Delivered'),
            self::FULFILLMENT_CANCELLED => trans('Cancelled'),
            self::FULFILLMENT_REFUNDED => trans('Refunded'),
            default => trans('Pending'),
        };
    }

    public static function fulfillmentStatusColor(?string $status): string
    {
        return match ($status) {
            self::FULFILLMENT_PENDING => 'warning',
            self::FULFILLMENT_CONFIRMED => 'primary',
            self::FULFILLMENT_PROCESSING => 'info',
            self::FULFILLMENT_PACKED => 'primary',
            self::FULFILLMENT_DISPATCHED => 'warning',
            self::FULFILLMENT_OUT_FOR_DELIVERY => 'info',
            self::FULFILLMENT_DELIVERED => 'success',
            self::FULFILLMENT_CANCELLED => 'danger',
            self::FULFILLMENT_REFUNDED => 'gray',
            default => 'gray',
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    public function getFulfillmentStatusLabelAttribute(): string
    {
        return self::fulfillmentStatusLabel($this->fulfillment_status);
    }

    public function getFulfillmentBadgeClassAttribute(): string
    {
        return match ($this->fulfillment_status) {
            self::FULFILLMENT_PENDING => 'order-status--pending',
            self::FULFILLMENT_CONFIRMED => 'order-status--confirmed',
            self::FULFILLMENT_PROCESSING => 'order-status--processing',
            self::FULFILLMENT_PACKED => 'order-status--packed',
            self::FULFILLMENT_DISPATCHED => 'order-status--dispatched',
            self::FULFILLMENT_OUT_FOR_DELIVERY => 'order-status--out-for-delivery',
            self::FULFILLMENT_DELIVERED => 'order-status--delivered',
            self::FULFILLMENT_CANCELLED => 'order-status--cancelled',
            self::FULFILLMENT_REFUNDED => 'order-status--refunded',
            default => 'order-status--pending',
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        if ($this->fulfillment_status === self::FULFILLMENT_REFUNDED) {
            return trans('Refunded');
        }

        if ($this->fulfillment_status === self::FULFILLMENT_CANCELLED) {
            return trans('Cancelled');
        }

        return match ($this->status) {
            self::STATUS_SUCCESS => trans('Paid'),
            self::STATUS_FAILED => trans('Payment Failed'),
            self::STATUS_REVIEW => trans('Payment Review'),
            self::STATUS_PENDING => $this->payment_method === 'cash_on_delivery'
                ? trans('Pay on Delivery')
                : trans('Payment Pending'),
            default => trans('Payment Pending'),
        };
    }

    public function getPaymentStatusColorAttribute(): string
    {
        if ($this->fulfillment_status === self::FULFILLMENT_REFUNDED) {
            return 'gray';
        }

        if ($this->fulfillment_status === self::FULFILLMENT_CANCELLED) {
            return 'danger';
        }

        return match ($this->status) {
            self::STATUS_SUCCESS => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_REVIEW => 'warning',
            default => 'gray',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return $this->payment_method === 'cash_on_delivery'
            ? trans('Cash on Delivery')
            : str((string) ($this->payment_method ?: $this->payment_type ?: trans('Not specified')))
                ->replace('_', ' ')
                ->title()
                ->value();
    }

    /**
     * Backward-compatible accessor used by older storefront templates.
     */
    public function getStatusBadgeAttribute(): string
    {
        return 'order-status-badge '.$this->fulfillment_badge_class;
    }

    public function getRouteKeyName(): string
    {
        return 'invoice_number';
    }
}
