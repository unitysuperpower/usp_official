<?php

// Defaults for Admin → Settings → Appearance. Saved values override these key by key.
return [
    'colors' => [
        'light' => [
            'bg' => '#fafbf8', 'surface' => '#ffffff', 'ink' => '#183332', 'muted' => '#65726d', 'line' => '#dde4dd',
            'soft' => '#eef2eb', 'accent' => '#226c57', 'lime' => '#d3eea3', 'button-bg' => '#183332', 'button-ink' => '#ffffff',
        ],
        'dark' => [
            'bg' => '#112320', 'surface' => '#192e29', 'ink' => '#e3ede3', 'muted' => '#a0b3a9', 'line' => '#33473e',
            'soft' => '#20382f', 'accent' => '#a9d29c', 'lime' => '#d3eea3', 'button-bg' => '#cce8b5', 'button-ink' => '#17372a',
        ],
    ],
    'fonts' => ['body' => 'DM Sans', 'heading' => 'Manrope', 'accent' => 'Georgia'],
    'radius' => 1.0, // multiplier for corner rounding: 0 = square, 1 = default, 2 = very round
    'mode' => 'light', // light, dark or system
    'allow_toggle' => true, // visitors may switch light/dark in their account settings
    'brand' => [
        'name' => 'USP', 'subtitle' => 'TECH SOLUTION', 'mark' => 'u',
        'logo' => null, 'logo_dark' => null, 'favicon' => null,
    ],
    'footer' => [
        'tagline' => "Thoughtfully designed.\nPurposefully built.",
        'statement' => 'Your next big idea.',
        'statement_emphasis' => 'Let’s make it happen.',
        'copyright' => 'USP Tech Solution',
        'motto' => 'Ideas. Craft. Impact.',
    ],

    // Fonts served from fonts.bunny.net (GDPR-friendly Google Fonts mirror); system fonts need no download.
    'font_choices' => [
        'DM Sans' => 'sans-serif', 'Manrope' => 'sans-serif', 'Inter' => 'sans-serif', 'Poppins' => 'sans-serif',
        'Roboto' => 'sans-serif', 'Open Sans' => 'sans-serif', 'Lato' => 'sans-serif', 'Montserrat' => 'sans-serif',
        'Nunito' => 'sans-serif', 'Raleway' => 'sans-serif', 'Work Sans' => 'sans-serif', 'Plus Jakarta Sans' => 'sans-serif',
        'Outfit' => 'sans-serif', 'Space Grotesk' => 'sans-serif', 'Sora' => 'sans-serif', 'Urbanist' => 'sans-serif',
        'Playfair Display' => 'serif', 'Merriweather' => 'serif', 'Lora' => 'serif', 'Fraunces' => 'serif', 'DM Serif Display' => 'serif',
        'Georgia' => 'serif', 'System UI' => 'sans-serif',
    ],
    'system_fonts' => ['Georgia' => 'Georgia', 'System UI' => 'system-ui, -apple-system, "Segoe UI"'],

    // One-click starting points in the editor (light-mode colors; dark mode is kept unless the preset has one).
    'presets' => [
        'Forest' => ['accent' => '#226c57', 'ink' => '#183332', 'lime' => '#d3eea3', 'bg' => '#fafbf8', 'soft' => '#eef2eb', 'line' => '#dde4dd', 'muted' => '#65726d', 'surface' => '#ffffff', 'button-bg' => '#183332', 'button-ink' => '#ffffff'],
        'Ocean' => ['accent' => '#1d5fa8', 'ink' => '#14263d', 'lime' => '#bfe3ff', 'bg' => '#f7f9fc', 'soft' => '#eaf1f9', 'line' => '#d9e2ee', 'muted' => '#5f6f82', 'surface' => '#ffffff', 'button-bg' => '#14263d', 'button-ink' => '#ffffff'],
        'Royal' => ['accent' => '#5b3fc4', 'ink' => '#1f1838', 'lime' => '#e4dbff', 'bg' => '#faf9fd', 'soft' => '#f0ecfa', 'line' => '#e1dcef', 'muted' => '#6b6580', 'surface' => '#ffffff', 'button-bg' => '#1f1838', 'button-ink' => '#ffffff'],
        'Sunset' => ['accent' => '#c2501f', 'ink' => '#33201a', 'lime' => '#ffd9b8', 'bg' => '#fdfaf7', 'soft' => '#f8eee6', 'line' => '#eddfd4', 'muted' => '#7a675d', 'surface' => '#ffffff', 'button-bg' => '#33201a', 'button-ink' => '#ffffff'],
        'Slate' => ['accent' => '#334155', 'ink' => '#0f172a', 'lime' => '#e2e8f0', 'bg' => '#f8fafc', 'soft' => '#f1f5f9', 'line' => '#e2e8f0', 'muted' => '#64748b', 'surface' => '#ffffff', 'button-bg' => '#0f172a', 'button-ink' => '#ffffff'],
    ],
];
