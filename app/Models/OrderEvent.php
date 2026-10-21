<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'type', 'from_status', 'to_status', 'note', 'details', 'created_at'];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function getLabelAttribute(): string
    {
        return match ($this->type) {
            'payment_recorded' => 'Pembayaran dicatat',
            'processing' => 'Pesanan sedang diproses',
            'shipped' => 'Pesanan ditandai dikirim',
            'ready_for_pickup' => 'Pesanan siap diambil',
            'completed' => 'Pesanan selesai',
            'cancelled' => 'Pesanan dibatalkan',
            default => 'Pesanan diperbarui',
        };
    }
}
