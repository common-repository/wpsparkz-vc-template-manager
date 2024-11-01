<?php

if ( ! class_exists( 'WSTmLoader' ) ) {
	
	/**
	* Responsible for setting up constants, classes and includes.
	*
	* @since 0.1
	*/
	final class WSTmLoader {
		
		/**
		 * Load the builder if it's not already loaded, otherwise
		 * show an admin notice.
		 *
		 * @since 0.1
		 * @return void
		 */ 
		static public function init() {
			/* VC Required Version */
			define( 'WSTM_VC_REQUIRED_VERSION', '4.11' );
			
			if ( ! defined( 'WPB_VC_VERSION' ) ) {
			
				add_action('admin_notices',         __CLASS__ . '::vc_required_admin_notice');
				add_action('network_admin_notices', __CLASS__ . '::vc_required_admin_notice');
				return;
			} elseif ( version_compare( WPB_VC_VERSION, WSTM_VC_REQUIRED_VERSION ) < 0 ) {

				add_action('admin_notices',         __CLASS__ . '::vc_required_admin_notice');
				add_action('network_admin_notices', __CLASS__ . '::vc_required_admin_notice');
				return;
			}

			
			
						
			self::define_constants();
			//self::define_varibles();
			self::load_files();
		}

		/**
		 * Define addon constants.
		 *
		 * @since 0.1
		 * @return void
		 */ 
		static private function define_constants() {	
			define('WSTM_VERSION', '1.0.0');
			define('WSTM_LITE_VERSION', true);
			define('WSTM_FILE', trailingslashit(dirname(dirname(__FILE__))) . 'wpsparkz-vc-templates.php');
			define('WSTM_DIR', plugin_dir_path( WSTM_FILE ) );
			define('WSTM_URL', plugins_url( '/', WSTM_FILE ) );
			define('WSTM_ASSET_URL', WSTM_URL . 'assets/' );
			
		}

		/**
		 * Define variables.
		 *
		 * @since 0.1
		 * @return void
		 */ 
		static private function define_varibles() {	
			
		}

		/**
		 * Loads classes and includes.
		 *
		 * @since 0.1
		 * @return void
		 */ 
		static private function load_files()
		{
			/* Classes */
			require_once WSTM_DIR . 'classes/class-ws-tm-model.php';
			require_once WSTM_DIR . 'classes/class-ws-tm-templates.php';
			require_once WSTM_DIR . 'classes/class-ws-tm-admin-menu.php';
		}
		/**
		 * Shows an admin notice if cornerstone is not insatalled and activated.
		 *
		 * @since 0.1
		 * @return void
		 */
		static public function vc_required_admin_notice() {
			
			$message = __( 'WPSparkz VC Template Manager plugin requires <a href="%s">Visual Composer Page Builder</a> plugin Version 4.11 or greater installed & activated.', 'sjda' );
			
			self::render_admin_notice( sprintf( $message, admin_url( 'plugins.php' ) ), 'error' );
		}

		/**
		 * Renders an admin notice.
		 *
		 * @since 0.1
		 * @access private
		 * @param string $message
		 * @param string $type
		 * @return void
		 */ 
		static private function render_admin_notice( $message, $type = 'update' ) {
			if ( ! is_admin() ) {
				return;
			}
			else if ( ! is_user_logged_in() ) {
				return;
			}
			else if ( ! current_user_can( 'update_core' ) ) {
				return;
			}
			
			echo '<div class="' . $type . '">';
			echo '<p>' . $message . '</p>';
			echo '</div>';
		}
	}

	WSTmLoader::init();
}

