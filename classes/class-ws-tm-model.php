<?php

	/**
	* For Post Type
	*/
	class WSTmModel {
		public static $post_type = "wstm_templates";
		public static $settings_tab = 'wstm_templates';
		public static $meta_data_name = "wstm_templates";
		public static $current_post_type = false;
		
		static public function init() {
			add_action( 'init', __CLASS__ . '::init_settings' );
		}

		static public function init_settings() {

			/* Get Old templated in wstmTemplates */
			self::install_old_templates();

			/* Create Post Types */
			self::createPostType();
			
			/* Action / Filters */
			self::initActionsFilters();

			/* Add wstmtemplate into allowed post types for visual composer. */
			self::wstmInAllowedPostType();
		}

		/* Action / Filters */
		static public function initActionsFilters() {

			/* Enable on page load ( open ) Backend Vc Editor */
			add_filter( 'wpb_vc_js_status_filter', __CLASS__ . '::setEditorOnLoad' );

			/* Navigation backend controls */
			add_filter( 'vc_nav_controls', __CLASS__ . '::navBackendFrontend' );

			/* Navigation frontend controls */
			add_filter( 'vc_nav_front_controls', __CLASS__ . '::navBackendFrontend' );

			/* Add settings tab in visual composer settings */
			add_filter( 'vc_settings_tabs', __CLASS__ . '::addAdminTab' );

			/* Add setting options to tab @ER */
			add_action( 'vc_settings_tab-' . self::$settings_tab, __CLASS__ . '::addTabSetting' );
		}
		
		/**
		 * @param $value
		 *
		 * @return string
		 */
		static public function setEditorOnLoad( $value ) {
			return self::isSamePostType() ? 'true' : $value;
		}

		/**
		 * @return bool
		 */
		static public function isSamePostType( $type = '' ) {
			return $type ? $type === self::wstmPostType() : get_post_type() === self::wstmPostType();
		}

		/**
		 * WSTM templates button on navigation bar of the Front / Backend editor.
		 *
		 * @param $buttons
		 *
		 * @return array
		 */
		static public function navBackendFrontend( $buttons ) {
			if ( ! vc_user_access()->part( 'templates' )->can()->get() ) {
				return $buttons;
			}

			if ( self::getCurrentPostType() == "vc_grid_item" ) {
				return $buttons;
			}

			$new_buttons = array();

			foreach ( $buttons as $button ) {
				if ( $button[0] != 'templates' ) {
					// disable custom css as well but only in wstm_templates page
					if ( ! self::isSamePostType() || ( self::isSamePostType() && $button[0] != 'custom_css' ) ) {
						$new_buttons[] = $button;
					}
				} else {
					$new_buttons[] = array(
						'custom_templates',
						'<li class="vc_navbar-border-right"><a href="#" class="vc_icon-btn wstm_templates_button"  id="wstm_templates-editor-button" title="' . __( 'Templates', 'js_composer' ) . '"></a></li>'
					);
				}
			}

			return $new_buttons;
		}

		/**
		 * Get Current Post Type.
		 *
		 */
		static public function getCurrentPostType() {
			if ( self::$current_post_type ) {
				return self::$current_post_type;
			}
			
			$post_type = false;
			
			if ( isset( $_GET['post'] ) ) {
				$post_type = get_post_type( $_GET['post'] );
			} else if ( isset( $_GET['post_type'] ) ) {
				$post_type = $_GET['post_type'];
			}

			self::$current_post_type = $post_type;

			return self::$current_post_type;
		}

		/**
		 * Create tab on VC settings page.
		 *
		 * @param $tabs
		 *
		 * @return array
		 */
		static public function addAdminTab( $tabs ) {

			if ( ! vc_user_access()->part( 'templates' )->can()->get() ) {
				return $tabs;
			}

			$tabs[ self::$settings_tab ] = __( 'WSTM Options', "wstm" );

			return $tabs;
		}

		/**
		 * Create tab fields. in Visual composer settings page options-general.php?page=vc_settings
		 *
		 * @param Vc_Settings $settings
		 */
		static public function addTabSetting( Vc_Settings $settings ) {

			$settings->addSection( self::$settings_tab );

			add_filter( 'vc_setting-tab-form-' . self::$settings_tab, __CLASS__ . '::setFormAttr' );

			$settings->addField( self::$settings_tab, __( 'Export VC Templates', "wstm" ), 'wstm_export', __CLASS__ . '::settingsFieldExportSanitize', __CLASS__ . '::settingsFieldExport' );
		}

		/**
		 * Custom attributes for tab form.
		 * @see addTabSetting
		 *
		 * @param $attr
		 *
		 * @return string
		 */
		static public function setFormAttr( $attr ) {
			$attr .= ' enctype="multipart/form-data"';

			return $attr;
		}

		/**
		 * Sanitize export field.
		 * @return bool
		 */
		static public function settingsFieldExportSanitize() {
			return false;
		}

		/**
		 * Builds export link in settings tab.
		 */
		static public function settingsFieldExport() {
			echo '<a href="export.php?page=wpb_vc_settings&wstm_action=export_wstm_templates" class="button">' . __( 'Download Export File', "wstm" ) . '</a>';
		}

		/**
		 * Import templates from file to the database by parsing xml file
		 * @return bool
		 */
		static public function settingsFieldImportSanitize() {
			$file = isset( $_FILES['wstm_import'] ) ? $_FILES['wstm_import'] : false;
			if ( $file === false || ! file_exists( $file['tmp_name'] ) ) {
				return false;
			} else {
				$post_types = get_post_types( array( 'public' => true ) );
				$roles = get_editable_roles();
				
				$wstm_templates = simplexml_load_file( $file['tmp_name'] );
				
				foreach ( $wstm_templates as $template ) {
					$template_post_types = $template_user_roles = $meta_data = array();
					
					$content = (string) $template->content;
					
					$id = self::create_template( (string) $template->title, $content );
					
					self::contentMediaUpload( $id, $content );
					
					foreach ( $template->post_types as $type ) {
						$post_type = (string) $type->post_type;
						if ( in_array( $post_type, $post_types ) ) {
							$template_post_types[] = $post_type;
						}
					}
					if ( ! empty( $template_post_types ) ) {
						$meta_data['post_type'] = $template_post_types;
					}
					foreach ( $template->user_roles as $role ) {
						$user_role = (string) $role->user_role;
						if ( in_array( $user_role, $roles ) ) {
							$template_user_roles[] = $user_role;
						}
					}
					if ( ! empty( $template_user_roles ) ) {
						$meta_data['user_role'] = $template_user_roles;
					}
					update_post_meta( (int) $id, self::$meta_data_name, $meta_data );
				}
				@unlink( $file['tmp_name'] );
			}

			return false;
		}

		/**
		 * Upload external media files in a post content to media library.
		 *
		 * @param $post_id
		 * @param $content
		 *
		 * @return bool
		 */
		static public function contentMediaUpload( $post_id, $content ) {
			preg_match_all( '/<img|a[^>]* src|href=[\'"]?([^>\'" ]+)/', $content, $matches );
			foreach ( $matches[1] as $match ) {
				if ( ! empty( $match ) ) {
					$file_array = array();
					$file_array['name'] = basename( $match );
					$tmp_file = download_url( $match );
					$file_array['tmp_name'] = $tmp_file;
					if ( is_wp_error( $tmp_file ) ) {
						@unlink( $file_array['tmp_name'] );
						$file_array['tmp_name'] = '';

						return false;
					}
					$desc = $file_array['name'];
					$id = media_handle_sideload( $file_array, $post_id, $desc );
					if ( is_wp_error( $id ) ) {
						@unlink( $file_array['tmp_name'] );

						return false;
					} else {
						$src = wp_get_attachment_url( $id );
					}
					$content = str_replace( $match, $src, $content );
				}
			}
			wp_update_post( array(
				'ID' => $post_id,
				'post_content' => $content
			) );

			return true;
		}

		/**
		 * Build import file input.
		 */
		static public function settingsFieldImport() {
			echo '<input type="file" name="wstm_import">';
		}
		
		/**
		 * @static
		 * Migrate default templates into wstmtemplates
		 * @return void
		 */
		static public function install_old_templates() {
			$is_migrated = get_option( 'is_wstmTemplates_migrated' ); 

			// Check is migration already performed
			if ( $is_migrated !== 'yes' ) {
				$templates = (array) get_option( 'wpb_js_templates' );
				foreach ( $templates as $template ) {
					self::create_template( $template['name'], $template['template'] );
				}
				update_option( 'is_wstmTemplates_migrated', 'yes' );
			}
		}
		/**
		 * Creates new template.
		 * @static
		 *
		 * @param $title
		 * @param $content
		 *
		 * @return int|WP_Error
		 */
		public static function create_template( $title, $content ) {
			return wp_insert_post( array(
				'post_title' => $title,
				'post_content' => $content,
				'post_status' => 'publish',
				'post_type' => self::wstmPostType()
			) );
		}

		/**
		 * @return string
		 */
		static public function wstmPostType() {
			return self::$post_type;
		}

		/**
		 * @return void
		 */
		static public function createPostType() {
			register_post_type( self::wstmPostType(),
				array(
					'labels' => self::getPostTypesLabels(),
					'public' => false,
					'has_archive' => false,
					'show_in_nav_menus' => true,
					'exclude_from_search' => true,
					'publicly_queryable' => false,
					'show_ui' => true,
					'query_var' => true,
					'capability_type' => 'post',
					'hierarchical' => false,
					'menu_position' => null,
					/*'menu_icon' => self::assetUrl( 'images/icon.gif' ),*/
					'show_in_menu' => false,//! WPB_VC_NEW_MENU_VERSION,
					'taxonomies'   => array( 'category' ),
				)
			);
		}

		/**
		 * @return labels array
		 */
		static public function getPostTypesLabels() {
			return array(
				'add_new_item' => __( 'Add WSTM Template', "wstm" ),
				'name' => __( 'WSTM Templates', "wstm" ),
				'singular_name' => __( 'WSTM Template', "wstm" ),
				'edit_item' => __( 'Edit WSTM Template', "wstm" ),
				'view_item' => __( 'View WSTM Template', "wstm" ),
				'search_items' => __( 'Search WSTM Templates', "wstm" ),
				'not_found' => __( 'No WSTM Templates found', "wstm" ),
				'not_found_in_trash' => __( 'No WSTM Templates found in Trash', "wstm" ),
			);
		}

		/**
		 * @return void
		 *
		 */
		static public function wstmInAllowedPostType() {
			if ( self::isValidPostType() ) {
				$js_content_types = get_option( 'wpb_js_content_types' );

				if ( ! is_array( $js_content_types ) || empty( $js_content_types ) ) {
					
					$js_content_types = array( self::wstmPostType(), 'page' );
					update_option( 'wpb_js_content_types', $js_content_types );
				} elseif ( ! in_array( self::wstmPostType(), $js_content_types ) ) {

					$js_content_types[] = self::wstmPostType();
					update_option( 'wpb_js_content_types', $js_content_types );
				}

				vc_set_default_editor_post_types( array(
					'page',
					'wstm_templates'
				) );


				vc_editor_set_post_types( vc_editor_post_types() + array( 'wstm_templates' ) );
				add_action( 'admin_init', __CLASS__ . '::createTemplateMetaBox' );
				add_filter( 'vc_role_access_with_post_types_get_state', '__return_true' );
				add_filter( 'vc_role_access_with_backend_editor_get_state', '__return_true' );
				add_filter( 'vc_role_access_with_frontend_editor_get_state', '__return_true' );
				add_filter( 'vc_check_post_type_validation', '__return_true' );
			}
			add_action( 'save_post', __CLASS__ . '::saveTemplateMetaBox' );
		}

		/**
		 * Create meta box for self::$wstmPostType, with template settings
		 */
		static public function createTemplateMetaBox() {
			add_meta_box( 'wstm_template_settings_metabox', __( 'Template Settings', "wstm" ), __CLASS__ . '::rightMetaBoxOutput', self::wstmPostType(), 'side', 'high' );
		}

		/**
		 * Used in meta box VcTemplateManager::createMetaBox
		 */
		static public function rightMetaBoxOutput() {
			$data = get_post_meta( get_the_ID(), self::$meta_data_name, true );
			$data_post_types = isset( $data['post_type'] ) ? $data['post_type'] : array();
			$post_types = get_post_types( array( 'public' => true ) );
			
			echo '<div class="misc-pub-section">
            <div class="wstmtemplate_title"><b>' . __( 'Post types', 'wstm' ) . '</b></div>
            <div class="input-append">
                ';
			foreach ( $post_types as $type ) {
				if ( $type != 'attachment' && ! self::isSamePostType( $type ) ) {
					echo '<label><input type="checkbox" name="' . esc_attr( self::$meta_data_name ) . '[post_type][]" value="' . esc_attr( $type ) . '"' . ( in_array( $type, $data_post_types ) ? ' checked="true"' : '' ) . '> ' . ucfirst( $type ) . '</label><br/>';
				}
			}
			echo '</div><p>' . __( 'Select for which post types this template should be available. Default: Available for all post types.', 'wstm' ) . '</p></div>';
			$groups = get_editable_roles();
			$data_user_role = isset( $data['user_role'] ) ? $data['user_role'] : array();
			echo '<div class="misc-pub-section vc_user_role">
            <div class="wstmtemplate_title"><b>' . __( 'Roles', 'wstm' ) . '</b></div>
            <div class="input-append">
                ';
			foreach ( $groups as $key => $g ) {
				echo '<label><input type="checkbox" name="' . self::$meta_data_name . '[user_role][]" value="' . $key . '"' . ( in_array( $key, $data_user_role ) ? ' checked="true"' : '' ) . '> ' . $g['name'] . '</label><br/>';
			}
			echo '</div><p>' . __( 'Select for user roles this template should be available. Default: Available for all user roles.', 'wstm' ) . '</p></div>';
		}

		/**
		 * Saves post data in databases after publishing or updating template's post.
		 *
		 * @param $post_id
		 *
		 * @return bool
		 */
		static public function saveTemplateMetaBox( $post_id ) {
			if ( ! self::isSamePostType() ) {
				return true;
			}
			if ( isset( $_POST[ self::$meta_data_name ] ) ) {
				$options = isset( $_POST[ self::$meta_data_name ] ) ? (array) $_POST[ self::$meta_data_name ] : Array();
				update_post_meta( (int) $post_id, self::$meta_data_name, $options );
			} else {
				delete_post_meta( (int) $post_id, self::$meta_data_name );
			}

			return true;
		}


		static public function isValidPostType() {
			$type = get_post_type();

			$post = ( isset( $_GET['post'] ) && self::compareType( get_post_type( $_GET['post'] ) ) );
			$post_type = ( isset( $_GET['post_type'] ) && self::compareType( $_GET['post_type'] ) );
			$post_type_id = ( isset( $_GET['post_id'] ) && self::compareType( get_post_type( (int) $_GET['post_id'] ) ) );
			$post_vc_type_id = ( isset( $_GET['vc_post_id'] ) && self::compareType( get_post_type( (int) $_GET['vc_post_id'] ) ) );
			
			return (
				( $type && self::compareType( $type ) ) ||
				( $post ) ||
				( $post_type ) ||
				( $post_type_id )||
				( $post_vc_type_id )
			);
		}

		static public function compareType( $type ) {
			return in_array( $type, array_merge( vc_editor_post_types(), array( 'wstm_templates' ) ) );
		}

	}

	WSTmModel::init();