<x-mail::message>
# Your Pod24 booking is confirmed

Hi {{ $booking->contact_name }},

Thanks for booking Pod24! Your session is locked in.

**Date:** {{ $booking->starts_at->format('l, F j, Y') }}
**Time:** {{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->format('H:i') }}
**Total paid:** AED {{ number_format($booking->total_aed_cents / 100, 2) }}

Your booking reference: **{{ $booking->ulid }}**

We'll see you on the day. Reply to this email if anything changes.

Pod24 - twofour54
</x-mail::message>
