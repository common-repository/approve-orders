<?php
namespace ApproveOrders;

use ApproveOrders\Settings as AOFWC_Settings;
use ApproveOrders\Modules\PendingPaymentNotification as AOFWC_PendingPaymentEmail;
use ApproveOrders\Modules\ApprovedOrderNotification as AOFWC_ApprovedOrderEmail;
use ApproveOrders\Modules\RejectedOrderNotification as AOFWC_RejectedOrderEmail;
use ApproveOrders\Modules\AdminAwaitingApprovalNotification as AOFWC_AwaitingApprovalEmail;

/**
 * The back-end plugin class for Approve Orders.
 */
class Backend {

	/**
	 * Initialize the back-end functionality of the plugin.
	 */
	public function init() {
		// Code for the constructor
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'save_approve_orders_settings' ) );

		// Register a new order status
		add_action( 'init', array( $this, 'register_custom_order_status' ) );
		// Add the new order status to the list of order statuses in WooCommerce
		add_filter( 'wc_order_statuses', array( $this, 'add_custom_order_status' ) );
		// Add the new order status to the admin order list
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_custom_order_status_column' ) );
		// Display the custom order status in the admin order list
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'display_custom_order_status_column' ) );
		// Add to WooCommerce Email Classes
		add_filter( 'woocommerce_email_classes', array( $this, 'add_order_notification_email' ) );
		add_filter( 'woocommerce_email_actions', array( $this, 'add_order_notification_action' ) );
		// Trigger the custom email when order status changes to pending
		add_action( 'woocommerce_order_status_changed', array( $this, 'send_order_notification' ), 10, 3 );
		// Add buttons to the WooCommerce order edit page
		add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'add_buttons_to_order_edit_page' ) );

		add_action( 'admin_init', array( $this, 'enqueue_assets' ) );

		// Trigger email on order status change to awaiting approval.
		//add_action( 'woocommerce_order_status_awaiting-approval', array( $this, 'send_awaiting_approval_notification' ) );
	}

	/**
	 * Add Admin Menu
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return void
	 */
	public function register_settings_page() {
		add_menu_page(
			__( 'Approve Orders', 'approve-orders' ),
			__( 'Approve Orders', 'approve-orders' ),
			'manage_options',
			'approve-orders',
			array( $this, 'render_settings_page' ),
			'dashicons-saved'
		);
	}

	/**
	 * Register Approve Order Settings
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'aofwc_settings',
			'aofwc_option',
			array( $this, 'sanitize_callback' )
		);
	}

	/**
	 * Render Settings Page
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return void
	 */
	public function render_settings_page() {
		$settings = AOFWC_Settings::get_instance();
		$option   = $settings->get();

		$approved_status   = isset( $option['approved_status'] ) ? $option['approved_status'] : '';
		$rejected_status   = isset( $option['rejected_status'] ) ? $option['rejected_status'] : '';
		$approval_workflow = isset( $option['approval_workflow'] ) ? $option['approval_workflow'] : 'disabled';
		$selected_gateways = isset( $option['selected_gateways'] ) ? $option['selected_gateways'] : array();
		$selected_roles    = isset( $option['selected_roles'] ) ? $option['selected_roles'] : array();

		$order_statuses     = wc_get_order_statuses(); // Get all available order statuses
		$available_gateways = \WC_Payment_Gateways::instance()->get_available_payment_gateways();
		$available_roles    = ( function_exists( 'wp_roles' ) ) ? wp_roles()->roles : array();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Approve Orders Settings', 'approve-orders' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'aofwc_settings' );
				wp_nonce_field( 'aofwc_settings_save', 'aofwc_nonce' );
				?>
				<table class="form-table">
					<tr>
						<th scope="row1"><?php esc_html_e( 'Order Approval Process', 'approve-orders' ); ?></th>
						<td>
							<input type="hidden" name="aofwc_option[approval_workflow]" value="disabled" />							
							<input type = 'checkbox' name = 'aofwc_option[approval_workflow]' value = 'enabled' <?php checked( $approval_workflow === 'enabled', true ); ?> />
							<p class="description"><?php esc_html_e( '( Set new orders to \'Awaiting Approval\' across the entire site )', 'approve-orders' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row1"><?php esc_html_e( 'Status after Approval', 'approve-orders' ); ?></th>
						<td>
							<select class="regular-text wc-enhanced-select" name="aofwc_option[approved_status]">
								<?php foreach ( $order_statuses as $status => $label ) { ?>
									<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $approved_status, $status ); ?>><?php echo esc_html( $label ); ?></option>
								<?php } ?>
							</select>
							<p class="description"><?php esc_html_e( '( Set the order status for approved orders )', 'approve-orders' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row1"><?php esc_html_e( 'Status after Rejection', 'approve-orders' ); ?></th>
						<td>
							<select class="regular-text wc-enhanced-select" name="aofwc_option[rejected_status]">
								<?php foreach ( $order_statuses as $status => $label ) { ?>
									<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $rejected_status, $status ); ?>><?php echo esc_html( $label ); ?></option>
								<?php } ?>
							</select>
							<p class="description"><?php esc_html_e( '( Set the order status for rejected orders )', 'approve-orders' ); ?></p>
						</td>
					</tr>					
					<tr>
						<th scope="row1"><?php esc_html_e( 'Payment Gateway for Order Approval', 'approve-orders' ); ?></th>
						<td>													
							<select multiple class="regular-text wc-enhanced-select" name="aofwc_option[selected_gateways][]">
								<?php foreach ( $available_gateways as $gateway ) { ?>
									<option value="<?php echo esc_attr( $gateway->id ); ?>" <?php echo in_array( $gateway->id, $selected_gateways, true ) ? 'selected' : ''; ?>><?php echo esc_html( $gateway->get_title() ); ?></option>
								<?php } ?>
							</select>
							<p class="description"><?php esc_html_e( '( Send orders for approval to selected gateways. Leave blank to allow all )', 'approve-orders' ); ?></p>							
						</td>
					</tr>
					<?php if ( ! empty( $available_roles ) ) { ?>
					<tr>
						<th scope="row1"><?php esc_html_e( 'User Role for Order Approval', 'approve-orders' ); ?></th>
						<td>													
							<select multiple class="regular-text wc-enhanced-select" name="aofwc_option[selected_roles][]">
								<?php foreach ( $available_roles as $role_key => $role ) { ?>
									<option value="<?php echo esc_attr( $role_key ); ?>" <?php echo in_array( $role_key, $selected_roles, true ) ? 'selected' : ''; ?>><?php echo esc_html( $role['name'] ); ?></option>
								<?php } ?>
							</select>
							<p class="description"><?php esc_html_e( '( Send orders for approval to selected roles. Leave blank for all )', 'approve-orders' ); ?></p>							
						</td>
					</tr>
					<?php } ?>									
				</table>
				<?php
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize input data
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return void
	 */
	public function sanitize_callback( $input ) {
		return sanitize_post( $input, 'db' );
	}

	/**
	 * Register Custom Order Status
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return void
	 */
	public function register_custom_order_status() {
		register_post_status(
			'wc-awaiting-approval',
			array(
				'label'                     => _x( 'Awaiting Approval', 'Order status', 'approve-orders' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: count, %s: count */
				'label_count'               => _n_noop( 'Awaiting Approval <span class="count">(%s)</span>', 'Awaiting Approval <span class="count">(%s)</span>', 'approve-orders' ),
			),
			'wc-approved',
			array(
				'label'                     => _x( 'Approved', 'Order status', 'approve-orders' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: count, %s: count */
				'label_count'               => _n_noop( 'Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>', 'approve-orders' ),
			),
			'wc-rejected',
			array(
				'label'                     => _x( 'Rejected', 'Order status', 'approve-orders' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: count, %s: count */
				'label_count'               => _n_noop( 'Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>', 'approve-orders' ),
			)
		);
	}

	/**
	 * Add the custom order status to the list of order statuses.
	 *
	 * @param array $order_statuses The list of order statuses.
	 * @return array The updated list of order statuses.
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function add_custom_order_status( $order_statuses ) {
		$order_statuses['wc-awaiting-approval'] = _x( 'Awaiting Approval', 'Order status', 'approve-orders' );
		$order_statuses['wc-approved']          = _x( 'Approved', 'Order status', 'approve-orders' );
		$order_statuses['wc-rejected']          = _x( 'Rejected', 'Order status', 'approve-orders' );
		return $order_statuses;
	}

	/**
	 * Add the custom order status column to the admin order list.
	 *
	 * @param array $columns The list of columns.
	 * @return array The updated list of columns.
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function add_custom_order_status_column( $columns ) {
		$columns['order_status'] = __( 'Status', 'approve-orders' );
		return $columns;
	}

	/**
	 * Display the custom order status in the admin order list.
	 *
	 * @param string $column The column name.
	 * @return void
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function display_custom_order_status_column( $column ) {
		global $post;
		if ( $column === 'order_status' ) {
			$order  = wc_get_order( $post->ID );
			$status = $order->get_status();
			return wc_get_order_status_name( $status );
		}
	}

	/**
	 * Add the pending payment notification email class to the list of email classes.
	 *
	 * @param array $email_classes The list of email classes.
	 * @return array The updated list of email classes.
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function add_order_notification_email( $email_classes ) {
		$email_classes['AOFWC_PendingPaymentEmail']   = new AOFWC_PendingPaymentEmail();
		$email_classes['AOFWC_ApprovedOrderEmail']    = new AOFWC_ApprovedOrderEmail();
		$email_classes['AOFWC_RejectedOrderEmail']    = new AOFWC_RejectedOrderEmail();
		$email_classes['AOFWC_AwaitingApprovalEmail'] = new AOFWC_AwaitingApprovalEmail();
		return $email_classes;
	}

	/**
	 * Add the pending payment notification email to the list of email actions.
	 *
	 * @param array $email_actions The list of email actions.
	 * @return array The updated list of email actions.
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function add_order_notification_action( $email_actions ) {
		$email_actions[] = 'woocommerce_order_status_pending';
		return $email_actions;
	}

	/**
	 * Send order notification when order status changes to pending, approved, rejected.
	 *
	 * @param int $order_id The ID of the order.
	 * @param string $old_status The old order status.
	 * @param string $new_status The new order status.
	 * @return void
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function send_order_notification( $order_id, $old_status, $new_status ) {
		// Check if the new status is 'pending'
		if ( $new_status === 'pending' ) {
			$email = new AOFWC_PendingPaymentEmail();
			$email->trigger( $order_id );
		}
		// Check if the new status is 'approved'
		if ( $new_status === 'approved' ) {
			$email = new AOFWC_ApprovedOrderEmail();
			$email->trigger( $order_id );
		}
		// Check if the new status is 'rejected'
		if ( $new_status === 'rejected' ) {
			$email = new AOFWC_RejectedOrderEmail();
			$email->trigger( $order_id );
		}
	}

	/**
	 * Save Approve Orders Settings
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return void
	 */
	public function save_approve_orders_settings() {

		// Check if the nonce is set and verify it
		if ( ! isset( $_POST['aofwc_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aofwc_nonce'] ) ), 'aofwc_settings_save' ) ) {
			// Nonce is not set or is invalid
			return;
		}

		if ( isset( $_POST['aofwc_option'] ) ) { // phpcs:ignore 												
			$options = sanitize_post( $_POST['aofwc_option'] ); // phpcs:ignore

			$settings = AOFWC_Settings::get_instance();
			$settings->set( wp_unslash( $options ) ); // phpcs:ignore
		}
	}

	/**
	 * Add custom buttons to the order edit page
	 *
	 * @param WC_Order $order The order object.
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return void
	 */
	public function add_buttons_to_order_edit_page( $order ) {
		// Display buttons only for orders with the "pending" status

		$settings = AOFWC_Settings::get_instance();
		$options  = $settings->get();

		$approved_status = str_replace( 'wc-', '', isset( $options['approved_status'] ) ? $options['approved_status'] : '' );
		$rejected_status = str_replace( 'wc-', '', isset( $options['rejected_status'] ) ? $options['rejected_status'] : '' );

		$approve_url = wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=' . esc_attr( $approved_status ) . '&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' );
		$reject_url  = wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=' . esc_attr( $rejected_status ) . '&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' );

		if ( 'awaiting-approval' === $order->get_status() ) {

			/* translators: %s: approve url, %s: text */
			$approve_button = sprintf(
				'<a href="%s" type="button" class="button aofwc-approve">%s</a>',
				$approve_url,
				esc_html__( 'Approve', 'approve-orders' )
			);

			/* translators: %s: reject url, %s: text */
			$reject_button = sprintf(
				'<a href="%s" type="button" class="button aofwc-reject">%s</a>',
				$reject_url,
				esc_html__( 'Reject', 'approve-orders' )
			);

			echo wp_kses_post( $approve_button . $reject_button );
			return;
		}

		if ( 'pending' === $order->get_status() ) {

			/* translators: %s: reject url, %s: text */
			$reject_button = sprintf(
				'<a href="%s" type="button" class="button aofwc-reject">%s</a>',
				$reject_url,
				esc_html__( 'Reject', 'approve-orders' )
			);

			echo wp_kses_post( $reject_button );
			return;
		}

	}

	/**
	 * Send awaiting approval notification when order status changes to pending, approved, rejected.
	 *
	 * @param int $order_id The ID of the order.
	 * @param string $old_status The old order status.
	 * @param string $new_status The new order status.
	 * @return void
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function send_awaiting_approval_notification( $order_id ) {
		$email = new AwaitingApprovalEmail();
		$email->trigger( $order_id );
	}

	public function enqueue_assets() {
		// Enqueue SelectWoo script and styles
		wp_enqueue_script( 'selectWoo' );
		wp_enqueue_style( 'selectWoo', WC()->plugin_url() . '/assets/css/select2.css', array(), AOFWC_VERSION );
		// Enqueue admin styles
		wp_register_style( 'aofwc-admin', AOFWC_URL . '/assets/css/admin.css', array(), AOFWC_VERSION );
		wp_enqueue_style( 'aofwc-admin' );
		// Enqueue admin script
		wp_register_script( 'aofwc-admin', AOFWC_URL . '/assets/js/admin.js', array( 'jquery' ), AOFWC_VERSION );
		wp_enqueue_script( 'aofwc-admin' );
	}

}
