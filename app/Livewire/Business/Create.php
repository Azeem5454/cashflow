<?php

namespace App\Livewire\Business;

use App\Models\Business;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';
    public ?string $description = null;
    public string $currency = 'PKR';
    public string $upgradeModalFeature = '';

    protected function rules(): array
    {
        return [
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'currency'    => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
        ];
    }

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user->isPro() && $user->ownedBusinesses()->count() >= 1) {
            $this->upgradeModalFeature = 'business';
        }
    }

    public function save(): void
    {
        $user = auth()->user();

        if (! $user->isPro() && $user->ownedBusinesses()->count() >= 1) {
            $this->upgradeModalFeature = 'business';

            return;
        }

        $validated = $this->validate();

        $business = Business::create([
            'owner_id'    => $user->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?: null,
            'currency'    => $validated['currency'],
        ]);

        $business->members()->attach($user->id, ['role' => 'owner']);

        $this->redirect(route('businesses.show', $business));
    }

    public function render()
    {
        return view('livewire.business.create');
    }
}
