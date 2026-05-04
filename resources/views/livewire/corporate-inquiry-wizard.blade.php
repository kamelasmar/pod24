<section class="container mx-auto py-16 px-4 max-w-3xl">
    @if ($step === 4)
        {{-- Success state --}}
        <div class="text-center py-8">
            <div class="w-20 h-20 mx-auto mb-8 rounded-full bg-pod-accent-soft flex items-center justify-center" aria-hidden="true">
                <svg class="w-10 h-10 text-pod-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-3">Thanks</div>
            <h1 class="text-3xl md:text-4xl font-bold tracking-tight mb-4">We're on it.</h1>
            <p class="text-pod-muted max-w-md mx-auto mb-2">Your inquiry is in. We'll come back inside <strong class="text-pod-ink-deep">24 hours</strong> with a tailored proposal.</p>
            <p class="text-xs text-pod-muted font-mono mt-6">Reference: {{ $submittedUlid }}</p>
            <a href="/" class="inline-block mt-10 text-sm text-pod-muted hover:text-pod-ink-deep underline">← Back to home</a>
        </div>
    @else
        @php $stepLabels = ['Event type', 'Scope &amp; services', 'Your details']; @endphp
        <a href="/" class="text-sm text-pod-muted hover:text-pod-ink-deep mb-3 inline-block">← Back to home</a>
        <div class="text-xs uppercase tracking-[0.2em] text-pod-accent font-bold mb-2">Corporate &amp; on-location</div>
        <x-pod24.wizard-progress :step="$step" :total="3" :labels="$stepLabels" />

        <h1 class="text-3xl md:text-4xl font-bold tracking-tight mb-2">
            {{ ['What are you producing?', 'Help us scope it.', 'How do we reach you?'][$step - 1] ?? '' }}
        </h1>
        <p class="text-pod-muted mb-10">{{ match ($step) {
            1 => 'Pick the closest match — we\'ll tailor the rest of the form.',
            2 => 'Rough numbers are fine. We use these to put the right team on the brief.',
            3 => 'We respond within 24 hours, often faster.',
            default => '',
        } }}</p>

        @if ($step === 1)
            <div class="space-y-3">
                @foreach (\App\Livewire\CorporateInquiryWizard::EVENT_TYPES as $key => $info)
                    <button wire:click="selectEventType('{{ $key }}')"
                            type="button"
                            @class([
                                'w-full text-left p-5 border-2 rounded-lg transition-all cursor-pointer flex items-start gap-4',
                                'border-pod-accent bg-pod-accent-soft' => $eventType === $key,
                                'border-pod-border hover:border-pod-accent' => $eventType !== $key,
                            ])>
                        <div class="flex-1">
                            <div class="font-bold text-pod-ink-deep mb-1">{{ $info['label'] }}</div>
                            <div class="text-sm text-pod-muted">{{ $info['desc'] }}</div>
                        </div>
                        <span class="text-pod-accent font-bold mt-1">→</span>
                    </button>
                @endforeach
            </div>
        @endif

        @if ($step === 2)
            <div class="space-y-8">
                <div>
                    <label class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-3">Where will it happen?</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @foreach ([
                            'studio' => ['Our studio', 'You come to Pod24 at Yas Creative Hub.'],
                            'on_location' => ['On location', 'We bring our team and equipment to your venue.'],
                            'both' => ['Both', 'A mix of studio sessions and on-location filming.'],
                        ] as $value => [$title, $desc])
                            <button wire:click="$set('locationPreference', '{{ $value }}')"
                                    type="button"
                                    @class([
                                        'p-4 border-2 rounded-lg text-left transition-all cursor-pointer',
                                        'border-pod-accent bg-pod-accent-soft' => $locationPreference === $value,
                                        'border-pod-border hover:border-pod-accent/50' => $locationPreference !== $value,
                                    ])>
                                <div class="font-bold text-pod-ink-deep text-sm mb-1">{{ $title }}</div>
                                <div class="text-xs text-pod-muted leading-relaxed">{{ $desc }}</div>
                            </button>
                        @endforeach
                    </div>
                    @error('locationPreference')<p class="text-red-700 text-sm mt-2">{{ $message }}</p>@enderror
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-3">Approximate audience</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach (\App\Livewire\CorporateInquiryWizard::ATTENDEE_BANDS as $band)
                                <button wire:click="$set('attendeesEstimate', '{{ $band }}')"
                                        type="button"
                                        @class([
                                            'px-4 py-2 rounded-full border text-sm font-semibold transition-all cursor-pointer',
                                            'bg-pod-ink-deep text-white border-pod-ink-deep' => $attendeesEstimate === $band,
                                            'border-pod-border text-pod-ink hover:border-pod-accent' => $attendeesEstimate !== $band,
                                        ])>{{ $band }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-3">Approximate length</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach (\App\Livewire\CorporateInquiryWizard::DAYS_BANDS as $band)
                                <button wire:click="$set('daysEstimate', '{{ $band }}')"
                                        type="button"
                                        @class([
                                            'px-4 py-2 rounded-full border text-sm font-semibold transition-all cursor-pointer',
                                            'bg-pod-ink-deep text-white border-pod-ink-deep' => $daysEstimate === $band,
                                            'border-pod-border text-pod-ink hover:border-pod-accent' => $daysEstimate !== $band,
                                        ])>{{ $band }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div>
                    <label for="preferred-dates" class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-2">Preferred dates <span class="text-pod-muted font-normal normal-case tracking-normal">(optional)</span></label>
                    <input type="text"
                           id="preferred-dates"
                           wire:model="preferredDates"
                           placeholder="e.g. mid-October, last week of June, 12-14 March"
                           class="w-full border border-pod-border rounded-lg p-3 focus:outline-none focus:border-pod-accent focus:ring-2 focus:ring-pod-accent-soft">
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-3">Services you're interested in <span class="text-pod-muted font-normal normal-case tracking-normal">(pick any)</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        @foreach (\App\Livewire\CorporateInquiryWizard::SERVICES as $key => $label)
                            @php $isSelected = in_array($key, $serviceInterests, true); @endphp
                            <label for="svc-{{ $key }}"
                                   @class([
                                       'flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-all',
                                       'border-pod-accent bg-pod-accent-soft' => $isSelected,
                                       'border-pod-border hover:border-pod-accent/50' => ! $isSelected,
                                   ])>
                                <input type="checkbox"
                                       id="svc-{{ $key }}"
                                       wire:click="toggleService('{{ $key }}')"
                                       @checked($isSelected)
                                       class="w-4 h-4 accent-pod-accent cursor-pointer">
                                <span class="text-sm text-pod-ink-deep">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button wire:click="back" type="button"
                            class="px-6 py-4 border-2 border-pod-border rounded-full font-bold hover:border-pod-ink-deep transition-all cursor-pointer">
                        ← Back
                    </button>
                    <button wire:click="nextFromScope" type="button"
                            class="flex-1 bg-pod-ink-deep text-white py-4 rounded-full font-bold hover:bg-pod-accent hover:text-pod-ink-deep transition-all cursor-pointer">
                        Continue →
                    </button>
                </div>
            </div>
        @endif

        @if ($step === 3)
            <div class="space-y-4 mb-6">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label for="ci-name" class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-2">Full name</label>
                        <input type="text" id="ci-name" wire:model="contactName" autocomplete="name"
                               class="w-full border border-pod-border rounded-lg p-3 focus:outline-none focus:border-pod-accent focus:ring-2 focus:ring-pod-accent-soft">
                        @error('contactName')<p class="text-red-700 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ci-company" class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-2">Company <span class="text-pod-muted font-normal normal-case tracking-normal">(optional)</span></label>
                        <input type="text" id="ci-company" wire:model="contactCompany" autocomplete="organization"
                               class="w-full border border-pod-border rounded-lg p-3 focus:outline-none focus:border-pod-accent focus:ring-2 focus:ring-pod-accent-soft">
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label for="ci-email" class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-2">Work email</label>
                        <input type="email" id="ci-email" wire:model="contactEmail" autocomplete="email"
                               class="w-full border border-pod-border rounded-lg p-3 focus:outline-none focus:border-pod-accent focus:ring-2 focus:ring-pod-accent-soft">
                        @error('contactEmail')<p class="text-red-700 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ci-phone" class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-2">Phone <span class="text-pod-muted font-normal normal-case tracking-normal">(optional)</span></label>
                        <input type="tel" id="ci-phone" wire:model="contactPhone" autocomplete="tel"
                               class="w-full border border-pod-border rounded-lg p-3 focus:outline-none focus:border-pod-accent focus:ring-2 focus:ring-pod-accent-soft">
                    </div>
                </div>
                <div>
                    <label for="ci-msg" class="block text-xs uppercase tracking-[0.15em] text-pod-muted font-bold mb-2">A few lines about the project <span class="text-pod-muted font-normal normal-case tracking-normal">(optional)</span></label>
                    <textarea id="ci-msg" wire:model="message" rows="4"
                              class="w-full border border-pod-border rounded-lg p-3 focus:outline-none focus:border-pod-accent focus:ring-2 focus:ring-pod-accent-soft"></textarea>
                </div>
            </div>

            <div class="flex gap-3">
                <button wire:click="back" type="button"
                        class="px-6 py-4 border-2 border-pod-border rounded-full font-bold hover:border-pod-ink-deep transition-all cursor-pointer">
                    ← Back
                </button>
                <button wire:click="submit"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                        type="button"
                        class="flex-1 bg-pod-accent text-pod-ink-deep py-4 rounded-full font-bold hover:bg-pod-accent-deep hover:text-white transition-all cursor-pointer disabled:opacity-60 disabled:cursor-wait">
                    <span wire:loading.remove wire:target="submit">Send inquiry →</span>
                    <span wire:loading wire:target="submit">Sending…</span>
                </button>
            </div>
            <p class="text-xs text-pod-muted text-center mt-4">We respond within 24 hours.</p>
        @endif
    @endif
</section>
