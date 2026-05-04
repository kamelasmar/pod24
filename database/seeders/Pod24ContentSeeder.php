<?php

namespace Database\Seeders;

use App\Modules\Content\Models\FaqItem;
use App\Modules\Content\Models\Testimonial;
use App\Modules\Content\Models\UseCase;
use Illuminate\Database\Seeder;

class Pod24ContentSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'Is Pod24 a video or audio podcast studio?',
                'answer' => 'Pod24 is a video-first podcast studio. Every session is recorded with three cameras at HD/4K with live multi-cam switching, broadcast lighting, and broadcast audio captured in parallel. You walk out with both the finished video and clean audio stems.',
            ],
            [
                'question' => 'Where is Pod24 located?',
                'answer' => 'Pod24 is at Yas Creative Hub in Abu Dhabi. Sessions take place onsite - drop us your email if you\'d like to visit before booking. For off-site/on-location video production (conferences, corporate offsites, brand activations), use the on-location filming form.',
            ],
            [
                'question' => 'Who owns the recordings?',
                'answer' => 'You do. Every file we capture during your session is yours - multi-cam video on an external HDD, plus broadcast-ready audio stems synchronised to the video, delivered within 24 hours.',
            ],
            [
                'question' => 'What happens if I need to cancel or reschedule?',
                'answer' => 'Cancellations 7+ days before your session are fully refundable. 3-7 days before earns a 50% refund. Inside 72 hours, the booking is non-refundable, but we can usually reschedule without penalty.',
            ],
            [
                'question' => 'Do you handle editing and publishing?',
                'answer' => 'Yes - episode editing (1h / 2h / 3h tiers), highlights reels, subtitles in English and Arabic, jingles, branding, and platform distribution are all available. Add-ons during checkout for in-studio sessions; full menu in the corporate flow.',
            ],
            [
                'question' => 'Can I have guests dial in remotely?',
                'answer' => 'Yes. We bring remote guests in as a live video feed alongside the in-studio cameras, all routed through the TriCaster switcher. Studio-grade quality on our side, regardless of where the guest is.',
            ],
            [
                'question' => 'Do you offer packages for brands and recurring series?',
                'answer' => 'Yes - multi-day rates, recurring-session retainers, and branded video series production are all handled through the corporate flow. Tell us about your project and we will scope a quote.',
            ],
        ];

        foreach ($faqs as $i => $faq) {
            FaqItem::updateOrCreate(
                ['question->en' => $faq['question']],
                [
                    'question' => ['en' => $faq['question']],
                    'answer' => ['en' => $faq['answer']],
                    'is_published' => true,
                    'sort_order' => $i + 1,
                ]
            );
        }

        $testimonials = [
            [
                'name' => 'Placeholder Name',
                'role' => 'Host · Placeholder Podcast',
                'quote' => 'We recorded five episodes in one day and walked out with video, audio, and clips ready to post. It felt like having a studio team on-call.',
            ],
            [
                'name' => 'Placeholder Name',
                'role' => 'Founder · Placeholder Studios',
                'quote' => 'The studio at Yas Creative Hub is genuinely better than what we get in most Dubai studios. We\'ve recorded five episodes there in a single afternoon.',
            ],
            [
                'name' => 'Placeholder Name',
                'role' => 'Producer · Placeholder Network',
                'quote' => 'Being able to record with a guest in London dialling in - without dropping broadcast quality on our side - is the reason we switched.',
            ],
        ];

        foreach ($testimonials as $t) {
            Testimonial::updateOrCreate(
                ['name' => $t['name'], 'role' => $t['role']],
                [
                    'quote' => ['en' => $t['quote']],
                    'is_published' => true,
                ]
            );
        }

        $useCases = [
            [
                'title' => 'Interview shows',
                'description' => 'Up to 4 guests on camera, three angles, live multi-cam switching for YouTube-ready cuts.',
            ],
            [
                'title' => 'Solo & monologue',
                'description' => 'Single-host video podcasts with broadcast lighting and clean audio capture.',
            ],
            [
                'title' => 'Branded video series',
                'description' => 'Recurring company formats. Identical setup every week, same look every episode.',
            ],
            [
                'title' => 'Remote guest shows',
                'description' => 'Live remote feeds mixed alongside the in-studio cameras through the TriCaster switcher.',
            ],
        ];

        foreach ($useCases as $i => $u) {
            UseCase::updateOrCreate(
                ['title->en' => $u['title']],
                [
                    'title' => ['en' => $u['title']],
                    'description' => ['en' => $u['description']],
                    'is_published' => true,
                    'sort_order' => $i + 1,
                ]
            );
        }
    }
}
