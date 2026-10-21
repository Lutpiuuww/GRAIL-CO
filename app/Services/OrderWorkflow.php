<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderWorkflow
{
    private function locked(User $actor, Sale $sale, Closure $operation): void
    {
        DB::transaction(function () use ($actor, $sale, $operation) {
            $actor = User::lockForUpdate()->findOrFail($actor->id);
            abort_unless($actor->is_active && $actor->isBackOffice(), 403);
            $sale = Sale::lockForUpdate()->findOrFail($sale->id);
            $operation($sale, $actor);
        });
    }

    private function assertCurrent(Sale $sale, array $data): void
    {
        if (! $sale->checkout_token || $sale->status !== 'completed') {
            throw ValidationException::withMessages(['order' => 'Hanya pesanan checkout aktif yang dapat diperbarui.']);
        }
        if ($sale->order_version !== (int) $data['version']) {
            throw ValidationException::withMessages(['order' => 'Pesanan sudah diperbarui. Muat ulang halaman dan periksa status terbaru.']);
        }
    }

    private function event(Sale $sale, User $actor, string $type, ?string $from, ?string $to, ?string $note, array $details = []): void
    {
        $sale->events()->create(['user_id' => $actor->id, 'type' => $type, 'from_status' => $from, 'to_status' => $to, 'note' => $note, 'details' => $details, 'created_at' => now()]);
    }

    public function payment(User $actor, Sale $sale, array $data): void
    {
        $this->locked($actor, $sale, function (Sale $sale, User $actor) use ($data) {
            $this->assertCurrent($sale, $data);
            if ($sale->payment_status !== 'pending') {
                throw ValidationException::withMessages(['payment' => 'Pembayaran sudah dicatat atau dikembalikan.']);
            }
            $sale->forceFill(['payment_status' => 'paid', 'payment_reference' => $data['reference'], 'paid_by' => $actor->id, 'paid_at' => now(), 'order_version' => $sale->order_version + 1])->save();
            $this->event($sale, $actor, 'payment_recorded', 'pending', 'paid', $data['note'] ?? null, ['reference' => $data['reference']]);
        });
    }

    public function fulfillment(User $actor, Sale $sale, array $data): void
    {
        $this->locked($actor, $sale, function (Sale $sale, User $actor) use ($data) {
            $this->assertCurrent($sale, $data);
            if ($sale->nextFulfillmentStatus() !== $data['action']) {
                throw ValidationException::withMessages(['order' => 'Perubahan status tidak sesuai urutan atau metode pengiriman.']);
            }
            if (($sale->payment_method === 'bank_transfer' || $data['action'] === 'completed') && $sale->payment_status !== 'paid') {
                throw ValidationException::withMessages(['payment' => 'Konfirmasi pembayaran terlebih dahulu sebelum melanjutkan status ini.']);
            }
            $from = $sale->fulfillment_status ?? 'pending';
            $updates = ['fulfillment_status' => $data['action'], 'order_version' => $sale->order_version + 1];
            $details = [];
            if ($data['action'] === 'shipped') {
                $updates += ['carrier' => $data['carrier'], 'tracking_number' => $data['tracking_number'], 'shipped_at' => now()];
                $details = ['carrier' => $data['carrier'], 'tracking_number' => $data['tracking_number']];
            }
            if ($data['action'] === 'ready_for_pickup') {
                $pickup = Carbon::createFromFormat('Y-m-d\TH:i', $data['pickup_at'], 'Asia/Jakarta')->startOfMinute()->utc();
                if ($pickup->lt(now()->startOfMinute())) {
                    throw ValidationException::withMessages(['pickup_at' => 'Jadwal pengambilan tidak boleh di masa lalu (WIB).']);
                }
                $updates += ['pickup_location' => $data['pickup_location'], 'pickup_at' => $pickup, 'ready_at' => now()];
                $details = ['pickup_location' => $data['pickup_location'], 'pickup_at' => $pickup->toIso8601String()];
            }
            if ($data['action'] === 'completed') {
                $updates['completed_at'] = now();
            }
            $sale->forceFill($updates)->save();
            $this->event($sale, $actor, $data['action'], $from, $data['action'], $data['note'] ?? null, $details);
        });
    }

    public function cancel(User $actor, Sale $sale, string $reason): void
    {
        $this->locked($actor, $sale, function (Sale $sale, User $actor) use ($reason) {
            abort_unless($actor->role === 'admin', 403);
            if (! $sale->canBeVoided()) {
                throw ValidationException::withMessages(['sale' => 'Transaksi sudah dibatalkan atau barang sudah dikirim/siap diambil/selesai. Pembatalan langsung tidak tersedia; fitur retur belum tersedia.']);
            }
            foreach ($sale->items()->orderBy('product_id')->get() as $item) {
                Product::withTrashed()->lockForUpdate()->findOrFail($item->product_id)->increment('stock', $item->quantity);
            }
            $from = $sale->fulfillment_status ?? 'pending';
            $updates = ['status' => 'cancelled', 'cancelled_by' => $actor->id, 'cancelled_at' => now(), 'cancellation_reason' => $reason, 'order_version' => $sale->order_version + 1];
            if ($sale->checkout_token) {
                $updates['fulfillment_status'] = 'cancelled';
                if ($sale->payment_status === 'paid') {
                    $updates += ['payment_status' => 'refunded', 'refunded_at' => now()];
                }
            }
            $sale->forceFill($updates)->save();
            if ($sale->checkout_token) {
                $this->event($sale, $actor, 'cancelled', $from, 'cancelled', $reason, ['payment_status' => $sale->payment_status]);
            }
        });
    }
}
