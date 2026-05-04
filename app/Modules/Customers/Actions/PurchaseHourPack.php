<?php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\HourPack;

class PurchaseHourPack
{
    /** @var callable */
    private $createSession;

    public function __construct(?callable $createSession = null)
    {
        $this->createSession = $createSession ?? function (array $params) {
            \Stripe\Stripe::setApiKey(config('stripe.secret'));
            return \Stripe\Checkout\Session::create($params);
        };
    }

    public function execute(User $customer, HourPack $pack): array
    {
        $session = ($this->createSession)([
            'mode' => 'payment',
            'customer_email' => $customer->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'aed',
                    'unit_amount' => $pack->price_aed_cents,
                    'product_data' => [
                        'name' => $pack->getTranslation('name', 'en'),
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'customer_id' => (string) $customer->id,
                'hour_pack_id' => (string) $pack->id,
            ],
            'success_url' => route('account.dashboard') . '?pack_purchased=1',
            'cancel_url' => route('account.dashboard') . '?pack_cancelled=1',
        ]);

        return [
            'session_id' => $session->id,
            'url' => $session->url,
        ];
    }
}
