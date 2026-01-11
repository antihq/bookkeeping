<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Profile')] class extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    #[Validate('image|max:10240')]
    public $photo;

    public function mount(): void
    {
        $this->name = Auth::user()->name ?? '';
        $this->email = Auth::user()->email;
    }

    public function updatedPhoto(): void
    {
        $this->validateOnly('photo');

        Auth::user()->updateProfilePhoto($this->photo);

        Flux::toast('Profile photo has been saved.', variant: 'success');

        $this->photo = null;
    }

    public function removePhoto(): void
    {
        Auth::user()->deleteProfilePhoto();

        $this->dispatch('profile-photo-removed');
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast('Profile information has been saved.', variant: 'success');
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
};
?>

<section class="mx-auto max-w-6xl space-y-8">
    <flux:heading size="xl">Profile</flux:heading>

    <div class="space-y-14">
        <div class="space-y-6">
            <header>
                <flux:heading size="lg">Profile information</flux:heading>
                <flux:text class="mt-1">Update your account's profile information and email address.</flux:text>
            </header>

            <div class="flex items-start gap-6">
                <flux:file-upload wire:model="photo">
                    <!-- Custom avatar uploader -->
                    <div
                        class="relative flex size-20 cursor-pointer items-center justify-center rounded-full border border-zinc-200 bg-zinc-100 transition-colors hover:border-zinc-300 hover:bg-zinc-200 dark:border-white/10 dark:bg-white/10 dark:hover:border-white/10 hover:dark:bg-white/15 in-data-dragging:dark:bg-white/15"
                    >
                        <!-- Show uploaded file if it exists -->

                        @if ($photo && ! $errors->has('photo'))
                            <img src="{{ $photo?->temporaryUrl() }}" class="size-full rounded-full object-cover" />
                        @elseif (auth()->user()->profile_photo_path)
                            <img
                                src="{{ auth()->user()->profile_photo_url }}"
                                class="size-full rounded-full object-cover"
                            />
                        @else
                            <!-- Show boring avatar if no file is uploaded -->
                            <x-boring-avatar :name="auth()->user()->name ?? auth()->user()->email" variant="beam" size="80" />
                        @endif

                        <!-- Corner upload icon -->
                        <div class="absolute right-0 bottom-0 rounded-full bg-white dark:bg-zinc-800">
                            <flux:icon
                                name="arrow-up-circle"
                                variant="solid"
                                class="text-zinc-500 dark:text-zinc-400"
                            />
                        </div>
                    </div>
                </flux:file-upload>

                <div class="flex-1 space-y-3">
                    <div class="space-y-2">
                        <flux:label>Profile photo</flux:label>
                        <flux:text>JPG, PNG, or GIF up to 10MB</flux:text>
                    </div>

                    @if ($photo && ! $errors->has('photo'))
                        <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <flux:icon name="check-circle" variant="solid" size="xs" />
                            <span>{{ $photo->getClientOriginalName() }}</span>
                        </div>
                    @endif

                    @if (auth()->user()->profile_photo_path)
                        <flux:button wire:click="removePhoto" size="xs">Remove photo</flux:button>
                    @endif
                </div>
            </div>

            <form wire:submit="updateProfileInformation" class="w-full max-w-lg space-y-8">
                <flux:input wire:model="name" :label="'Name'" type="text" required autofocus autocomplete="name" />

                <div>
                    <flux:input wire:model="email" :label="'Email'" type="email" required autocomplete="email" />

                    @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                        <div>
                            <flux:text class="mt-4">
                                Your email address is unverified.

                                <flux:link
                                    class="cursor-pointer text-sm"
                                    wire:click.prevent="resendVerificationNotification"
                                >
                                    Click here to re-send the verification email.
                                </flux:link>
                            </flux:text>

                            @if (session('status') === 'verification-link-sent')
                                <flux:text class="!dark:text-green-400 mt-2 font-medium !text-green-600">
                                    A new verification link has been sent to your email address.
                                </flux:text>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-end">
                        <flux:button variant="primary" type="submit" class="w-full">Save changes</flux:button>
                    </div>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <livewire:account.delete-user-form />
        </div>
    </div>
</section>
