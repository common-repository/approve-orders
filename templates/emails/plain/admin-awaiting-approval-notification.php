<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '= ' . esc_attr( $email_heading ) . " =\n\n";

/* translators: %s: order id */
printf( esc_html__( 'You have received a new order #%d that is pending approval.', 'approve-orders' ), esc_attr( $order->get_order_number() ) );

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Email_Customer_Details::order_downloads() Shows download links for digital items.
 * @hooked WC_Email_Customer_Details::order_details() Shows customer details.
 * @hooked WC_Email_Customer_Details::order_addresses() Shows customer addresses.
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Email_Customer_Details::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Email_Customer_Details::customer_details() Shows customer details.
 * @hooked WC_Email_Customer_Details::email_addresses() Shows email addresses.
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo wp_kses_post( wpautop( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) );
