<?php

/**
	* For Post Type
	*/
class WSTmTemplates {
	static public $filename = 'wstmTemplate';

	static public function init() {
		add_action( 'init', __CLASS__ . '::init_settings' );
	}

	static public function init_settings() {
		
		/* Import Export Funxtions */

		add_action( 'wp_loaded', __CLASS__ . '::templateImportExport' );
		
		/* Action / Filters */
		self::initActionsFilters();
	}

	static public function initActionsFilters() {


		/* Filters */
		add_filter( 'vc_templates_render_category', __CLASS__ . '::renderTemplateBlock' );
		add_filter( 'vc_templates_render_template', __CLASS__ . '::renderTemplateWindow', 10, 2 );

		if ( WSTmModel::$post_type !== 'vc_grid_item' ) {
			add_filter( 'vc_get_all_templates', __CLASS__ . '::replaceCustomWithWSTmTemplates' );
		}
		
		/* Actions */
		add_action( 'wp_ajax_wstm_save_template', __CLASS__ . '::saveTemplate' );
		add_action( 'wp_ajax_wstm_delete_template', __CLASS__ . '::deleteTemplate' );

		/* Preview Template as Backend */
		add_action( 'vc_templates_render_backend_template_preview', __CLASS__ . '::getTemplateContentPreview', 10, 2 );

		/* Render Template */
		add_filter( 'vc_templates_render_frontend_template', __CLASS__ . '::renderFrontendTemplate' , 10, 2 );
		add_filter( 'vc_templates_render_backend_template', __CLASS__ . '::renderBackendTemplate', 10, 2 );

		/* Enqueue CSS and JS */
		add_action( 'vc_frontend_editor_enqueue_js_css', __CLASS__ . '::fronendJsCss' );
		add_action( 'vc_backend_editor_enqueue_js_css', __CLASS__ . '::backendJsCss' );
	}

