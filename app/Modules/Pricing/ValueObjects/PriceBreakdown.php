<?php

namespace App\Modules\Pricing\ValueObjects;

final readonly class PriceBreakdown
{
    public const VAT_RATE_BPS = 500;   // 5.00% in basis points

    public function __construct(
        public int $base_aed_cents = 0,
        public int $weekend_markup_aed_cents = 0,
        public int $after_hours_markup_aed_cents = 0,
        public int $addons_aed_cents = 0,
        public int $hour_pack_credit_value_aed_cents = 0,
    ) {}

    public function subtotal(): int
    {
        return $this->base_aed_cents
            + $this->weekend_markup_aed_cents
            + $this->after_hours_markup_aed_cents
            + $this->addons_aed_cents
            - $this->hour_pack_credit_value_aed_cents;
    }

    public function vat(): int
    {
        return (int) round($this->subtotal() * self::VAT_RATE_BPS / 10_000);
    }

    public function total(): int
    {
        return $this->subtotal() + $this->vat();
    }

    public function toArray(): array
    {
        return [
            'base_aed_cents' => $this->base_aed_cents,
            'weekend_markup_aed_cents' => $this->weekend_markup_aed_cents,
            'after_hours_markup_aed_cents' => $this->after_hours_markup_aed_cents,
            'addons_aed_cents' => $this->addons_aed_cents,
            'hour_pack_credit_value_aed_cents' => $this->hour_pack_credit_value_aed_cents,
            'subtotal_aed_cents' => $this->subtotal(),
            'vat_aed_cents' => $this->vat(),
            'total_aed_cents' => $this->total(),
        ];
    }
}
