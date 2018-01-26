// Initialization and events code for the app
WooZoneLite = (function ($) {
    "use strict";
    
    var ajaxurl			= woozone_vars.ajax_url,
    	  lang			= woozone_vars.lang;
    var current_aff	= {};
    
    // init function, autoload
    (function init() {
    	
		// load the triggers
    	$(document).ready(function(){
			console.log( 'WooZoneLite frontend script is loaded!' );

	    	var $current_aff = $('#WooZoneLite_current_aff');
	    	if ( $current_aff.length > 0 ) {
				current_aff = $current_aff.data('current_aff');
			}
  
    		triggers();
    	});
    	
    })();


	// :: TRIGGERS
	function triggers() {
		checkout_email();

		// fix images on https/ssl
		/*(setInterval( function() {
			var $imgFound = $("img[src*='ssl-images']");
			$imgFound.each(function(){
				var that = $(this),
					src = that.attr('src');
				
				if( src.indexOf('//') == 0 ){
					
					if( src.indexOf("ssl-images") != false ){
						that.attr('src', "https:" + src );
					}
				}
			});
		}, 500 );*/
		
	};


    // :: PRODUCT COUNTRY AVAILABILITY
    var product_country_check = (function() {
        
        var DISABLED				= false; // disable this module!
        var DEBUG					= false;
    	var maincontainer			= null,
    		  mainloader				= null,
    		  product_data			= {},
    		  current_country		= {},
    		  available_countries 	= [],
    		  main_aff_id				= '',
    		  aff_ids						= [],
    		  cc_template 			= null,
    		  verify_interval			= 300, // verify requests: interval in miliseconds
    		  verify_max_steps	= 15; // verify requests: maximum number of steps
    		  

        // Test!
        function __() { console.log('__ method'); };
        
        // get public vars
        function get_vars() {
            return $.extend( {}, {
            } );
        };
        
	    // init function, autoload
	    (function init() {
	    	
	    	if ( DISABLED ) return false;

	        // load the triggers
	        $(document).ready(function(){
	            maincontainer = $(".WooZoneLite-country-check");
	            mainloader	  = maincontainer.find('.WooZoneLite-country-loader');
console.log( maincontainer );
				// main box
				if ( maincontainer.length ) {
					var product_pms = {
		            	'id'				: maincontainer.data('prodid'),
		            	'asin'				: maincontainer.data('asin'),
		            	'country'		: maincontainer.data('prodcountry'),
		            	'boxpos'		: maincontainer.data('boxpos')
					};
					position_box( product_pms );
		            build_product_box( product_pms );
				}

				// small box on minicart
				build_box_minicart( product_pms );
				
				triggers();
	        });
	    })();
	    
	    // triggers
	    function triggers() {
			maincontainer.on('click', 'li .WooZoneLite-cc_checkbox input[type="radio"]', function (e) {
				if (DEBUG) console.log( 'clicked', $(this) ); 
				//save_countries();
				save_product_country( $(this).parents('li:first').data('country') );
			});
	    };
	    
	    function build_box_minicart( pms ) {
			var newel 	  		= null,
				  tpl				= $('#WooZoneLite-cc-small-template'),
				  minicart		= $('div.kd_small-cart .cart-details ul.kd_small_cart_items'),
				  //minicart		= $('div.widget_shopping_cart_content .cart-details ul.kd_small_cart_items'),
				  is_kingdom = minicart.length; // theme: kingdom

			var cached = $('.WooZoneLite-cc-small-cached').html();
			//cached = JSON.stringify(cached);
			cached = typeof cached != 'undefined'
				? JSON && JSON.parse(cached) || $.parseJSON(cached) : cached;
			if (DEBUG) console.log( 'cached', cached );

			if ( ! tpl.length ) return false;
			if ( ! is_kingdom || ! cached.length ) return false;
 
			$.each(cached, function(index, value) {
				//console.log( index, value );
				var current = minicart.find('li').filter(function(i) {
					return value['cart_item_key'] == $(this).data('prodid');
				});
 
	    		var __ = $( tpl.html() ).clone();
	    		__.find(".WooZoneLite-cc_domain").addClass( value.product_country.replace(".", "-") ).prop('title', value.country_name);
	    		__.find(".WooZoneLite-cc_status").addClass( value.country_status_css ).prop('title', value.country_status_text);

				//console.log( current.find('.kd_cart_item-details'), __ ); 
	    		current.find('.kd_cart_item-details').append( __ );
			});
	    };
	    
	    // position product box
	    function position_box( pms ) {
	    	console.log(pms );
			var newel 	  		= null,
				  is_kingdom = $('div.product#product-' + pms.id + ' > div.row:first .kd_description').length; // theme: kingdom

			if ( 'before_add_to_cart' == pms.boxpos ) {
				// theme: kingdom
				console.log(is_kingdom );
				if ( is_kingdom ) {
					newel = $('div.product#product-' + pms.id + ' > div.row:first .kd_description .cart');
					maincontainer.insertBefore( newel ).show();
				}
			}
			else if ( 'before_title_and_thumb' == pms.boxpos ) {
				newel = $('div.product#product-' + pms.id);
				if ( newel.length )
					maincontainer.prependTo( newel ).show();
				else {
					// theme: kingdom
					if ( is_kingdom ) {
						newel = $('div.product#product-' + pms.id + ' > div.row:first');
						maincontainer.insertBefore( newel ).show();
					}
				}
			}
			else if ( 'before_woocommerce_tabs' == pms.boxpos ) {
				newel = $('div.product#product-' + pms.id + ' div.woocommerce-tabs.wc-tabs-wrapper');
				if ( newel.length )
					maincontainer.insertBefore( newel ).addClass('WooZoneLite-boxpos-before_woocommerce_tabs').show();
				else {
					// theme: kingdom
					if ( is_kingdom ) {
						newel = $('div.product#product-' + pms.id + ' > div.row:first');
						maincontainer.insertAfter( newel ).show();
					}
				}
			}
	    };
	    
	    // init product
	    function build_product_box( pms ) {
            //var pms		= typeof pms == 'object' ? pms : {},
            //	  id			= misc.hasOwnProperty(pms, 'id') ? pms.id : null,
            //	  asin			= misc.hasOwnProperty(pms, 'asin') ? pms.asin : null,
            //	  country	= misc.hasOwnProperty(pms, 'country') ? pms.country : null;

	    	set_product_data( pms );
	    	build_countries_list();
	    	if (DEBUG) console.log( product_data, available_countries );

			load_template();
			make_requests();
	    };

	    // build countries list
	    function build_countries_list() {
	    	available_countries 	= [];

			// aff ids json parse
			var cached_aff_ids = maincontainer.find('.WooZoneLite-country-affid').html();
			//cached_aff_ids = JSON.stringify(cached_aff_ids);
			cached_aff_ids = typeof cached_aff_ids != 'undefined'
				? JSON && JSON.parse(cached_aff_ids) || $.parseJSON(cached_aff_ids) : cached_aff_ids;
			//if (DEBUG) console.log( cached_aff_ids );
			if ( cached_aff_ids && misc.hasOwnProperty(cached_aff_ids, 'main_aff_id') )
				main_aff_id = cached_aff_ids.main_aff_id;
			if ( cached_aff_ids && misc.hasOwnProperty(cached_aff_ids, 'aff_ids') )
				aff_ids = cached_aff_ids.aff_ids;

            // countries json parse
			var cached_countries = maincontainer.find('.WooZoneLite-country-cached').html();
			//cached_countries = JSON.stringify(cached_countries);
			cached_countries = typeof cached_countries != 'undefined'
				? JSON && JSON.parse(cached_countries) || $.parseJSON(cached_countries) : cached_countries;
			//if (DEBUG) console.log( cached_countries );
			
			$.each( cached_countries, function( index, value ){
				var __ = {
					'domain'		: value.domain,
					'name'			: value.name
				};
				if ( misc.hasOwnProperty( value, 'available' ) ) {
					__['available'] = value.available;
				}
				add_country( __ );
			});
			return false;
			// STOPPED HERE 

			add_country({
				"domain": 'com',
				"name": "United States"
			});
		
			add_country({
				"domain": 'co.uk',
				"name": "United Kingdom"
			});
		
			add_country({
				"domain": 'de',
				"name": "Deutschland"
			});
		
			add_country({
				"domain": 'fr',
				"name": "France"
			});
		
			add_country({
				"domain": 'co.jp',
				"name": "Japan"
			});
		
			add_country({
				"domain": 'ca',
				"name": "Canada"
			});
			
			add_country({
				"domain": 'cn',
				"name": "China"
			});
		
			add_country({
				"domain": 'in',
				"name": "India"
			});
		
			add_country({
				"domain": 'it',
				"name": "Italia"
			});
		
			add_country({
				"domain": 'es',
				"name": "España"
			});
		
			add_country({
				"domain": 'com.mx',
				"name": "Mexico"
			});
		
			add_country({
				"domain": 'com.br',
				"name": "Brazil"
			});
	    };

	    // add country to countries list
	    function add_country( new_country, where ) {
	    	var where = where || 'available';
	    	
	    	if ( 'available' == where )
	    		available_countries.push( new_country );
	    };
	
		// per country template - ul.li (to build the final box with all available countries)
	    function load_template() {
	    	cc_template = maincontainer.find("#WooZoneLite-cc-template").html();
	    };
	
		// set product data
	    function set_product_data( pms ) {
	    	product_data = pms;
	    };
	    
		// product exists on amazon shops
	    function product_exist( elm, domain ) {
	    	var jqxhr = $.ajax({
		        crossDomain: true,
		        type:"GET",
		        processData: false,
		        contentType: "application/json; charset=utf-8",
		        async: true,
				converters: {"* text": window.String, "text html": true, "text json": true, "text xml": jQuery.parseXML},
		        url: build_product_link( domain, product_data['asin'] ),
		        data: {},
		        dataType: "jsonp",                
		        jsonp: false,
		        ///*
		        complete: function (XMLHttpRequest, textStatus) {
		        	if (DEBUG) console.log( XMLHttpRequest, textStatus );
		        	if ( 404 == XMLHttpRequest.status ) {
		        		add_country_status_html( elm, 0 );
		        		add_country_status( domain, 0 );
		        	} else {
		        		add_country_status_html( elm, 1 );
		        		add_country_status( domain, 1 );
		        	}
				}
				//*/
		    });
  			/*
		    jqxhr.always(function( XMLHttpRequest ) {
    			if (DEBUG) console.log( domain, XMLHttpRequest );
		        if (DEBUG) console.log( domain , "product verified" );
  			});
		    jqxhr.done(function( data, textStatus, jqXHR ) {
    			if (DEBUG) console.log( domain, data, textStatus, jqXHR );
		        if (DEBUG) console.log( domain , "product valid" );
  			});
		    jqxhr.fail(function( XMLHttpRequest, textStatus, errorThrown ) {
    			if (DEBUG) console.log( domain, XMLHttpRequest, textStatus, errorThrown );
		        if (DEBUG) console.log( domain , "product not found" );
  			});
  			*/
	    };

		// make requests to amazon shops
	    function make_requests() {
	    	var pending = 0;

	    	$.each( available_countries, function( key, value ) {
	    		var __ = $(cc_template).clone();

	    		__.data('country', value.domain);
	    		__.find(".WooZoneLite-cc_domain").addClass( value.domain.replace(".", "-") ).prop('title', value.name);
	    		__.find(".WooZoneLite-cc_name > a").text( value.name ).attr('href', build_product_link( value.domain, product_data['asin'], true ));
	    		
	    		var _countryflag_aslink = __.find(".WooZoneLite-cc_domain > a");
	    		if ( _countryflag_aslink.length ) { // add link to country flag
	    			_countryflag_aslink.attr('href', build_product_link( value.domain, product_data['asin'], true ));
	    		}
	    		
	    		// default country
	    		if ( value.domain == product_data['country'] ) {
	    			__.find('.WooZoneLite-cc_checkbox input[type=radio]').prop('checked', true);
	    			current_country['elm'] = __.find('.WooZoneLite-cc_checkbox input[type=radio]');
	    			current_country['country'] = value.domain;
	    		}

	    		maincontainer.append( __ );

				// cached
				if ( misc.hasOwnProperty( value, 'available' ) ) {
					add_country_status_html( __, value.available );
				}
				else {
					pending++;
	    			product_exist( __, value.domain );					
				}
	    	} );

			// verify all product amazon country verify requests are finished
	    	if ( pending )
		    	verify_requests();
	    };
	    
	    // verify status of requests to amazon shops
	    function verify_requests() {
	    	var timer 		= null,
	    		  contor		= 0;

	    	function _verify() {
	    		var pending = 0,
	    			  is_done = contor >= verify_max_steps;

		    	$.each( available_countries, function( key, value ) {
					if ( ! misc.hasOwnProperty( value, 'available' ) )
						pending++;
		    	} );
	    		if (DEBUG) console.log( contor, pending );

	    		if ( ! pending || is_done ) {
            		clearTimeout( timer );
            		timer = null;

            		if ( pending && is_done ) {
				    	$.each( available_countries, function( key, value ) {
							if ( ! misc.hasOwnProperty( value, 'available' ) ) {
								var $current = maincontainer.find('li').filter(function(i) {
									return $(this).data('country') == value.domain;
								});

		        				add_country_status_html( $current, 0 );
								add_country_status( value.domain, 0 );
							}
				    	} );
            		}

					save_countries();

            		return false;
	    		}

		    	contor++;
		    	
		    	timer = setTimeout( function() {
		    		_verify();
		    	}, verify_interval );
	    	};

		    timer = setTimeout( function() {
		    	_verify();
		    }, verify_interval );
	    };
	    
	    // add country status: available or not
	    function add_country_status( country, status ) {
	    	var index = get_available_country_index( country );
	    	if ( index <= -1 ) return false;
			//if (DEBUG) console.log( index, available_countries );
	    	available_countries[ index ]['available'] = status;
	    	return true;
	    };
	    
	    function add_country_status_html( elm, status ) {
			if ( status ) {
				if (DEBUG) console.log( elm.data('country') , "product valid" );
				elm.find(".WooZoneLite-cc-status").html( "<span class='WooZoneLite-status-available'>" + lang.available_yes + "</span" );
			} else {
				if (DEBUG) console.log( elm.data('country') , "product not found" );
				elm.find(".WooZoneLite-cc-status").html( "<span class='WooZoneLite-status-unavailable'>" + lang.available_no + "</span" );
			}
	   };
	    
	    // get available country index from array of 'available_countries' based on country domain
	    function get_available_country_index( country ) {
	    	var index = -1;
			$.each( available_countries, function( key, value ) {
				if ( country == value.domain ) {
					index = key;
					return false;
				}
			} );
			return index;
	    };

		// save countries per product
		function save_countries() {
            var countries = JSON.stringify( available_countries );
            var data = {
				action					: 'WooZoneLite_frontend',
				sub_action			: 'save_countries',
				product_id				: product_data['id'],
				product_country	: current_country['country'],
				countries				: countries
			};
			if (DEBUG) console.log( data );
			
			loading( 'show', lang.saving );
			$.post(ajaxurl, data, function(response) {

				if ( misc.hasOwnProperty(response, 'status') ) {}
				loading( 'close' );
			}, 'json')
			.fail(function() {})
			.done(function() {})
			.always(function() {});
		};
		
		// save countries per product
		function save_product_country( country ) {
            var data = {
				action					: 'WooZoneLite_frontend',
				sub_action			: 'save_product_country',
				product_id				: product_data['id'],
				product_country	: country || current_country['country']
			};
			if (DEBUG) console.log( data );
			
			loading( 'show', lang.saving );
			$.post(ajaxurl, data, function(response) {

				if ( misc.hasOwnProperty(response, 'status') ) {}
				loading( 'close' );
			}, 'json')
			.fail(function() {})
			.done(function() {})
			.always(function() {});
		};
		
		// build product link for amazon country shop
		function build_product_link( domain, asin, add_tag ) {
			var addtag 	= add_tag || false,
				  link 			=  "https://www.amazon." + ( domain ) + "/dp/" + asin;
			
			if ( addtag )
				link += '/?tag=' + get_aff_id( domain );
			return link;
		};
		
		function get_aff_id( country ) {
			var aff_id = main_aff_id;

			$.each(aff_ids, function( index, value ) {
				if ( value.country == country ) {
					aff_id = value.aff_id;
					return false;
				}
			});
			return aff_id;
		};
		
        // Loading
        function loading( status, msg ) {
        	var msg = msg || '';

			if ( '' == msg && 'show' == status )
				msg = lang.loading;

			if ( '' != msg )
				mainloader.find('.WooZoneLite-country-loader-text').html( msg );

        	if ( 'show' == status )
        		mainloader.fadeIn('fast');
        	else
        		mainloader.fadeOut('fast');
		};

        // external usage
        return {
            // attributes
            'v'                     		: get_vars,
            
            // methods
            '__'                    		: __,
			'add_country'			: add_country,
			'set_product_data'	: set_product_data,
			'make_requests'		: make_requests
        };
	})();
	
	
    // :: AMAZON SHOPS CHECKOUT ON CART PAGE
    var country_shop_checkout = (function() {
        
        var DISABLED				= false; // disable this module!
        var DEBUG					= false;
    	var maincontainer			= null,
    		  shops						= [],
    		  shops_status			= {
    		  	'success'		: [],
    		  	'cancel'			: []
    		  };
    		  

        // Test!
        function __() { console.log('__ method'); };
        
        // get public vars
        function get_vars() {
            return $.extend( {}, {
            } );
        };
        
	    // init function, autoload
	    (function init() {
	    	
	    	if ( DISABLED ) return false;

	        // load the triggers
	        $(document).ready(function(){
	            maincontainer = $(".WooZoneLite-cart-checkout");

				// main box
				if ( maincontainer.length ) {
					maincontainer.find('ul li').each(function(index, value) {
						var $this 		= $(this),
							  country	= $this.data('domain');

						shops.push( country );
					});
					if (DEBUG) console.log( shops ); 	
				}

				triggers();
	        });
	    })();
	    
	    // triggers
	    function triggers() {
	    	// checkout form
			maincontainer.on('submit', 'li .WooZoneLite-cc_checkout form', function (e) {
				e.preventDefault();
				if (DEBUG) console.log( 'form to submit!' );
				
				var form 			= this,
					  $form			= $(form),
					  $li 				= $form.parents('li:first'),
					  country 		= $li.data('domain');

				//console.log( form, $form );
				if ( $.inArray( country, shops_status.success) <= -1 && $.inArray( country, shops_status.cancel) <= -1 )
					shops_status.success.push( country );
				//console.log( shops_status );
				set_status_html( $li, 1 );

				form.submit();

				return true;
			});

			// cancel
			maincontainer.on('click', 'li .WooZoneLite-cc_checkout input[type="button"].cancel', function (e) {
				e.preventDefault();
				if (DEBUG) console.log( 'form canceled!' );
				
				var $form 		= $(this).parents('form:first'),
					  $li 				= $form.parents('li:first'),
					  country 		= $li.data('domain');
 
				//console.log( $form );
				if ( $.inArray( country, shops_status.success) <= -1 && $.inArray( country, shops_status.cancel) <= -1 )
					shops_status.cancel.push( country );
				//console.log( shops_status );
				set_status_html( $li, 0 );

				return true;
			});
	    };
	    
	    function allow_checkout() {
	    	var __ = [].concat( shops_status.success, shops_status.cancel );
	    	if (DEBUG) console.log( __, __.length == shops.length );
	    	return __.length == shops.length;
	    };
	    
	    function set_status_html( elm, status ) {
	    	var text 			= status ? lang.amzcart_checkout : lang.amzcart_cancel,
	    		  css_class 	= status ? 'success' : 'cancel';
			elm.find('.WooZoneLite-cc_status').removeClass('success cancel').addClass( css_class ).text( text );
	    };
	    
	    function set_msg_html( status, text ) {
	    	var elm 			= maincontainer.find('.WooZoneLite-cart-msg'),
	    		  css_class 	= status ? 'success' : 'cancel';
			//elm.prepend( $('<div />').removeClass('success cancel').addClass( css_class ).text( text ) );
			var __ = $('<div />').removeClass('success cancel').addClass( css_class ).text( text );
			elm.html( __ );
	    };
    	
        // external usage
        return {
            // attributes
            'v'                     			: get_vars,
            
            // methods
            '__'                    			: __,
            'allow_checkout'			: allow_checkout,
            'set_msg_html'				: set_msg_html
        };
	})();


    // :: CROSS SELL BOX
    var cross_sell_box = (function() {
        
        var DISABLED				= false; // disable this module!
        var DEBUG					= false;
    	var maincontainer			= null,
    		  mainloader				= null,
    		  multiple_asins 			= [];
    		  

        // Test!
        function __() { console.log('__ method'); };
        
        // get public vars
        function get_vars() {
            return $.extend( {}, {
            } );
        };
        
	    // init function, autoload
	    (function init() {
	    	
	    	if ( DISABLED ) return false;

	        // load the triggers
	        $(document).ready(function(){
	            maincontainer = $("body");
	            mainloader	  = maincontainer.find('.WooZoneLite-cross-sell-loader');

				triggers();

				if ( maincontainer.find('.main-cross-sell').length ) {
					load_box();
				}
	        });
	    })();
	    
	    // load box
	    function load_box() {
	    	var box 	= maincontainer.find('.main-cross-sell'),
	    		  asin		= maincontainer.find('.main-cross-sell').data('asin'),
	    		  boxd 	= maincontainer.find('.WooZoneLite-cross-sell-debug');

            var data = {
				action					: 'WooZoneLite_frontend',
				sub_action			: 'load_cross_sell',
				asin						: asin
			};
			if (DEBUG) console.log( data );

			loading( 'show', lang.load_cross_sell_box );

			$.post(ajaxurl, data, function(response) {

				if ( misc.hasOwnProperty(response, 'status') ) {
					box.html( response.html ).css({
						'min-height' : 'initial'
					});
					if ( boxd.length ) {
						boxd.html( response.debug ); boxd.show();
					}
				}
				loading( 'close' );
			}, 'json')
			.fail(function() {})
			.done(function() {})
			.always(function() {});
	    };
	    
	    // empty cache
	    function empty_cache( that ) {
	    	var box 	= maincontainer.find('.main-cross-sell'),
	    		  asin		= maincontainer.find('.main-cross-sell').data('asin'),
	    		  boxd 	= maincontainer.find('.WooZoneLite-cross-sell-debug');
	    		  
            var data = {
				action					: 'WooZoneLite_frontend',
				sub_action			: 'cross_sell_empty_cache',
				asin						: asin
			};
			if (DEBUG) console.log( data );

			that.prop('disabled', true).after( lang.saving );

			$.post(ajaxurl, data, function(response) {

				if ( misc.hasOwnProperty(response, 'status') ) {
					window.location.reload();
				}
				loading( 'close' );
			}, 'json')
			.fail(function() {})
			.done(function() {})
			.always(function() {});
	    };
	    
	    // triggers
	    function triggers() {

			// debug mode
		    $("body").on("click", '.WooZoneLite-cross-sell-debug button', function(e) {
		    	empty_cache( $(this) );
		    });

			// selection checkboxes
		    $("body").on("change", '.cross-sell input', function(e) {
		        var that				= $(this),
		            row				= that.parents('li').eq(0),
		            asin				= that.val(),
		            the_thumb	= $('#cross-sell-thumb-' + asin).parents('li'),
		            buy_block		= $('div.cross-sell-buy-btn');

		        var price_dec_sep = $('.cross-sell .cross-sell-price-sep').data('price_dec_sep');

		        buy_block.fadeOut('fast');
		        if( that.is(':checked') ){
		            row.attr('class', '');
		            the_thumb.fadeIn('fast');
		        }
		        else{
		            row.attr('class', '');
		            row.addClass('cross-sale-uncheck');

		            the_thumb.fadeOut('fast');
		        }

		        var _total_price 		= 0,
		        	  remaining_items	= 0;

		        $(".cross-sell ul.cross-sell-items li:not(.cross-sale-uncheck)").each(function(){
		            var that    = $(this);
		            var price   = that.find('.cross-sell-item-price').data('item_price'); //that.find('.cross-sell-item-price').text().replace(/[^-\d\.,]/g, '')

		            _total_price = _total_price + parseFloat(price);

		            remaining_items++;
		        });

		        if ( _total_price > 0 ) {
		            _total_price = _total_price.toFixed(2);
		            if ( ',' == price_dec_sep ) {
		                _total_price = numberFormat( _total_price );
		            }
		            $("#feq-products").show();
		            var curr_price = $("#cross-sell-buying-price").text().match(/[\d.]+/);

		            $("#cross-sell-buying-price").text( $("#cross-sell-buying-price").text().replace(curr_price, _total_price) );
		        }
		        else{
		            $("#feq-products").fadeOut('fast');
		            var curr_price = $("#cross-sell-buying-price").text().match(/[\d.]+/);
		            $("#cross-sell-buying-price").text( $("#cross-sell-buying-price").text().replace(curr_price, _total_price) );
		        }

		        buy_block.fadeIn('fast');
		    });

			// add to cart / checkout button
		    $("body").on("click", '.cross-sell a#cross-sell-add-to-cart', function(e) {
		        e.preventDefault();

		        var that = $(this);

		        // get all selected products
		        var totals_checked  = $(".cross-sell ul.cross-sell-items li:not(.cross-sale-uncheck)").size();
		        $(".cross-sell ul.cross-sell-items li:not(.cross-sale-uncheck)").each(function() {
		            var that		= $(this),
		            	  q			= 1,
		            	  asin		= that.find('input').val();

		            multiple_asins.push(asin);
		        });

		        if( totals_checked > 0 ){
		        	var newurl = that.attr('href') + '?amz_cross_sell=yes&asins=' + multiple_asins.join(',');

		            // window.location.href seems to have inconstant behavior in some browsers & also window.location directly not working in versions of IE
		            // didn't work in my chrome/jimmy
		            //window.location = newurl;

		            $(location).attr('href', newurl);
		        }
		    });
	    };
	    
        // Loading
        function loading( status, msg ) {
        	var msg = msg || '';

			if ( '' == msg && 'show' == status )
				msg = lang.loading;

			if ( '' != msg )
				mainloader.find('.WooZoneLite-cross-sell-loader-text').html( msg );

        	if ( 'show' == status )
        		mainloader.fadeIn('fast');
        	else
        		mainloader.fadeOut('fast');
		};

	    function numberWithCommas(number) {
	        var parts = number.toString().split(".");
	        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
	        return parts.join(".");
	    };

	    function numberFormat(number) {
	        return number.replace(',', '###').replace('.', ',').replace('###', '.');
	    };

        // external usage
        return {
            // attributes
            'v'                     		: get_vars,

            // methods
            '__'                    		: __
		};
	})();
	    
	    
	// :: OTHERS
    // custom user email collecting before redirect to amazon
    function checkout_email() {
		if ( ! $('.cart_totals').length ) return false;

   		var woozone_email_wrapper 		= $('.woozone_email_wrapper'),
   			  woozone_checkout_email 		= $('#woozone_checkout_email'),
   			  woozone_email_mandatory 	= $('#woozone_checkout_email_required');

		// checkout email is active
  
		if( woozone_checkout_email.length > 0 ) {
	    	woozone_email_wrapper.insertBefore( $('.wc-proceed-to-checkout') );

   			var checkout_btn = $('.wc-proceed-to-checkout .checkout-button'),
   				  checkout_link = checkout_btn.attr('href');
 
   			if( woozone_email_mandatory.length > 0 && woozone_email_mandatory.val() == '1' ) {
    			checkout_btn.addClass('disabled');
    			checkout_btn.attr('href', '#amz_checkout_email');
    			//console.log( checkout_btn );
    			//alert('E-mail field is mandatory!');
    		}

   			woozone_email_wrapper.on('keyup', woozone_checkout_email, function(e) {
				var woozone_validate_email = /([A-Z0-9a-z_-][^@])+?@[^$#<>?]+?\.[\w]{2,4}/.test(woozone_checkout_email.val());
	    				 
	    		 if( woozone_validate_email ) {

   				 	if( woozone_email_mandatory.length > 0 && woozone_email_mandatory.val() == '1' ) {
    				 	checkout_btn.removeClass('disabled');
    				 	checkout_btn.attr('href', checkout_link);
    				 }

    				 woozone_checkout_email.css({'border': '1px solid #d1d1d1'});

   				 } else {

   				 	if( woozone_email_mandatory.length > 0 && woozone_email_mandatory.val() == '1' ) {
    				 	checkout_btn.addClass('disabled');
    					checkout_btn.attr('href', '#amz_checkout_email');
    				}

    				woozone_checkout_email.css({'border': '1px solid red'});

				}
			});
		}

		$('.wc-proceed-to-checkout').on('click', '.checkout-button', function(e) {
			// checkout email is active
			if ( woozone_checkout_email.length > 0 ) {
				if( woozone_email_mandatory.length > 0 && woozone_email_mandatory.val() == '1' ) {
					if ( $(this).hasClass('disabled') ) {
						e.preventDefault();
						return false;
					}
				}

				if ( ! country_shop_checkout.allow_checkout() ) {
					//console.log( 'checkout: You must check or cancel all amazon shops!' );
					country_shop_checkout.set_msg_html( false, lang.amzcart_cancel_msg );
					return false;
				}
				else {
					//console.log( 'checkout: all good.' );
					country_shop_checkout.set_msg_html( true, lang.amzcart_checkout_msg );
				}
				//return false; // uncomment to debug

				if ( woozone_checkout_email.val() != '' ) {
					jQuery.post(woozone_vars.ajax_url, 
		    		{
						'action': 'WooZoneLite_before_user_checkout',
		    			'_nonce': $('#woozone_checkout_email_nonce').val(),
		    			'email': woozone_checkout_email.val()
		    		}, function(data, textStatus) {
						if ( (textStatus === 'success') || (textStatus === 'email_exists') ) {
							//window.location.href = woozone_vars.checkout_url;
							$(this).prop('href', woozone_vars.checkout_url); // to be sure it does the action!
						} else {
							alert( textStatus );
						}
					});
				}
			}
			// checkout email is NOT active
			else {
				if ( ! country_shop_checkout.allow_checkout() ) {
					//console.log( 'checkout: You must check or cancel all amazon shops!' );
					country_shop_checkout.set_msg_html( false, lang.amzcart_cancel_msg );
					return false;
				}
				else {
					//console.log( 'checkout: all good.' );
					country_shop_checkout.set_msg_html( true, lang.amzcart_checkout_msg );
				}
				//return false; // uncomment to debug
				
				// update feb 2017
				// no need to do anything, as the current button action will go to checkout and do the reload itself
				$(this).prop('href', woozone_vars.checkout_url); // to be sure it does the action!

				// dageorge: i've commented this as I don't know why an ajax request is needed when email checkout is not active! (feedback from a client)
				//jQuery.post(woozone_vars.ajax_url, 
	    		//{
				//	'action': 'WooZoneLite_before_user_checkout',
	    		//}, function(data, textStatus) {
				//	if ( (textStatus === 'success') ) {
				//		window.location.href = woozone_vars.checkout_url;
				//	}
				//});
	    		
	    	}
		});
    };
    
    // open popup
    function popup(url, title, params) {
		//url = 'http://www.amazon' + current_aff['user_country']['website'] + url;
		window.open(url, title, params);
    };


    // :: MISC
    var misc = {
    
        hasOwnProperty: function(obj, prop) {
            var proto = obj.__proto__ || obj.constructor.prototype;
            return (prop in obj) &&
            (!(prop in proto) || proto[prop] !== obj[prop]);
        }
    };

	// external usage
   	return {
   		'popup'				: popup
   	}
})(jQuery);