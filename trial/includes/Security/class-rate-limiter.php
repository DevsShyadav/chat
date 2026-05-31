<?php
/**
 * Rate Limiter - Prevents abuse by limiting message frequency.
 *
 * @package WPAICB\Security
 */

namespace WPAICB\Security;

use WPAICB\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rate_Limiter {

    /**
     * Check if a session is within rate limits.
     *
     * @param string $identifier Session ID or IP address.
     * @return bool True if within limits, false if rate limited.
     */
    public function check( $identifier ) {
        $max_requests = (int) Admin::get_settings( 'rate_limit', 20 );
        $window       = (int) Admin::get_settings( 'rate_limit_window', 60 ); // minutes

        $transient_key = 'wpaicb_rl_' . md5( $identifier );
        $data          = get_transient( $transient_key );

        if ( false === $data ) {
            // First request - initialize
            set_transient( $transient_key, array(
                'count'    => 1,
                'start'    => time(),
            ), $window * 60 );

            return true;
        }

        // Check if window has expired
        $elapsed = time() - $data['start'];
        if ( $elapsed > ( $window * 60 ) ) {
            // Reset window
            set_transient( $transient_key, array(
                'count'    => 1,
                'start'    => time(),
            ), $window * 60 );

            return true;
        }

        // Check count
        if ( $data['count'] >= $max_requests ) {
            return false;
        }

        // Increment counter
        $data['count']++;
        set_transient( $transient_key, $data, $window * 60 );

        return true;
    }

    /**
     * Get remaining requests for an identifier.
     *
     * @param string $identifier Session ID or IP.
     * @return int Remaining requests.
     */
    public function get_remaining( $identifier ) {
        $max_requests  = (int) Admin::get_settings( 'rate_limit', 20 );
        $transient_key = 'wpaicb_rl_' . md5( $identifier );
        $data          = get_transient( $transient_key );

        if ( false === $data ) {
            return $max_requests;
        }

        return max( 0, $max_requests - $data['count'] );
    }

    /**
     * Reset rate limit for an identifier.
     *
     * @param string $identifier Session ID or IP.
     */
    public function reset( $identifier ) {
        $transient_key = 'wpaicb_rl_' . md5( $identifier );
        delete_transient( $transient_key );
    }
}
