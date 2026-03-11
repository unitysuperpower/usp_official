<?php
/**
 * @see https://github.com/artesaos/seotools
 */

return [
    'inertia' => env('SEO_TOOLS_INERTIA', false),
    'meta' => [
        /*
         * The default configurations to be used by the meta generator.
         */
        'defaults'       => [
            'title'        => env('APP_NAME', 'TechSolution'), // set false to total remove
            'titleBefore'  => false, // Put defaults.title before page title, like 'It's Over 9000! - Dashboard'
            'description'  => 'Professional service provider delivering innovative solutions for your business needs. Quality services with expert consultation.', // set false to total remove
            'separator'    => ' | ',
            'keywords'     => ['services', 'business solutions', 'professional services', 'consultation', 'TechSolution'],
            'canonical'    => 'current', // Set to null or 'full' to use Url::full(), set to 'current' to use Url::current(), set false to total remove
            'robots'       => 'index, follow', // Set to 'all', 'none' or any combination of index/noindex and follow/nofollow
        ],
        /*
         * Webmaster tags are always added.
         */
        'webmaster_tags' => [
            'google'    => null,
            'bing'      => null,
            'alexa'     => null,
            'pinterest' => null,
            'yandex'    => null,
            'norton'    => null,
        ],

        'add_notranslate_class' => false,
    ],
    'opengraph' => [
        /*
         * The default configurations to be used by the opengraph generator.
         */
        'defaults' => [
            'title'       => env('APP_NAME', 'USP Services'), // set false to total remove
            'description' => 'Professional service provider delivering innovative solutions for your business needs. Quality services with expert consultation.', // set false to total remove
            'url'         => null, // Set null for using Url::current(), set false to total remove
            'type'        => 'website',
            'site_name'   => env('APP_NAME', 'TechSolution'),
            'images'      => [],
        ],
    ],
    'twitter' => [
        /*
         * The default values to be used by the twitter cards generator.
         */
        'defaults' => [
            'card'        => 'summary_large_image',
            'site'        => '@TechSolution',
        ],
    ],
    'json-ld' => [
        /*
         * The default configurations to be used by the json-ld generator.
         */
        'defaults' => [
            'title'       => env('APP_NAME', 'USP Services'), // set false to total remove
            'description' => 'Professional service provider delivering innovative solutions for your business needs. Quality services with expert consultation.', // set false to total remove
            'url'         => 'current', // Set to null or 'full' to use Url::full(), set to 'current' to use Url::current(), set false to total remove
            'type'        => 'WebPage',
            'images'      => [],
        ],
    ],
];
