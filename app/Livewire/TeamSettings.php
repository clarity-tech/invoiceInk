<?php

namespace App\Livewire;

use App\Models\Team;
use Livewire\Attributes\Rule;
use Livewire\Component;

class TeamSettings extends Component
{
    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('nullable|string|max:50|unique:teams,slug')]
    public string $slug = '';

    #[Rule('nullable|string|max:100|unique:teams,custom_domain')]
    public string $custom_domain = '';

    public ?Team $team = null;

    public function mount(): void
    {
        $this->team = auth()->user()?->currentTeam;

        if ($this->team) {
            $this->name = $this->team->name;
            $this->slug = $this->team->slug ?? '';
            $this->custom_domain = $this->team->custom_domain ?? '';
        }
    }

    public function updateTeamName(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        $this->team->update([
            'name' => $this->name,
        ]);

        session()->flash('message', 'Team name updated successfully!');
    }

    public function updateSlug(): void
    {
        $this->validate([
            'slug' => 'nullable|string|max:50|alpha_dash|unique:teams,slug,'.$this->team->id,
        ]);

        $this->team->update([
            'slug' => $this->slug ?: null,
        ]);

        session()->flash('message', 'Team URL handle updated successfully!');
    }

    public function updateCustomDomain(): void
    {
        $this->validate([
            'custom_domain' => 'nullable|string|max:100|regex:/^([a-z0-9-]+\.)+[a-z]{2,}$/i|unique:teams,custom_domain,'.$this->team->id,
        ]);

        $this->team->update([
            'custom_domain' => $this->custom_domain ?: null,
        ]);

        session()->flash('message', 'Custom domain updated successfully!');
    }

    public function render()
    {
        return view('livewire.team-settings')
            ->layout('layouts.app', ['title' => 'Team Settings']);
    }
}
