<?php

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Devices')] class extends Component
{
    #[Locked]
    public string $loginUrl = '';

    #[Locked]
    public string $qrCodeSvg = '';

    public function mount(): void
    {
        $this->generateLoginUrl();
    }

    public function generateLoginUrl(): void
    {
        $this->loginUrl = URL::temporarySignedRoute('device-login', now()->addMinutes(15), ['user' => $this->user->id]);

        $this->generateQrCode();
    }

    public function regenerate(): void
    {
        $this->generateLoginUrl();

        Flux::toast('Login link has been regenerated.', variant: 'success');
    }

    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    private function generateQrCode(): void
    {
        $renderer = new ImageRenderer(new RendererStyle(400), new SvgImageBackEnd);

        $writer = new Writer($renderer);
        $this->qrCodeSvg = $writer->writeString($this->loginUrl);
    }
};

?>

<section class="mx-auto max-w-6xl space-y-8">
    <flux:heading size="xl">Devices</flux:heading>

    <div class="space-y-14">
        <div class="space-y-6">
            <header class="space-y-1">
                <flux:heading size="lg">Login to other devices</flux:heading>
                <flux:text>
                    Easily log in to your account on other devices without entering your password.
                </flux:text>
            </header>

            <div class="max-w-lg space-y-6">
                <div class="space-y-3">
                    <flux:input
                        label="Login link"
                        :value="$loginUrl"
                        variant="filled"
                        description="Share this link with yourself to automatically log in on another device."
                        description:trailing="Link expires in 15 minutes."
                        readonly
                        copyable
                    />
                </div>

                <div class="flex items-center gap-4">
                    <flux:modal.trigger name="qr-code-modal">
                        <flux:button icon="qr-code" icon:variant="micro">Show QR Code</flux:button>
                    </flux:modal.trigger>

                    <flux:button
                        icon="arrow-path"
                        icon:variant="micro"
                        variant="outline"
                        wire:click="regenerate"
                    >
                        Regenerate Link
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <flux:modal name="qr-code-modal" class="max-w-md md:min-w-md">
        <div class="space-y-6">
            <div class="flex flex-col items-center space-y-4">
                <div class="w-full">
                    <flux:icon.qr-code class="text-zinc-400" />
                </div>

                <div class="space-y-2">
                    <flux:heading size="lg">Scan to Login</flux:heading>
                    <flux:text>Use your mobile device's camera or QR code scanner to open this login link.</flux:text>
                </div>
            </div>

            <div class="flex justify-center">
                <div
                    class="relative aspect-square w-64 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700"
                >
                    <div
                        class="flex h-full items-center justify-center bg-white [&_svg]:h-auto [&_svg]:w-full [&_svg]:max-w-full"
                    >
                        {!! $qrCodeSvg !!}
                    </div>
                </div>
            </div>
        </div>
    </flux:modal>
</section>
