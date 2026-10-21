<?php

use App\Models\Sale;
use App\Models\User;
use App\Services\OrderWorkflow;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.default') !== 'mysql' || ! str_ends_with(config('database.connections.mysql.database'), '_test')) {
    throw new RuntimeException('Worker hanya untuk MySQL khusus _test pada APP_ENV=testing.');
}
$actor = User::findOrFail((int) $argv[1]);
$sale = Sale::findOrFail((int) $argv[2]);
echo "ready\n";
flush();
try {
    app(OrderWorkflow::class)->payment($actor, $sale, ['version' => 0, 'reference' => 'DEMO-PARALLEL-'.$actor->id]);
    echo "payment_recorded\n";
} catch (ValidationException $exception) {
    echo "stale_rejected\n";
}
