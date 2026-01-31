<?php
namespace FenanPay\FenanPay\WC;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WC_Payment_Gateway;

/**
 * FenanPay WooCommerce Gateway
 * - Uses API Key to call FenanPay Payment Intent API
 * - Redirects to checkout URL on success
 */
class WC_FenanPay_Gateway extends WC_Payment_Gateway {

    protected $testmode;
    protected $api_key;
    protected $webhook_secret;
    protected $currency;
    protected $custom_return_url;
    protected $custom_webhook_url;
    protected $notify_url;

    public function __construct() {
        $this->id                 = 'fenanpay';
        $this->icon               = apply_filters( 'fenanpay_icon', plugins_url( 'src/assets/fenanpay1.png', __FILE__ ) );
        $this->has_fields         = false;
        $this->method_title       = __( 'FenanPay', 'fenanpay' );
        $this->method_description = __( 'Pay using FenanPay (external payment flow).', 'fenanpay' );

        // Form fields and settings
        $this->init_form_fields();
        $this->init_settings();

        // Map settings to properties
        $this->title              = $this->get_option( 'title', 'FenanPay' );
        $this->description        = $this->get_option( 'description', '' );
        $this->testmode           = 'yes' === $this->get_option( 'testmode' );
        $this->api_key            = $this->get_option( 'api_key' );
        $this->webhook_secret     = $this->get_option( 'webhook_secret' );
        $this->currency           = $this->get_option( 'currency', 'ETB' );
        $this->custom_return_url  = $this->get_option( 'custom_return_url' );
        $this->custom_webhook_url = $this->get_option( 'custom_webhook_url' );

        // notify URL (webhook) - use custom or default
        $this->notify_url = ! empty( $this->custom_webhook_url ) ? $this->custom_webhook_url : home_url( '/?wc-api=wc_fenanpay' );

        // Hooks
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

        // Add API endpoint hook for wc-api=wc_fenanpay
        add_action( 'woocommerce_api_wc_fenanpay', array( $this, 'handle_webhook' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'fenanpay' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable FenanPay', 'fenanpay' ),
                'default' => 'no',
            ),
            'title' => array(
                'title'       => __( 'Title', 'fenanpay' ),
                'type'        => 'text',
                'description' => __( 'Title shown to customer during checkout.', 'fenanpay' ),
                'default'     => __( 'FenanPay', 'fenanpay' ),
            ),
            'description' => array(
                'title' => __( 'Description', 'fenanpay' ),
                'type'  => 'textarea',
                'default' => __( 'Pay with FenanPay.', 'fenanpay' ),
            ),
            'testmode' => array(
                'title'       => __( 'Test Mode', 'fenanpay' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable Test Mode (Sandbox)', 'fenanpay' ),
                'default'     => 'yes',
                'description' => __( 'If enabled, transactions will be sent to the sandbox endpoint.', 'fenanpay' ),
            ),
            'api_key' => array(
                'title'       => __( 'API Key', 'fenanpay' ),
                'type'        => 'password',
                'description' => __( 'Your FenanPay API Key.', 'fenanpay' ),
            ),
            'webhook_secret' => array(
                'title'       => __( 'Webhook Secret (optional)', 'fenanpay' ),
                'type'        => 'password',
                'description' => __( 'Used to verify webhook signatures if provided.', 'fenanpay' ),
            ),
            'currency' => array(
                'title'       => __( 'Currency', 'fenanpay' ),
                'type'        => 'select',
                'description' => __( 'Currency for FenanPay transactions.', 'fenanpay' ),
                'default'     => 'ETB',
                'options'     => array(
                    'ETB' => __( 'Ethiopian Birr (ETB)', 'fenanpay' ),
                    'USD' => __( 'US Dollar (USD)', 'fenanpay' ),
                ),
            ),
            'custom_return_url' => array(
                'title'       => __( 'Return URL (optional)', 'fenanpay' ),
                'type'        => 'url',
                'description' => __( 'URL where customers return after payment. Leave empty for default WooCommerce thank you page.', 'fenanpay' ),
                'placeholder' => home_url( '/checkout/order-received/' ),
            ),
            'custom_webhook_url' => array(
                'title'       => __( 'Webhook URL (optional)', 'fenanpay' ),
                'type'        => 'url',
                'description' => __( 'Custom webhook endpoint URL. Leave empty to use default.', 'fenanpay' ),
                'placeholder' => home_url( '/?wc-api=wc_fenanpay' ),
            ),
            'webhook_info' => array(
                'title'       => __( 'Current Webhook Endpoint', 'fenanpay' ),
                'type'        => 'title',
                'description' => sprintf( __( 'Current webhook endpoint: <code>%s</code><br>Set this URL in your FenanPay dashboard.', 'fenanpay' ), $this->notify_url ),
            ),
        );
    }

    /**
     * Process the payment: call FenanPay Intent endpoint and redirect customer to payment content URL.
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return array(
                'result' => 'failure',
            );
        }

        // Endpoint selection
        $endpoint = $this->testmode
            ? 'https://api.fenanpay.com/api/v1/payment/sandbox/intent'
            : 'https://api.fenanpay.com/api/v1/payment/intent';

        // Payment Intent Unique ID (must be unique per attempt)
        // Format: orderID_timestamp_random
        $unique_id = $order->get_order_number() . '_' . time();

        // Prepare Customer Info
        $customer_info = array(
            'name'  => substr( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(), 0, 100 ),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
        );

        // Build Payload
        $body = array(
            'amount'                   => (float) $order->get_total(),
            'currency'                 => $this->currency, // Use plugin currency setting
            'paymentIntentUniqueId'    => $unique_id,
            'methods'                  => array(), // Empty array = all enabled methods
            'returnUrl'                => ! empty( $this->custom_return_url ) ? $this->custom_return_url : $this->get_return_url( $order ),
            'callbackUrl'              => $this->notify_url,
            'expireIn'                 => 3600, // 1 hour expiration
            'commissionPaidByCustomer' => false,
            'items'                    => null, // explicitly null to rely on amount
            'customerInfo'             => $customer_info,
        );

        $response = wp_remote_post( $endpoint, array(
            'headers' => array(
                'apiKey'       => $this->api_key,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 45,
        ) );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            $order->add_order_note( 'FenanPay connection error: ' . $error_message );
            wc_add_notice( __( 'Connection error: ' . $error_message, 'fenanpay' ), 'error' );
            return array( 'result' => 'failure' );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body_raw = wp_remote_retrieve_body( $response );
        $data = json_decode( $body_raw, true );

        // Success is 200
        if ( $code === 200 && ! empty( $data['content'] ) ) {
            // Save the unique ID to order for later verification if needed
            $order->update_meta_data( '_fenanpay_unique_id', $unique_id );
            $order->save();

            // Mark as pending
            $order->update_status( 'pending', __( 'Payment intent created. Redirecting to FenanPay.', 'fenanpay' ) );

            // Clear cart
            WC()->cart->empty_cart();

            return array(
                'result'   => 'success',
                'redirect' => $data['content'],
            );
        } else {
            // Handle Error
            $msg = isset( $data['message'] ) ? $data['message'] : 'Unknown error (Status ' . $code . ')';
            $order->add_order_note( 'FenanPay API Error (' . $code . '): ' . $msg );
            wc_add_notice( __( 'Payment error: ' . $msg, 'fenanpay' ), 'error' );
            return array( 'result' => 'failure' );
        }
    }

    /**
     * Standard WooCommerce thank you page content
     */
    public function thankyou_page( $order_id ) {
        if ( $this->description ) {
            echo wpautop( wp_kses_post( $this->description ) );
        }
    }

    /**
     * Handle webhook from FenanPay
     */
    public function handle_webhook() {
        $payload = file_get_contents( 'php://input' );
        $signature_header = isset( $_SERVER['HTTP_X_FENANPAY_SIGNATURE'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_X_FENANPAY_SIGNATURE'] ) ) : '';

        // Signature Verification
        if ( ! empty( $this->webhook_secret ) ) {
            if ( empty( $signature_header ) ) {
                status_header( 400 );
                exit( 'Missing signature' );
            }
            $computed = hash_hmac( 'sha256', $payload, $this->webhook_secret );
            if ( ! hash_equals( $computed, $signature_header ) ) {
                status_header( 403 );
                exit( 'Invalid signature' );
            }
        }

        $data = json_decode( $payload, true );
        if ( ! is_array( $data ) ) {
            status_header( 400 );
            exit( 'Invalid payload' );
        }

        // FenanPay webhook fields (generic handling)
        $pg_unique_id = isset( $data['paymentIntentUniqueId'] ) ? $data['paymentIntentUniqueId'] : '';
        
        $order_id = 0;
        // Try to extract order ID from unique_id (Format: {orderid}_{timestamp})
        if ( $pg_unique_id && strpos( $pg_unique_id, '_' ) !== false ) {
            $parts = explode( '_', $pg_unique_id );
            $order_id = intval( $parts[0] );
        }

        if ( ! $order_id ) {
            status_header( 200 );
            exit( 'Order ID not recognized' );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            status_header( 200 );
            exit( 'Order not found' );
        }

        $status = isset( $data['status'] ) ? strtoupper( $data['status'] ) : '';

        if ( $status === 'SUCCESS' || $status === 'PAID' || $status === 'COMPLETED' ) {
            if ( ! $order->has_status( 'completed' ) && ! $order->has_status( 'processing' ) ) {
                $order->payment_complete();
                $order->add_order_note( 'FenanPay payment confirmed via webhook.' );
            }
        } elseif ( $status === 'FAILED' ) {
            $order->update_status( 'failed', 'FenanPay payment failed (webhook).' );
        } elseif ( $status === 'EXPIRED' ) {
            $order->update_status( 'cancelled', 'FenanPay payment session expired (webhook).' );
        }

        status_header( 200 );
        echo 'ok';
        exit;
    }

    public function handle_webhook_direct() {
        $this->handle_webhook();
    }
}
