/*
Document   :  Modules Manager
Author     :  Andrei Dinca, AA-Team http://codecanyon.net/user/AA-Team
*/
// Initialization and events code for the app
WooZoneLiteModulesManager = (function ($) {
	"use strict";
	
	// public
	var debug_level = 0;
	var maincontainer = null;
	var mainloading = null;
	var lightbox = null;

	// init function, autoload
	(function init() {
		// load the triggers
		$(document).ready(function(){
			maincontainer = $("#WooZoneLite");
			mainloading = $("#WooZoneLite-main-loading");
			lightbox = $("#WooZoneLite-lightbox-overlay");

			triggers();
		});
	})();
	
	function activate_bulk_rows( status ) {
		var ids = [], __ck = $('.WooZoneLite-form .WooZoneLite-table input.WooZoneLite-item-checkbox:checked');
		__ck.each(function (k, v) {
			ids[k] = $(this).attr('name').replace('WooZoneLite-item-checkbox-', '');
		});
		ids = ids.join(',');
  
 		if (ids.length<=0) {
			alert('You didn\'t select any rows!');
			return false;
		}
  
		mainloading.fadeIn('fast');

		jQuery.post(ajaxurl, {
			'action' 		: 'WooZoneLiteModuleChangeStatus_bulk_rows',
			'id'			: ids,
			'the_status'		: status == 'activate' ? 'true' : 'false',
			'debug_level'		: debug_level
		}, function(response) {
			if( response.status == 'valid' ){
				mainloading.fadeOut('fast');

				//refresh page!
				window.location.reload();
				return false;
			}
			mainloading.fadeOut('fast');
			alert('Problems occured while trying to activate the selected modules!');
		}, 'json');
	}
	
	function triggers()
	{
		maincontainer.on('click', 'input#WooZoneLite-item-check-all', function(){
			var that = $(this),
				checkboxes = $('.WooZoneLite-table input.WooZoneLite-item-checkbox');
				
			if( that.is(':checked') ){
				checkboxes.prop('checked', true);
			}
			else{
				checkboxes.prop('checked', false);
			}
		});

		maincontainer.on('click', '#WooZoneLite-activate-selected', function(e){
			e.preventDefault();
  
			if ( confirm('Are you sure you want to activate the selected modules?') ) {
				activate_bulk_rows( 'activate' );
			}
		});
		
		maincontainer.on('click', '#WooZoneLite-deactivate-selected', function(e){
			e.preventDefault();
  
			if ( confirm('Are you sure you want to deactivate the selected modules?') ) {
				activate_bulk_rows( 'deactivate' );
			}
		});
		
		//all checkboxes are checked by default!
		$('.WooZoneLite-form .WooZoneLite-table input.WooZoneLite-item-checkbox').attr('checked', 'checked');

		if ( $('.WooZoneLite-form .WooZoneLite-table input.WooZoneLite-item-checkbox:checked').length <= 0 ) {
			$('.WooZoneLite-form .WooZoneLite-table input#WooZoneLite-item-check-all').css('display', 'none');
			$('.WooZoneLite-form input#WooZoneLite-activate-selected').css('display', 'none');
			$('.WooZoneLite-form input#WooZoneLite-deactivate-selected').css('display', 'none');
			$('.WooZoneLite-list-table-left-col').css('display', 'none');
		}
		
	}

	// external usage
	return {
    	}
})(jQuery);
