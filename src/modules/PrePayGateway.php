<?php
namespace ApproveOrders\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class PrePayGateway
 *
 * @package ApproveOrders\Modules
 * @since 1.0.0
 */
class PrePayGateway extends \WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {

		$this->id                 = 'preordergateway-approve-orders';
		$this->icon               = apply_filters( 'woocommerce_pre_order_icon', '' );
		$this->has_fields         = false;
		$this->method_title       = __( 'Pre Order', 'approve-orders' );
		$this->method_description = __( 'Allows customers to place orders without making a payment.', 'approve-orders' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user settings variables.
		$this->title        = $this->get_option( 'preorder-title' );
		$this->description  = $this->get_option( 'preorder-description' );
		$this->instructions = $this->get_option( 'preorder-instructions' );
		$this->enabled      = $this->get_option( 'preorder-enabled' );

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

		// Customer Emails
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		// Add Prepay Gateway to
	}

	/**
	 * Initialize gateway settings form fields
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'preorder-enabled'      => array(
				'title'   => __( 'Enable/Disable', 'approve-orders' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Pre Order Payment', 'approve-orders' ),
				'default' => 'yes',
			),
			'preorder-title'        => array(
				'title'       => __( 'Title', 'approve-orders' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'approve-orders' ),
				'default'     => __( 'Pre Order', 'approve-orders' ),
				'desc_tip'    => true,
			),
			'preorder-description'  => array(
				'title'       => __( 'Description', 'approve-orders' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'approve-orders' ),
				'default'     => __( 'Place an order without making a payment. Payment will be processed later.', 'approve-orders' ),
			),
			'preorder-instructions' => array(
				'title'       => __( 'Instructions', 'approve-orders' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'approve-orders' ),
				'default'     => __( 'Thank you! Your order is pending approval. You will be notified once reviewed.', 'approve-orders' ),
			),
		);
	}

	/**
	 * Thank you oage function fo PrePay Gateway
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}
	}

	/**
	 * Email instructions for PrePay Gateway
	 *
	 * @param WC_Order $order
	 * @param bool $sent_to_admin
	 * @param bool $plain_text
	 *
	 * @since 1.0.0
	 *
	 * @return void	 
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && 'preorder-approve-orders' === $order->get_payment_method() ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) ) . PHP_EOL;
		}
	}

	/**
	 * Process Payment for PrePay Gateway
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Mark as Awaiting Approval (we're awaiting the payment)
		$order->update_status( 'awaiting-approval', esc_html__( 'Awaiting pre order payment', 'approve-orders' ) );

		// Reduce stock levels
		wc_reduce_stock_levels( $order_id );

		// Remove cart
		WC()->cart->empty_cart();

		// Return thank-you page redirect
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}
}
