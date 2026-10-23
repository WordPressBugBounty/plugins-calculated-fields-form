<?php
/*
Documentation: https://stripe.com/docs/api
WordPress-native HTTP client for the Stripe REST API, replacing the official SDK.

Every call returns the decoded response body (array) or a WP_Error, so callers use
is_wp_error() instead of catching exceptions.
*/

if ( ! class_exists( 'CPCFF_Stripe_HTTP' ) )
{
    class CPCFF_Stripe_HTTP
    {
        const API_BASE = 'https://api.stripe.com/v1';
        const TIMEOUT = 30; // Same default the SDK used.

        private $secret_key;

        public function __construct( string $secret_key )
        {
            $this->secret_key = trim( $secret_key );
        }

        /**
         * @param string      $path            Resource path, with or without leading slash.
         * @param array       $params          Parameters, encoded with Stripe's bracket notation.
         * @param string|null $idempotency_key Optional value for the Idempotency-Key header.
         * @return array|WP_Error
         */
        public function post( string $path, array $params = [], ?string $idempotency_key = null )
        {
            return $this->request( 'POST', $path, $params, $idempotency_key );
        }

        /**
         * @return array|WP_Error Decoded response body.
         */
        public function get( string $path, array $params = [] )
        {
            return $this->request( 'GET', $path, $params );
        }

        /**
         * Same as post(), named apart so call sites read as a sub-resource update.
         *
         * @return array|WP_Error
         */
        public function post_subresource( string $path, array $params = [] )
        {
            return $this->request( 'POST', $path, $params );
        }

        /**
         * @return array|WP_Error
         */
        private function request( string $method, string $path, array $params = [], ?string $idempotency_key = null )
        {
            if ( '' === $this->secret_key )
                return new WP_Error( 'stripe_missing_api_key', __( 'Stripe: no API key configured.', 'calculated-fields-form' ) );

            $url = self::API_BASE . '/' . self::encode_path( $path );
            $encoded = $this->encode_params( $params );

            $args = array(
                'method'  => $method,
                'timeout' => self::TIMEOUT,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->secret_key,
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ),
            );

            if ( 'GET' === $method )
            {
                if ( '' !== $encoded )
                    $url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . $encoded;
            }
            else
            {
                $args['body'] = $encoded;

                if ( ! empty( $idempotency_key ) )
                    $args['headers']['Idempotency-Key'] = $idempotency_key;
            }

            $response = wp_remote_request( $url, $args );

            if ( is_wp_error( $response ) )
                return $response;

            $status = (int) wp_remote_retrieve_response_code( $response );
            $body   = (string) wp_remote_retrieve_body( $response );
            $data   = json_decode( $body, true );

            if ( $status < 200 || 300 <= $status )
                return new WP_Error(
                    'stripe_api_error',
                    $this->error_message( $data, $body ),
                    array(
                        'status'   => $status,
                        'response' => is_array( $data ) ? $data : $body,
                    )
                );

            return is_array( $data ) ? $data : array();
        } // End request

        /**
         * Encodes each path segment so one can never escape the resource it belongs to.
         * The SDK did this per resource id; the previous behavior of concatenating the path
         * as given let a client-supplied id such as "../../charges/ch_x" resolve to a
         * different Stripe endpoint.
         *
         * @return string Encoded path, relative to the API base.
         */
        private static function encode_path( string $path ) : string
        {
            $segments = explode( '/', trim( $path, '/' ) );
            $encoded  = array();

            foreach ( $segments as $segment )
            {
                if ( '' === $segment )
                    continue;

                // RFC 3986 leaves '.' unreserved, so rawurlencode() would not stop a '..'
                // segment from traversing.
                if ( '.' === $segment || '..' === $segment )
                    continue;

                $encoded[] = str_replace( array( '%2F', '%2E' ), '', rawurlencode( $segment ) );
            }

            return implode( '/', $encoded );
        } // End encode_path

        private function error_message( $data, $body )
        {
            $message = '';

            if ( is_array( $data ) && isset( $data['error'] ) )
            {
                if ( is_array( $data['error'] ) && ! empty( $data['error']['message'] ) )
                    $message = $data['error']['message'];
                elseif ( is_string( $data['error'] ) && '' !== $data['error'] )
                    $message = $data['error'];
            }

            return '' !== $message ? 'Stripe: ' . $message : 'Stripe API error: ' . $body;
        } // End error_message

        /**
         * Stripe expects nested values in bracket notation with literal brackets.
         */
        private function encode_params( array $params )
        {
            $pairs = array();

            foreach ( $params as $key => $value )
                $pairs = array_merge( $pairs, $this->flatten_params( $value, (string) $key ) );

            return implode( '&', $pairs );
        } // End encode_params

        private function flatten_params( $value, $key )
        {
            if ( is_array( $value ) )
            {
                $pairs = array();
                foreach ( $value as $index => $item )
                    $pairs = array_merge( $pairs, $this->flatten_params( $item, $key . '[' . $index . ']' ) );
                return $pairs;
            }

            return array( $this->encode_component( $key ) . '=' . $this->encode_component( $value ) );
        } // End flatten_params

        private function encode_component( $value )
        {
            // Stripe expects "true"/"false"; PHP would cast a bool to "1", which Stripe
            // rejects with "Invalid boolean: 1". The SDK had this bug.
            if ( is_bool( $value ) )
                $value = $value ? 'true' : 'false';

            return str_replace( array( '%5B', '%5D' ), array( '[', ']' ), urlencode( (string) $value ) );
        } // End encode_component
    }
}