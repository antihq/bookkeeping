<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

new #[Title('Two Factor Authentication')] class extends Component
{
    #[Locked]
    public bool $twoFactorEnabled;

    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showModal = false;

    public bool $showVerificationStep = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        abort_unless(Features::enabled(Features::twoFactorAuthentication()), Response::HTTP_FORBIDDEN);

        if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
            $disableTwoFactorAuthentication(auth()->user());
        }

        $this->twoFactorEnabled = auth()
            ->user()
            ->hasEnabledTwoFactorAuthentication();
        $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }

    public function enable(EnableTwoFactorAuthentication $enableTwoFactorAuthentication): void
    {
        $enableTwoFactorAuthentication(auth()->user());

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()
                ->user()
                ->hasEnabledTwoFactorAuthentication();
        }

        $this->loadSetupData();

        $this->showModal = true;
    }

    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->closeModal();

        $this->twoFactorEnabled = true;
    }

    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }

    public function closeModal(): void
    {
        $this->reset('code', 'manualSetupKey', 'qrCodeSvg', 'showModal', 'showVerificationStep');

        $this->resetErrorBag();

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()
                ->user()
                ->hasEnabledTwoFactorAuthentication();
        }
    }

    public function getModalConfigProperty(): array
    {
        if ($this->twoFactorEnabled) {
            return [
                'title' => 'Two-Factor Authentication Enabled',
                'description' => 'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
                'buttonText' => 'Close',
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => 'Verify Authentication Code',
                'description' => 'Enter the 6-digit code from your authenticator app.',
                'buttonText' => 'Continue',
            ];
        }

        return [
            'title' => 'Enable Two-Factor Authentication',
            'description' => 'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.',
            'buttonText' => 'Continue',
        ];
    }

    private function loadSetupData(): void
    {
        $user = auth()->user();

        try {
            $this->qrCodeSvg = $user?->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (\Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }
};
?>

<section class="mx-auto max-w-6xl space-y-8">
    <flux:heading size="lg">Two Factor Authentication</flux:heading>

    <div class="space-y-14">
        <div class="space-y-8">
            <header>
                <flux:heading>Authentication status</flux:heading>
                <flux:text class="mt-1">
                    Add an extra layer of security to your account using two-factor authentication.
                </flux:text>
            </header>

            <div class="flex w-full max-w-lg flex-col space-y-6 text-sm" wire:cloak>
                @if ($twoFactorEnabled)
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <flux:badge color="green">Enabled</flux:badge>
                        </div>
                        <flux:text>
                            With two-factor authentication enabled, you will be prompted for a secure, random pin during
                            login, which you can retrieve from the TOTP-supported application on your phone.
                        </flux:text>
                        <livewire:account.recovery-codes :$requiresConfirmation />
                        <div class="flex justify-start">
                            <flux:button
                                variant="danger"
                                icon="shield-exclamation"
                                icon:variant="outline"
                                size="sm"
                                wire:click="disable"
                            >
                                Disable 2FA
                            </flux:button>
                        </div>
                    </div>
                @else
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <flux:badge color="red">Disabled</flux:badge>
                        </div>
                        <flux:text variant="subtle">
                            When you enable two-factor authentication, you will be prompted for a secure pin during
                            login. This pin can be retrieved from a TOTP-supported application on your phone.
                        </flux:text>
                        <flux:button
                            variant="primary"
                            icon="shield-check"
                            icon:variant="outline"
                            size="sm"
                            wire:click="enable"
                        >
                            Enable 2FA
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <flux:modal name="two-factor-setup-modal" class="max-w-md md:min-w-md" @close="closeModal" wire:model="showModal">
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div
                    class="w-auto rounded-full border border-stone-100 bg-white p-0.5 shadow-sm dark:border-stone-600 dark:bg-stone-800"
                >
                    <div
                        class="relative overflow-hidden rounded-full border border-stone-200 bg-stone-100 p-2.5 dark:border-stone-600 dark:bg-stone-200"
                    >
                        <div
                            class="absolute inset-0 flex h-full w-full items-stretch justify-around divide-x divide-stone-200 opacity-50 dark:divide-stone-300 [&>div]:flex-1"
                        >
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <div
                            class="absolute inset-0 flex h-full w-full flex-col items-stretch justify-around divide-y divide-stone-200 opacity-50 dark:divide-stone-300 [&>div]:flex-1"
                        >
                            @for ($i = 1; $i <= 5; $i++)
                                <div></div>
                            @endfor
                        </div>

                        <flux:icon.qr-code class="dark:text-accent-foreground relative z-20" />
                    </div>
                </div>

                <div class="space-y-2 text-center">
                    <flux:heading size="lg">{{ $this->modalConfig['title'] }}</flux:heading>
                    <flux:text>{{ $this->modalConfig['description'] }}</flux:text>
                </div>
            </div>

            @if ($showVerificationStep)
                <div class="space-y-6">
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <flux:otp
                            name="code"
                            wire:model="code"
                            length="6"
                            label="OTP Code"
                            label:sr-only
                            class="mx-auto"
                        />
                    </div>

                    <div class="flex items-center space-x-3">
                        <flux:button variant="outline" class="flex-1" wire:click="resetVerification">Back</flux:button>

                        <flux:button
                            variant="primary"
                            class="flex-1"
                            wire:click="confirmTwoFactor"
                            x-bind:disabled="$wire.code.length < 6"
                        >
                            Confirm
                        </flux:button>
                    </div>
                </div>
            @else
                @error('setupData')
                    <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
                @enderror

                <div class="flex justify-center">
                    <div
                        class="relative aspect-square w-64 overflow-hidden rounded-lg border border-stone-200 dark:border-stone-700"
                    >
                        @empty($qrCodeSvg)
                            <div
                                class="absolute inset-0 flex animate-pulse items-center justify-center bg-white dark:bg-stone-700"
                            >
                                <flux:icon.loading />
                            </div>
                        @else
                            <div class="flex h-full items-center justify-center p-4">
                                <div class="rounded bg-white p-3">
                                    {!! $qrCodeSvg !!}
                                </div>
                            </div>
                        @endempty
                    </div>
                </div>

                <div>
                    <flux:button
                        :disabled="$errors->has('setupData')"
                        variant="primary"
                        class="w-full"
                        wire:click="showVerificationIfNecessary"
                    >
                        {{ $this->modalConfig['buttonText'] }}
                    </flux:button>
                </div>

                <div class="space-y-4">
                    <div class="relative flex w-full items-center justify-center">
                        <div class="absolute inset-0 top-1/2 h-px w-full bg-stone-200 dark:bg-stone-600"></div>
                        <span
                            class="relative bg-white px-2 text-sm text-stone-600 dark:bg-stone-800 dark:text-stone-400"
                        >
                            or, enter
                        </span>
                    </div>

                    <div
                        class="flex items-center space-x-2"
                        x-data="{
                            copied: false,
                            async copy() {
                                try {
                                    await navigator.clipboard.writeText('{{ $manualSetupKey }}')
                                    this.copied = true
                                    setTimeout(() => (this.copied = false), 1500)
                                } catch (e) {
                                    console.warn('Could not copy to clipboard')
                                }
                            },
                        }"
                    >
                        <div class="flex w-full items-stretch rounded-xl border dark:border-stone-700">
                            @empty($manualSetupKey)
                                <div class="flex w-full items-center justify-center bg-stone-100 p-3 dark:bg-stone-700">
                                    <flux:icon.loading variant="mini" />
                                </div>
                            @else
                                <input
                                    type="text"
                                    readonly
                                    value="{{ $manualSetupKey }}"
                                    class="w-full bg-transparent p-3 text-stone-900 outline-none dark:text-stone-100"
                                />

                                <button
                                    @click="copy()"
                                    class="cursor-pointer border-l border-stone-200 px-3 transition-colors dark:border-stone-600"
                                >
                                    <flux:icon.document-duplicate x-show="!copied" variant="outline" />
                                    <flux:icon.check x-show="copied" variant="solid" class="text-green-500" />
                                </button>
                            @endempty
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>
</section>
