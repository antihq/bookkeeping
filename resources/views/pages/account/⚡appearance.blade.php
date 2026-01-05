<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Appearance')] class extends Component {};
?>

<section class="mx-auto max-w-6xl space-y-8">
    <flux:heading size="lg">Appearance</flux:heading>

    <div class="space-y-14">
        <div class="space-y-8">
            <header>
                <flux:heading>Theme preference</flux:heading>
                <flux:text class="mt-1">Select your preferred color scheme.</flux:text>
            </header>

            <div class="max-w-lg">
                <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                    <flux:radio value="light" icon="sun">Light</flux:radio>
                    <flux:radio value="dark" icon="moon">Dark</flux:radio>
                    <flux:radio value="system" icon="computer-desktop">System</flux:radio>
                </flux:radio.group>
            </div>
        </div>
    </div>
</section>
