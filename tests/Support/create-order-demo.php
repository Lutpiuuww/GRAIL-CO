<?php

use App\Http\Controllers\CheckoutController;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\CheckoutQuote;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.default') !== 'mysql' || ! str_ends_with(config('database.connections.mysql.database'), '_test')) {
    throw new RuntimeException('Fixture UI hanya boleh dibuat pada MySQL _test, APP_ENV=testing.');
}
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--force' => true]);
$user = User::where('email', 'customer@grail.co')->firstOrFail();
$product = Product::where('sku', 'GRL-WT-01')->firstOrFail();
$cart = [$product->id => 1];
$token = (string) Str::uuid();
$session = app('session')->driver();
$session->start();
$session->put('cart', $cart);
$session->put('checkout.draft', [
    'token' => $token, 'cart' => $cart, 'quote' => CheckoutQuote::make($cart, 'standard'),
    'details' => ['recipient_name' => 'Customer Demo UTS', 'phone' => '081234567890', 'email' => $user->email, 'address' => 'Jalan Simulasi UTS nomor 12', 'city' => 'Bandung', 'province' => 'Jawa Barat', 'postal_code' => '40111', 'delivery_method' => 'standard', 'payment_method' => 'bank_transfer'],
]);
$request = Request::create('/checkout', 'POST', ['submission_token' => $token]);
$request->setLaravelSession($session);
$request->setUserResolver(fn () => $user);
app(CheckoutController::class)->store($request);
$sale = Sale::where('checkout_token', $token)->firstOrFail();
echo 'UI QA order: '.$sale->id.' / '.$sale->sale_number."\n";
