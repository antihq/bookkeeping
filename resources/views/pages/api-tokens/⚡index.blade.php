<?php

use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\Jetstream;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public $name = '';

    public $permissions = [];

    public function create()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $this->user->createToken($this->pull('name'), Jetstream::validPermissions($this->pull('permissions')));
    }

    public function delete($tokenId)
    {
        $this->user
            ->tokens()
            ->where('id', $tokenId)
            ->first()
            ->delete();
    }

    #[Computed]
    public function user()
    {
        return Auth::user();
    }
};
?>

<div>
    {{-- We must ship. - Taylor Otwell --}}
</div>
