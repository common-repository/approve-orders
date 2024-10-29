<?php
namespace ApproveOrders\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class ApprovedOrderNotification
 *
 * @package ApproveOrders\Modules
 * @since 1.0.0
 */
class ApprovedOrderNotification extends \WC_Email {

	public function __construct() {
		$this->id             = 'approved_order_notification';
		$this->title          = __( 'Approved Order Notification', 'approve-orders' );
		$this->description    = __( 'This email is sent when an order status changes to approved', 'approve-orders' );
		$this->template_html  = 'emails/approved-order-notification.php';
		$this->template_plain = 'emails/plain/approved-order-notification.php';
		$this->template_base  = AOFWC_DIR . 'templates/';
		$this->customer_email = true;
		$this->subject        = __( 'Your Order #{order_number} has been confirmed', 'approve-orders' );
		$this->heading        = __( 'Order #{order_number} confirmed', 'approve-orders' );

		// Call parent constructor
		parent::__construct();
	}

	public function trigger( $order_id, $order = false ) {

		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {

			$this->object = $order;

			$this->find[]    = '{order_date}';
			$this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->get_date_created() ) );

			$this->find[]    = '{order_number}';
			$this->replace[] = $this->object->get_order_number();

			$this->recipient = $this->object->get_billing_email();
			$this->send( $this->recipient, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}
	}

	public function get_content_html() {
		ob_start();
		wc_get_template(
			$this->template_html,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'email'              => $this,
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
			),
			$this->template_base,
			$this->template_base
		);
		return ob_get_clean();
	}

	public function get_content_plain() {
		ob_start();
		wc_get_template(
			$this->template_plain,
			array(
				'order'         => $this->object,
				'email_heading' => $this->get_heading(),
				'email'         => $this,
				'sent_to_admin' => false,
				'plain_text'    => true,
			),
			$this->template_base,
			$this->template_base
		);
		return ob_get_clean();
	}

	public function init_form_fields() {
		$this->form_fields = apply_filters(
			'wc_offline_form_fields',
			array(
				'enabled'    => array(
					'title'   => __( 'Enable/Disable', 'approve-orders' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'approve-orders' ),
					'default' => 'yes',
				),
				'subject'    => array(
					'title'       => __( 'Subject', 'approve-orders' ),
					'type'        => 'text',
					'description' => __( 'This controls the email subject line. Leave blank to use the default subject: "{site_title} - Your Order #{order_number} has been confirmed".', 'approve-orders' ),
					'placeholder' => __( 'Your Order #{order_number} has been confirmed', 'approve-orders' ),
					'default'     => __( 'Your Order #{order_number} has been confirmed', 'approve-orders' ),
				),
				'heading'    => array(
					'title'       => __( 'Email Heading', 'approve-orders' ),
					'type'        => 'text',
					'description' => __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: "Order #{order_number} confirmed".', 'approve-orders' ),
					'placeholder' => __( 'Order #{order_number} confirmed', 'approve-orders' ),
					'default'     => __( 'Order #{order_number} confirmed', 'approve-orders' ),
				),
				'email_type' => array(
					'title'       => __( 'Email type', 'approve-orders' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'approve-orders' ),
					'default'     => 'html',
					'class'       => 'wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
				),
			)
		);
	}
}

