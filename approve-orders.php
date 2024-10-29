<?php
/**
 * Plugin Name: Approve Orders
 * Plugin URI: https://neebplugins.com/plugin/approve-orders/
 * Description: Adds an order approval, cancellation workflow to your WooCommerce store.
 * Version: 1.0.1
 * Author: NeeB Plugins
 * Author URI: https://neebplugins.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: approve-orders
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * WC requires at least: 9.0
 * WC tested up to: 9.3
 * Requires Plugins: woocommerce
 *
 * @package Approve_Orders
 */

 namespace ApproveOrders;

 use ApproveOrders\Backend as AOFWC_Backend;
 use ApproveOrders\Frontend as AOFWC_Frontend;

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
	exit;
}

// Your code starts here.

defined( 'AOFWC_VERSION' ) or define( 'AOFWC_VERSION', '1.0.0' );
defined( 'AOFWC_FILE' ) or define( 'AOFWC_FILE', __FILE__ );
defined( 'AOFWC_BASE' ) or define( 'AOFWC_BASE', plugin_basename( AOFWC_FILE ) );
defined( 'AOFWC_DIR' ) or define( 'AOFWC_DIR', plugin_dir_path( AOFWC_FILE ) );
defined( 'AOFWC_URL' ) or define( 'AOFWC_URL', plugins_url( '/', AOFWC_FILE ) );

// Include dependencies
if ( file_exists( AOFWC_DIR . 'vendor/autoload.php' ) ) {
	require_once AOFWC_DIR . 'vendor/autoload.php';
} else {
	exit;
}

/**
 * The main plugin class for Approve Orders.
 */
class ApproveOrders {

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
	 * Initialize the plugin.
	 */
	public function __construct() {

		// Hook into the 'wp' action to load the frontend class
		add_action( 'init', array( $this, 'load_plugin' ) );

		// Hook into the 'admin_init' action to load the backend class
		add_action( 'plugins_loaded', array( $this, 'load_backend' ) );

		// Load text domain for translations
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// HPOS Compatibility
		add_action( 'before_woocommerce_init', array( $this, 'hpos_compatible' ) );

		// Plugin action links
		add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
		// Load wc email classes on woocommerce_init
		add_action( 'woocommerce_init', array( $this, 'load_wc_email_class' ) );
		// Pre Plugin Activate Check
		add_action( 'pre_plugin_activate', array( $this, 'prevent_plugin_activation_on_multisite' ) );

	}

	/**
	 * Load the backend class.
	 */
	public function load_backend() {
		// Run plugin if the site is not Multisite
		if ( ! is_multisite() ) {
			// Initialize the back-end functionality
			$backend = new AOFWC_Backend();
			$backend->init();
		} else {
			add_action( 'admin_notices', array( $this, 'multisite_admin_notification' ) );
		}
	}

	/**
	 * Load the frontend class.
	 */
	public function load_plugin() {
		// Run plugin if the site is not Multisite
		if ( ! is_multisite() ) {
			// Initialize the front-end functionality
			$frontend = new AOFWC_Frontend();
			$frontend->init();
		}
	}

	/**
	 * Load the HPOS Compatibility class.
	 */
	public function hpos_compatible() {
		// Define WooCommerce Compatibilities here
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', AOFWC_FILE, true );
		}
	}

	/**
	 * Plugin Action links
	 */
	public function action_links( $links ) {

		$links = array_merge(
			array(
				'<a href="' . esc_url( admin_url( 'admin.php?page=approve-orders' ) ) . '">' . esc_html( 'Settings', 'approve-orders' ) . '</a>',
			),
			$links
		);
		return $links;
	}

	/**
	 * Load Text Domain for Translation
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'approve-orders', false, AOFWC_BASE . '/languages' );
	}

	/**
	 * Load WC Email Classes
	 */
	public function load_wc_email_class() {
		if ( ! class_exists( 'WC_Email' ) ) {
			include_once WC()->plugin_path() . '/includes/emails/class-wc-email.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingCustomFunction
		}
	}

	/**
	 * Multisite admin notification
	 */
	public function multisite_admin_notification() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Multisite Not Supported, Please deactivate Approve Orders plugin', 'approve-orders' ); ?> .</p>
		</div>
		<?php
	}

	/**
	 * Prevent plugin activation on multisite
	 *
	 * @param string $plugin The plugin being activated.
	 * @param bool $network_wide Whether the activation is network-wide.
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public static function prevent_plugin_activation_on_multisite() {
		if ( is_multisite() ) {
			wp_die( 'The "Approve Orders" plugin is not supported on multisite' );
		}
	}

}

// Run plugin instance
$plugin_instance = ApproveOrders::get_instance();

register_activation_hook( __FILE__, array( $plugin_instance, 'prevent_plugin_activation_on_multisite' ) );
