/* global vc */
var wstm_template_editor;
(function ( $ ) {
	'use strict';
	
	/* Disable Frontend Editor */
	if ( window.pagenow && 'wstm_templates' === window.pagenow ) {
		if ( window.vc_user_access && window.vc &&  window.vc.visualComposerView ) {
			window.vc.visualComposerView.prototype.initializeAccessPolicy = function () {
				this.accessPolicy = {
					be_editor: vc_user_access().editor( 'backend_editor' ),
					fe_editor: false,
					classic_editor: ! vc_user_access().check( 'backend_editor', 'disabled_ce_editor', undefined, true )
				};
			}
		}
		vc.events.on( 'vc:access:backend:ready', function(access) {
			access.add( 'fe_editor', false );
			$( '.wpb_switch-to-front-composer, .vc_control-preview' ).remove();
			$( '#wpb-edit-inline' ).parent().remove();
			$( '.vc_spacer:last-child' ).remove();
		} );
	}


	var WSTmOptions, WSTmPanelSelector, WSTmPanelEditorBackend, WSTmPanelEditorFrontend;
	WSTmOptions = {
		save_template_action: 'wstm_save_template',
		delete_template_action: 'wstm_delete_template',
		appendedClass: 'wstm_templates',
		appendedTemplateType: 'wstm_templates',
	};
	if ( window.vc && window.vc.TemplateWindowUIPanelBackendEditor ) {
		WSTmPanelEditorBackend = vc.TemplateWindowUIPanelBackendEditor.extend( WSTmOptions );
		WSTmPanelEditorFrontend = vc.TemplateWindowUIPanelFrontendEditor.extend( WSTmOptions );
		WSTmPanelSelector = '#vc_ui-panel-templates';
	} else {
		WSTmPanelEditorBackend = vc.TemplatesPanelViewBackend.extend( WSTmOptions );
		WSTmPanelEditorFrontend = vc.TemplatesPanelViewFrontend.extend( WSTmOptions );
		WSTmPanelSelector = '#vc_templates-panel';
	}

	
	$( document ).ready( function () {
		// we need to update currect template panel to new one (extend functionality)
		if ( window.vc_mode && window.vc_mode === 'admin_page' ) {
			if ( vc.templates_panel_view ) {
				vc.templates_panel_view.undelegateEvents(); // remove is required to detach event listeners and clear memory
				vc.templates_panel_view = wstm_template_editor = new WSTmPanelEditorBackend( { el: WSTmPanelSelector } );

				$( '#wstm_templates-editor-button' ).click( function ( e ) {
					e && e.preventDefault && e.preventDefault();
					vc.templates_panel_view.render().show(); // make sure we show our window :)
				} );
			}
		}
	} );

	$( window ).on( 'vc_build', function () {
		if ( window.vc && window.vc.templates_panel_view ) {
			vc.templates_panel_view.undelegateEvents(); // remove is required to detach event listeners and clear memory
			vc.templates_panel_view = wstm_template_editor = new WSTmPanelEditorFrontend( { el: WSTmPanelSelector } );

			$( '#wstm_templates-editor-button' ).click( function ( e ) {
				e && e.preventDefault && e.preventDefault();
				vc.templates_panel_view.render().show(); // make sure we show our window :)
			} );
		}
	} );
})( window.jQuery );
