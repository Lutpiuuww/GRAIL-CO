<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    public const FULFILLMENT_LABELS = ['pending' => 'Menunggu konfirmasi', 'processing' => 'Diproses', 'shipped' => 'Dikirim', 'ready_for_pickup' => 'Siap diambil', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'];

    protected $fillable = ['user_id', 'sale_number', 'total', 'status', 'sold_at', 'cancelled_by', 'cancelled_at', 'cancellation_reason', 'checkout_token', 'delivery_details', 'delivery_method', 'shipping_cost', 'payment_method', 'payment_status', 'fulfillment_status'];

    protected function casts(): array
    {
        return ['sold_at' => 'datetime', 'cancelled_at' => 'datetime', 'total' => 'decimal:2', 'shipping_cost' => 'decimal:2', 'delivery_details' => 'array', 'order_version' => 'integer', 'paid_at' => 'datetime', 'refunded_at' => 'datetime', 'pickup_at' => 'datetime', 'shipped_at' => 'datetime', 'ready_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('id');
    }

    public function getOrderStatusLabelAttribute(): string
    {
        if ($this->status === 'cancelled') {
            return 'Dibatalkan';
        }

        return $this->checkout_token ? (self::FULFILLMENT_LABELS[$this->fulfillment_status ?? 'pending'] ?? 'Menunggu konfirmasi') : ucfirst($this->status);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'Tercatat lunas', 'refunded' => 'Pengembalian perlu diproses', default => 'Belum dikonfirmasi',
        };
    }

    public function nextFulfillmentStatus(): ?string
    {
        if (! $this->checkout_token || $this->status === 'cancelled') {
            return null;
        }

        return match ($this->fulfillment_status ?? 'pending') {
            'pending' => 'processing',
            'processing' => $this->delivery_method === 'pickup' ? 'ready_for_pickup' : 'shipped',
            'shipped', 'ready_for_pickup' => 'completed',
            default => null,
        };
    }

    public function canBeVoided(): bool
    {
        return $this->status === 'completed' && (! $this->checkout_token || in_array($this->fulfillment_status ?? 'pending', ['pending', 'processing'], true));
    }

    public function canCustomerCancel(): bool
    {
        return (bool) $this->checkout_token
            && $this->status === 'completed'
            && ($this->fulfillment_status ?? 'pending') === 'pending'
            && $this->payment_status === 'pending';
    }
}
