<?php
/**
 * Arithmetic evaluator for calculation fields. Supports numbers, + - * /, parentheses and unary minus.
 * Field references ([field_key] or {field_key}) are replaced by submitted numbers before evaluation.
 * Nothing is executed as code.
 *
 * @package TechSolution_Form_Studio_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TSFS_Pro_Calculator {

    /**
     * Field keys referenced by a formula.
     */
    public static function references( $formula ) {
        preg_match_all( '/\[([a-z0-9_]+)\]|\{([a-z0-9_]+)\}/i', (string) $formula, $matches );
        return array_values( array_unique( array_filter( array_merge( $matches[1], $matches[2] ) ) ) );
    }

    /**
     * Evaluate a formula. $values maps field keys to numbers. Returns a float, or null when the formula is invalid.
     */
    public static function evaluate( $formula, $values ) {
        $expression = preg_replace_callback(
            '/\[([a-z0-9_]+)\]|\{([a-z0-9_]+)\}/i',
            function ( $m ) use ( $values ) {
                $key = strtolower( '' !== $m[1] ? $m[1] : $m[2] );
                $value = isset( $values[ $key ] ) ? (float) $values[ $key ] : 0.0;
                return '(' . $value . ')';
            },
            (string) $formula
        );

        $tokens = self::tokenize( $expression );
        if ( null === $tokens ) {
            return null;
        }
        $position = 0;
        $result   = self::parse_expression( $tokens, $position );
        if ( null === $result || $position !== count( $tokens ) || ! is_finite( $result ) ) {
            return null;
        }
        return $result;
    }

    private static function tokenize( $expression ) {
        if ( strlen( $expression ) > 2000 ) {
            return null;
        }
        preg_match_all( '/\s*(?:(\d+(?:\.\d+)?(?:[eE][-+]?\d+)?)|([-+*\/()]))\s*/A', $expression, $matches, PREG_SET_ORDER );
        $consumed = 0;
        $tokens   = array();
        foreach ( $matches as $match ) {
            $consumed += strlen( $match[0] );
            $tokens[]  = '' !== $match[1] ? (float) $match[1] : $match[2];
        }
        return $consumed === strlen( $expression ) ? $tokens : null;
    }

    // expression := term (('+' | '-') term)*
    private static function parse_expression( $tokens, &$pos ) {
        $value = self::parse_term( $tokens, $pos );
        while ( null !== $value && isset( $tokens[ $pos ] ) && ( '+' === $tokens[ $pos ] || '-' === $tokens[ $pos ] ) ) {
            $op    = $tokens[ $pos++ ];
            $right = self::parse_term( $tokens, $pos );
            if ( null === $right ) {
                return null;
            }
            $value = '+' === $op ? $value + $right : $value - $right;
        }
        return $value;
    }

    // term := factor (('*' | '/') factor)*
    private static function parse_term( $tokens, &$pos ) {
        $value = self::parse_factor( $tokens, $pos );
        while ( null !== $value && isset( $tokens[ $pos ] ) && ( '*' === $tokens[ $pos ] || '/' === $tokens[ $pos ] ) ) {
            $op    = $tokens[ $pos++ ];
            $right = self::parse_factor( $tokens, $pos );
            if ( null === $right ) {
                return null;
            }
            if ( '/' === $op ) {
                $value = 0.0 == $right ? 0.0 : $value / $right;
            } else {
                $value = $value * $right;
            }
        }
        return $value;
    }

    // factor := number | '(' expression ')' | ('-' | '+') factor
    private static function parse_factor( $tokens, &$pos ) {
        if ( ! isset( $tokens[ $pos ] ) ) {
            return null;
        }
        $token = $tokens[ $pos ];
        if ( is_float( $token ) ) {
            $pos++;
            return $token;
        }
        if ( '-' === $token || '+' === $token ) {
            $pos++;
            $value = self::parse_factor( $tokens, $pos );
            return null === $value ? null : ( '-' === $token ? -$value : $value );
        }
        if ( '(' === $token ) {
            $pos++;
            $value = self::parse_expression( $tokens, $pos );
            if ( null === $value || ! isset( $tokens[ $pos ] ) || ')' !== $tokens[ $pos ] ) {
                return null;
            }
            $pos++;
            return $value;
        }
        return null;
    }
}
