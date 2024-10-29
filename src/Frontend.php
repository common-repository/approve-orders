<?php
namespace ApproveOrders;

use ApproveOrders\Settings as AOFWC_Settings;
use ApproveOrders\Modules\PrePayGateway as AOFWC_PreOrderPay;

/**
 * The front-end plugin class for Approve Orders.
 */
class Frontend {
	/**
	 * Initialize the front-end functionality of the plugin.
	 */
	public function init() {

		// Add the PreOrderPay gateway to the list of available payment gateways
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_pre_order_gateway' ) );
		// Show Allowed Payment Gayeways
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'allowed_gateways' ) );
		// Add WooCommerce Thank You hook
		add_action( 'woocommerce_thankyou', array( $this, 'woocommerce_thankyou' ), 10, 1 );

	}

	/**
	 * Add the PreOrderPay gateway to the list of available payment gateways.
	 *
	 * @param array $gateways The list of available payment gateways.
	 * @return array The list of available payment gateways with the PreOrderPay gateway added.
	 */
	public function add_pre_order_gateway( $gateways ) {
		$gateways[] = AOFWC_PreOrderPay::class;
		return $gateways;
	}

	/**
	 * Manage Allowed Payment Gateways.
	 *
	 * @param array $gateways The list of available payment gateways.
	 * @return array The list of available payment gateways with the PreOrderPay gateway added.
	 */
	public function allowed_gateways( $available_gateways ) {

		// If Approve Orders settings page then return
		if( isset( $_GET['page'] ) && 'approve-orders' === sanitize_text_field( $_GET['page'] ) ){ // phpcs:ignore
			return $available_gateways;
		}

		$settings          = AOFWC_Settings::get_instance();
		$options           = $settings->get();
		$selected_gateways = $options['selected_gateways'];
		$allowed_gateways  = array_intersect( $selected_gateways, array_keys( $available_gateways ) );

		// Manage Pre Order payment gateway on Checkout and Order Pay Page
		foreach ( $available_gateways as $gateway_id => $gateway ) {
			if ( is_checkout_pay_page() ) {
				if ( in_array( $gateway_id, $allowed_gateways, true ) ) {
					unset( $available_gateways[ $gateway_id ] );
				}
			} else {

				if ( 'enabled' === $options['approval_workflow'] && ! empty( $allowed_gateways ) && ! in_array( $gateway_id, $allowed_gateways, true ) ) {
					unset( $available_gateways[ $gateway_id ] );
				}
			}
		}

		return $available_gateways;
	}

	/**
	 * Add woocommerce_thankyou hook method
	 * @param string $order_id The list of available payment gateways.
	 */
	public function woocommerce_thankyou( $order_id ) {
		// Return early if the page is the Order Pay page or if no order ID is provided
		if ( is_checkout_pay_page() || ! $order_id ) {
			return;
		}
	
		$settings          = AOFWC_Settings::get_instance();
		$options           = $settings->get();
		$selected_gateways = $options['selected_gateways'];
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		$allowed_gateways  = array_intersect( $selected_gateways, array_keys( $available_gateways ) );
	
		// Get the order and current user
		$order = wc_get_order( $order_id );
		$current_user = wp_get_current_user();
	
		// Check if approval workflow is enabled
		if ( 'enabled' === $options['approval_workflow'] ) {
			$should_update = false;
	
			// Check payment method
			if ( ! empty( $allowed_gateways ) && in_array( $order->get_payment_method(), $allowed_gateways, true ) ) {
				$should_update = true;
			}
	
			// Check user role
			if ( array_intersect( $options['selected_roles'], $current_user->roles ) ) {
				$should_update = true;
			}
	
			// Update order status if conditions are met
			if ( $should_update ) {
				$order->update_status( 'awaiting-approval', __( 'Awaiting pre order payment', 'approve-orders' ) );
			}
		}
	}	

}
