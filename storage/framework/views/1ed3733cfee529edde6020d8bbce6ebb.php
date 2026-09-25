

<?php $iconTrailing ??= $attributes->pluck('icon:trailing'); ?>
<?php $iconLeading ??= $attributes->pluck('icon:leading'); ?>
<?php $iconVariant ??= $attributes->pluck('icon:variant'); ?>

<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'iconTrailing' => null,
    'variant' => 'outline',
    'iconVariant' => null,
    'iconLeading' => null,
    'type' => 'button',
    'loading' => null,
    'align' => 'center',
    'size' => 'base',
    'square' => null,
    'color' => null,
    'inset' => null,
    'icon' => null,
    'kbd' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'iconTrailing' => null,
    'variant' => 'outline',
    'iconVariant' => null,
    'iconLeading' => null,
    'type' => 'button',
    'loading' => null,
    'align' => 'center',
    'size' => 'base',
    'square' => null,
    'color' => null,
    'inset' => null,
    'icon' => null,
    'kbd' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$iconLeading = $icon ??= $iconLeading;

// Button should be a square if it has no text contents...
$square ??= $slot->isEmpty() && ! ($iconLeading && $iconTrailing);

// Size-up icons in square/icon-only buttons... (xs buttons just get micro size/style...)
$iconVariant ??= ($size === 'xs')
    ? ($square ? 'micro' : 'micro')
    : ($square ? 'mini' : 'micro');

$iconTrailingVariant ??= $attributes->pluck('icon-trailing:variant', $iconVariant);

// When using the outline icon variant, we need to size it down to match the default icon sizes...
$iconClasses = Flux::classes()
    ->add($iconVariant === 'outline' ? ($square && $size !== 'xs' ? 'size-5' : 'size-4') : '')
    ->add($attributes->pluck('icon:class'))
    ;

$iconTrailingClasses = Flux::classes()
    ->add($iconTrailingVariant === 'outline' ? ($square && $size !== 'xs' ? 'size-5' : 'size-4') : '')
    ->add($attributes->pluck('icon-trailing:class'))
    ;

$isTypeSubmitAndNotDisabledOnRender = $type === 'submit' && ! $attributes->has('disabled');

$isJsMethod = str_starts_with($attributes->whereStartsWith('wire:click')->first() ?? '', '$js.');

$loading ??= $loading ?? ($isTypeSubmitAndNotDisabledOnRender || $attributes->whereStartsWith('wire:click')->isNotEmpty() && ! $isJsMethod);

if ($loading && $type !== 'submit' && ! $isJsMethod) {
    $attributes = $attributes->merge(['wire:loading.attr' => 'data-flux-loading']);

    // We need to add `wire:target` here because without it the loading indicator won't be scoped
    // by method params, causing multiple buttons with the same method but different params to
    // trigger each other's loading indicators...
    if (! $attributes->has('wire:target') && $target = $attributes->whereStartsWith('wire:click')->first()) {
        $attributes = $attributes->merge(['wire:target' => $target], escape: false);
    }
}

$isColored = $color
    && in_array($variant, ['filled', 'outline', 'ghost', 'subtle'], true)
    && ! in_array($color, ['slate', 'gray', 'zinc', 'neutral', 'stone', 'mauve', 'olive', 'mist', 'taupe'], true);

