<?php

	/**
	* 
	*/
	class WSTmAdminMenu {
		
		static public function init() {

			add_action( 'vc_menu_page_build', __CLASS__ . '::add_vc_submenu_page' );
		}

		/**
		 * Add sub page to Visual Composer pages
		 *
		 * @since 1.2
		 */
		static public function add_vc_submenu_page() {
			if ( vc_user_access()->part( 'templates' )->checkStateAny( true, null )->get() ) {
				$labels = WSTmModel::getPostTypesLabels();
				add_submenu_page( VC_PAGE_MAIN_SLUG, $labels['name'], $labels['name'], 'manage_options', 'edit.php?post_type=' . rawurlencode( WSTmModel::wstmPostType() ), '' );
			}
		}
	}

	WSTmAdminMenu::init();