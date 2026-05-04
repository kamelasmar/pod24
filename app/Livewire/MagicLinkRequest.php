<?php

namespace App\Livewire;

use Livewire\Component;

class MagicLinkRequest extends Component
{
    public string $email = '';

    public function render()
    {
        return view('livewire.magic-link-request')->extends('pod24.layouts.public');
    }
}