$classes = Flux::classes()
    ->add('relative items-center font-medium justify-center whitespace-nowrap')
    ->add('disabled:opacity-50 dark:disabled:opacity-50 disabled:cursor-default disabled:pointer-events-none disabled:shadow-none')
    ->add(match ($align) {
        'start' => 'justify-start',
        'center' => 'justify-center',
        'end' => 'justify-end',
    })
    ->add(match ($size) { // Size...
        'base' => 'h-10 text-sm rounded-lg gap-2' . ' ' . (
            $square
                ? 'w-10'
                // If we have an icon, we want to reduce the padding on the side that has the icon...
                : ($iconLeading && $iconLeading !== '' ? 'ps-3' : 'ps-4') . ' ' . ($iconTrailing && $iconTrailing !== '' ? 'pe-3' : 'pe-4')
        ),
        'sm' => 'h-8 text-sm rounded-md gap-2' . ' ' . (
            $square
                ? 'w-8'
                : ($iconLeading && $iconLeading !== '' ? 'ps-2' : 'ps-3') . ' ' . ($iconTrailing && $iconTrailing !== '' ? 'pe-2' : 'pe-3')
        ),
        'xs' => 'h-6 text-xs rounded-md gap-1' . ' ' . (
            $square
                ? 'w-6'
                : ($iconLeading && $iconLeading !== '' ? 'ps-1' : 'ps-2') . ' ' . ($iconTrailing && $iconTrailing !== '' ? 'pe-1' : 'pe-2')
        ),
    })
    ->add('inline-flex') // Buttons are inline by default but links are blocks, so inline-flex is needed here to ensure link-buttons are displayed the same as buttons...
    ->add($inset ? match ($size) { // Inset...
        'base' => $square
            ? Flux::applyInset($inset, top: '-mt-2.5', right: '-me-2.5', bottom: '-mb-2.5', left: '-ms-2.5')
            : Flux::applyInset($inset, top: '-mt-2.5', right: ($iconTrailing && $iconTrailing !== '' ? '-me-3' : '-me-4'), bottom: '-mb-3', left: ($iconLeading && $iconLeading !== '' ? '-ms-3' : '-ms-4')),
        'sm' => $square
            ? Flux::applyInset($inset, top: '-mt-1.5', right: '-me-1.5', bottom: '-mb-1.5', left: '-ms-1.5')
            : Flux::applyInset($inset, top: '-mt-1.5', right: ($iconTrailing && $iconTrailing !== '' ? '-me-2' : '-me-3'), bottom: '-mb-1.5', left: ($iconLeading && $iconLeading !== '' ? '-ms-2' : '-ms-3')),
        'xs' => $square
            ? Flux::applyInset($inset, top: '-mt-1', right: '-me-1', bottom: '-mb-1', left: '-ms-1')
            : Flux::applyInset($inset, top: '-mt-1', right: ($iconTrailing && $iconTrailing !== '' ? '-me-1' : '-me-2'), bottom: '-mb-1', left: ($iconLeading && $iconLeading !== '' ? '-ms-1' : '-ms-2')),
    } : '')
    ->add(match (true) { // Background color...
        $isColored && $variant === 'filled' => 'bg-[color-mix(in_oklab,_var(--color-button-soft),_transparent_80%)] hover:bg-[color-mix(in_oklab,_var(--color-button-soft),_transparent_70%)] dark:bg-[color-mix(in_oklab,_var(--color-button-soft),_transparent_60%)] dark:hover:bg-[color-mix(in_oklab,_var(--color-button-soft),_transparent_50%)]',
        $isColored && $variant === 'outline' => 'bg-white hover:bg-[color-mix(in_oklab,_var(--color-button-soft)_6%,_white)] dark:bg-zinc-700 dark:hover:bg-[color-mix(in_oklab,_var(--color-accent-content)_10%,_var(--color-zinc-700))]',
        $isColored && $variant === 'ghost' => 'bg-transparent hover:bg-[color-mix(in_oklab,_var(--color-button-soft),_transparent_80%)] active:bg-[color-mix(in_oklab,_var(--color-button-soft),_transparent_70%)] dark:hover:bg-[color-mix(in_oklab,_var(--color-button-soft),_transparent_60%)] dark:active:bg-[color-mix(in_oklab,_var(--color-button-soft),_transparent_50%)]',
        $isColored && $variant === 'subtle' => 'bg-transparent hover:bg-(--color-button-tint) dark:hover:bg-[color-mix(in_oklab,_var(--color-button-deep),_transparent_28%)]',
        default => match ($variant) {
            'primary' => 'bg-[var(--color-accent)] hover:bg-[color-mix(in_oklab,_var(--color-accent),_transparent_10%)]',
            'filled' => 'bg-zinc-800/5 hover:bg-zinc-800/10 dark:bg-white/10 dark:hover:bg-white/20',
            'outline' => 'bg-white hover:bg-zinc-50 dark:bg-zinc-700 dark:hover:bg-zinc-600/75',
            'danger' => 'bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-500',
            'ghost' => 'bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15',
            'subtle' => 'bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15',
        },
    })
    ->add(match (true) { // Text color...
        $isColored && $variant === 'filled' => 'text-(--color-button-strong) dark:text-(--color-button-soft-foreground)',
        $isColored && $variant === 'outline' => 'text-(--color-button-strong) dark:text-(--color-accent-content)',
        $isColored && $variant === 'ghost' => 'text-(--color-button-strong) dark:text-(--color-button-muted)',
        $isColored && $variant === 'subtle' => 'text-[color-mix(in_oklab,_var(--color-accent-content),_var(--color-zinc-500)_46%)] hover:text-(--color-button-strong) dark:text-[color-mix(in_oklab,_var(--color-button-muted),_var(--color-zinc-400)_48%)] dark:hover:text-(--color-button-muted)',
        default => match ($variant) {
            'primary' => 'text-[var(--color-accent-foreground)]',
            'filled' => 'text-zinc-800 dark:text-white',
            'outline' => 'text-zinc-800 dark:text-white',
            'danger' => 'text-white',
            'ghost' => 'text-zinc-800 dark:text-white',
            'subtle' => 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white',
        },
    })
    ->add(match (true) { // Border color...
        $isColored && $variant === 'outline' => 'border border-[color-mix(in_oklab,_var(--color-button-border)_18%,_var(--color-zinc-200))] border-b-[color-mix(in_oklab,_color-mix(in_oklab,_var(--color-button-border)_26%,_var(--color-zinc-300))_80%,_transparent)] hover:border-[color-mix(in_oklab,_var(--color-button-border)_28%,_var(--color-zinc-200))] hover:border-b-[color-mix(in_oklab,_var(--color-button-border)_36%,_var(--color-zinc-200))] dark:border-[color-mix(in_oklab,_var(--color-accent-content)_22%,_var(--color-zinc-600))] dark:border-b-[color-mix(in_oklab,_var(--color-accent-content)_30%,_var(--color-zinc-600))] dark:hover:border-[color-mix(in_oklab,_var(--color-accent-content)_32%,_var(--color-zinc-600))] dark:hover:border-b-[color-mix(in_oklab,_var(--color-accent-content)_40%,_var(--color-zinc-600))]',
        default => match ($variant) {
            'primary' => 'border border-black/10 dark:border-0',
            'outline' => 'border border-zinc-200 hover:border-zinc-200 disabled:border-zinc-200 border-b-zinc-300/80 dark:border-zinc-600 dark:hover:border-zinc-600 dark:disabled:border-zinc-600',
            default => '',
        },
    })
    ->add(match ($variant) { // Shadows...
        'primary' => 'shadow-[inset_0px_1px_--theme(--color-white/.2)]',
        'danger' => 'shadow-[inset_0px_1px_var(--color-red-500),inset_0px_2px_--theme(--color-white/.15)] dark:shadow-none',
        'outline' => match ($size) {
            'base' => 'shadow-xs',
            'sm' => 'shadow-xs',
            'xs' => 'shadow-none',
        },
        default => '',
    })
    ->add(match ($variant) { // Grouped border treatments...
        'ghost' => '',
        'subtle' => '',
        'outline' => '[[data-flux-button-group]_&]:border-s-0 [:is([data-flux-button-group]>&:first-child,_[data-flux-button-group]_:first-child>&)]:border-s-[1px]',
        'filled' => '[[data-flux-button-group]_&]:border-e [:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-0 [[data-flux-button-group]_&]:border-zinc-200/80 dark:[[data-flux-button-group]_&]:border-zinc-900/50',
        'danger' => '[[data-flux-button-group]_&]:border-e [:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-0 [[data-flux-button-group]_&]:border-red-600 dark:[[data-flux-button-group]_&]:border-red-900/25',
        'primary' => '[[data-flux-button-group]_&]:border-e-0 [:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-[1px] dark:[:is([data-flux-button-group]>&:last-child,_[data-flux-button-group]_:last-child>&)]:border-e-0 [:is([data-flux-button-group]>&:not(:first-child),_[data-flux-button-group]_:not(:first-child)>&)]:border-s-[1px]! [:is([data-flux-button-group]>&:not(:first-child),_[data-flux-button-group]_:not(:first-child)>&)]:border-s-[color-mix(in_srgb,var(--color-accent-foreground),transparent_85%)]',
    })
    ->add($loading ? [ // Loading states...
        '*:transition-opacity',
        $type === 'submit' ? '[&[disabled]>:not([data-flux-loading-indicator])]:opacity-0' : '[&[data-loading]>:not([data-flux-loading-indicator])]:opacity-0 [&[data-flux-loading]>:not([data-flux-loading-indicator])]:opacity-0',
        $type === 'submit' ? '[&[disabled]>[data-flux-loading-indicator]]:opacity-100' : '[&[data-loading]>[data-flux-loading-indicator]]:opacity-100 [&[data-flux-loading]>[data-flux-loading-indicator]]:opacity-100',
        $type === 'submit' ? '[&[disabled]]:pointer-events-none' : 'data-loading:pointer-events-none data-flux-loading:pointer-events-none',
    ] : [])
    ->add(($variant === 'primary' || $isColored) ? match ($color) {
        'slate' => '[--color-accent:var(--color-slate-800)] [--color-accent-content:var(--color-slate-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-slate-800)]',
        'gray' => '[--color-accent:var(--color-gray-800)] [--color-accent-content:var(--color-gray-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-gray-800)]',
        'zinc' => '[--color-accent:var(--color-zinc-800)] [--color-accent-content:var(--color-zinc-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-zinc-800)]',
        'neutral' => '[--color-accent:var(--color-neutral-800)] [--color-accent-content:var(--color-neutral-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-neutral-800)]',
        'stone' => '[--color-accent:var(--color-stone-800)] [--color-accent-content:var(--color-stone-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-stone-800)]',
        'mauve' => '[--color-accent:var(--color-mauve-800)] [--color-accent-content:var(--color-mauve-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-mauve-800)]',
        'olive' => '[--color-accent:var(--color-olive-800)] [--color-accent-content:var(--color-olive-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-olive-800)]',
        'mist' => '[--color-accent:var(--color-mist-800)] [--color-accent-content:var(--color-mist-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-mist-800)]',
        'taupe' => '[--color-accent:var(--color-taupe-800)] [--color-accent-content:var(--color-taupe-800)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-white)] dark:[--color-accent-content:var(--color-white)] dark:[--color-accent-foreground:var(--color-taupe-800)]',
        'red' => '[--color-accent:var(--color-red-500)] [--color-accent-content:var(--color-red-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-red-500)] dark:[--color-accent-content:var(--color-red-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'orange' => '[--color-accent:var(--color-orange-500)] [--color-accent-content:var(--color-orange-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-orange-400)] dark:[--color-accent-content:var(--color-orange-400)] dark:[--color-accent-foreground:var(--color-orange-950)]',
        'amber' => '[--color-accent:var(--color-amber-400)] [--color-accent-content:var(--color-amber-600)] [--color-accent-foreground:var(--color-amber-950)] dark:[--color-accent:var(--color-amber-400)] dark:[--color-accent-content:var(--color-amber-400)] dark:[--color-accent-foreground:var(--color-amber-950)]',
        'yellow' => '[--color-accent:var(--color-yellow-400)] [--color-accent-content:var(--color-yellow-600)] [--color-accent-foreground:var(--color-yellow-950)] dark:[--color-accent:var(--color-yellow-400)] dark:[--color-accent-content:var(--color-yellow-400)] dark:[--color-accent-foreground:var(--color-yellow-950)]',
        'lime' => '[--color-accent:var(--color-lime-400)] [--color-accent-content:var(--color-lime-600)] [--color-accent-foreground:var(--color-lime-900)] dark:[--color-accent:var(--color-lime-400)] dark:[--color-accent-content:var(--color-lime-400)] dark:[--color-accent-foreground:var(--color-lime-950)]',
        'green' => '[--color-accent:var(--color-green-600)] [--color-accent-content:var(--color-green-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-green-600)] dark:[--color-accent-content:var(--color-green-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'emerald' => '[--color-accent:var(--color-emerald-600)] [--color-accent-content:var(--color-emerald-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-emerald-600)] dark:[--color-accent-content:var(--color-emerald-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'teal' => '[--color-accent:var(--color-teal-600)] [--color-accent-content:var(--color-teal-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-teal-600)] dark:[--color-accent-content:var(--color-teal-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'cyan' => '[--color-accent:var(--color-cyan-600)] [--color-accent-content:var(--color-cyan-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-cyan-600)] dark:[--color-accent-content:var(--color-cyan-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'sky' => '[--color-accent:var(--color-sky-600)] [--color-accent-content:var(--color-sky-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-sky-600)] dark:[--color-accent-content:var(--color-sky-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'blue' => '[--color-accent:var(--color-blue-500)] [--color-accent-content:var(--color-blue-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-blue-500)] dark:[--color-accent-content:var(--color-blue-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'indigo' => '[--color-accent:var(--color-indigo-500)] [--color-accent-content:var(--color-indigo-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-indigo-500)] dark:[--color-accent-content:var(--color-indigo-300)] dark:[--color-accent-foreground:var(--color-white)]',
        'violet' => '[--color-accent:var(--color-violet-500)] [--color-accent-content:var(--color-violet-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-violet-500)] dark:[--color-accent-content:var(--color-violet-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'purple' => '[--color-accent:var(--color-purple-500)] [--color-accent-content:var(--color-purple-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-purple-500)] dark:[--color-accent-content:var(--color-purple-300)] dark:[--color-accent-foreground:var(--color-white)]',
        'fuchsia' => '[--color-accent:var(--color-fuchsia-600)] [--color-accent-content:var(--color-fuchsia-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-fuchsia-600)] dark:[--color-accent-content:var(--color-fuchsia-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'pink' => '[--color-accent:var(--color-pink-600)] [--color-accent-content:var(--color-pink-600)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-pink-600)] dark:[--color-accent-content:var(--color-pink-400)] dark:[--color-accent-foreground:var(--color-white)]',
        'rose' => '[--color-accent:var(--color-rose-500)] [--color-accent-content:var(--color-rose-500)] [--color-accent-foreground:var(--color-white)] dark:[--color-accent:var(--color-rose-500)] dark:[--color-accent-content:var(--color-rose-400)] dark:[--color-accent-foreground:var(--color-white)]',
        default => '',
    } : '')
    ->add($isColored ? match ($color) {
        'red' => '[--color-button-tint:var(--color-red-50)] [--color-button-soft-foreground:var(--color-red-200)] [--color-button-muted:var(--color-red-300)] [--color-button-soft:var(--color-red-400)] [--color-button-border:var(--color-red-500)] [--color-button-strong:var(--color-red-700)] [--color-button-deep:var(--color-red-950)]',
        'orange' => '[--color-button-tint:var(--color-orange-50)] [--color-button-soft-foreground:var(--color-orange-200)] [--color-button-muted:var(--color-orange-300)] [--color-button-soft:var(--color-orange-400)] [--color-button-border:var(--color-orange-500)] [--color-button-strong:var(--color-orange-700)] [--color-button-deep:var(--color-orange-950)]',
        'amber' => '[--color-button-tint:var(--color-amber-50)] [--color-button-soft-foreground:var(--color-amber-200)] [--color-button-muted:var(--color-amber-300)] [--color-button-soft:var(--color-amber-400)] [--color-button-border:var(--color-amber-500)] [--color-button-strong:var(--color-amber-700)] [--color-button-deep:var(--color-amber-950)]',
        'yellow' => '[--color-button-tint:var(--color-yellow-50)] [--color-button-soft-foreground:var(--color-yellow-200)] [--color-button-muted:var(--color-yellow-300)] [--color-button-soft:var(--color-yellow-400)] [--color-button-border:var(--color-yellow-500)] [--color-button-strong:var(--color-yellow-700)] [--color-button-deep:var(--color-yellow-950)]',
        'lime' => '[--color-button-tint:var(--color-lime-50)] [--color-button-soft-foreground:var(--color-lime-200)] [--color-button-muted:var(--color-lime-300)] [--color-button-soft:var(--color-lime-400)] [--color-button-border:var(--color-lime-500)] [--color-button-strong:var(--color-lime-700)] [--color-button-deep:var(--color-lime-950)]',
        'green' => '[--color-button-tint:var(--color-green-50)] [--color-button-soft-foreground:var(--color-green-200)] [--color-button-muted:var(--color-green-300)] [--color-button-soft:var(--color-green-400)] [--color-button-border:var(--color-green-500)] [--color-button-strong:var(--color-green-700)] [--color-button-deep:var(--color-green-950)]',
        'emerald' => '[--color-button-tint:var(--color-emerald-50)] [--color-button-soft-foreground:var(--color-emerald-200)] [--color-button-muted:var(--color-emerald-300)] [--color-button-soft:var(--color-emerald-400)] [--color-button-border:var(--color-emerald-500)] [--color-button-strong:var(--color-emerald-700)] [--color-button-deep:var(--color-emerald-950)]',
        'teal' => '[--color-button-tint:var(--color-teal-50)] [--color-button-soft-foreground:var(--color-teal-200)] [--color-button-muted:var(--color-teal-300)] [--color-button-soft:var(--color-teal-400)] [--color-button-border:var(--color-teal-500)] [--color-button-strong:var(--color-teal-700)] [--color-button-deep:var(--color-teal-950)]',
        'cyan' => '[--color-button-tint:var(--color-cyan-50)] [--color-button-soft-foreground:var(--color-cyan-200)] [--color-button-muted:var(--color-cyan-300)] [--color-button-soft:var(--color-cyan-400)] [--color-button-border:var(--color-cyan-500)] [--color-button-strong:var(--color-cyan-700)] [--color-button-deep:var(--color-cyan-950)]',
        'sky' => '[--color-button-tint:var(--color-sky-50)] [--color-button-soft-foreground:var(--color-sky-200)] [--color-button-muted:var(--color-sky-300)] [--color-button-soft:var(--color-sky-400)] [--color-button-border:var(--color-sky-500)] [--color-button-strong:var(--color-sky-700)] [--color-button-deep:var(--color-sky-950)]',
        'blue' => '[--color-button-tint:var(--color-blue-50)] [--color-button-soft-foreground:var(--color-blue-200)] [--color-button-muted:var(--color-blue-300)] [--color-button-soft:var(--color-blue-400)] [--color-button-border:var(--color-blue-500)] [--color-button-strong:var(--color-blue-700)] [--color-button-deep:var(--color-blue-950)]',
        'indigo' => '[--color-button-tint:var(--color-indigo-50)] [--color-button-soft-foreground:var(--color-indigo-200)] [--color-button-muted:var(--color-indigo-300)] [--color-button-soft:var(--color-indigo-400)] [--color-button-border:var(--color-indigo-500)] [--color-button-strong:var(--color-indigo-700)] [--color-button-deep:var(--color-indigo-950)]',
        'violet' => '[--color-button-tint:var(--color-violet-50)] [--color-button-soft-foreground:var(--color-violet-200)] [--color-button-muted:var(--color-violet-300)] [--color-button-soft:var(--color-violet-400)] [--color-button-border:var(--color-violet-500)] [--color-button-strong:var(--color-violet-700)] [--color-button-deep:var(--color-violet-950)]',
        'purple' => '[--color-button-tint:var(--color-purple-50)] [--color-button-soft-foreground:var(--color-purple-200)] [--color-button-muted:var(--color-purple-300)] [--color-button-soft:var(--color-purple-400)] [--color-button-border:var(--color-purple-500)] [--color-button-strong:var(--color-purple-700)] [--color-button-deep:var(--color-purple-950)]',
        'fuchsia' => '[--color-button-tint:var(--color-fuchsia-50)] [--color-button-soft-foreground:var(--color-fuchsia-200)] [--color-button-muted:var(--color-fuchsia-300)] [--color-button-soft:var(--color-fuchsia-400)] [--color-button-border:var(--color-fuchsia-500)] [--color-button-strong:var(--color-fuchsia-700)] [--color-button-deep:var(--color-fuchsia-950)]',
        'pink' => '[--color-button-tint:var(--color-pink-50)] [--color-button-soft-foreground:var(--color-pink-200)] [--color-button-muted:var(--color-pink-300)] [--color-button-soft:var(--color-pink-400)] [--color-button-border:var(--color-pink-500)] [--color-button-strong:var(--color-pink-700)] [--color-button-deep:var(--color-pink-950)]',
        'rose' => '[--color-button-tint:var(--color-rose-50)] [--color-button-soft-foreground:var(--color-rose-200)] [--color-button-muted:var(--color-rose-300)] [--color-button-soft:var(--color-rose-400)] [--color-button-border:var(--color-rose-500)] [--color-button-strong:var(--color-rose-700)] [--color-button-deep:var(--color-rose-950)]',
        default => '',
    } : '')
    ;

    // Exempt subtle and ghost buttons from receiving border roundness overrides from button.group...
    $attributes = $attributes->merge([
        'data-flux-group-target' => ! in_array($variant, ['subtle', 'ghost']),
    ]);
?>

<?php if (isset($component)) { $__componentOriginal1bac653003a70249c8d1bced240ca490 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1bac653003a70249c8d1bced240ca490 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::with-tooltip','data' => ['attributes' => $attributes]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::with-tooltip'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <?php if (isset($component)) { $__componentOriginal44af18836315d1ca840cbdae84566f63 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal44af18836315d1ca840cbdae84566f63 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::button-or-link-pure','data' => ['type' => $type,'attributes' => $attributes->class($classes),'dataFluxButton' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::button-or-link-pure'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($type),'attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes->class($classes)),'data-flux-button' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <?php if ($loading): ?>
            <div class="absolute inset-0 flex items-center justify-center opacity-0" data-flux-loading-indicator>
                <?php if (isset($component)) { $__componentOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.index','data' => ['icon' => 'loading','variant' => $iconVariant,'class' => $iconClasses]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'loading','variant' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($iconVariant),'class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($iconClasses)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2)): ?>
<?php $attributes = $__attributesOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2; ?>
<?php unset($__attributesOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2)): ?>
<?php $component = $__componentOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2; ?>
<?php unset($__componentOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2); ?>
<?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (is_string($iconLeading) && $iconLeading !== ''): ?>
            <?php if (isset($component)) { $__componentOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.index','data' => ['icon' => $iconLeading,'variant' => $iconVariant,'class' => $iconClasses]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($iconLeading),'variant' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($iconVariant),'class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($iconClasses)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2)): ?>
<?php $attributes = $__attributesOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2; ?>
<?php unset($__attributesOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2)): ?>
<?php $component = $__componentOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2; ?>
<?php unset($__componentOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2); ?>
<?php endif; ?>
        <?php elseif ($iconLeading): ?>
            <?php echo e($iconLeading); ?>

        <?php endif; ?>

        <?php if (($loading || $iconLeading || $iconTrailing) && ! $slot->isEmpty()): ?>
            
            
            <span><?php echo e($slot); ?></span>
        <?php else: ?>
            <?php echo e($slot); ?>

        <?php endif; ?>

        <?php if ($kbd): ?>
            <div class="text-xs text-zinc-400 dark:text-zinc-400"><?php echo e($kbd); ?></div>
        <?php endif; ?>

        <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
            
            <?php $iconClasses->add($square ? '' : '-ms-1'); ?>
            <?php if (isset($component)) { $__componentOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.index','data' => ['icon' => $iconTrailing,'variant' => $iconTrailingVariant,'class' => $iconTrailingClasses]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($iconTrailing),'variant' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($iconTrailingVariant),'class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($iconTrailingClasses)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2)): ?>
<?php $attributes = $__attributesOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2; ?>
<?php unset($__attributesOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2)): ?>
<?php $component = $__componentOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2; ?>
<?php unset($__componentOriginalc7d5f44bf2a2d803ed0b55f72f1f82e2); ?>
<?php endif; ?>
        <?php elseif ($iconTrailing): ?>
            <?php echo e($iconTrailing); ?>

        <?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal44af18836315d1ca840cbdae84566f63)): ?>
<?php $attributes = $__attributesOriginal44af18836315d1ca840cbdae84566f63; ?>
<?php unset($__attributesOriginal44af18836315d1ca840cbdae84566f63); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal44af18836315d1ca840cbdae84566f63)): ?>
<?php $component = $__componentOriginal44af18836315d1ca840cbdae84566f63; ?>
<?php unset($__componentOriginal44af18836315d1ca840cbdae84566f63); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1bac653003a70249c8d1bced240ca490)): ?>
<?php $attributes = $__attributesOriginal1bac653003a70249c8d1bced240ca490; ?>
<?php unset($__attributesOriginal1bac653003a70249c8d1bced240ca490); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1bac653003a70249c8d1bced240ca490)): ?>
<?php $component = $__componentOriginal1bac653003a70249c8d1bced240ca490; ?>
<?php unset($__componentOriginal1bac653003a70249c8d1bced240ca490); ?>
<?php endif; ?>
<?php /**PATH /home/usp/Laravel_Projects/usp_official/vendor/livewire/flux/src/../stubs/resources/views/flux/button/index.blade.php ENDPATH**/ ?>