<?php
namespace ApproveOrders\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class AdminAwaitingApprovalNotification
 *
 * @package ApproveOrders\Modules
 * @since 1.0.0
 */
class AdminAwaitingApprovalNotification extends \WC_Email {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'admin_awaiting_approval';
		$this->title          = __( 'Order Awaiting Approval', 'approve-orders' );
		$this->description    = __( 'Notification sent to admin when an order status changes to Awaiting Approval.', 'approve-orders' );
		$this->heading        = __( 'Order Awaiting Approval', 'approve-orders' );
		$this->subject        = __( '[{site_title}] Order #{order_number} is awaiting approval', 'approve-orders' );
		$this->customer_email = false;
		$this->template_html  = 'emails/admin-awaiting-approval-notification.php';
		$this->template_plain = 'emails/plain/admin-awaiting-approval-notification.php.php';
		$this->template_base  = AOFWC_DIR . 'templates/';

		// Call parent constructor.
		parent::__construct();

		// Other settings.
		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param int            $order_id The order ID.
	 * @param WC_Order|false $order The order object.
	 */
	public function trigger( $order_id, $order = false ) {
		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object                         = $order;
			$this->placeholders['{order_number}'] = $order->get_order_number();
			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Get content HTML.
	 *
	 * @return string
	 */
	public function get_content_html() {
		ob_start();
		wc_get_template_html(
			$this->template_html,
			array(
				'order'         => $this->object,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => false,
				'email'         => $this,
			)
		);
		return ob_get_clean();

	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		ob_start();
		wc_get_template_html(
			$this->template_plain,
			array(
				'order'         => $this->object,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => true,
				'email'         => $this,
			)
		);
		return ob_get_clean();
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => __( 'Enable/Disable', 'approve-orders' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'approve-orders' ),
				'default' => 'yes',
			),
			'recipient'  => array(
				'title'       => __( 'Recipient(s)', 'approve-orders' ),
				'type'        => 'text',
				/* translators: %s: default admin email */ 
				'description' => sprintf( esc_html__( 'Enter recipient(s) (comma separated) for this email. Defaults to %s.', 'approve-orders' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
				'default'     => '',
				'placeholder' => '',
			),
			'subject'    => array(
				'title'       => __( 'Subject', 'approve-orders' ),
				'type'        => 'text',
				/* translators: %s: email subject */ 
				'description' => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'approve-orders' ), $this->subject ),
				'placeholder' => '',
				'default'     => '',
			),
			'heading'    => array(
				'title'       => __( 'Email Heading', 'approve-orders' ),
				'type'        => 'text',
				/* translators: %s: default heading */ 
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'approve-orders' ), $this->heading ),
				'placeholder' => '',
				'default'     => '',
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'approve-orders' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'approve-orders' ),
				'default'     => 'html',
				'class'       => 'wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
			),
		);
	}
}



