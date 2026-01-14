<?php

namespace App\Livewire;

use Livewire\Component;

class NavigationMenu extends Component
{
    protected $listeners = [
        'refresh-navigation-menu' => '$refresh',
    ];

    public function render()
    {
        return view('navigation-menu');
    }
}