	/**
	 * Export existing template in XML format.
	 *
	 * @param int $id (optional) Template ID. If not specified, export all templates
	 */
	static public function templateImportExport( $id = null ) {
		if ( ! isset( $_GET['wstm_action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		} 


		if ( $_GET['wstm_action'] === 'export_wstm_templates' ) {
			
			$id = ( isset( $_GET['id'] ) ? $_GET['id'] : null );
			self::exportTemplate( $id );
		}elseif ( $_GET['wstm_action'] === 'import_wstm_templates' ) {
		
			self::importTemplate();
		}
	}
	
	
	/**
	 * Export existing template in XML format.
	 *
	 * @param int $id (optional) Template ID. If not specified, export all templates
	 */
	static public function exportTemplate( $id = null ) {
		if ( $id ) {
			$template = get_post( $id );
			$templates = $template ? array( $template ) : array();
		} else {
			$templates = get_posts( array(
				'post_type' => WSTmModel::wstmPostType(),
				'numberposts' => - 1
			) );
		}

		$xml = '<?xml version="1.0"?><templates>';
		foreach ( $templates as $template ) {
			$xml .= self::convertToXml( $template );
		}
		$xml .= '</templates>';
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . self::$filename . '_' . date( 'dMY' ) . '.xml' );
		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
		echo $xml;
		die();
	}

	/**
	 * Convert template/post to xml for export
	 *
	 * @param $template Template object
	 *
	 * @return string
	 */
	static public function convertToXml( $template ) {
		$id = $template->ID;
		$meta_data = get_post_meta( $id, WSTmModel::$meta_data_name, true );
		$post_types = isset( $meta_data['post_type'] ) ? $meta_data['post_type'] : false;
		$user_roles = isset( $meta_data['user_role'] ) ? $meta_data['user_role'] : false;
		$xml = '';
		$xml .= '<template>';
		$xml .= '<title>' . apply_filters( 'the_title_rss', $template->post_title ) . '</title>'
		        . '<content>' . self::wxr_cdata( apply_filters( 'the_content_export', $template->post_content ) ) . '</content>';
		if ( $post_types !== false ) {
			$xml .= '<post_types>';
			foreach ( $post_types as $t ) {
				$xml .= '<post_type>' . $t . '</post_type>';
			}
			$xml .= '</post_types>';
		}
		if ( $user_roles !== false ) {
			$xml .= '<user_roles>';
			foreach ( $user_roles as $u ) {
				$xml .= '<user_role>' . $u . '</user_role>';
			}
			$xml .= '</user_roles>';
		}

		$xml .= '</template>';

		return $xml;
	}

	/**
	 * CDATA field type for XML
	 *
	 * @param $str
	 *
	 * @return string
	 */
	static public function wxr_cdata( $str ) {
		if ( seems_utf8( $str ) == false ) {
			$str = utf8_encode( $str );
		}

		$str = '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', $str ) . ']]>';

		return $str;
	}

	/* Template top block */
	static public function renderTemplateBlock( $category ) {
		if ( WSTmModel::$post_type === $category['category'] ) {
			if ( vc_user_access()->part( 'templates' )->checkStateAny( true, null )->get() ) {
				
				$category['output'] = '<div class="vc_column vc_col-sm-12" data-vc-hide-on-search="true">';
					$category['output'] .= '<div class="vc_element_label">';
						$category['output'] .= esc_html( 'Save current layout as a template', 'js_composer' );
					$category['output'] .= '</div>';

					$category['output'] .= '<div class="vc_input-group">';
						$category['output'] .= '<input name="padding" class="vc_form-control wpb-textinput vc_panel-templates-name" type="text" value=""
					       placeholder="' . esc_attr( 'Template name', 'js_composer' ) . '">';
					    $category['output'] .= '<span class="vc_input-group-btn">';
					    	$category['output'] .= '<button class="vc_btn vc_btn-primary vc_btn-sm vc_template-save-btn">';
					    		$category['output'] .= esc_html( 'Save template', 'js_composer' );
					    	$category['output'] .= '</button>';
					    $category['output'] .= '</span>';
					$category['output'] .= '</div>';

					$category['output'] .= '<span class="vc_description">';
						$category['output'] .= esc_html( 'Save your layout and reuse it on different sections of your website', 'js_composer' );
					$category['output'] .= '</span>';
				$category['output'] .= '</div>';
			}

			$category['output'] .= '<div class="vc_col-md-12">';
				if ( isset( $category['category_name'] ) ) {
					$category['output'] .= '<h3>' . esc_html( $category['category_name'] ) . '</h3>';
				}
				if ( isset( $category['category_description'] ) ) {
					$category['output'] .= '<p class="vc_description">' . esc_html( $category['category_description'] ) . '</p>';
				}
			$category['output'] .= '</div>';

			$category['output'] .= '<div class="vc_column vc_col-sm-12">';
				$category['output'] .= '<ul class="vc_templates-list-my_templates">';
					if ( ! empty( $category['templates'] ) ) {
						foreach ( $category['templates'] as $template ) {
							$category['output'] .= visual_composer()->templatesPanelEditor()->renderTemplateListItem($template);
						}
					}
				$category['output'] .= '</ul>';
			$category['output'] .= '</div>';
		}

		return $category;
	}

	/**
	 * Hook templates panel window rendering, if template type is wstm_templates render it
	 *
	 * @param $template_name
	 * @param $template_data
	 *
	 * @return string
	 */
	static public function renderTemplateWindow( $template_name, $template_data ) {
		if ( WSTmModel::$post_type === $template_data['type'] ) {
			return self::renderTemplateWindowWSTmTemplates( $template_name, $template_data );
		}

		return $template_name;
	}

	/**
	 * Rendering wstm template for panel window
	 *
	 * @param $template_name
	 * @param $template_data
	 *
	 * @return string
	 */
	static public function renderTemplateWindowWSTmTemplates( $template_name, $template_data ) {
		
		$template_id = esc_attr( $template_data['unique_id'] );
		$template_id_hash = md5( $template_id ); // needed for jquery target for TTA
		$template_name = esc_html( $template_name );
		$delete_template_title = esc_attr( 'Delete template', 'wstm' );
		$preview_template_title = esc_attr( 'Preview template', 'wstm' );
		$add_template_title = esc_attr( 'Add template', 'wstm' );
		$edit_template_title = esc_attr( 'Edit template', 'wstm' );
		$template_url = esc_attr( admin_url( 'post.php?post=' . $template_data['unique_id'] . '&action=edit' ) );
		$output = $edit_ouput = '';
		
		if ( vc_user_access()->part( 'templates' )->checkStateAny( true, null )->get() ) {
				$edit_ouput .= '<a href="'.$template_url.'"  class="vc_general vc_ui-control-button" title="'.$edit_template_title.'" target="_blank">';
					$edit_ouput .= '<i class="vc_ui-icon-pixel vc_ui-icon-pixel-control-edit-dark"></i>';
				$edit_ouput .= '</a>';
			
				$edit_ouput .= '<button type="button" class="vc_general vc_ui-control-button" data-vc-ui-delete="template-title" title="'.$delete_template_title.'">';
					$edit_ouput .= '<i class="vc_ui-icon-pixel vc_ui-icon-pixel-control-trash-dark"></i>';
				$edit_ouput .= '</button>';
		}

		$output .= '<button type="button" class="vc_ui-list-bar-item-trigger" title="'.$add_template_title.'" data-template-handler="" data-vc-ui-element="template-title">';
			$output .= $template_name;
		$output .= '</button>';

		$output .= '<div class="vc_ui-list-bar-item-actions">';
			$output .= '<button type="button" class="vc_general vc_ui-control-button" title="'.$add_template_title.'" data-template-handler="">';
				$output .= '<i class="vc_ui-icon-pixel vc_ui-icon-pixel-control-add-dark"></i>';
			$output .= '</button>';

			$output .= $edit_ouput;

			$output .= '<button type="button" class="vc_general vc_ui-control-button" title="'.$preview_template_title.'" data-vc-container=".vc_ui-list-bar" data-vc-preview-handler data-vc-target="[data-template_id_hash='.$template_id_hash.']">';
				$output .= '<i class="vc_ui-icon-pixel vc_ui-preview-icon"></i>';
			$output .= '</button>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Function used to replace old my templates with new wstm templates
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	static public function replaceCustomWithWSTmTemplates( array $data ) {
		$wstm_templates = self::getTemplateList();
		$wstm_arr = array();
		$wstm_category_slug = 'my_templates';
		$wstm_category_name = 'My Templates';

		
		foreach ( $wstm_templates as $template_name => $template_id ) {
			$wstm_categories = get_the_terms( $template_id, 'category' );
			if ( $wstm_categories && is_array( $wstm_categories ) ) {
				$wstm_category_name = $wstm_categories[0]->name;
				$wstm_category_slug = $wstm_categories[0]->slug;
			}
			$wstm_arr[] = array(
				'unique_id' => $template_id,
				'name' => $template_name,
				'type' => 'wstm_templates',
				// for rendering in backend/frontend with ajax);
			);
			
		}

		if ( ! empty( $data ) ) {
			$found = false;
			foreach ( $data as $key => $category ) {
				if ( $category['category'] == 'my_templates' ) {
					$found = true;
					$data[ $key ]['templates'] = $wstm_arr;
				}
			}
			if ( ! $found ) {
				$data[] = array(
					'templates' => $wstm_arr,
					'category' => 'my_templates',
					'category_name' => __( 'My Templates', 'js_composer' ),
					'category_description' => __( 'Append previously saved template to the current layout', 'js_composer' ),
					'category_weight' => 10,
				);
			}
		} else {
			$data[] = array(
				'templates' => $wstm_arr,
				'category' => 'my_templates',
				'category_name' => __( 'My Templates', 'js_composer' ),
				'category_description' => __( 'Append previously saved template to the current layout', 'js_composer' ),
				'category_weight' => 10,
			);
		}

		return $data;
	}

	/**
	 * Gets list of existing templates. Checks access rules defined by template author.
	 * @return array
	 */
	static public function getTemplateList() {
		
		global $current_user;
		wp_get_current_user();
		$current_user_role = isset( $current_user->roles[0] ) ? $current_user->roles[0] : false;
		$list = array();
		
		$templates = get_posts( array(
			'post_type' => WSTmModel::$post_type,
			'numberposts' => - 1
		) );
		
		$post = get_post( isset( $_POST['post_id'] ) ? $_POST['post_id'] : null );
		
		foreach ( $templates as $template ) {
			
			$id = $template->ID;
			$meta_data = get_post_meta( $id, WSTmModel::$meta_data_name, true );
			$post_types = isset( $meta_data['post_type'] ) ? $meta_data['post_type'] : false;
			$user_roles = isset( $meta_data['user_role'] ) ? $meta_data['user_role'] : false;
			
			if ( ( ! $post || ! $post_types || in_array( $post->post_type, $post_types ) ) && 
				 ( ! $current_user_role || ! $user_roles || in_array( $current_user_role, $user_roles ) )
			) {
				$list[ $template->post_title ] = $id;
			/* $list[ $template->post_title ] = array(
					'id' => $id,
					'category' => ''
					);*/
			}
		}

		return $list;
	}

	/**
	 * Used to save new template from ajax request in new panel window
	 *
	 */
	static public function saveTemplate() {
		
		if ( ! vc_verify_admin_nonce() || ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) ) {
			die();
		}

		$title = vc_post_param( 'template_name' );
		$content = vc_post_param( 'template' );
		
		$template_id = WSTmModel::create_template( $title, $content );
		$template_title = get_the_title( $template_id );
		
		echo visual_composer()->templatesPanelEditor()->renderTemplateListItem( 
				array(
					'name' => $template_title,
					'unique_id' => $template_id,
					'type' => WSTmModel::wstmPostType()
				) 
			);
		die();
	}

	/**
	 * Used to delete template by template id
	 *
	 * @param int $template_id - if provided used, if not provided used vc_post_param('template_id')
	 */
	static public function deleteTemplate( $template_id = null ) {
		if ( ! vc_verify_admin_nonce() || ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) ) {
			die();
		}

		$post_id = $template_id ? $template_id : vc_post_param( 'template_id' );
		
		if ( ! is_null( $post_id ) ) {
			$post = get_post( $post_id );

			if ( ! $post || ! WSTmModel::isSamePostType( $post->post_type ) ) {
				die( 'failed to delete' );
			} else if( wp_delete_post( $post_id ) ) {
				die( 'deleted' );
			}
		}
		die( 'failed to delete' );
	}

