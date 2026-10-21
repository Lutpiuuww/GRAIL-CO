<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function checkoutData(array $overrides = []): array
    {
        return array_replace(['recipient_name' => 'UTS Test Customer', 'phone' => '081234567890', 'delivery_method' => 'pickup', 'payment_method' => 'cod'], $overrides);
    }

    protected function prepareCheckout(array $cart, array $details = []): string
    {
        $this->withSession(['cart' => $cart])->post('/checkout/review', $this->checkoutData($details))
            ->assertSessionHasNoErrors()->assertRedirect('/checkout/review');

        return session('checkout.draft.token');
    }
}
