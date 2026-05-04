<?php

namespace App\Livewire;

use App\Modules\Quotes\Models\Quote;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * 3-step corporate inquiry wizard:
 *  1. Event type
 *  2. Scope, dates, services of interest
 *  3. Contact details + submit
 *
 * Submits to the `quotes` table for the admin pipeline (Plan 5 builds the
 * Filament inbox + status management on top of this).
 */
class CorporateInquiryWizard extends Component
{
    public const TOTAL_STEPS = 3;

    public const EVENT_TYPES = [
        'conference' => ['label' => 'Conference activation', 'desc' => 'Record speakers on the sidelines, same-day clips for social.'],
        'brand_series' => ['label' => 'Branded podcast series', 'desc' => 'Recurring branded format — strategy through distribution.'],
        'offsite' => ['label' => 'Corporate offsite', 'desc' => 'Leadership conversations, internal comms, team storytelling.'],
        'takeover' => ['label' => 'Studio takeover', 'desc' => 'Block-book Pod24 for a multi-day production sprint.'],
        'other' => ['label' => 'Something else', 'desc' => 'Tell us what you have in mind — we\'ve probably done it.'],
    ];

    public const SERVICES = [
        'episode_editing' => 'Episode editing',
        'highlights' => 'Highlights / reels',
        'distribution' => 'Content distribution',
        'translation' => 'Translation + subtitling',
        'subtitles_en' => 'Subtitles — English',
        'subtitles_ar' => 'Subtitles — Arabic',
        'jingle' => 'Custom jingle',
        'intro' => 'Podcast intro',
        'branding' => 'Branding package',
        'platform_setup' => 'Podcast platform setup',
    ];

    public const ATTENDEE_BANDS = ['<50', '50-200', '200-500', '500+'];
    public const DAYS_BANDS = ['1 day', '2-3 days', '4-7 days', '8+ days'];

    #[Url]
    public int $step = 1;

    public ?string $eventType = null;
    public ?string $attendeesEstimate = null;
    public ?string $daysEstimate = null;
    public ?string $locationPreference = null;
    public array $serviceInterests = [];
    public ?string $preferredDates = null;

    public string $contactName = '';
    public string $contactCompany = '';
    public string $contactEmail = '';
    public string $contactPhone = '';
    public string $message = '';

    public ?string $submittedUlid = null;

    public function selectEventType(string $type): void
    {
        if (! array_key_exists($type, self::EVENT_TYPES)) {
            return;
        }
        $this->eventType = $type;
        $this->step = 2;
    }

    public function toggleService(string $key): void
    {
        if (! array_key_exists($key, self::SERVICES)) {
            return;
        }
        if (in_array($key, $this->serviceInterests, true)) {
            $this->serviceInterests = array_values(array_filter(
                $this->serviceInterests,
                fn ($s) => $s !== $key,
            ));
        } else {
            $this->serviceInterests[] = $key;
        }
    }

    public function nextFromScope(): void
    {
        $this->validate([
            'locationPreference' => 'required|in:studio,on_location,both',
        ]);
        $this->step = 3;
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function submit(): void
    {
        $this->validate([
            'eventType' => 'required|in:'.implode(',', array_keys(self::EVENT_TYPES)),
            'locationPreference' => 'required|in:studio,on_location,both',
            'contactName' => 'required|string|min:2|max:120',
            'contactEmail' => 'required|email|max:160',
            'contactCompany' => 'nullable|string|max:160',
            'contactPhone' => 'nullable|string|max:40',
            'message' => 'nullable|string|max:2000',
        ]);

        $quote = Quote::create([
            'type' => 'corporate',
            'status' => 'new',
            'event_type' => $this->eventType,
            'attendees_estimate' => $this->attendeesEstimate,
            'days_estimate' => $this->daysEstimate,
            'location_preference' => $this->locationPreference,
            'service_interests' => array_values(array_intersect($this->serviceInterests, array_keys(self::SERVICES))),
            'preferred_dates' => $this->preferredDates,
            'contact_name' => $this->contactName,
            'contact_email' => $this->contactEmail,
            'contact_phone' => $this->contactPhone ?: null,
            'contact_company' => $this->contactCompany ?: null,
            'message' => $this->message ?: null,
        ]);

        $this->submittedUlid = $quote->ulid;
        $this->step = 4;       // success state — outside the 3-step progress
    }

    public function render()
    {
        return view('livewire.corporate-inquiry-wizard')
            ->extends('pod24.layouts.public');
    }
}