	/**
	 * Get template content for preview.
	 *
	 * @param $template_id
	 * @param $template_type
	 *
	 * @return string
	 */
	static public function getTemplateContentPreview( $template_id, $template_type ) {
		if ( WSTmModel::$post_type === $template_type ) {
			WPBMap::addAllMappedShortcodes();
			// do something to return output of wstm template
			$post = get_post( $template_id );
			if ( WSTmModel::isSamePostType( $post->post_type ) ) {
				return $post->post_content;
			}
		}

		return $template_id;
	}

	/**
	 * Used to render template for frontend
	 *
	 * @param $template_id
	 * @param $template_type
	 *
	 * @return string|int
	 */
	static public function renderFrontendTemplate( $template_id, $template_type ) {
		if ( WSTmModel::$post_type === $template_type ) {
			WPBMap::addAllMappedShortcodes();
			// do something to return output of templatera template
			$post = get_post( $template_id );
			if ( WSTmModel::isSamePostType( $post->post_type ) ) {
				vc_frontend_editor()->enqueueRequired();
				vc_frontend_editor()->setTemplateContent( $post->post_content );
				vc_frontend_editor()->render( 'template' );
				die();
			}
		}

		return $template_id;
	}

	/**
	 * Used to render template for backend
	 *
	 * @param $template_id
	 * @param $template_type
	 *
	 * @return string|int
	 */
	static public function renderBackendTemplate( $template_id, $template_type ) {
		if ( WSTmModel::$post_type === $template_type ) {
			WPBMap::addAllMappedShortcodes();
			// do something to return output of wstm template
			$post = get_post( $template_id );
			if ( WSTmModel::isSamePostType( $post->post_type ) ) {
				echo $post->post_content;
				die();
			}
		}

		return $template_id;
	}

