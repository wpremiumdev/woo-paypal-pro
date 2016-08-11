<?php

/**
 * @class       Premiumdev_Woo_PayPal_Pro_PayPal_listner
 * @version	1.0.0
 * @package	Woo_PayPal_Pro
 * @category	Class
 * @author      @author     mbj-webdevelopment <mbjwebdevelopment@gmail.com>
 */
class Premiumdev_Woo_PayPal_Pro_PayPal_listner {

    public function __construct() {

        $this->liveurl = 'https://ipnpb.paypal.com/cgi-bin/webscr';
        $this->testurl = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
    }

    public function premiumdev_woo_paypal_pro_check_ipn_request() {
        @ob_clean();
        $ipn_response = !empty($_POST) ? $_POST : false;
        if ($ipn_response && $this->premiumdev_woo_paypal_pro_check_ipn_request_is_valid($ipn_response)) {
            header('HTTP/1.1 200 OK');
            return true;
        } else {
            return false;
        }
    }

    public function premiumdev_woo_paypal_pro_check_ipn_request_is_valid($ipn_response) {
        $is_sandbox = (isset($ipn_response['test_ipn'])) ? 'yes' : 'no';
        if ('yes' == $is_sandbox) {
            $paypal_adr = $this->testurl;
        } else {
            $paypal_adr = $this->liveurl;
        }
        $validate_ipn = array('cmd' => '_notify-validate');
        $validate_ipn += stripslashes_deep($ipn_response);
        $params = array(
            'body' => $validate_ipn,
            'sslverify' => false,
            'timeout' => 60,
            'httpversion' => '1.1',
            'compress' => false,
            'decompress' => false,
            'user-agent' => 'pal-pro/'
        );
        $response = wp_remote_post($paypal_adr, $params);
        if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && strstr($response['body'], 'VERIFIED')) {
            return true;
        }
        return false;
    }

    public function premiumdev_woo_paypal_pro_successful_request($IPN_status) {
        $ipn_response = !empty($_POST) ? $_POST : false;
        $ipn_response['IPN_status'] = ( $IPN_status == true ) ? 'Verified' : 'Invalid';
        $posted = stripslashes_deep($ipn_response);
        $this->premiumdev_woo_paypal_pro_third_party_API_request($posted);
        $this->premiumdev_woo_paypal_pro_ipn_response_data_handler($posted);
    }

    public function premiumdev_woo_paypal_pro_third_party_API_request($posted) {

        $settings = get_option('woocommerce_woo_paypal_pro_settings');
        if (isset($settings['premium_enable_ipn']) && 'no' == $settings['premium_enable_ipn']) {
            return;
        }
        if (isset($settings['premium_notifyurl']) && empty($settings['premium_notifyurl'])) {
            return;
        }
        $express_checkout_notifyurl = site_url('?Pal_Pro&action=ipn_handler');
        $third_party_notifyurl = str_replace('&amp;', '&', $settings['premium_notifyurl']);
        if (trim($express_checkout_notifyurl) == trim($third_party_notifyurl)) {
            return;
        }
        $params = array(
            'body' => $posted,
            'sslverify' => false,
            'timeout' => 60,
            'httpversion' => '1.1',
            'compress' => false,
            'decompress' => false,
            'user-agent' => 'pal-pro/'
        );
        wp_remote_post($third_party_notifyurl, $params);
        return;
    }

    public function premiumdev_woo_paypal_pro_ipn_response_data_handler($posted = null) {
        $log = new WC_Logger();
        $log->add('Woo_PayPal_Pro_Callback', print_r($posted, true));
        if (isset($posted) && !empty($posted)) {

            if (isset($posted['parent_txn_id']) && !empty($posted['parent_txn_id'])) {

                $PayPalRequestData = $this->premiumdev_woo_paypal_pro_get_API_for_paypal($posted['parent_txn_id']);
                if (!class_exists('Woo_Pal_Pro_lib')) {
                    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/lib/paypal.class.php';
                }

                $Pal_Pro_API = new Woo_Pal_Pro_lib($PayPalRequestData);

                // Prepare request arrays
                $GTDFields = array(
                    'transactionid' => $posted['parent_txn_id']       // PayPal transaction ID of the order you want to get details for.
                );
                $GTDPayPalRequestData = array('GTDFields' => $GTDFields);

                $PayPalResult = $Pal_Pro_API->GetTransactionDetails($GTDPayPalRequestData);
                $posted['payment_status'] = isset($PayPalResult['PAYMENTSTATUS']) ? $PayPalResult['PAYMENTSTATUS'] : '';

//                if (isset($posted['auth_id']) && !empty($posted['auth_id'])) {
//                    $paypal_txn_id = $posted['auth_id'];
//                } else {
                $paypal_txn_id = $posted['parent_txn_id'];
                // }
            } else if (isset($posted['txn_id']) && !empty($posted['txn_id'])) {
                $paypal_txn_id = $posted['txn_id'];
            } else {
                return false;
            }
            if ($this->premiumdev_woo_paypal_pro_exist_post_by_title($paypal_txn_id) != false) {
                $post_id = $this->premiumdev_woo_paypal_pro_exist_post_by_title($paypal_txn_id);
                $this->premiumdev_woo_paypal_pro_update_post_status($posted, $post_id);
            }
        }
    }

    public function premiumdev_woo_paypal_pro_get_API_for_paypal() {

        $settings = get_option('woocommerce_woo_paypal_pro_settings');
        $ID = "Woo_PayPal_Pro";
        $sandbox = ($settings['premium_testmode'] == 'yes') ? TRUE : FALSE;
        $apiusername = '';
        $apipassword = '';
        $apisignature = '';
        $apisubject = '';
        $apimethod = 'GetTransactionDetails';
        $buttonsource = 'mbjtechnolabs_SP';

        if ($sandbox) {
            $apiusername = ($settings['premium_sandbox_username']) ? $settings['premium_sandbox_username'] : '';
            $apipassword = ($settings['premium_sandbox_password']) ? $settings['premium_sandbox_password'] : '';
            $apisignature = ($settings['premium_sandbox_signature']) ? $settings['premium_sandbox_signature'] : '';
        } else {
            $apiusername = ($settings['premium_live_username']) ? $settings['premium_live_username'] : '';
            $apipassword = ($settings['premium_live_password']) ? $settings['premium_live_password'] : '';
            $apisignature = ($settings['premium_live_signature']) ? $settings['premium_live_signature'] : '';
        }

        return $PayPalRequestData = array('ID' => $ID, 'Sandbox' => $sandbox, 'APIUsername' => $apiusername, 'APIPassword' => $apipassword, 'APISignature' => $apisignature, 'APISubject' => $apisubject, 'APIMethod' => $apimethod, 'Buttonsource' => $buttonsource);
    }

    public function premiumdev_woo_paypal_pro_exist_post_by_title($ipn_txn_id) {
        global $wpdb;
        $post_data = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->postmeta}, {$wpdb->posts} WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_value = %s AND $wpdb->postmeta.meta_key = '_transaction_id' AND $wpdb->posts.post_type = 'shop_order' ", $ipn_txn_id));
        if (empty($post_data)) {
            return false;
        } else {
            return $post_data;
        }
    }

    public function premiumdev_woo_paypal_pro_update_post_status($posted, $order_id) {
        $order = new WC_Order($order_id);
        $payment_status = ($posted['payment_status']) ? strtolower($posted['payment_status']) : 'on-hold';

        if ('completed' === strtolower($posted['payment_status'])) {
            $order->payment_complete((!empty($posted['txn_id'])) ? wc_clean($posted['txn_id']) : '' );
            if (!empty($posted['mc_fee'])) {
                update_post_meta($order->id, 'PayPal Transaction Fee', wc_clean($posted['mc_fee']));
            }
        } else {

            $status = $this->premiumdev_woo_paypal_pro_get_status($payment_status);
            $pending_reason = isset($posted['pending_reason']) ? $posted['pending_reason'] : '';
            $order->update_status($status, $pending_reason);
            $order->reduce_order_stock();
            WC()->cart->empty_cart();
        }
    }

    public function premiumdev_woo_paypal_pro_get_status($payment_status) {
        if ('partiallyrefunded' === $payment_status) {
            $payment_status = 'refunded';
        }
        return $payment_status;
    }

}