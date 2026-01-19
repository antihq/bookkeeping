<?php

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Categories')] class extends Component {
    #[Computed]
    public function categories()
    {
        return $this->team->categories()->get();
    }

    #[Computed]
    public function team(): Team
    {
        return Auth::user()->currentTeam;
    }
};
?>

<section class="mx-auto max-w-lg">
    @if ($this->categories->count() === 0)
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="tag" size="lg" class="text-gray-400 dark:text-gray-600" />
            <flux:text class="mt-4 text-gray-500 dark:text-gray-400">No categories yet</flux:text>
        </div>
    @else
        <div class="flex flex-wrap justify-between gap-x-6 gap-y-4">
            <flux:heading size="xl">All categories</flux:heading>
        </div>

        <div class="mt-8">
            <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
            <div
                class="divide-y divide-zinc-100 overflow-hidden dark:divide-white/5 dark:text-white"
            >
                @foreach ($this->categories as $category)
                    <div
                        wire:key="category-{{ $category->id }}"
                        class="relative flex items-center justify-between gap-4 py-4"
                    >
                        <div>
                            <flux:heading class="leading-6!">
                                <a href="{{ route('categories.show', $category) }}" wire:navigate>
                                    {{ $category->name }}
                                </a>
                            </flux:heading>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