	/* Enqueue Frontend JS CSS */
	static public function fronendJsCss() {
		if ( WSTmModel::isValidPostType() && ( vc_user_access()->part( 'frontend_editor' )->can()->get() ) ) {
			
			self::templateGrid();
			$dependency = array( 'vc-frontend-editor-min-js', );

			
			wp_enqueue_script( 'wstm-template-editor', WSTM_ASSET_URL .'js/template-editor.js', $dependency, WSTM_VERSION, true );
			wp_enqueue_script( 'wstm-template-options', WSTM_ASSET_URL .'js/template-options.js', array(), WSTM_VERSION, true );
			
			wp_enqueue_style( 'wstm-template-css', WSTM_ASSET_URL .'css/template-style.css', false, WSTM_VERSION );
		}
	}

	/* Enqueue Backend JS CSS */
	static public function backendJsCss() {
		if ( WSTmModel::isValidPostType() && ( vc_user_access()->part( 'backend_editor' )->can()->get() || WSTmModel::isSamePostType()) ) {
				
			self::templateGrid();
			$dependency = array( 'vc-backend-min-js' );
				
			wp_enqueue_script( 'wstm-template-editor', WSTM_ASSET_URL .'js/template-editor.js', $dependency, WSTM_VERSION, true );
			wp_enqueue_script( 'wstm-template-options', WSTM_ASSET_URL .'js/template-options.js', array(), WSTM_VERSION, true );
			
			wp_enqueue_style( 'wstm-template-css', WSTM_ASSET_URL .'css/template-style.css', false, WSTM_VERSION );
		}
	}

	/* Grid Js Enqueue */
	static public function templateGrid() {
		/*if ( WSTmModel::isSamePostType() ) {
			wp_enqueue_script( 'wstm-template-grid-js', WSTM_ASSET_URL . 'js/template-grid.js', array( 'wpb_js_composer_js_listeners' ), WSTM_VERSION, true );
		}*/
	}
}

WSTmTemplates::init();