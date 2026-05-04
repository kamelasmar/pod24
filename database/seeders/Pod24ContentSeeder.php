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
                'question' => 'Where is Pod24 located?',
                'answer' => 'Pod24 is at Yas Creative Hub in Abu Dhabi. Sessions take place onsite — drop us your email if you\'d like to visit before booking. For off-site/on-location filming (conferences, corporate offsites), use the on-location filming form.',
            ],
            [
                'question' => 'Who owns the recordings?',
                'answer' => 'You do. Every file we capture during your session is yours, delivered as broadcast-ready stems and synced multi-cam video within 24 hours.',
            ],
            [
                'question' => 'What happens if I need to cancel or reschedule?',
                'answer' => 'Cancellations 7+ days before your session are fully refundable. 3-7 days before earns a 50% refund. Inside 72 hours, the booking is non-refundable, but we can usually reschedule without penalty.',
            ],
            [
                'question' => 'Do you handle editing and publishing?',
                'answer' => 'Yes — episode editing, social clips, cover art, and platform distribution are available as add-ons during checkout. Or bring your own editor and we hand off clean stems.',
            ],
            [
                'question' => 'Can I have guests dial in remotely?',
                'answer' => 'Absolutely. We integrate with Riverside and Squadcast so remote guests record locally on their device while joining your session live. Studio-grade quality for everyone, regardless of location.',
            ],
            [
                'question' => 'Do you offer packages for brands and recurring series?',
                'answer' => 'Yes — multi-day rates, recurring-session retainers, and branded series production are all handled through the brands-and-events flow. Tell us about your project and we will scope a quote.',
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
                'quote' => 'Being able to record with a guest in London dialling in — without dropping broadcast quality on our side — is the reason we switched.',
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
                'title' => 'Interview podcasts',
                'description' => 'Up to 4 guests, 4K multi-cam, clean audio tracks.',
            ],
            [
                'title' => 'Solo & monologue',
                'description' => 'Tight setup, minimal crew, maximum sound quality.',
            ],
            [
                'title' => 'Video podcasts',
                'description' => 'YouTube-ready cuts and social clips out of the same session.',
            ],
            [
                'title' => 'Remote guest shows',
                'description' => 'Studio-grade audio locally, broadcast feed from overseas.',
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
