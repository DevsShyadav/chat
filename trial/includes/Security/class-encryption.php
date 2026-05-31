<?php
/**
 * Encryption - Handles API key encryption at rest.
 *
 * @package WPAICB\Security
 */

namespace WPAICB\Security;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Encryption {

    /**
     * Encryption method.
     *
     * @var string
     */
    private $method = 'aes-256-cbc';

    /**
     * Get the encryption key.
     *
     * @return string
     */
    private function get_key() {
        // Use WordPress auth key as encryption key
        if ( defined( 'AUTH_KEY' ) && AUTH_KEY ) {
            return hash( 'sha256', AUTH_KEY );
        }

        // Fallback to a generated key stored in options
        $key = get_option( 'wpaicb_encryption_key' );
        if ( ! $key ) {
            $key = wp_generate_password( 64, true, true );
            update_option( 'wpaicb_encryption_key', $key );
        }

        return hash( 'sha256', $key );
    }

    /**
     * Encrypt a value.
     *
     * @param string $value The value to encrypt.
     * @return string Encrypted value (base64 encoded).
     */
    public function encrypt( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        $key       = $this->get_key();
        $iv_length = openssl_cipher_iv_length( $this->method );
        $iv        = openssl_random_pseudo_bytes( $iv_length );

        $encrypted = openssl_encrypt( $value, $this->method, $key, 0, $iv );

        if ( false === $encrypted ) {
            return $value; // Return original if encryption fails
        }

        // Prepend IV as fixed-length prefix (no separator needed since IV length is constant)
        return base64_encode( $iv . $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
    }

    /**
     * Decrypt a value.
     *
     * @param string $value The encrypted value (base64 encoded).
     * @return string Decrypted value.
     */
    public function decrypt( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        $key  = $this->get_key();
        $data = base64_decode( $value, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

        if ( false === $data ) {
            return $value; // Return as-is if not valid base64
        }

        $iv_length = openssl_cipher_iv_length( $this->method );

        if ( strlen( $data ) <= $iv_length ) {
            return $value; // Return as-is if too short to contain IV + data
        }

        $iv        = substr( $data, 0, $iv_length );
        $encrypted = substr( $data, $iv_length );

        $decrypted = openssl_decrypt( $encrypted, $this->method, $key, 0, $iv );

        if ( false === $decrypted ) {
            return $value; // Return as-is if decryption fails
        }

        return $decrypted;
    }

    /**
     * Check if a value appears to be encrypted.
     *
     * @param string $value Value to check.
     * @return bool
     */
    public function is_encrypted( $value ) {
        if ( empty( $value ) ) {
            return false;
        }

        $decoded = base64_decode( $value, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

        if ( false === $decoded ) {
            return false;
        }

        $iv_length = openssl_cipher_iv_length( $this->method );

        return strlen( $decoded ) > $iv_length;
    }
}
