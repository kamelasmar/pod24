@extends('pod24.layouts.public')

@section('content')
    <x-pod24.hero />
    <x-pod24.action-cards />
    <x-pod24.meet-pod />
    <x-pod24.included />
    <x-pod24.book-widget :facility="$pod24Facility" />
    <x-pod24.b2b-form />
    <x-pod24.how />
    <x-pod24.samples />
    <x-pod24.testimonials :items="$testimonials" />
    <x-pod24.faq :items="$faqItems" />
    <x-pod24.final-cta />
    <x-pod24.sticky-cta />
    <x-pod24.footer />
@endsection
