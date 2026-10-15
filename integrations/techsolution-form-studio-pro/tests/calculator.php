<?php
// Run with: php tests/calculator.php. Formulas are parsed as arithmetic only.
define( 'ABSPATH', '/' );
require __DIR__ . '/../includes/class-tsfs-pro-calculator.php';
$cases = array(
    array( '[qty] * 25', array( 'qty' => 3 ), 75 ),
    array( '([a] + {b}) / 2 - -1', array( 'a' => 4, 'b' => 6 ), 6 ),
    array( '-[a] * (2 + 3)', array( 'a' => 2 ), -10 ),
    array( '10 / [zero]', array( 'zero' => 0 ), 0 ),
    array( '[qty] * 25 + system()', array( 'qty' => 3 ), null ),
    array( '1 +', array(), null ),
    array( '(1 + 2', array(), null ),
    array( '2 ** 3', array(), null ),
);
$fail = 0;
foreach ( $cases as $c ) {
    $r = TSFS_Pro_Calculator::evaluate( $c[0], $c[1] );
    $ok = null === $c[2] ? null === $r : ( null !== $r && abs( $r - $c[2] ) < 1e-9 );
    echo ( $ok ? 'ok  ' : 'FAIL' ), ' ', $c[0], ' => ', var_export( $r, true ), "\n";
    $fail += $ok ? 0 : 1;
}
exit( $fail ? 1 : 0 );
