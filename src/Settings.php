<?php
namespace ApproveOrders;

/**
 * The back-end plugin class for Approve Orders.
 */
class Settings {

	private $option_name = 'aofwc_option';
	private $settings;
	private $defaults = array(
		'approval_workflow' => 'enabled',
		'approved_status'   => 'wc-pending',
		'rejected_status'   => 'wc-cancelled',
		'selected_gateways' => array( 'preordergateway-approve-orders' ),
		'selected_roles'    => array(),
	);

	/**
	 * Private constructor to prevent direct instantiation of the ApproveOrders class.
	 */
	private static $instance;

	/**
	 * Returns the Singleton instance of the ApproveOrders class.
	 *
	 * @return ApproveOrders The Singleton instance of the ApproveOrders class.
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * The constructor.
	 */
	public function __construct() {
		// No settings yet
	}

	/**
	 * Get settings
	 * 
	 * @param void
	 * 
	 * @since 1.0.0
	 * 
	 * @return array $settings	 
	 */
	public function get() {

		// Merge saved options with defaults
		$settings = wp_parse_args( get_option( $this->option_name, array() ), $this->defaults );

		$this->settings = $settings;
		return $this->settings;
	}

	/**
	 * Set settings
	 * 
	 * @param array $settings
	 * 
	 * @since 1.0.0
	 * 
	 * @return void	 
	 */
	public function set( $settings ) {
		update_option( $this->option_name, $settings );
	}

}
