/*
Document   :  Asset Download
Author     :  Andrei Dinca, AA-Team http://codecanyon.net/user/AA-Team
*/

// Initialization and events code for the app
WooZoneLiteAssetDownload = (function ($) {
    "use strict";

    // public
    var debug_level = 0;
    var maincontainer = null;
    var download_buttons = null;
	var ajax_error_message = '<div id="WooZoneLite-error-popup">' +
								'<h2><i class="fa fa-exclamation-triangle"></i>Error 500: Internal server error occured!</h2>' + 
								'<p id="WooZoneLite-ajax-error-reason-title"><b>Possible reasons are:</b></p>' + 
								'<ul id="WooZoneLite-ajax-error-reasons">' +
									'<li><a target="_blank" href="http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP">Memory limit exceeded</a>(ask hosting provider to increase it)</li>' +
									'<li><a target="_blank" href="https://affiliate-program.amazon.com/gp/advertising/api/detail/faq.html">Amazon request throttled</a>(you made too many requests to Amazon in a short period of time)</li>' +
								'</ul>' +
								'<a onClick="window.location.reload();" id="WooZoneLite-refresh-button" href="#"><i class="fa fa-refresh"></i>RELOAD PAGE</a>' + 
							'</div>';

	// init function, autoload
	(function init() {
		// load the triggers
		$(document).ready(function(){
			maincontainer = $(".WooZoneLite-asset-download");
			triggers();
		});
	})();
	
	function fireAjaxError(jqXHR, textStatus){
		//console.log( jqXHR, textStatus  );
		if( jqXHR.status == 500 ) {
			jQuery('.WooZoneLite-loader').hide(); 
			jQuery('#WooZoneLite').append( ajax_error_message );
		}
	}

	function row_loading( row, status )
	{
		if( status == 'show' ){
			if( row.size() > 0 ){
				if( row.find('.WooZoneLite-row-loading-marker').size() == 0 ){
					var row_loading_box = $('<div class="WooZoneLite-row-loading-marker"><div class="WooZoneLite-row-loading"><div class="WooZoneLite-meter psp-animate" style="width:30%; margin: 10px 0px 0px 30%;"><span style="width:100%"></span></div></div></div>')
					row_loading_box.find('div.WooZoneLite-row-loading').css({
						'width': row.width(),
						'height': row.height()
					});

					row.find('td').eq(0).append(row_loading_box);
				}
				row.find('.WooZoneLite-row-loading-marker').fadeIn('fast');
			}
		}else{
			row.find('.WooZoneLite-row-loading-marker').fadeOut('fast');
		}
	}
	
	function download_asset( asset, step, step_size, callback ) 
	{
		var marker = $(".WooZoneLite-process-progress-marker"),
			tail_list = asset.parent('ul'),
			asset_id = asset.data('id'),
			next_asset = asset.next('li'),
			start_time = new Date().getTime(),
			is_last_item = false, is_first_item = false;
		
		if( typeof step == 'undefined' ){
			step = 1;
			step_size = (100 / tail_list.find('li').size());
		}
		if ( step == 1 ) {
			is_first_item = true;			
		}

		// end of lists
		if( next_asset.size() == 0 ){
			is_last_item = true;
		}
		
		// make current asset li download in progress
		asset.addClass('inprogress');
		asset.append('<div class="WooZoneLite-process-progress">Load</div>');
console.log( "asset_id: ", asset_id  );
		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		$.post(ajaxurl, {
			'action' 		: 'WooZoneLite_download_asset',
			'id'			: asset_id,
			'is_first_item'	: (is_first_item ? 'yes' : 'no'),
			'is_last_item'	: (is_last_item ? 'yes' : 'no'),
			'debug_level'	: debug_level
		}, function(response) {

            console.log( response  );

			var end_time = new Date().getTime(),
				execution_time = (end_time - start_time) / 1000 + " seconds"; // seconds
			
			// add download log new row
			$(".WooZoneLite-downoad-log ol").append( "<li>" + ( response.msg.replace("{execution_time}", execution_time) ) + "</li>" );
			
			$(".WooZoneLite-downoad-log").animate({
				scrollTop: 99999
			}, 1);
			
			// remove asset from list
			asset.remove();
			
			// made the asset tail ul smaller
			tail_list.width( tail_list.width() - 86 );
			
			// update the progress bar 
			marker.width( (step_size * step) + "%" );
			marker.find('span').text( Math.ceil(step_size * step) + "%" );
			
			// increse the number of downloaded or failed 
			var downloaded = $(".WooZoneLite-value-downloaded").eq(0),
				downloaded_value = parseInt(downloaded.text());
			
			downloaded.text( ( downloaded_value + 1 ) );
  
			// is end of list, so stop execution
			if( is_last_item == true ){
				
				// remove the tail container
				tail_list.parent('div').remove();
				$(".WooZoneLite-asset-download-lightbox .WooZoneLite-downoad-log").css('height', "+=100px");
				
				// show close button
				$("a#WooZoneLite-close-btn").show();
  
				if( typeof callback == 'function' ){
					callback();
				}

				return false;
			}
			
			if ( !is_last_item ) {
				// increse the step
				step = step + 1;
			
				// continuing the tail
				download_asset( next_asset, step, step_size, callback );
			}

		}, 'json').fail( function( jqXHR, textStatus ) {
  			fireAjaxError(jqXHR, textStatus);
		});
	}
	
	function download_asset_lightbox( prod_id, callback )
	{
		$.post(ajaxurl, {
			'action' 		: 'WooZoneLiteDownoadAssetLightbox',
			'prod_id'		: prod_id,
			'debug_level'	: debug_level
		}, function(response) {
			if( response.status == 'valid' ){
				
				$(".WooZoneLite-asset-download").append( response.html );
				
				WooZoneLite.to_ajax_loader_close();
  
				// start download each images
				download_asset( $(".WooZoneLite-asset-download").find('.WooZoneLite-images-tail').find('li').eq(0), undefined, 100, function(){
					if( typeof callback == 'function' ){
						callback();
					}
				});
			} else {
				
				WooZoneLite.to_ajax_loader_close();
				alert( response.html );
				// $(".WooZoneLite-asset-download").append( response.html );
			}
		}, 'json').fail( function( jqXHR, textStatus ) {
  			fireAjaxError(jqXHR, textStatus);
		});
	}
	
	function tail_download_all_products( download_btn )
	{
		WooZoneLite.to_ajax_loader( "Downloading all products assets" );
		
		// remove the current lightbox 
		//$(".WooZoneLite-asset-download-lightbox").remove();
		
		var prod_id = download_btn.data('prodid');
		
		download_asset_lightbox( prod_id, function(){
			
			$("tr[data-itemid='" + ( prod_id ) + "']").remove();
			download_buttons = $(".WooZoneLite-download-assets-btn");
			
			if( download_buttons.eq(0).size() > 0 ){
				tail_download_all_products( download_buttons.eq(0) );
			}
			else{
				window.location.reload();
			}
		});
	}
	
	function delete_assets_for_products( products )
	{	
		var prod_ids = [];
		products.each(function(){
			prod_ids.push( $(this).val() );
		});
		
		WooZoneLite.to_ajax_loader( "Deleting assets for products: " + prod_ids );
			
		$.post(ajaxurl, {
			'action' 		: 'WooZoneLiteDeleteAssetsProducts',
			'products'		: prod_ids,
			'debug_level'	: debug_level
		}, function(response) {
			if( response.status == 'valid' ){
				$.each( prod_ids, function( key, value ) {
					$("tr[data-itemid='" + ( value ) + "']").remove();
				});
				
				/*if( $(".WooZoneLite-table assets-download-list tbody tr").size() < 1 ){
					window.location.reload();
				} */
			}
			
			WooZoneLite.to_ajax_loader_close();
		}, 'json').fail( function( jqXHR, textStatus ) {
  			fireAjaxError(jqXHR, textStatus);
		});
	}
	
	function triggers()
	{
		maincontainer.on("click", 'a#WooZoneLite-close-btn', function(e){
			e.preventDefault();
			var that = $(this)
			
			// $(".WooZoneLite-asset-download-lightbox").remove();
		});
			
		maincontainer.on("click", 'a.WooZoneLite-download-assets-btn', function(e){
			e.preventDefault();
			var that = $(this),
				prod_id = that.data('prodid');
  
			if( e.clicked != true ){
				WooZoneLite.to_ajax_loader( "Downloading assets" );
				
				// console.log( that, prod_id );
				download_asset_lightbox( prod_id, function() {
				    $("tr[data-itemid='" + ( prod_id ) + "']").remove();
				    window.location.reload();
				} );
			}
			e.clicked = true; 
		});
		
		maincontainer.on("click", 'a.WooZoneLite-download-all-assets-btn', function(e){
			e.preventDefault();
			
			var that = $(this);
			download_buttons = $(".WooZoneLite-download-assets-btn");
			
			tail_download_all_products( download_buttons.eq(0) );
		});
		
		maincontainer.on("click", 'a.WooZoneLite-delete-all-assets-btn', function(e){
			e.preventDefault();
			
			var that = $(this),
				selected_products = maincontainer.find("input[name='delete_asset']:checked");
			
			if( selected_products.size() == 0 ){
				alert('Please select at least one product asset!');
				return false;
			}
			
			delete_assets_for_products( selected_products );
		});
		
		maincontainer.on("click", 'a.WooZoneLite-show-variations', function(e){
			e.preventDefault();
			
			var that = $(this);
			
			that.slideUp('fast');
			that.next('.WooZoneLite-variations-list').css({
				'height': '100%'
			});
		});
		
		/*
		maincontainer.on("click", 'a.WooZoneLite-button', function (e) {
			e.preventDefault();
			
			var $this = $(this), row = $this.parents('.WooZoneLite-table.assets-download-list').parents('tr').eq(0), itemid = row.data('itemid');

			row_loading(row, 'show');
			download_asset( itemid, row );
		});*/
	}

	// external usage
	return {
		"download_asset": download_asset
    }
})(jQuery);
