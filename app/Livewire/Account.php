<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Account')]
class Account extends Component
{
    public string $name;

    public string $email;

    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())],
        ]);

        auth()->user()->update($validated);

        $this->dispatch('toast', message: 'Profile saved');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed:passwordConfirmation'],
        ]);

        auth()->user()->update(['password' => $this->password]);

        $this->reset('currentPassword', 'password', 'passwordConfirmation');

        $this->dispatch('toast', message: 'Password updated');
    }

    public function render(): View
    {
        return view('livewire.account');
    }
}
