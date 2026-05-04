<?php

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Exceptions\SlotUnavailable;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingAddon;
use App\Modules\Catalog\Models\Addon;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Pricing\Actions\CalculateBookingPrice;
use App\Modules\Pricing\ValueObjects\BookingDraft;
use Illuminate\Support\Facades\DB;

class CreateBookingHold
{
    public function __construct(private CalculateBookingPrice $pricer) {}

    public function execute(
        BookingDraft $draft,
        array $contact,
        ?array $address = null,
        ?int $customerId = null,
        ?string $marketingConsentAt = null,
    ): Booking {
        return DB::transaction(function () use ($draft, $contact, $address, $customerId, $marketingConsentAt) {
            // Lock the facility row to serialize concurrent capacity checks
            $facility = Facility::lockForUpdate()->findOrFail($draft->facility_id);

            $occupying = Booking::where('facility_id', $facility->id)
                ->whereIn('status', BookingStatus::occupyingValues())
                ->where('starts_at', '<', $draft->ends_at->endOfDay())
                ->where('ends_at', '>', $draft->starts_at->startOfDay())
                ->count();

            if ($occupying >= $facility->max_concurrent_per_day) {
                throw new SlotUnavailable("Facility {$facility->id} is fully booked on " . $draft->starts_at->toDateString());
            }

            $price = $this->pricer->execute($draft);

            $booking = Booking::create([
                'facility_id' => $draft->facility_id,
                'service_tier_id' => $draft->service_tier_id,
                'customer_id' => $customerId,
                'package_type' => $draft->package_type,
                'starts_at' => $draft->starts_at,
                'ends_at' => $draft->ends_at,
                'total_hours' => $draft->totalHours(),
                'status' => BookingStatus::Hold->value,
                'contact_name' => $contact['name'],
                'contact_email' => $contact['email'],
                'contact_phone' => $contact['phone'] ?? null,
                // For in-studio bookings the address is the studio's; off-site bookings
                // can override with the customer's location.
                'address' => $address ?? $facility->address,
                'subtotal_aed_cents' => $price->subtotal(),
                'weekend_markup_aed_cents' => $price->weekend_markup_aed_cents,
                'after_hours_markup_aed_cents' => $price->after_hours_markup_aed_cents,
                'addons_aed_cents' => $price->addons_aed_cents,
                'hour_pack_credit_value_aed_cents' => $price->hour_pack_credit_value_aed_cents,
                'vat_aed_cents' => $price->vat(),
                'total_aed_cents' => $price->total(),
                'hold_expires_at' => now()->addMinutes(15),
                'marketing_consent_at' => $marketingConsentAt,
            ]);

            foreach ($draft->addons as $addonInput) {
                $addon = Addon::find($addonInput['addon_id']);
                BookingAddon::create([
                    'booking_id' => $booking->id,
                    'addon_id' => $addon->id,
                    'qty' => $addonInput['qty'],
                    'price_at_booking_aed_cents' => $addon->price_aed_cents,
                ]);
            }

            if ($draft->customer_id && $draft->requestedPackHours > 0) {
                $balance = app(\App\Modules\Customers\Actions\HourPackBalance::class)
                    ->forCustomer($draft->customer_id, $draft->facility_id);
                $hoursToRedeem = min($draft->requestedPackHours, $balance, $draft->totalHours());
                if ($hoursToRedeem > 0) {
                    $booking->update(['hour_pack_credits_used' => $hoursToRedeem]);
                    app(\App\Modules\Customers\Actions\RedeemHourPackHours::class)
                        ->execute($booking, $hoursToRedeem);
                }
            }

            return $booking;
        });
    }
}
