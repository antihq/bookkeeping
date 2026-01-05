<?php

use Laravel\Jetstream\Jetstream;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Component;

new class extends Component
{
    public PersonalAccessToken $token;

    public $permissions = [];

    public function update()
    {
        $this->token->update([
            'abilities' => Jetstream::validPermissions($this->permissions),
        ]);
    }
};
?>

<div>
    {{-- An unexamined life is not worth living. - Socrates --}}
</div>
