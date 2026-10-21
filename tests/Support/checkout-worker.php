<?php

use App\Http\Controllers\CheckoutController;
use App\Models\User;
use App\Services\CheckoutQuote;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.default') !== 'mysql' || ! str_ends_with(config('database.connections.mysql.database'), '_test')) {
    throw new RuntimeException('Worker hanya boleh berjalan pada database MySQL berakhiran _test dalam APP_ENV=testing.');
}
$session = app('session')->driver();
$session->start();
$session->put('cart', [(int) $argv[2] => 1]);
$request = Request::create('/checkout', 'POST', ['submission_token' => $argv[3] ?? (string) Str::uuid()]);
$request->setLaravelSession($session);
$user = User::findOrFail((int) $argv[1]);
$request->setUserResolver(fn () => $user);
$cart = [(int) $argv[2] => 1];
$session->put('checkout.draft', [
    'token' => $request->input('submission_token'), 'cart' => $cart,
    'quote' => CheckoutQuote::make($cart, 'pickup'),
    'details' => ['recipient_name' => $user->name, 'phone' => '081234567890', 'email' => $user->email, 'delivery_method' => 'pickup', 'payment_method' => 'cod'],
]);
echo "ready\n";
flush();
try {
    $response = app(CheckoutController::class)->store($request);
    echo str_contains($response->getTargetUrl(), '/checkout/success/') ? "completed\n" : "insufficient_stock\n";
} catch (ValidationException $exception) {
    if (! isset($exception->errors()['cart'])) {
        throw $exception;
    }
    echo "insufficient_stock\n";
}
