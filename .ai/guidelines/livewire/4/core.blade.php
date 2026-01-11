## Livewire v4

### Component Creation
- Prefer single-file components (`.blade.php` with embedded PHP) or multi-file components over class-based components.
- Use `{{ $assist->artisanCommand('make:livewire post.create') }}` for single-file (default) or `{{ $assist->artisanCommand('make:livewire post.create --mfc') }}` for multi-file.
- Convert between formats with `{{ $assist->artisanCommand('livewire:convert post.create') }}`.

### Routing
- Use `Route::livewire()` with component names for single-file and multi-file components:
@verbatim
<code-snippet name="Routing to component example" lang="php">
    Route::livewire('/dashboard', 'pages::dashboard');
</code-snippet>
@endverbatim

### Directives and Modifiers
- Use to `wire:navigate:scroll` for scroll preservation with `@persist`:
@verbatim
    ```blade
    @persist('sidebar')
        <div class="overflow-y-scroll" wire:navigate:scroll>
            <!-- ... -->
        </div>
    @endpersist
    ```
@endverbatim
- New v4 directives: `wire:sort`, `wire:intersect`, `wire:ref`, `wire:show`, `wire:text`
- New v4 modifiers: `.renderless`, `.preserve-scroll`

### Streaming
- Use to v4 streaming method signature:
@verbatim
    ```php
    $this->stream(content: 'Hello', replace: true, el: '#container');
    ```
@endverbatim

### JavaScript Hooks
- Use to new `$wire.$js` syntax:
@verbatim
<code-snippet name="livewire:load example" lang="js">
    $wire.$js.bookmark = () => {
        // Toggle bookmark...
    }
</code-snippet>
@endverbatim

### New Features in v4
- Islands architecture (`@island` directive)
- Single-file components (`.blade.php` with embedded PHP)
- Multi-file components
- Viewport intersection with `wire:intersect`
- Drag-and-drop sorting with `wire:sort`
- Async actions with `.async` modifier or `#[Async]` attribute
- Defer loading with `#[Defer]` attribute
- Renderless actions with `#[Renderless]` attribute or `.renderless` modifier
- Automatic scroll preservation with `.preserve-scroll` modifier
- `data-loading` attribute for styling loading states
- JavaScript access to `$errors` magic property
@verbatim
- Slots and attribute forwarding with `{{ $attributes }}`
- JavaScript in view-based components without `@script` wrapper
@endverbatim
