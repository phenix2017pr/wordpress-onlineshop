jQuery(document).ready(function($){
	'use strict';

	//Block cart on fragment refresh
	$(document.body).on('wc_fragment_refresh',block_cart);

	//Unblock cart
	$(document.body).on('wc_fragments_refreshed wc_fragments_loaded',function(){
		content_height();
		unblock_cart();
	});

	// refresh fragment on document load
	if(!xoo_wsc_localize.added_to_cart){
		$( document.body ).trigger( 'wc_fragment_refresh' );
	}


	//Toggle Side Cart
	function toggle_sidecart(toggle_type){
		var toggle_element = $('.xoo-wsc-modal , body'),
			toggle_class   = 'xoo-wsc-active';

		if(toggle_type == 'show'){
			toggle_element.addClass(toggle_class);
		}
		else if(toggle_type == 'hide'){
			toggle_element.removeClass(toggle_class);
		}
		else{
			toggle_element.toggleClass('xoo-wsc-active');
		}
	}
	$('.xoo-wsc-basket').on('click',toggle_sidecart);


	//Set Cart content height
	function content_height(){
		var header = $('.xoo-wsc-header').outerHeight(); 
		var footer = $('.xoo-wsc-footer').outerHeight();
		var screen = $(window).height();
		$('.xoo-wsc-body').height(screen-(header+footer));
	};

	content_height();

	$(window).resize(function(){
    	content_height();
	});

	//Auto open Side Cart when item added to cart without ajax
	(function(){
		if(xoo_wsc_localize.added_to_cart){
			var toggled = false;
			$(document).on('wc_fragments_refreshed',function(){
				if(!toggled){
					setTimeout(toggle_sidecart,1,'show');
					toggled = true;
				}
			})
		}
	}());
	

	//Auto open with ajax
	if(xoo_wsc_localize.auto_open_cart == 1){
		$(document).on('added_to_cart',function(){
			setTimeout(toggle_sidecart,1,'show');
		});
	}


	//Block Cart
	function block_cart(){
		$('.xoo-wsc-updating').show();
	}

	//Unblock cart
	function unblock_cart(){
		$('.xoo-wsc-updating').hide();
	}


	//Close Side Cart
	function close_sidecart(e){
		$.each(e.target.classList,function(key,value){
			if(value != 'xoo-wsc-container' && (value == 'xoo-wsc-close' || value == 'xoo-wsc-opac' || value == 'xoo-wsc-basket' || value == 'xoo-wsc-cont')){
				$('.xoo-wsc-modal , body').removeClass('xoo-wsc-active');
			}
		})
	}

	$('body').on('click','.xoo-wsc-close , .xoo-wsc-opac , .xoo-wsc-cont',function(e){
		e.preventDefault();
		close_sidecart(e);
	});

	

	//Add to cart function
	function add_to_cart(atc_btn,product_data){

		// Trigger event.
		$( document.body ).trigger( 'adding_to_cart', [ atc_btn, product_data ] );

		$.ajax({
				url: xoo_wsc_localize.wc_ajax_url.toString().replace( '%%endpoint%%', 'xoo_wsc_add_to_cart' ),
				type: 'POST',
				data: $.param(product_data),
			    success: function(response){
			    	
			    	add_to_cart_button_check_icon(atc_btn);

					if(response.fragments){
						// Trigger event so themes can refresh other areas.
						$( document.body ).trigger( 'added_to_cart', [ response.fragments, response.cart_hash, atc_btn ] );
					}
					else if(response.error){
						show_notice('error',response.error)
					}
					else{
						console.log(response);
					}
			
			    }
			})
	}


	//Update cart
	function update_cart(cart_key,new_qty){
		
		$.ajax({
			url: xoo_wsc_localize.wc_ajax_url.toString().replace( '%%endpoint%%', 'xoo_wsc_update_cart' ),
			type: 'POST',
			data: {
				cart_key: cart_key,
				new_qty: new_qty
			},
			success: function(response){
				if(response.fragments){
					var fragments = response.fragments,
						cart_hash =  response.cart_hash;

					//Set fragments
			   		$.each( response.fragments, function( key, value ) {
						$( key ).replaceWith( value );
						$( key ).stop( true ).css( 'opacity', '1' ).unblock();
					});


					if(wc_cart_fragments_params){
				   		var cart_hash_key = wc_cart_fragments_params.ajax_url.toString() + '-wc_cart_hash';
						//Set cart hash
						sessionStorage.setItem( wc_cart_fragments_params.fragment_name, JSON.stringify( fragments ) );
						localStorage.setItem( cart_hash_key, cart_hash );
						sessionStorage.setItem( cart_hash_key, cart_hash );
					}

					$(document.body).trigger('wc_fragments_loaded');
				}
				else{
					//Print error
					show_notice('error',response.error);
				}
			}

		})
	}



	//Remove item from cart
	$(document).on('click','.xoo-wsc-remove',function(e){
		e.preventDefault();
		block_cart();
		var product_row = $(this).parents('.xoo-wsc-product');
		var cart_key = product_row.data('xoo_wsc');
		update_cart(cart_key,0);
	})

	//Add to cart on single page
	if(xoo_wsc_localize.ajax_atc == 1){
		$(document).on('submit','form.cart',function(e){
			e.preventDefault();
			block_cart();
			var form = $(this);
			var atc_btn  = form.find( 'button[type="submit"]');

			add_to_cart_button_loading_icon(atc_btn);

			var product_data = form.serializeArray();

			// if button as name add-to-cart get it and add to form
	        if( atc_btn.attr('name') && atc_btn.attr('name') == 'add-to-cart' && atc_btn.attr('value') ){
	            product_data.push({ name: 'add-to-cart', value: atc_btn.attr('value') });
	        }

	        product_data.push({name: 'action', value: 'xoo_wsc_add_to_cart'});

			add_to_cart(atc_btn,product_data);//Ajax add to cart
		})
	}

	


	//Notice Function
	function show_notice(notice_type,notice){
	 	$('.xoo-wsc-notice').html(notice).attr('class','xoo-wsc-notice').addClass('xoo-wsc-nt-'+notice_type);
	 	$('.xoo-wsc-notice-box').fadeIn('fast');
	 	clearTimeout(fadenotice);
	 	var fadenotice = setTimeout(function(){
	 		$('.xoo-wsc-notice-box').fadeOut('slow');
	 	},2000);
	};

	//Add to cart preloader
	function add_to_cart_button_loading_icon(atc_btn){
		if(xoo_wsc_localize.atc_icons != 1) return;

		if(atc_btn.find('.xoo-wsc-icon-atc').length !== 0){
			atc_btn.find('.xoo-wsc-icon-atc').attr('class','xoo-wsc-icon-spinner2 xoo-wsc-icon-atc xoo-wsc-active');
		}
		else{
			atc_btn.append('<span class="xoo-wsc-icon-spinner2 xoo-wsc-icon-atc xoo-wsc-active"></span>');
		}
	}

	//Add to cart check icon
	function add_to_cart_button_check_icon(atc_btn){
		if(xoo_wsc_localize.atc_icons != 1) return;
		// Check icon
   		atc_btn.find('.xoo-wsc-icon-atc').attr('class','xoo-wsc-icon-checkmark xoo-wsc-icon-atc');
	}
})