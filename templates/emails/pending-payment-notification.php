<?php
/**
 * Order Pending email to Customer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
	<p>
		<?php
			/* translators: %d: order number*/
			printf( esc_html__( 'Your order #%d has been approved.', 'approve-orders' ), esc_attr( $order->get_order_number() ) );
		?>
	</p>
<?php
 /**
 * adding payment link options
 * @since 2.0.4
 */
$pay_now_url = $order->get_checkout_payment_url();

?>
<h2 class="email-upsell-title"><?php printf( esc_html__( 'Payment Instructions', 'approve-orders' ) ); ?> </h2>
<p class="email-upsell-p"><?php printf( esc_html__( 'To complete your order, please click the following link to proceed with payment ', 'approve-orders' ) ); ?>
<a target="_blank" href='<?php echo esc_url( $pay_now_url ); ?>'> <?php printf( esc_html__( 'Pay now', 'approve-orders' ) ); ?></a></p>
<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo '<p>' . esc_html__( 'Order Details:', 'approve-orders' ) . '</p>';
}


/**
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Emails::order_schema_markup() Adds Schema.org markup.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
