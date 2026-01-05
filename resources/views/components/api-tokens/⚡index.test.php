<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('api-tokens.index')
        ->assertStatus(200);
});
