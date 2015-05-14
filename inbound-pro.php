<?php
/*
Plugin Name: Inbound Pro
Plugin URI: http://www.inboundnow.com/
Description: Pro Version of Inbound Now Plugins
Author: Inbound Now
Author: Inbound Now
Version: 1.0.1
Author URI: http://www.inboundnow.com/
Text Domain: inbound-pro
Domain Path: /lang/
*/


if ( !class_exists('Inbound_Pro_Plugin')	) {

	final class Inbound_Pro_Plugin {

		/* START PHP VERSION CHECKS */
		/**
		 * Admin notices, collected and displayed on proper action
		 *
		 * @var array
		 */
		public static $notices = array();

		/**
		 * Whether the current PHP version meets the minimum requirements
		 *
		 * @return bool
		 */
		public static function is_valid_php_version() {
			return version_compare( PHP_VERSION, '5.3', '>=' );
		}

		/**
		 * Invoked when the PHP version check fails. Load up the translations and
		 * add the error message to the admin notices
		 */
		static function fail_php_version() {
			self::notice( __( 'Inbound Professional Components require PHP version 5.3+, plugin is currently NOT ACTIVE.', 'inbound-email' ) );
		}

		/**
		 * Handle notice messages according to the appropriate context (WP-CLI or the WP Admin)
		 *
		 * @param string $message
		 * @param bool $is_error
		 * @return void
		 */
		public static function notice( $message, $is_error = true ) {
			if ( defined( 'WP_CLI' ) ) {
				$message = strip_tags( $message );
				if ( $is_error ) {
					WP_CLI::warning( $message );
				} else {
					WP_CLI::success( $message );
				}
			} else {
				// Trigger admin notices
				add_action( 'all_admin_notices', array( __CLASS__, 'admin_notices' ) );

				self::$notices[] = compact( 'message', 'is_error' );
			}
		}

		/**
		 * Show an error or other message in the WP Admin
		 *
		 * @action all_admin_notices
		 * @return void
		 */
		public static function admin_notices() {
			foreach ( self::$notices as $notice ) {
				$class_name	= empty( $notice['is_error'] ) ? 'updated' : 'error';
				$html_message = sprintf( '<div class="%s">%s</div>', esc_attr( $class_name ), wpautop( $notice['message'] ) );
				echo wp_kses_post( $html_message );
			}
		}

		/**
		* Main Inbound_Pro_Plugin Instance
		*/
		public function __construct() {
			self::define_constants();
			self::load_pro_classes();
			self::load_core_components();
			self::load_text_domain_init();
		}

		/*
		* Setup plugin constants
		*
		*/
		private static function define_constants() {

			define('INBOUND_PRO_CURRENT_VERSION', '1.0.1' );
			define('INBOUND_PRO_URLPATH', WP_PLUGIN_URL.'/'.plugin_basename( dirname(__FILE__) ).'/' );
			define('INBOUND_PRO_PATH', WP_PLUGIN_DIR.'/'.plugin_basename( dirname(__FILE__) ).'/' );
			define('INBOUND_PRO_SLUG', plugin_basename( dirname(__FILE__) ) );
			define('INBOUND_PRO_FILE', __FILE__ );

			$uploads = wp_upload_dir();
			define('INBOUND_PRO_UPLOADS_PATH', $uploads['basedir'].'/inbound-pro/' );
			define('INBOUND_PRO_UPLOADS_URLPATH', $uploads['baseurl'].'/inbound-pro/' );
			define('INBOUND_PRO_STORE_URL', 'http://www.inboundnow.com/market/' );

		}

		/**
		*  Include required plugin files
		*/
		private static function load_core_components() {

            include_once('core/cta/calls-to-action.php');
            include_once('core/leads/leads.php');
            include_once('core/landing-pages/landing-pages.php');
            if (!self::get_customer_status()) {
                return;
            }
			include_once('core/inbound-mailer/inbound-mailer.php');
			include_once('core/inbound-automation/inbound-automation.php');

		}

		/**
		*  Load inbound pro classes
		*/
		private static function load_pro_classes() {

			/* Frontend & Admin */
			include_once( INBOUND_PRO_PATH . 'classes/class.options-api.php');
			include_once( INBOUND_PRO_PATH . 'classes/class.extension-loader.php');
			include_once( INBOUND_PRO_PATH . 'classes/class.analytics.php');
			include_once( INBOUND_PRO_PATH . 'assets/plugins/advanced-custom-fields-pro/acf.php');

			/* Admin Only */
			if (is_admin()) {
                include_once( INBOUND_PRO_PATH . 'classes/admin/class.updater.php');
				include_once( INBOUND_PRO_PATH . 'classes/admin/class.activate.php');
				include_once( INBOUND_PRO_PATH . 'classes/admin/class.menus.adminmenu.php');
				include_once( INBOUND_PRO_PATH . 'classes/admin/class.lead-field-mapping.php');
				include_once( INBOUND_PRO_PATH . 'classes/admin/class.settings.php');
				include_once( INBOUND_PRO_PATH . 'classes/admin/class.download-management.php');
				include_once( INBOUND_PRO_PATH . 'classes/admin/class.inbound-api-wrapper.php');
				include_once( INBOUND_PRO_PATH . 'classes/admin/class.ajax.listeners.php');
				include_once( INBOUND_PRO_PATH . 'classes/admin/class.oauth-engine.php');

				/* load ACF Settings */
				include_once( INBOUND_PRO_PATH . 'assets/settings/inbound-setup.php');

			}


		}

        /**
         * Get customer status
         */
        public static function get_customer_status() {
            $customer = Inbound_Options_API::get_option( 'inbound-pro' , 'customer' , array() );
            $status = ( isset($customer['active']) ) ? $customer['active'] : false;
            return $status;
        }

		/**
		*	Loads the correct .mo file for this plugin
		*
		*/
		private static function load_text_domain_init() {
			add_action( 'init' , array( __CLASS__ , 'load_text_domain' ) );
		}

		public static function load_text_domain() {
			load_plugin_textdomain( 'inbound-pro' , false , INBOUND_PRO_SLUG . '/lang/' );
		}


	}

	/* Initiate Plugin */
	if ( Inbound_Pro_Plugin::is_valid_php_version() ) {
		// Get Inbound Now Running
		$Inbound_Pro_Plugin = new Inbound_Pro_Plugin;
	} else {
		// Show Fail
		Inbound_Pro_Plugin::fail_php_version();
	}


}
