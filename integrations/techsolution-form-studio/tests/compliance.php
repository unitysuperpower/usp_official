<?php
// Run with: php tests/compliance.php. Static checks for WordPress.org directory requirements.
$root   = dirname( __DIR__ );
$errors = array();

$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $files as $file ) {
    $path = $file->getPathname();
    $rel  = substr( $path, strlen( $root ) + 1 );
    if ( 0 === strpos( $rel, 'tests/' ) || ! preg_match( '/\.(php|js|css)$/', $rel ) ) {
        continue;
    }
    $code = file_get_contents( $path );

    // Guideline 5: no license checks, locked features or remote installers in the directory plugin.
    foreach ( array( 'license_key', 'is_pro_active', 'license_valid', 'tsfs_install_pro', 'Plugin_Upgrader', 'pro_enabled', 'tsfs-pro-locked', 'api/licenses' ) as $needle ) {
        if ( false !== stripos( $code, $needle ) ) {
            $errors[] = "$rel contains \"$needle\" (locked or licensed functionality).";
        }
    }
    // No arbitrary CSS/JS from users.
    if ( false !== strpos( $code, 'custom_css' ) ) {
        $errors[] = "$rel stores or prints custom CSS.";
    }
    // Translations load automatically for directory plugins.
    if ( false !== strpos( $code, 'load_plugin_textdomain' ) ) {
        $errors[] = "$rel calls load_plugin_textdomain().";
    }

    if ( '.php' === substr( $rel, -4 ) ) {
        // Scripts and styles must be enqueued (SpreadsheetML <Style> elements in the Excel export are XML, not CSS).
        if ( preg_match( '/<script\b|<style\b/', $code ) ) {
            $errors[] = "$rel prints a <script> or <style> tag; enqueue it instead.";
        }
        if ( preg_match( '/\son(click|change|input|submit|load)="/i', $code ) ) {
            $errors[] = "$rel uses an inline event handler.";
        }
        if ( 'uninstall.php' !== $rel && ! preg_match( "/defined\(\s*'ABSPATH'\s*\)/", $code ) ) {
            $errors[] = "$rel can be loaded directly (missing ABSPATH check).";
        }
        // Text domain must match the slug.
        if ( preg_match_all( "/(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e|_n|_x)\(\s*'(?:[^'\\\\]|\\\\.)*'\s*,\s*'([^']+)'/", $code, $m ) ) {
            foreach ( array_unique( $m[1] ) as $domain ) {
                if ( 'techsolution-form-studio' !== $domain ) {
                    $errors[] = "$rel uses text domain \"$domain\".";
                }
            }
        }
        // Global declarations must be prefixed.
        if ( preg_match_all( '/^\s*(?:final\s+)?(class|function)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $code, $m, PREG_SET_ORDER ) ) {
            foreach ( $m as $decl ) {
                if ( 'class' === $decl[1] && 0 !== strpos( $decl[2], 'TSFS_' ) ) {
                    $errors[] = "$rel declares unprefixed class {$decl[2]}.";
                }
            }
        }
        if ( preg_match_all( "/(?:update_option|add_option|set_transient|add_shortcode|wp_schedule_event|register_block_type|wp_register_script|wp_enqueue_script|wp_register_style|wp_enqueue_style)\(\s*'([a-z0-9_\-]+)'/", $code, $m ) ) {
            foreach ( $m[1] as $name ) {
                if ( 0 !== strpos( $name, 'tsfs_' ) && ! in_array( $name, array( 'techsolution-form-studio' ), true ) && 0 !== strpos( $name, 'tsfs-' ) && 0 !== strpos( $name, 'techsolution-form-studio/' ) ) {
                    $errors[] = "$rel uses unprefixed option/hook \"$name\".";
                }
            }
        }
        if ( preg_match_all( "/add_action\(\s*'wp_ajax_(?:nopriv_)?([a-z0-9_]+)'/", $code, $m ) ) {
            foreach ( $m[1] as $name ) {
                if ( 0 !== strpos( $name, 'tsfs_' ) ) {
                    $errors[] = "$rel registers unprefixed AJAX action \"$name\".";
                }
            }
        }
    }
}

$main = file_get_contents( $root . '/techsolution-form-studio.php' );
foreach ( array( 'Plugin Name:       TechSolution Form Studio', 'Text Domain:       techsolution-form-studio', 'License:           GPL v2 or later' ) as $header ) {
    if ( false === strpos( $main, $header ) ) {
        $errors[] = "Main file header missing \"$header\".";
    }
}
if ( false !== stripos( $main, 'Update URI' ) ) {
    $errors[] = 'Directory plugins must not declare an Update URI.';
}

$readme = file_get_contents( $root . '/readme.txt' );
foreach ( array( '=== TechSolution Form Studio ===', 'Contributors: usptechsolution', '== External services ==', 'policies.google.com/privacy', 'cloudflare.com/privacypolicy' ) as $needle ) {
    if ( false === strpos( $readme, $needle ) ) {
        $errors[] = "readme.txt is missing \"$needle\".";
    }
}
if ( preg_match( '/Stable tag:\s*(\S+)/', $readme, $tag ) && preg_match( '/Version:\s*(\S+)/', $main, $version ) && $tag[1] !== $version[1] ) {
    $errors[] = "Stable tag {$tag[1]} does not match Version {$version[1]}.";
}

if ( $errors ) {
    fwrite( STDERR, "Compliance check failed:\n - " . implode( "\n - ", $errors ) . "\n" );
    exit( 1 );
}
echo "Passed directory compliance checks (no licensing, no custom code, enqueued assets, prefixes, readme).\n";
