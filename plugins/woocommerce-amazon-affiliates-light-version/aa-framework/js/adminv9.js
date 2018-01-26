/*
	Document   :  aaFreamwork
	Created on :  August, 2013
	Author     :  Andrei Dinca, AA-Team http://codecanyon.net/user/AA-Team
*/

// Initialization and events code for the app
WooZoneLite = (function ($) {
	"use strict";

	var option = {
		'prefix': "WooZoneLite"
	};
	
	var settings		= null;
	var 
		t                   = null,
		ajaxBox             = null,
		section             = 'dashboard',
		subsection          = '',
		in_loading_section  = null,
		topMenu             = null,
		debug_level         = 0,
		loading             = null,
		maincontainer       = null,
		mainloading         = null,
		lightbox            = null,
		installDefaultIsRunning = false,
		installDefaultMsg = 'If you already configured the plugin settings with <Amazon config> module, these settings will be overwritten with the default one. Installing default settings should be used when you activate the plugin for the first time. Are you sure you want to proceed?';

	$.fn.center = function() {
		this.css("position","absolute");
	    this.css("top", Math.max(0, (($(window).height() - $(this).outerHeight()) / 2) + $(window).scrollTop()) + "px");
	    return this;
	};
	
	function init() 
	{
		$(document).ready(function(){
			
			t = $("div#WooZoneLite");
			ajaxBox = t.find('#WooZoneLite-ajax-response');
			topMenu = t.find('nav.WooZoneLite-nav');
			
			if (t.size() > 0 ) {
				//fixLayoutHeight();
			}
			
			// plugin depedencies if default!
			if ( $("li#WooZoneLite-nav-depedencies").length > 0 ) {
				section = 'depedencies';
			}
			
			maincontainer = $("#WooZoneLite-wrapper");
			mainloading = $("#WooZoneLite-main-loading");
			lightbox = $("#WooZoneLite-lightbox-overlay");

            // plugin settings
            settings = t.find('#WooZoneLite-plugin-settings').html();
            //settings = JSON.stringify(settings);
            settings = typeof settings != 'undefined'
                ? JSON && JSON.parse(settings) || $.parseJSON(settings) : settings;
			
			triggers();
		});
	}
	
	function ajax_loading( label ) 
	{
		// append loading
		loading = $('<div class="WooZoneLite-loader-wrapper"><div class="WooZoneLite-loader-holder"><div class="WooZoneLite-loader"></div> ' + ( label ) + '</div></div>');
		ajaxBox.html(loading);
	}

	function take_over_ajax_loader( label, target )
	{
		loading = $('<div class="WooZoneLite-loader-take-over-wrapper"><div class="WooZoneLite-loader-holder"><div class="WooZoneLite-loader"></div> ' + ( label ) + '</div></div>');
		
		if( typeof target != 'undefined' ) {
			target.append(loading);
		}else{
			t.append(loading);
	   }
	}

	function take_over_ajax_loader_close()
	{
		t.find(".WooZoneLite-loader-take-over-wrapper").remove();
	}
	
	function makeRequest( callback ) 
	{
		// fix for duble loading of js function
		if( in_loading_section == section ){
			return false;
		}
		in_loading_section = section;
		
		// do not exect the request if we are not into our ajax request pages
		if( ajaxBox.size() == 0 ) return false;

		ajax_loading( "Loading section: " + section );
		var data = {
			'action': 'WooZoneLiteLoadSection',
			'section': section
		}; 
		
		jQuery.post(ajaxurl, data, function (response) {
			
			if( response.status == 'redirect' ){
				window.location = response.url;
				return;
			}
			
			if (response.status == 'ok') {
				$("h1.WooZoneLite-section-headline").html(response.headline);
				//return true;
				loading.fadeOut( 350, function(){

					ajaxBox.attr( 'class', "WooZoneLite-section-"  + section );
					
					ajaxBox.html(response.html);
				
					makeTabs();
					
					if( typeof WooZoneLiteDashboard != "undefined" ){
						WooZoneLiteDashboard.init();
					}
					
					// find new open
					var new_open = topMenu.find('li#WooZoneLite-nav-' + section);
					topMenu.find("a.active").removeClass("active");
					new_open.find("a").addClass("active");

					//console.log( new_open.find("a")  );
					
					// callback - subsection!
					if ( $.isArray(callback) && $.isFunction( callback[0] ) ) {
						if ( callback.length == 1 ) {
							callback[0]();
						}
						else if ( callback.length == 2 ) {
							callback[0]( callback[1] );
						}
					}
				
					multiselect_left2right();
				});
			}
		},
		'json');
	}
	
	function installDefaultOptions($btn) {
		if ( installDefaultIsRunning ) return false;
		installDefaultIsRunning = true;
		
		var is_makeinstall = typeof $btn.data('makeinstall') != 'undefined' ? true : false;

		//console.log( is_makeinstall ); return false; 

		var theForm = $btn.parents('form').eq(0),
			value = $btn.val(),
			statusBoxHtml = theForm.find('div.WooZoneLite-message'); // replace the save button value with loading message
		$btn.val('installing default settings ...').removeClass('blue').addClass('gray');
		if (theForm.length > 0) { // serialiaze the form and send to saving data
			var data = {
				'action'				: 'WooZoneLiteInstallDefaultOptions',
				'options'			: theForm.serialize(),
				'is_makeinstall'	: is_makeinstall ? 1 : 0
			}; // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			jQuery.post(ajaxurl, data, function (response) {
				if (response.status == 'ok') {
					statusBoxHtml.addClass('WooZoneLite-success').html(response.html).fadeIn().delay(3000).fadeOut();
					
					// from default install
					if ( is_makeinstall ) {
						setTimeout(function () {
							var currentLoc 	= window.location.href,
								  newLoc		= currentLoc.replace(/#.*$/, '#!/dashboard');

							newLoc = currentLoc.replace(/page=.*$/, 'page=WooZoneLite_wizard');

							//window.location = '';
							window.location.replace( newLoc );
							window.location.reload();
							
							// replace the save button value with default message
							setTimeout( function() {
								$btn.val(value).removeClass('gray').addClass('blue');
								take_over_ajax_loader_close();							
							}, 500);
						},
						1500);
					}
					// choose to install settings
					else {
						setTimeout(function () {
							var currentLoc 	= window.location.href,
								  newLoc		= currentLoc.replace('#makeinstall', '');
							window.location.replace( newLoc );
							window.location.reload();
							
							// replace the save button value with default message
							$btn.val(value).removeClass('gray').addClass('blue');
							take_over_ajax_loader_close();							
						},
						2000);
					}
				} else {
					statusBoxHtml.addClass('WooZoneLite-error').html(response.html).fadeIn().delay(13000).fadeOut();
					
					// replace the save button value with default message
					$btn.val(value).removeClass('gray').addClass('blue');
					take_over_ajax_loader_close();
				}
			},
			'json');
		}
	}
	
	function saveOptions ($btn, callback) 
	{
		var theForm = $btn.parents('form').eq(0),
			value = $btn.val(),
			statusBoxHtml = theForm.find('div#WooZoneLite-status-box'); // replace the save button value with loading message
		$btn.val('saving setings ...').removeClass('green').addClass('gray');
		
		multiselect_left2right(true);

		if (theForm.length > 0) { // serialiaze the form and send to saving data
			var data = {
				'action': 'WooZoneLiteSaveOptions',
				'options': theForm.serialize()
			}; // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			jQuery.post(ajaxurl, data, function (response) {
				if (response.status == 'ok') {
					statusBoxHtml.addClass('WooZoneLite-success').html(response.html).fadeIn().delay(3000).fadeOut();
					if (section == 'synchronization') {
						updateCron();
					}
					
				} // replace the save button value with default message
				$btn.val(value).removeClass('gray').addClass('green');
				
				if( typeof callback == 'function' ){
					callback.call();
				}
			},
			'json');
		}
	}

	function moduleChangeStatus($btn) 
	{
		var value = $btn.text(),
			the_status = $btn.hasClass('activate') ? 'true' : 'false';
		// replace the save button value with loading message
		$btn.text('saving setings ...');
		var data = {
			'action': 'WooZoneLiteModuleChangeStatus',
			'module': $btn.attr('rel'),
			'the_status': the_status
		};
		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function (response) {
			if (response.status == 'ok') {
				window.location.reload();
			}
		},
		'json');
	}
	
	function updateCron() 
	{
		var data = {
			'action': 'WooZoneLiteSyncUpdate'
		}; // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function (response) {},
		'json');
	}
	
	function fixLayoutHeight() 
	{
		var win = $(window),
			WooZoneLiteWrapper = $("#WooZoneLite-wrapper"),
			minusHeight = 40,
			winHeight = win.height(); // show the freamwork wrapper and fix the height
		WooZoneLiteWrapper.css('min-height', parseInt(winHeight - minusHeight)).show();
		$("div#WooZoneLite-ajax-response").css('min-height', parseInt(winHeight - minusHeight - 240)).show();
	}
	
	function ajax_list()
	{
		var make_request = function( action, params, callback ){
			var loading = $("#WooZoneLite-main-loading");
			loading.show();
 
			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			jQuery.post(ajaxurl, {
				'action'        : 'WooZoneLiteAjaxList',
				'ajax_id'       : $(".WooZoneLite-table-ajax-list").find('.WooZoneLite-ajax-list-table-id').val(),
				'sub_action'    : action,
				'params'        : params
			}, function(response) {
   
				if( response.status == 'valid' )
				{
					$("#WooZoneLite-table-ajax-response").html( response.html );

					loading.fadeOut('fast');
				}
			}, 'json');
		}

		$(".WooZoneLite-table-ajax-list").on('change', 'select[name=WooZoneLite-post-per-page]', function(e){
			e.preventDefault();

			make_request( 'post_per_page', {
				'post_per_page' : $(this).val()
			} );
		})

		.on('change', 'select[name=WooZoneLite-filter-post_type]', function(e){
			e.preventDefault();

			make_request( 'post_type', {
				'post_type' : $(this).val()
			} );
		})
		
		.on('change', 'select[name=WooZoneLite-filter-post_parent]', function(e){
			e.preventDefault();

			make_request( 'post_parent', {
				'post_parent' : $(this).val()
			} );
		})

		.on('click', 'a.WooZoneLite-jump-page', function(e){
			e.preventDefault();

			make_request( 'paged', {
				'paged' : $(this).attr('href').replace('#paged=', '')
			} );
		})

		.on('click', '.WooZoneLite-post_status-list a', function(e){
			e.preventDefault();

			make_request( 'post_status', {
				'post_status' : $(this).attr('href').replace('#post_status=', '')
			} );
		})
		
		.on('change', 'select.WooZoneLite-filter-general_field', function(e){
			e.preventDefault();
			
			var $this       = $(this),
				filter_name = $this.data('filter_field'),
				filter_val  = $this.val();

			make_request( 'general_field', {
				'filter_name'    : filter_name,
				'filter_val'     : filter_val
			} );
		})
		
		.on('click', 'ul.WooZoneLite-filter-general_field a', function(e){
			e.preventDefault();
 
			var $this       = $(this),
				$parent_ul  = $this.parents('ul').first(),
				filter_name = $parent_ul.data('filter_field'),
				filter_val  = $this.data('filter_val');

			make_request( 'general_field', {
				'filter_name'    : filter_name,
				'filter_val'     : filter_val
			} );
		})        
		
		.on('click', 'input[name=WooZoneLite-search-btn]', function(e){
			e.preventDefault();
			
			make_request( 'search', {
				'search_text' : $(this).parent().find('#WooZoneLite-search-text').val()
			} );
		});
	}
	
	function amzCheckAWS()
	{
		$('body').on('click', '.WooZoneLiteCheckAmzKeys', function (e) {
			e.preventDefault();

			$('#AccessKeyID').val( $.trim( $('#AccessKeyID').val() ) );
			$('#SecretAccessKey').val( $.trim( $('#SecretAccessKey').val() ) );
			$('.WooZoneLite-aff-ids input').each(function() {
				$(this).val( $.trim( $(this).val() ) );
			});

			var that = $(this),
				old_value = that.val(),
				submit_btn = that.parents('form').eq(0).find('input[type=submit]');
			
			that.removeClass('blue').addClass('gray');
			that.val('Checking your keys ...'); 
			
			saveOptions(submit_btn, function(){
				
				jQuery.post(ajaxurl, {
					'action' : 'WooZoneLiteCheckAmzKeys'
				}, function(response) {

						var msg = response.msg + "\n\n";

						if( response.status == 'valid' ){
							msg += 'WooCommerce Amazon Affiliates was able to connect to Amazon with the specified AWS Key Pair and Associate ID';
						}
						else{
							msg += 'WooCommerce Amazon Affiliates was not able to connect to Amazon with the specified AWS Key Pair and Associate ID. Please triple-check your AWS Keys and Associate ID.';
						}
						alert( msg );

						that.val( old_value ).removeClass('gray').addClass('blue');
				}, 'json');
			});
		});
	}
	
	function removeHelp()
	{
		$("#WooZoneLite-help-container").remove();  
	}
	
	function showHelp( that )
	{
		removeHelp();

		var help_type = that.data('helptype');
		var operation = that.data('operation');
		var html = $('<div class="WooZoneLite-panel-widget" id="WooZoneLite-help-container" />');
		
		var btn_close_text = ( operation == 'help' ? 'Close HELP' : 'Close Feedback' );
		html.append("<a href='#close' class='WooZoneLite-button red' id='WooZoneLite-close-help'>" + btn_close_text + "</a>")
		if( help_type == 'remote' ){
			var url = that.data('url');
			var content_wrapper = $("#WooZoneLite-content");
			
			html.append( '<iframe src="' + ( url ) + '" style="width:100%; height: 100%;border: 1px solid #d7d7d7;" frameborder="0" id="WooZoneLite-iframe-docs"></iframe>' )
			
			content_wrapper.append(html);
			
			// feedback iframe related!
			//var $iframe = $('#WooZoneLite-iframe-docs'),
		}
	}
	
	function hashChange_old()
	{
		if ( location.href.indexOf("WooZoneLite#") != -1 ) {
			// Alerts every time the hash changes!
			if(location.hash != "") {
				section = location.hash.replace("#", '');
				
				var __tmp = section.indexOf('#');
				if ( __tmp == -1 ) subsection = '';
				else { // found subsection block!
						subsection = section.substr( __tmp+1 );
						section = section.slice( 0, __tmp );
					}
				} 
	 
				if ( subsection != '' )
				makeRequest([
					function (s) { scrollToElement( s ) },
					'#'+subsection
				]);
			else 
				makeRequest();
			return false;
		}
		if ( location.href.indexOf("=WooZoneLite") != -1 ) {
			makeRequest();
			return false;
		}
	}
	function hashChange_old2() {
		if (location.hash != "") {
			section = location.hash.replace("#!/", '');
			if( t.size() > 0 ) {
				makeRequest();
			}
		}else{
			if( t.size() > 0 && location.search == "?page=WooZoneLite" ){
				makeRequest();
			}
		}
	}
	function hashChange() {
		// main container exists?
		if( t.size() <= 0 ) {
			return false;
		}

		if (location.hash != "") {
			section = location.hash.replace("#!/", '');
			
			if (1) {
				var __tmp = section.indexOf('#');
				if ( __tmp == -1 ) {
				    subsection = '';
				} else { // found subsection block!
					subsection = section.substr( __tmp+1 );
					section = section.slice( 0, __tmp );
				}
 
    			if ( subsection != '' ) {
    			    var __re = /tab:([0-9a-zA-Z_-]*)/gi; //new RegExp("tab:([0-9a-zA-Z_-]*)", "gi");
    			    // is tab?
    			    if ( __re.test(subsection) ) {
                        var __match = subsection.match(__re); //__re.exec(subsection); //null;
                        sub_istab = typeof (__match[0]) != 'undefined' ? __match[0].replace('tab:', '') : '';

                        if ( sub_istab == '' ) return false;
                        makeRequest([
                            function (s) { 
                                $('.tabsHeader').find('a[title="'+s+'"]').click();
                            },
                            sub_istab
                        ]);
    			    }
    			    // other?
    			    else {
    			    	var whatPms = {
    			    		what		: subsection
    			    	};
 
        				makeRequest([
        					function (pms) {
        						var pms 	= pms || {},
        							  what 	= misc.hasOwnProperty(pms, 'what') ? pms.what : '';

        						if ( 'makeinstall' == what ) {
        							take_over_ajax_loader( "installing default settings ..." );
        							$('.WooZoneLite-installDefaultOptions').data('makeinstall', 'yes').trigger('click');
        							/*
        							if ( confirm( installDefaultMsg ) ) {
										$('.WooZoneLite-installDefaultOptions').data('makeinstall', 'yes').trigger('click');
        							} else {
										var currentLoc 	= window.location.href,
											  newLoc		= currentLoc.replace('#makeinstall', '');
										window.location.replace( newLoc );
        								take_over_ajax_loader_close();
        							}
        							*/
        						}
        					},
        					whatPms
        				]);
    				}
    			} else { 
    				makeRequest();
    			}
			}
		}else{
			if( location.search == "?page=WooZoneLite" ){
				makeRequest();
			}
		}
	}

	function multiselect_left2right( autselect ) {
		var $allListBtn = $('.multisel_l2r_btn');
		var autselect = autselect || false;
 
		if ( $allListBtn.length > 0 ) {
			$allListBtn.each(function(i, el) {
 
				var $this = $(el), $multisel_available = $this.prevAll('.WooZoneLite-multiselect-available').find('select.multisel_l2r_available'), $multisel_selected = $this.prevAll('.WooZoneLite-multiselect-selected').find('select.multisel_l2r_selected');
 
				if ( autselect ) {
					$multisel_selected.find('option').each(function() {
						$(this).prop('selected', true);
					});
					$multisel_available.find('option').each(function() {
						$(this).prop('selected', false);
					});
				} else {

				$this.on('click', '.moveright', function(e) {
					e.preventDefault();
					$multisel_available.find('option:selected').appendTo($multisel_selected);
				});
				$this.on('click', '.moverightall', function(e) {
					e.preventDefault();
					$multisel_available.find('option').appendTo($multisel_selected);
				});
				$this.on('click', '.moveleft', function(e) {
					e.preventDefault();
					$multisel_selected.find('option:selected').appendTo($multisel_available);
				});
				$this.on('click', '.moveleftall', function(e) {
					e.preventDefault();
					$multisel_selected.find('option').appendTo($multisel_available);
				});
				
				}
			});
		}
	}
	
	function makeTabs()
	{
		$('ul.WooZoneLite-tabs-header').each(function() {
			// For each set of tabs, we want to keep track of
			// which tab is active and it's associated content
			var $active, $content, $links = $(this).find('a');

			// If the location.hash matches one of the links, use that as the active tab.
			// If no match is found, use the first link as the initial active tab.
			var __tabsWrapper = $(this), __currentTab = $(this).find('li#WooZoneLite-tabs-current').attr('title');
			$active = $( $links.filter('[title="'+__currentTab+'"]')[0] || $links[0] );
			$active.addClass('active');
			$content = $( '.'+($active.attr('title')) );

			// Hide the remaining content
			$links.not($active).each(function () {
				$( '.'+($(this).attr('title')) ).hide();
			});

			// Bind the click event handler
			$(this).on('click', 'a', function(e){
				// Make the old tab inactive.
				$active.removeClass('active');
				$content.hide();

				// Update the variables with the new link and content
				__currentTab = $(this).attr('title');
				__tabsWrapper.find('li#WooZoneLite-tabs-current').attr('title', __currentTab);
				$active = $(this);
				$content = $( '.'+($(this).attr('title')) );

				// Make the tab active.
				$active.addClass('active');
				$content.show();

				// Prevent the anchor's default click action
				e.preventDefault();
			});
		});
	}
	
	function make_select_menu()
	{
		//console.log( maincontainer  );
	}

	function triggers() 
	{
		amzCheckAWS();

		make_select_menu();

		$(window).resize(function () {
			//fixLayoutHeight();
		});

		$("body").on('mousemove', '.WooZoneLite-loader-wrapper, .WooZoneLite-loader-take-over-wrapper', function( event ) {
			
			var pageCoords = "( " + event.pageX + ", " + event.pageY + " )";
			var clientCoords = "( " + event.clientX + ", " + event.clientY + " )";
			var parent = $(this).parent();
			var parentPos = parent.offset();
			//$( "span:first" ).text( "( event.pageX, event.pageY ) : " + pageCoords );
			//$( "span:last" ).text( "( event.clientX, event.clientY ) : " + clientCoords );
 
			event.pageY = event.pageY - 100;
			if( typeof parent != 'undefined' && parent.attr('id') != 'WooZoneLite' ) {
				event.pageY = event.pageY - parentPos.top + 31;
			}
			
			$(this).find(".WooZoneLite-loader-holder").css( 'top', event.pageY + 'px' );
			
		});
		
		$('body').on('click', '.WooZoneLite-saveOptions', function (e) {
			e.preventDefault();
			saveOptions($(this));
		});
		$('body').on('click', '.WooZoneLite-installDefaultOptions', function (e) {
			e.preventDefault();
			installDefaultOptions($(this));
		});
		$('.WooZoneLite-message_activate').on('click', '.submit a.button-primary', function (e) {
			if ( $('form#WooZoneLite_setup_box').length > 0 ) {
				take_over_ajax_loader( "installing default settings ..." );
				$('.WooZoneLite-installDefaultOptions').data('makeinstall', 'yes').trigger('click');
				/*
				if ( confirm( installDefaultMsg ) ) {
					$('.WooZoneLite-installDefaultOptions').data('makeinstall', 'yes').trigger('click');
				} else {
					var currentLoc 	= window.location.href,
						  newLoc		= currentLoc.replace('#makeinstall', '');
					window.location.replace( newLoc );
					take_over_ajax_loader_close();
				}
				*/
			}
		});
		
		$('body').on('click', '#' + option.prefix + "-module-manager a", function (e) {
			e.preventDefault();
			moduleChangeStatus($(this));
		}); // Bind the event.
		
		// Bind the hashchange event.
		/*
		$(window).on('hashchange', function(){
			hashChange();
		});
		hashChange();
		*/
		// Alerts every time the hash changes!
		$(window).hashchange(function () {
			hashChange();
		});
		// Trigger the event (useful on page load).
		$(window).hashchange();
		
		ajax_list();
		
		$("body").on('click', "a.WooZoneLite-show-feedback", function(e){
			e.preventDefault();
			
			showHelp( $(this) );
		});
		
		$("body").on('click', "a.WooZoneLite-show-docs-shortcut", function(e){
			e.preventDefault();
			
			$("a.WooZoneLite-show-docs").click();
		});
		
		$("body").on('click', "a.WooZoneLite-show-docs", function(e){
			e.preventDefault();
			
			showHelp( $(this) );
		});
		
		 $("body").on('click', "a#WooZoneLite-close-help", function(e){
			e.preventDefault();
			
			removeHelp();
		});
		
		multiselect_left2right();




		$('body').on('click', 'input#WooZoneLite-item-check-all', function(){
			var that = $(this),
				checkboxes = $('#WooZoneLite-list-table-posts input.WooZoneLite-item-checkbox');

			if( that.is(':checked') ){
				checkboxes.prop('checked', true);
			}
			else{
				checkboxes.prop('checked', false);
			}
		});

		$("body").on("click", "#WooZoneLite-list-rows a", function(e){
			e.preventDefault();
			$(this).parent().find('table').toggle("slow");
		});

		// publish / unpublish row
		$('body').on('click', ".WooZoneLite-do_item_publish", function(e){
			e.preventDefault();
			var that = $(this),
				row = that.parents('tr').eq(0),
				id  = row.data('itemid');
				
			do_item_action( id, 'publish' );
		});

		// delete row       
		$('body').on('click', ".WooZoneLite-do_item_delete", function(e){
			e.preventDefault();
			var that = $(this),
				row = that.parents('tr').eq(0),
				id  = row.data('itemid');

			//row.find('code').eq(0).text()
			if(confirm('Delete row with ID# '+id+' ? This action cannot be rollback !' )){
				do_item_action( id, 'delete' );
			}
		});
		
		$('body').on('click', '#WooZoneLite-do_bulk_delete_rows', function(e){
			e.preventDefault();

			if (confirm('Are you sure you want to delete the selected rows ? This action cannot be rollback !'))
				do_bulk_delete_rows();
		});
		
		//all checkboxes are checked by default!
		$('.WooZoneLite-form .WooZoneLite-table input.WooZoneLite-item-checkbox').attr('checked', 'checked');
				
		// inline edit
		inline_edit();
	}

	function do_item_action( itemid, sub_action )
	{
		var sub_action = sub_action || '';

		lightbox.fadeOut('fast');
		mainloading.fadeIn('fast');
		
		jQuery.post(ajaxurl, {
			'action'        : 'WooZoneLiteAjaxList_actions',
			'itemid'        : itemid,
			'sub_action'    : sub_action,
			'ajax_id'       : $(".WooZoneLite-table-ajax-list").find('.WooZoneLite-ajax-list-table-id').val(),
			'debug_level'   : debug_level
		}, function(response) {
			if( response.status == 'valid' ){
				mainloading.fadeOut('fast');
				//window.location.reload();
				$("#WooZoneLite-table-ajax-response").html( response.html );
				return false;
			}
			mainloading.fadeOut('fast');
			alert('Problems occured while trying to execute action: '+sub_action+'!');
		}, 'json');
	}

	function do_bulk_delete_rows() {
		var ids = [], __ck = $('.WooZoneLite-form .WooZoneLite-table input.WooZoneLite-item-checkbox:checked');
		__ck.each(function (k, v) {
			ids[k] = $(this).attr('name').replace('WooZoneLite-item-checkbox-', '');
		});
		ids = ids.join(',');
		if (ids.length<=0) {
			alert('You didn\'t select any rows!');
			return false;
		}
		
		lightbox.fadeOut('fast');
		mainloading.fadeIn('fast');

		jQuery.post(ajaxurl, {
			'action'        : 'WooZoneLiteAjaxList_actions',
			'id'            : ids,
			'sub_action'    : 'bulk_delete',
			'ajax_id'       : $(".WooZoneLite-table-ajax-list").find('.WooZoneLite-ajax-list-table-id').val(),
			'debug_level'   : debug_level
		}, function(response) {
			if( response.status == 'valid' ){
				mainloading.fadeOut('fast');
				//window.location.reload();
				$("#WooZoneLite-table-ajax-response").html( response.html );
				return false;
			}
			mainloading.fadeOut('fast');
			alert('Problems occured while trying to execute action: '+'bulk_delete_rows'+'!');
		}, 'json');
	}

	// inline edit fields
	var inline_edit = function() {

		function make_request( pms ) {
			var pms         = pms || {},
				replace     = misc.hasOwnProperty( pms, 'replace' ) ? pms.replace : null,
				itemid      = misc.hasOwnProperty( pms, 'itemid' ) ? pms.itemid : 0,
				table       = misc.hasOwnProperty( pms, 'table' ) ? pms.table : '',
				field       = misc.hasOwnProperty( pms, 'field' ) ? pms.field : '',
				new_val     = misc.hasOwnProperty( pms, 'new_val' ) ? pms.new_val : '',
				el_type     = misc.hasOwnProperty( pms, 'el_type' ) ? pms.el_type : '',
				new_text    = misc.hasOwnProperty( pms, 'new_text' ) ? pms.new_text : '';
				
			//console.log( row, itemid, field_name, field_value ); return false;             
			loading( replace, 'show' );

			jQuery.post(ajaxurl, {
				'action'        : 'WooZoneLiteAjaxList_actions',
				'itemid'        : itemid,
				'sub_action'    : 'edit_inline',
				'table'         : table,
				'field_name'    : field,
				'field_value'   : new_val,
				'ajax_id'       : $(".WooZoneLite-table-ajax-list").find('.WooZoneLite-ajax-list-table-id').val(),
				'debug_level'   : debug_level

			}, function(response) {

				loading( replace, 'close' );
				var orig     = replace.prev('.WooZoneLite-edit-inline'),
					just_new = 'input' == el_type ? new_val : new_text;
				orig.html( just_new );

				// success
				if( response.status == 'valid' ){
					replace.hide();
					orig.show();
					return false;
				}

				// error
				replace.hide();
				orig.show();
				//alert('Problems occured while trying to execute action: '+sub_action+'!');

			}, 'json');
		};
		
		function loading( row, status ) {
			if ( 'close' == status ) {
				row.find('i.WooZoneLite-edit-inline-loading').remove();
			}
			else {
				row.prepend( $('<i class="WooZoneLite-edit-inline-loading WooZoneLite-icon-content_spinner" />') );
			}
		};

		$(document).on(
			{
				mouseenter: function(e) {
					$(this).addClass('WooZoneLite-edit-inline-hover');
				},
				mouseleave: function(e) {
					$(this).removeClass('WooZoneLite-edit-inline-hover');
				}
			},
			'.WooZoneLite-edit-inline'
		);

		$(document).on('click', '.WooZoneLite-edit-inline', function(e) {
			var that    = $(this),
				replace = that.next('.WooZoneLite-edit-inline-replace');
				
			that.hide();
			replace.show().focus();
			replace.find('input,select').focus();
		});

		function change_and_blur(e) {
			var that = $(this);
			clearTimeout(change_and_blur.timeout);
			change_and_blur.timeout = null;
			change_and_blur.timeout = setTimeout(function(){
				__();
			}, 200);
 
			function __() {
				//var that        = $(this);
				var parent      = that.parent(),
					row         = that.parents('tr').first(),
					itemid      = row.data('itemid'),
					table       = parent.data('table'),
					field       = that.prop('name').replace('WooZoneLite-edit-inline[', '').replace(']', ''),
					new_val     = that.val(),
					el_type     = e.target.tagName.toLowerCase(),
					new_text    = 'select' == el_type ? that.find('option:selected').text() : '';
	 
				make_request({
					'replace'       : parent,
					'itemid'        : itemid,
					'table'         : table,
					'field'         : field,
					'new_val'       : new_val,
					'el_type'       : el_type,
					'new_text'      : new_text 
				});
			}
		}
		// $(document).on('change', '.WooZoneLite-edit-inline-replace input, .WooZoneLite-edit-inline-replace select', change_and_blur);
		$(document).on('blur', '.WooZoneLite-edit-inline-replace input, .WooZoneLite-edit-inline-replace select', change_and_blur);
	};

	(function responsiveMenu() {
		$( document ).ready(function() {
			$('.WooZoneLite-responsive-menu').toggle(function() {
				$('.WooZoneLite-nav').show();
			}, function() {
				$('.WooZoneLite-nav').hide();
			});
		});
	})();
	
	// demo keys
	function verify_products_demo_keys() {
		console.log( 'You can no longer import products using our demo keys.' );
		window.location.reload();
	}

	// :: MISC
	var misc = {
		hasOwnProperty: function(obj, prop) {
			var proto = obj.__proto__ || obj.constructor.prototype;
			return (prop in obj) &&
			(!(prop in proto) || proto[prop] !== obj[prop]);
		}
	}

	init();
	
	return {
		'init'                  				: init,
		'makeTabs'              		: makeTabs,
		'to_ajax_loader'        	: take_over_ajax_loader,
		'to_ajax_loader_close'  : take_over_ajax_loader_close,
		'verify_products_demo_keys'	: verify_products_demo_keys
	}
})(jQuery);