<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.default') !== 'mysql' || ! str_ends_with(config('database.connections.mysql.database'), '_test')) {
    throw new RuntimeException('Gunakan APP_ENV=testing dan database MySQL khusus berakhiran _test.');
}
Artisan::call('migrate', ['--force' => true]);
$tag = (string) Str::ulid();
$product = Product::create(['category_id' => Category::create(['name' => 'Concurrency '.$tag])->id, 'sku' => 'QA-'.$tag, 'name' => 'Last unit concurrency probe', 'purchase_price' => 100, 'selling_price' => 250, 'stock' => 1, 'minimum_stock' => 1]);
$users = User::factory()->count(2)->create(['role' => 'customer']);
$workers = [];
try {
    DB::beginTransaction();
    Product::lockForUpdate()->findOrFail($product->id);
    foreach ($users as $user) {
        $worker = new Process([PHP_BINARY, __DIR__.'/checkout-worker.php', (string) $user->id, (string) $product->id], base_path(), ['SESSION_DRIVER' => 'array']);
        $worker->setTimeout(20);
        $worker->start();
        $workers[] = $worker;
    }
    $deadline = microtime(true) + 10;
    do {
        $ready = count(array_filter($workers, fn ($worker) => str_contains($worker->getOutput(), 'ready')));
        if ($ready === 2) {
            break;
        }
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Worker tidak siap: '.implode(' | ', array_map(fn ($worker) => $worker->getErrorOutput(), $workers)));
        }
        usleep(20000);
    } while (true);
    usleep(300000);
    if (count(array_filter($workers, fn ($worker) => $worker->isRunning())) !== 2) {
        throw new RuntimeException('Kedua checkout seharusnya menunggu row lock.');
    }
    DB::commit();
    foreach ($workers as $worker) {
        $worker->wait();
        if (! $worker->isSuccessful()) {
            throw new RuntimeException($worker->getErrorOutput().$worker->getOutput());
        }
    }
    $output = implode('', array_map(fn ($worker) => $worker->getOutput(), $workers));
    $sales = Sale::whereHas('items', fn ($q) => $q->where('product_id', $product->id))->count();
    if (substr_count($output, "completed\n") !== 1 || substr_count($output, "insufficient_stock\n") !== 1 || $sales !== 1 || $product->fresh()->stock !== 0) {
        throw new RuntimeException('Hasil concurrency tidak sesuai: '.$output);
    }
    echo "PASS: dua checkout bersamaan, satu berhasil, satu ditolak; 1 sale, stok akhir 0.\n";
} finally {
    if (DB::transactionLevel()) {
        DB::rollBack();
    }
    foreach ($workers as $worker) {
        if ($worker->isRunning()) {
            $worker->stop();
        }
    }
}

// Two operators submit the same order version while a sale row is locked.
$sale = Sale::whereHas('items', fn ($q) => $q->where('product_id', $product->id))->firstOrFail();
$staff = User::factory()->count(2)->create(['role' => 'staff']);
$workers = [];
try {
    DB::beginTransaction();
    Sale::lockForUpdate()->findOrFail($sale->id);
    foreach ($staff as $actor) {
        $worker = new Process([PHP_BINARY, __DIR__.'/order-payment-worker.php', (string) $actor->id, (string) $sale->id], base_path());
        $worker->setTimeout(20);
        $worker->start();
        $workers[] = $worker;
    }
    $deadline = microtime(true) + 10;
    while (count(array_filter($workers, fn ($worker) => str_contains($worker->getOutput(), 'ready'))) !== 2) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Payment workers tidak siap: '.implode(' | ', array_map(fn ($worker) => $worker->getErrorOutput(), $workers)));
        }
        usleep(20000);
    }
    usleep(300000);
    if (count(array_filter($workers, fn ($worker) => $worker->isRunning())) !== 2) {
        throw new RuntimeException('Payment workers seharusnya menunggu sale row lock.');
    }
    DB::commit();
    foreach ($workers as $worker) {
        $worker->wait();
        if (! $worker->isSuccessful()) {
            throw new RuntimeException($worker->getErrorOutput().$worker->getOutput());
        }
    }
    $output = implode('', array_map(fn ($worker) => $worker->getOutput(), $workers));
    if (substr_count($output, "payment_recorded\n") !== 1 || substr_count($output, "stale_rejected\n") !== 1 || $sale->fresh()->order_version !== 1 || $sale->events()->count() !== 1 || $product->fresh()->stock !== 0) {
        throw new RuntimeException('Payment concurrency tidak sesuai: '.$output);
    }
    echo "PASS: dua petugas mencatat pembayaran bersamaan; 1 pembayaran, 1 audit event, versi lama ditolak, stok tetap 0.\n";
} finally {
    if (DB::transactionLevel()) {
        DB::rollBack();
    }
    foreach ($workers as $worker) {
        if ($worker->isRunning()) {
            $worker->stop();
        }
    }
}
