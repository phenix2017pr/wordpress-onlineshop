/*
Document   :  Insane Import
Author     :  Andrei Dinca, AA-Team http://codecanyon.net/user/AA-Team
*/

// Initialization and events code for the app
WooZoneLiteInsaneMode = (function($) {
	"use strict";

	// public
    var debug_level                     = 0,
        maincontainer                   = null,
        loading                         = null,
        background_loading_container    = null,
        containers                      = null,
        lang                            = null,
        default_import_settings         = null,
        general_settings                = null,                        
        box_queue_status_default        = 'close';

	// init function, autoload
	(function init() {
		// load the triggers
		$(document).ready(function() {

			maincontainer = $("#WooZoneLite-insane-import");
			loading = maincontainer.find("#WooZoneLite-main-loading");
			background_loading_container = maincontainer.find(".WooZoneLite-insane-work-in-progress");
			containers = {
			    loadprods:      {
			        wrap         : maincontainer.find('#WooZoneLite-content-scroll'),
			        search       : maincontainer.find('#WooZoneLite-content-search'),
			        grab         : maincontainer.find('#WooZoneLite-content-grab'),
			        bulk         : maincontainer.find('#WooZoneLite-content-bulk'),
			        status       : maincontainer.find('#WooZoneLite-loadprods-status')
			    },
			    loadstatus:     {
			        wrap         : maincontainer.find('#WooZoneLite-insane-loadstatus')
			    },
			    queueprods:     {
			        wrap         : maincontainer.find('#WooZoneLite-queued-products'),
			        results      : maincontainer.find('#WooZoneLite-queued-results-stats'),
			        export       : maincontainer.find('#WooZoneLite-export-asins'),
			        prods        : null,
			        check_all    : maincontainer.find('.WooZoneLite-check-all')
			    },
			    importprods:    {
			        wrap         : maincontainer.find('#WooZoneLite-insane-import-parameters'),
			        estimate     : null,
			        time         : null,
                    logo         : null,
                    screen_tmp   : maincontainer.find('#WooZoneLite-import-screen .WooZoneLite-iip-lightbox'),
                    screen_tmp_auto   : maincontainer.find('#WooZoneLite-import-screen-auto .WooZoneLite-iip-lightbox')
			    },
                importstatus:   {
                    wrap         : maincontainer.find('#WooZoneLite-insane-importstatus')
                },
			};
			containers.queueprods.prods      = containers.queueprods.wrap.find('.WZC-products-scroll-cointainer');
			containers.importprods.estimate  = containers.importprods.wrap.find('.WooZoneLite-insane-import-estimate');
			containers.importprods.time      = containers.importprods.estimate.find('.WooZoneLite-insane-import-ETA');
            containers.importprods.logo      = containers.importprods.estimate.find('.WooZoneLite-insane-import-ETA-logo');


			// language messages
            lang = maincontainer.find('#WooZoneLite-lang-translation').html();
            //lang = JSON.stringify(lang);
            lang = typeof lang != 'undefined'
                ? JSON && JSON.parse(lang) || $.parseJSON(lang) : lang;
            
            // import settings - default
            default_import_settings = maincontainer.find('#WooZoneLite-import-settings').html();
            //default_import_settings = JSON.stringify(default_import_settings);
            default_import_settings = typeof default_import_settings != 'undefined'
                ? JSON && JSON.parse(default_import_settings) || $.parseJSON(default_import_settings) : default_import_settings;
                
            // general settings
            general_settings = maincontainer.find('#WooZoneLite-general-settings').html();
            //general_settings = JSON.stringify(general_settings);
            general_settings = typeof general_settings != 'undefined'
                ? JSON && JSON.parse(general_settings) || $.parseJSON(general_settings) : general_settings;

            //background_loading( "some msg", 'show' ); // ajax loading

			triggers();
		});
	})();
	

    // :: SPEEDOMETER interface
	function drawTextAlongArc(context, str, centerX, centerY, radius, angle)
	{
		var numbers = [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 70, 85, 100];
        var len = numbers.length, s, cc = 5;
        context.save();
        
        context.translate(centerX, centerY);
        context.rotate(-1.49 * angle / 2);
        context.rotate(-1 * (angle / 10) / 2);
        
        for(var n = 0; n < len; n++) {
        	
        	context.rotate(angle / 9.15);
        	context.save();
        	context.translate(10, -1 * radius);
        	context.fillText( "|", 0, 21 );
        	context.fillText( numbers[n], 0, 42 );
        	context.restore();
        }
        context.restore();
    }
      
	function draw_tick_marks(options)
	{
		var canvas = document.getElementById('speedometer-markers'), 
	        angle = Math.PI * 1.2,
	        radius = 125;
	    if ( !canvas ) return false;
        var context = canvas.getContext('2d'),
            centerX = canvas.width / 2,
            centerY = canvas.height / 2;
	      
    	context.font = '12pt Calibri';
    	context.textAlign = 'center';
    	context.fillStyle = 'rgb(189, 195, 199)';
    	context.strokeStyle = 'rgb(189, 195, 199)';
    	context.lineWidth = 1;
    	drawTextAlongArc(context, '', centerX, centerY, radius, angle);
	
	    // draw circle lines
	    context.arc(centerX, centerY, radius - 10, 0, 2 * Math.PI, false);
	    context.stroke();
	    
	    context.beginPath();
	    context.rect(47, canvas.height - 23, 140, 100);
	    context.fillStyle = 'white';
	    context.fill();
      	
      	context.beginPath();
	    context.rect(167, canvas.height - 26, 40, 40);
	    context.fillStyle = 'white';
	    context.fill();
	}
	
	function change_speedometer_value( new_value )
	{
		var speedometer       = maincontainer.find("#WooZoneLite-speedometer"),
			needle 		      = speedometer.find("#speedometer-needle"),
			zero_pos 	      = -233,
			max_real_pos      = 51,
			max_insane_pos    = 71,
			one_grade         = 4.7,
			increase          = 0,
			new_pos           = zero_pos;

        new_value = new_value <= 5 ? 0 : parseInt( new_value - 5 );

        if ( new_value <= 45 ) {
            increase = new_value * one_grade;
        } else if ( new_value <= 65 ) {
            increase = 45 * one_grade + ( new_value - 45 ) * one_grade / 4;
        } else if ( new_value <= 95 ) {
            increase = 45 * one_grade + 20 * one_grade / 4 + ( new_value - 65 ) * one_grade / 3; // 20 = 65 - 45
        } else {
            increase = -zero_pos + max_insane_pos;
        }
        increase = parseInt( increase );
        
        new_pos = parseInt( zero_pos + increase );
        new_pos = new_pos > max_real_pos ? max_insane_pos : new_pos;
		
		// products per minute
		speedometer.find('#WooZoneLite-speedometer-name i').html( new_value );

		needle.css({
			'transform': 'rotate(' + new_pos + 'deg)'
		});
	}
	
	
	// :: QUEUE PRODUCTS interface
    var queueprod = (function() {
       
        function set_queue_width() {
            var w = ( containers.queueprods.prods.find("ul li").eq(0).outerWidth() + 10 ) // + 30
                * containers.queueprods.prods.find("ul li").size();
 
            containers.queueprods.wrap.data("list_width", w);
 
            // current box status
            var list = containers.queueprods.prods.find("ul"),
                status = containers.queueprods.wrap.hasClass('WooZoneLite-open') ? 'open' : 'close';
            if ( status == 'close' ) {
                list.width( containers.queueprods.wrap.data("list_width") );
            }
        };

        function create_products_scroll( status ){
            containers.queueprods.prods.width( $("#WooZoneLite-content").width() - 70 );
            set_queue_width();
            view_products_list( status, true );
        };

        function view_products_list( status, is_init )
        {
            var list = containers.queueprods.prods.find("ul"),
                btn = maincontainer.find("#WooZoneLite-expand-all");
            var is_init = is_init || false;
            
            // toggle box status
            if ( status == 'toggle' ) {
                status = containers.queueprods.wrap.hasClass('WooZoneLite-open') ? 'close' : 'open';
            }
    
            if ( status == 'close' ) {
                //console.log( 'close' );
                list.width( containers.queueprods.wrap.data("list_width") );
                containers.queueprods.wrap.removeClass('WooZoneLite-open').addClass('WooZoneLite-close');
                
                // change button text
                btn.find("span").eq(1).hide();
                btn.find("span").eq(0).show();
    
            } else {
                //console.log( 'open' );
                if ( !is_init ) { // if default is open, we don't want to overwrite the ul list data saved width with the 100% value!
                    containers.queueprods.wrap.data("list_width", list.width());
                }
                list.width( "100%" );
                containers.queueprods.wrap.removeClass('WooZoneLite-close').addClass('WooZoneLite-open');
                
                // change button text
                btn.find("span").eq(0).hide();
                btn.find("span").eq(1).show();
            }
            return;
        };
        
        function check_all() {
            /*containers.queueprods.check_all.on('click', "input[type='checkbox'], label", function () {
                var that = $(this), elType = that.prop('tagName').toUpperCase();
                if ( elType == 'LABEL' ) {
                    that.parent().find('input[type="checkbox"]').trigger('click');
                    return false;
                }
            });*/
            var check_all   = containers.queueprods.check_all.find( 'input[type="checkbox"]' );
                
            // check all click
            containers.queueprods.check_all.on('click', 'input[type="checkbox"]', function (e) {
                var prods           = containers.queueprods.prods.find( 'ul li input[type="checkbox"]' );
                var that            = $(this),
                    status          = that.prop('checked'),
                    parent          = that.parent();
 
                status ? parent.find('label').text( lang.uncheck_all ) : parent.find('label').text( lang.check_all );

                prods.each(function (i) {
                   $(this).trigger('click'); 
                });
            });

            // product checkbox click
            containers.queueprods.prods.on('click', 'ul li input[type="checkbox"]', function (e) {
                var prods           = containers.queueprods.prods.find( 'ul li input[type="checkbox"]' ),
                    prods_checked   = containers.queueprods.prods.find( 'ul li input[type="checkbox"]:checked' );

                prods.length == prods_checked.length ? check_all.prop('checked', true) : check_all.prop('checked', false);
                var that            = check_all,
                    status          = that.prop('checked'),
                    parent          = that.parent();
 
                status ? parent.find('label').text( lang.uncheck_all ) : parent.find('label').text( lang.check_all );
            });
        }
        
        // external usage
        return {
            'set_queue_width'           : set_queue_width,
            'create_products_scroll'    : create_products_scroll,
            'view_products_list'        : view_products_list,
            'check_all'                 : check_all
        };
    })();

	
	// :: TRIGGERS
	function triggers()
	{
		// get base score hint
		get_score_hint();
		
	    // tooltip
	    jQuery('span.tooltip, i, a').tipsy({live: true, gravity: 'n', html: true});

		// queue products create box
        queueprod.create_products_scroll( box_queue_status_default );
        queueprod.check_all();
		
		// queue show products button
		maincontainer.on('click', "#WooZoneLite-expand-all", function(e){
			e.preventDefault();
			
			var that     = $(this),
                parent   = that.parents().eq(2); 
 
			parent.find('.WooZoneLite-insane-panel-headline a[href="#WooZoneLite-queued-products"]').trigger('click');
			queueprod.view_products_list( 'toggle' );
		});

        // speedometer
        draw_tick_marks();

		// test speedometer
		//maincontainer.on('change', "#test-speedometer", function(){
		//	change_speedometer_value( $(this).val() );
		//});
		//change_speedometer_value( 105 );
		
		// TABS
		maincontainer.find(".WooZoneLite-insane-tabs").each(function(){
			var that = $(this),
				btns = that.find(".WooZoneLite-insane-panel-headline a"),
				tabs = that.find(".WooZoneLite-insane-tab-content");
			
			that.on('click', '.WooZoneLite-insane-panel-headline a', function(e){
				e.preventDefault();
				
				var btn = $(this),
					rel = $( btn.attr("href") );

				if( btn.hasClass('WooZoneLite-insane-button-on') ) return;
				
				tabs.hide();

                if( btn.attr('href') == '#WooZoneLite-export-asins' ) {
                    $('#WooZoneLite-queued-results-stats').hide();
                } else {
                    $('#WooZoneLite-queued-results-stats').show();
                }

				rel.fadeIn( 200 );

				btns.parent("div").find("a.WooZoneLite-insane-button-on").removeClass("WooZoneLite-insane-button-on");
				btn.addClass("WooZoneLite-insane-button-on");
			});
			
			//!! set default tab based on tab Index
			//$(".WooZoneLite-insane-panel-headline a").eq(2).click();
		});
		
		// range sliders
        $('input[type="range"]').rangeslider({
            polyfill    : false,
            //onInit      : function() {
            //    if (this.value == this.max) {
            //    }
            //}
        });
        $(document).on('input', 'input[type="range"]', function(e) {
            var that = $(this),
                val = that.val(),
                max = that.prop('max'),
                id = that.prop('id'),
                $output = $('#' + id + '-output');

            if ( val == max ) {
                val = 'all';
            }
            $output.val( val );
            
            // estimate import duration & speed
            importprod.estimate({
                speed           : 47,
                time            : 90000 // 1000 * 60 * 1.5 => 1.5 min
            });
        });
        
        $(document).on('change', 'input[type="range"], #WooZoneLite-to-category, #import-parameters-attributes', function(e) {
        	get_score_hint();
        });
        
        // show messages log button
        maincontainer.on('click', ".WooZoneLite-insane-buton-logs", function(e){
            e.preventDefault();
            
            var that    = $(this),
                log     = that.data('logcontainer');
            
            $('.WooZoneLite-insane-container-logs').filter(function(i){
                return $(this).prop('id') == log;
            }).toggle('WooZoneLite-logs-open');
        });
	}

	function get_score_hint()
	{   
        var tuning = {},
        	params = {};
        	tuning.variations = $('#import-parameters-nbvariations');
        	tuning.images = $('#import-parameters-nbimages');
        	tuning.attributes = $('#import-parameters-attributes');
        	tuning.category = $('#WooZoneLite-to-category');
         
        $.each(tuning, function(k, elm) {
        	var val = '';
        	
        	if( k == 'attributes' ) {
        		val = elm.is(':checked') ? 'true' : 'false';
        	}else{
        		val = elm.val();
        	}
        	params[k] = val;
        });
        
    	$.post(ajaxurl, {
    		'action'        : 'WooZoneLiteIM_GetScoreHint',
            'params'        : JSON.stringify(params)
    	}, function(response) {
            if ( response.status == 'valid' ) {
                $('.WooZoneLite-insane-import-score-hint').html( response.score );
            }
        }, 'json');
	}
	
    // :: LOADING
    function row_loading( row, status, extra )
    {
        var extra = extra || {};
        var isextra = ( typeof extra != 'undefined' && misc.size(extra) == 1 ? true : false );
        
        if( status == 'show' ){
            if( row.size() > 0 ){
                if( row.find('.WooZoneLite-row-loading-marker').size() == 0 ){
                    //<div class="WooZoneLite-loading-text">Loading</div><div class="WooZoneLite-meter WooZoneLite-animate" style="width:30%; margin: 10px 0px 0px 30%;"><span style="width:100%"></span></div>
                    var html = '<div class="WooZoneLite-meter WooZoneLite-animate" style="width:30%; margin: 10px auto;"><span style="width:100%"></span></div><div class="WooZoneLite-loading-text">' + lang.loading + '</div>';
                    //if ( isextra ) {
                    //    html = html + extra.html;
                    //}
                    var row_loading_box = $('<div class="WooZoneLite-row-loading-marker"><div class="WooZoneLite-row-loading">' + html + '</div></div>');
                    row_loading_box.find('div.WooZoneLite-row-loading').css({
                        'width'     : parseInt( row.outerWidth() ),
                        'height'    : parseInt( row.outerHeight() + 42 ),
                        'top'       : '-40px'
                    });
                    row.prepend(row_loading_box);
                }
                if ( isextra && $.trim( extra.html ) != '' ) {
                    row.find('.WooZoneLite-row-loading-marker')
                    .find('div.WooZoneLite-row-loading')
                    .find('div.WooZoneLite-loading-text')
                    .html( extra.html );
                }
                row.find('.WooZoneLite-row-loading-marker').find('div.WooZoneLite-row-loading').css({
                    'width'     : parseInt( row.outerWidth() ),
                    'height'    : parseInt( row.outerHeight() + 42 ),
                    'top'       : '-40px'
                });
                //text loading!
                row.find('.WooZoneLite-row-loading-marker').find('div.WooZoneLite-loading-text').css({
                    'height'    : parseInt( row.outerHeight() - 10 )
                });
                row.find('.WooZoneLite-row-loading-marker').fadeIn('fast');
            }
        } else {
            row.find('.WooZoneLite-row-loading-marker').fadeOut('slow');
        }
    }
    
    function background_loading( msg, status )
    {
        if( status != 'show' ) {
            background_loading_container.hide();
        }
        
        background_loading_container.find('span').text( msg );
        background_loading_container.show();
        background_loading_container.animate({
            'height': '90px'
        }, 350 );
    }

    
    // :: MESSAGES
    function set_status_msg_generic( status, msg, op, from ) {
        var _op = '',
            wrap = { li: '', i: '', span: '' };

        switch (op) {
            case 'bulk':
                _op = lang.load_op_bulk;
                break;
                
            case 'grab':
                _op = lang.load_op_grab;
                break;
                
            case 'search':
                _op = lang.load_op_search;
                break;
                
            case 'export':
                _op = lang.load_op_export;
                break;
                
            case 'import':
                _op = lang.load_op_import;
                break;
        }
        switch (status) {
            case 'invalid':
                wrap.li = 'error';
                wrap.i = 'minus-circle';
                wrap.span = 'error';
                break;
                
            case 'valid':
                wrap.li = 'success';
                wrap.i = 'check-circle';
                wrap.span = 'success';
                break;
                
            case 'info':
                wrap.li = 'notice';
                wrap.i = 'info';
                wrap.span = 'info';
                break;
        }
        //<span class="WooZoneLite-insane-logs-frame">Yesterday 10:24 PM</span>
        var html = '\
            <li class="WooZoneLite-log-' + wrap.li + '">\
                <i class="fa fa-' + wrap.i + '"></i>\
                <span class="WooZoneLite-insane-logs-frame">' + misc.get_current_date() + '</span>\
                <span class="WooZoneLite-insane-logs-frame">' + _op + '</span>\
                <br />\
                <span class="WooZoneLite-insane-logs-msg"> ' + msg + '</span>\
            </li>',
            html_ = '\
            <span class="WooZoneLite-message WooZoneLite-' + wrap.span + '">\
                <span class="WooZoneLite-insane-logs-frame">' + misc.get_current_date() + '</span>\
                <span class="WooZoneLite-insane-logs-frame">' + _op + '</span>\
                <br />\
                <span class="WooZoneLite-insane-logs-msg"> ' + msg + '</span>\
            </span>';

        if ( from == 'loadprod' ) {
            //containers.loadprods.status.html( html_ );
            containers.loadstatus.wrap.find('ul.WooZoneLite-insane-logs').prepend( html );
        } else {
            containers.importstatus.wrap.find('ul.WooZoneLite-insane-logs').prepend( html );
        }
    };
    

    // :: LOAD PRODUCTS in Queue
    var loadprod = (function() {
        
        var DEBUG                   = false,
            TEST                    = 0;
        var asins = {
            found               : [], // asins found, valid or not
            loaded              : [], // loaded in queue
            invalid             : [], // invalid - could not be loaded in queue
            already_imported    : [], // already_imported = already imported products ( NOT those which will be imported from selected queue )
            selected            : [], // selected for importing
            imported            : [], // imported from selected queue
            import_errors       : [] // not imported (have errors) from selected queue
        }, load_max_limit = 100;

        // Test!
        function __() { asins.found.push('asin1'); };
        
        // get public vars
        function get_vars() {
            return $.extend( {}, {
                asins       : asins
            } );
        };
        
        // init function, autoload
        (function init() {
            // load the triggers
            $(document).ready(function() {
    
                triggers();
            });
        })();
        
        // Triggers
        function triggers() {
            var box = containers.loadprods.wrap;
           
            keyword_autocomplete();
            //select_category();

            // BULK LOAD ASINs
            box.on('click', 'form#WooZoneLite-import-products #WooZoneLite-addASINtoQueue', function(e) {
                e.preventDefault();

                bulk_add_asin_to_queue();
            });
            
            // GRAB ASINs
            box.on('click', 'form#WooZoneLite-grab-products #WooZoneLite-grabb-button', function(e) {
                e.preventDefault();

                grab_parse_url();
            });
            
            // SEARCH PRODS
            box.on('submit', 'form#WooZoneLite-search-products', function(e){
            //box.on('click', 'form#WooZoneLite-search-products #WooZoneLite-button', function(e) {
                e.preventDefault();

                var form = $(this);
                search_prods( form );
            });
            
            // selected products in queue
            containers.queueprods.prods.on('click', 'ul li input[type="checkbox"]', function(e) {
                var that = $(this),
                    status = that.prop('checked'),
                    li = that.parents('li').eq(0),
                    asin = li.data('asin').toString(),
                    operation = null;

                if ( status ) {
                    operation = 'add';
                    li.addClass('selected');
                    
                    // add asin to selected asins list
                    set_results( [asin], 'selected' );
                } else {
                    operation = 'remove';
                    li.removeClass('selected');
                    
                    // remove asin from selected asins list 
                    misc.arrayRemoveElement(asins.selected, asin);
                    set_results( null, 'selected' );
                }
                
                // Import Products - update total products list
                if ( $.inArray(importprod.v().process_status, ['start', 'run', 'stop', 'finished']) > -1 ) {
                    //importprod.stop_import();
                    importprod.calculate_products_data( 
                        'total',
                        importprod.get_prod_data( li, importprod.v().import_params ),
                        operation
                    );
                }
            });
            
            // export asins
            containers.queueprods.export.on('click', 'form#WooZoneLite-export-form #WooZoneLite-export-button', function(e) {
                e.preventDefault();
                
                var form = $(this).parents('form').eq(0);
                export_asins( form );
            });
            
            // search products - change category
            var search_wrap         = containers.loadprods.search,
                categ_wrap          = search_wrap.find('#WooZoneLite-select-on-category');

            // change category
            search_wrap.on( 'change', 'select#WooZoneLite-search-category', function(){
                var that = $(this);
    
                search_select_pages( that.val() );
                get_category_params( that );
            });
            
            // change browse nodes and retrieve childrens
            search_wrap.on( 'change', '.WooZoneLite-param-optional.WooZoneLite-param-node select', function(){
                var that = $(this);
    
                get_browse_nodes( that );
            });
            
            // search products - sort parameter tooltip
            search_wrap.on('change', 'select[name="WooZoneLite-search[Sort]"]', function (e) {
                var that    = $(this);
                sort_tooltip( that );
            });
        };
        
        function sort_tooltip( that ) {
            var val     = that.val(),
                opt     = that.find("[value=" + ( val ) + "]"),
                desc    = opt.data('desc'),
                ttip    = that.parent().find('span'),
                title   = ttip.data('title');
                
            ttip.prop('title', title + '<br /><i><strong><u>' + val + '</u></strong>: ' + desc + '</i>');
        };
        
        // BULK LOAD ASINs
        function bulk_add_asin_to_queue() {
            loading( 'show', lang.loading );

            var box = containers.loadprods.bulk,
                asins_str = $.trim( box.find('#WooZoneLite-content-bulk-asin').val() ),
                delimiter = box.find("input[name=WooZoneLite-csv-delimiter]:checked").attr('id').split('radio-'),
                delimiter = delimiter[1],
                _asins = [];

            if ( delimiter == 'newline' ) {
                delimiter = "\n";
            } else if ( delimiter == 'comma' ) {
                delimiter = ",";
            } else if ( delimiter == 'tab' ) {
                delimiter = "\t";
            }
        
            if (asins_str == ""){
                set_status_msg( 'invalid', lang.bulk_add_asin, 'bulk' );
                loading( 'close', lang.bulk_add_asin );
                return false;
            }

            $.each( asins_str.split( delimiter ), function(key, val) {
                if ($.trim( val ) != "") {
                    _asins.push( $.trim( val ) );
                }
            });

            // success
            if (_asins.length > 0) {
                var msg = lang.bulk_asin_found.replace('%s', _asins.length) + _asins.join(', ');
                set_status_msg( 'valid', msg, 'bulk' );
                loading( 'show', msg );
                
                // update results
                set_results( _asins, 'found' );
                
                // load products in queue
                load_prods_by_asin( _asins, 'bulk' );
            }
            // error
            else {
                set_status_msg( 'invalid', lang.bulk_no_asin_found, 'bulk' );
                loading( 'show', lang.bulk_no_asin_found );
                return false;
            }
        };
        
        // GRAB ASINs
        function grab_parse_url() {
            var operation_id = new Date().getTime(); // in miliseconds
            loading( 'show', lang.loading );
            heartbeat.start( operation_id );

            var box = containers.loadprods.grab,
                form = box.find('form#WooZoneLite-grab-products');

            var data = {
                'action'        : 'WooZoneLiteIM_LoadProdsGrabParseURL',
                'params'        : form.serialize(),
                'operation_id'  : operation_id,
                'debug_level'   : debug_level
            };

            $.post(ajaxurl, data, function(response) {
                set_status_msg( response.status, response.msg, 'grab' );
                loading( 'show', response.msg );

                // success
                if (response.status == 'valid') {
                    var _asins = response.asins;

                    // update results
                    set_results( _asins, 'found' );
                    
                    // load products in queue
                    load_prods_by_asin( _asins, 'grab' );
                    return true;
                }
                // error
                else {
                    heartbeat.stop();
                    loading( 'close' );
                    return false;
                }

            }, 'json')
            .fail(function() { heartbeat.stop(); })
            .done(function() {})
            .always(function() {});
        };
        
        // SEARCH PRODS
        function search_prods( form ) {
            var operation_id = new Date().getTime(); // in miliseconds
            loading( 'show', lang.loading );
            heartbeat.start( operation_id );

            var box             = containers.loadprods.search,
                form            = typeof form != 'undefined' ? form : box.find('form#WooZoneLite-search-products'),
                asins_inqueue   = get_asins_inqueue(),
                nodename        = null, 
                nodeid          = null;

            var data            = [],
                form_params     = form.serializeArray();
                
            data.push(
                {name: 'debug_level',       value: debug_level},
                {name: 'action',            value: 'WooZoneLiteIM_LoadProdsBySearch'},
                {name: 'operation',         value: 'search'},
                {name: 'operation_id',      value: operation_id},
                {name: 'asins_inqueue',     value: asins_inqueue}
            );
            // get last BrowseNode value
            if ( $.isArray(form_params) ) {
                for (var i = 0, len = form_params.length; i < len; i++) {
                    var obj = form_params[i];
                    if ( typeof(obj) != 'undefined' 
                        && misc.hasOwnProperty(obj, 'name') && misc.hasOwnProperty(obj, 'value') ) {

                        if ( obj.name.search(/BrowseNode/gi) > 0 ) {
                            if ( obj.value != '' ) {
                                nodename = obj.name;
                                nodeid   = obj.value;
                            }
                            form_params.splice(i, 1);
                            --i;
                        }
                    }
                }
                if ( nodeid ) {
                    form_params.push(
                        {name: nodename, value: nodeid}
                    );
                }
                form_params = $.param( form_params ); // turn the result into a query string
            }
            data.push(
                {name: 'params', value: form_params}
            );
            data = $.param( data ); // turn the result into a query string
            //console.log( data ); return false;

            /*
            var data            = {
                'debug_level'       : debug_level,
                'action'            : 'WooZoneLiteIM_LoadProdsBySearch',
                'operation'         : 'search',
                'asins_inqueue'     : asins_inqueue,
                'params'            : form.serialize()
            };
            console.log( data ); return false;
            */

            $.post(ajaxurl, data, function(response) {
                if (1) {
                    set_status_msg( response.status, response.msg, 'search' );
                    //loading( 'show', response.msg );

                    if ( misc.hasOwnProperty(response, 'asins') ) {
                        // update results
                        set_results( response.asins.loaded, 'loaded', response );
                        set_results( response.asins.loaded, 'selected' );
                        set_results( response.asins.invalid, 'invalid' );
                        set_results( response.asins.already_imported, 'already_imported' );
                        set_results( response.asins.found, 'found' );
                        
                        // update queue prods width
                        queueprod.set_queue_width();
                        
                        if ( misc.hasOwnProperty(response.asins, 'remained') ) {
                            // load products in queue
                            load_prods_by_asin( response.asins.remained, 'search' );
                            return true;
                        }
                    }

                    heartbeat.stop();
                    loading( 'close' );
                }

            }, 'json')
            .fail(function() { heartbeat.stop(); })
            .done(function() {})
            .always(function() {});
        }
        
        // LOAD PRODS IN QUEUE & SET RESULTS
        function load_prods_by_asin( asin, op ) {
            
            var operation_id = new Date().getTime(); // in miliseconds
            heartbeat.start( operation_id );
                
            var contor = 0, max = Math.ceil( asin.length / 10 ) * 2;
            max = max <= 1000 ? max : 1000;
            function do_load() {
                ///*
                var _r          = array_splice_verify_already_exists(asins, asin, 0, load_max_limit),
                    asin_step   = _r.slice,
                    exists      = _r.already_exists;
                asin = _r.array;
                //*/
                //var asin_step = asin.splice(0, load_max_limit); // debug!
                //console.log( 'header' ); console.dir( asin ); console.dir( asin_step );
                
                // asins already exists in: loaded or invalid or already imported
                if ( exists.length > 0 ) {
                    set_status_msg( 'invalid', lang.already_exists.replace('%s', exists.length).replace('%s', exists.join(', ')), op );
                }
    
                if ( asin_step.length <= 0 || contor >= max ) {
                    heartbeat.stop();
                    loading( 'close' );
                    return false;
                }
            
                var asins_inqueue   = get_asins_inqueue();
                var data = {
                    'debug_level'       : debug_level,
                    'action'            : 'WooZoneLiteIM_LoadProdsByASIN',
                    'operation'         : op,
                    'operation_id'      : operation_id,
                    'asins'             : asin_step,
                    'page'              : contor + 1,
                    'asins_inqueue'     : asins_inqueue
                };
  
                $.post(ajaxurl, data, function(response) {
                    set_status_msg( response.status, response.msg, op );
                    loading( 'show', response.msg );

                    if ( misc.hasOwnProperty(response, 'asins') ) {
                        // update results
                        set_results( response.asins.loaded, 'loaded', response );
                        set_results( response.asins.loaded, 'selected', response );
                        set_results( response.asins.invalid, 'invalid' );
                        set_results( response.asins.already_imported, 'already_imported' );

                        asin = misc.arrayUnique( asin.concat( response.asins.remained ) );
                        
                        // update queue prods width
                        queueprod.set_queue_width();
                    }

                    ++contor;
                    
                    // repeat till all products from the asins list are marked as loaded or invalid
                    do_load();

                }, 'json')
                .fail(function() { heartbeat.stop(); })
                .done(function() {})
                .always(function() {});
            };
            do_load(); // init cycle
        }

        function set_results( asin, type, response ) {
            var arrlist = ['found', 'loaded', 'invalid', 'selected', 'already_imported', 'imported', 'import_errors'];
            if ( $.inArray(type, arrlist) > -1 ) {
                if ( asin != null ) {
                    asins[type] = misc.arrayUnique( asins[type].concat( asin ) );
                }
            }
            switch (type) {
                case 'found':
                case 'invalid':
                case 'already_imported':
                case 'imported':
                case 'import_errors':
                    break;

                case 'loaded':
                    var list = containers.queueprods.prods.find("ul");
                    //list.length && 
                    //console.log( list, response ); 
                    if ( typeof response != 'undefined' ) {
                        if ( misc.hasOwnProperty(response, 'html') ) {
                            list.prepend( response.html );
                        }
                    }
                    is_empty_queue();
                    break;
                    
                case 'selected':
                    // estimate import duration & speed
                    importprod.estimate({
                        speed           : 32,
                        time            : 3600000 // 1000 * 60 * 60 => 60 min => 1 hour
                    });
                    break;
            }
            if ( containers.queueprods.results.find('.WooZoneLite-stats-' + type + ' span span').length ) {
                containers.queueprods.results.find('.WooZoneLite-stats-' + type + ' span span').html( asins[type].length );
            }
            //console.dir( asins );
        };
        
        function export_asins( form ) {
            loading( 'show', lang.loading );

            var box = containers.queueprods.export,
                form = typeof form != 'undefined' ? form : box.find('form#WooZoneLite-search-products'),
                delimiter = box.find("input[name=WooZoneLite-export-delimiter]:checked").attr('id').split('radio-'),
                delimiter = delimiter[1],
                export_asins_type = box.find("#WooZoneLite-export-asins-type").val(),
                _asins = [];

            //if ( delimiter == 'newline' ) {
            //    delimiter = "\n";
            //} else if ( delimiter == 'comma' ) {
            //    delimiter = ",";
            //} else if ( delimiter == 'tab' ) {
            //    delimiter = "\t";
            //}
            
            switch (export_asins_type) {
                // loaded and valid
                case '1':
                    _asins = asins.loaded;
                    break;
                    
                // selected for import
                case '2':
                    _asins = asins.selected;
                    break;
                    
                // imported successfully
                case '3':
                    _asins = asins.imported;
                    break;
                    
                // not imported - with errors
                case '4':
                    _asins = asins.import_errors;
                    break;
                    
                // remained loaded in queue and remained selected in queue
                case '5':
                case '6':
                    var prods = containers.queueprods.prods.find( "ul li" + (export_asins_type == 6 ? '.selected' : '') );
                    $.each( prods, function(key, val) {
                        var that = $(this),
                            asin = that.data('asin');
                        if ($.trim( asin ) != "") {
                            _asins.push( $.trim( asin ) );
                        }
                    });
                    break;
                    
                // found invalid
                case '7':
                    _asins = asins.invalid;
                    break;
            }
        
            if (_asins.length <= 0){
                set_status_msg( 'invalid', lang.export_no_asin, 'export' );
                loading( 'close', lang.export_no_asin );
                return false;
            }
 
            var data = {
                'debug_level'           : debug_level,
                'action'                : 'WooZoneLiteIM_exportASIN',
                'asins'                 : _asins,
                'delimiter'             : delimiter,
                'export_asins_type'     : export_asins_type
            };
            data = $.extend({}, data);
  
            $.post(ajaxurl, data, function(response) {
                if (1) {
                    set_status_msg( response.status, response.msg, 'export' );
                    loading( 'show', response.msg );
                    
                    if( response.status == 'valid' ){
                    } else {
                        loading( 'close' );
                        return false;
                    }

                    // build download link
                    data = $.param( data );
                    var dwurl = ajaxurl + '?' + data + '&do_export=1';
                    //console.log( dwurl );
                    
                    loading( 'close' );
                    
                    // force download        
                    window.location = dwurl;
                    return true;
                }

            }, 'json')
            .fail(function() { loading( 'close' ); })
            .done(function() {})
            .always(function() {});
        }

        // Loading
        function loading( status, msg, from ) {
            var msg         = msg || '',
                from        = from || '',
                container = containers.loadprods.wrap.parent().parent();

            //if (status == 'close') return false; //debug!
            row_loading( container, status, {html: msg} );
            
            if ( from == '' ) {
                importprod.loading( status, lang.loadprods_inprogress, 'external' );
            }
        };

        function set_status_msg( status, msg, op ) {
            set_status_msg_generic( status, msg, op, 'loadprod' );
        };

        // UTILS
        function array_splice_verify_already_exists(items, array, start, howmany) {
            var start = start || 0,
                howmany = howmany || 'all',
                r = [],
                already_exists = [],
                ret = {},
                cc = 0,
                len = array.length;
            while ( cc < len ) {
                // not found in unique: loaded or invalid or already imported in database
                if( $.inArray(array[start], items['loaded']) == -1
                    && $.inArray(array[start], items['invalid']) == -1
                    && $.inArray(array[start], items['already_imported']) == -1 ) {
                    r.push( array[start] );
                } else {
                    already_exists.push( array[start] );
                }
                array.splice(start, 1);
                if ( howmany !== 'all' && r.length >= howmany ) break;
                cc++;
            }
            var ret = {
                'array'             : array,
                'slice'             : r,
                'already_exists'    : already_exists
            };
            return ret;
        };
       
        function get_asins_inqueue() {
            var asins_inqueue = [];
            asins_inqueue = asins_inqueue.concat( asins.loaded );
            asins_inqueue = asins_inqueue.concat( asins.invalid );
            asins_inqueue = asins_inqueue.concat( asins.already_imported );
            asins_inqueue = misc.arrayUnique( asins_inqueue );
            return asins_inqueue;
        }

        function search_select_pages( category ) {
            var category    = $.trim( category ),
                nb_pages    = 5, // grab first 5 pages (All categories)
                container   = containers.loadprods.search,
                dropdown    = container.find('#WooZoneLite-search-nbpages'),
                current     = dropdown.val();
            
            // grab first 10 pages
            if ( category != '' && category != 'AllCategories' ) {
                nb_pages = 10;
            }
            current = current > nb_pages ? nb_pages : current; 

            var html    = [],
                first   = dropdown.find('option:first');
            html.push( '<option value="' + first.val() + '" disabled="disabled">' + first.text() + '</option>' );
            for (var i=1; i<=nb_pages; ++i) {
                var text = i == 1 ? lang.search_pages_single : lang.search_pages_many.replace('%s', i);
                html.push( '<option value="' + i + '">' + text + '</option>' );
            }
            dropdown.html( html.join('') );
            dropdown.val( current );
        };

        function select_category() {
            var search_on   = maincontainer.find("#WooZoneLite-search-search_on"),
                def_search  = search_on.val(),
                custom      = maincontainer.find("#WooZoneLite-node"),
                dropdown    = maincontainer.find("#WooZoneLite-search-category");
            search_on.data('use_categ_field', 'category');
            
            function set_search_on( val )
            {
                val = $.trim( val ); 
                if( val != "" ){
                    search_on.val( val );
                }else{
                    search_on.val( def_search );
                }
            }
            
            custom.on( 'keydown, keyup', function(){
                var that      = $(this),
                    val       = that.val(),
                    text      = dropdown.find('option:selected').text();
    
                set_search_on( val );
                search_select_pages( val );
                if ( $.trim( val ) != "" ) {
                    search_on.data('use_categ_field', 'node');
                } else {
                    search_on.data('use_categ_field', 'category');
                    search_on.val( text );
                }
            });
            dropdown.on( 'change', function(){
                var that      = $(this),
                    val       = that.val(),
                    text      = that.find('option:selected').text();
    
                set_search_on( text );
                
                search_select_pages( val );
                search_on.data('use_categ_field', 'category');
            });
        }
        
        function keyword_autocomplete() {
            var autocomplete = maincontainer.find(".WooZoneLite-search-completion");
            
            maincontainer.on('keyup', "input#WooZoneLite-search-keyword", function(){
                
                var that = $(this),
                    data = {
                        'action': 'WooZoneLiteIM_KeywordAutocomplete',
                        'keyword': that.val()
                    };
                
                if( that.val() == "" ) return;
                    
                $.post(ajaxurl, data, function (response) {
                    if( response['status'] == 'valid' ){
                        autocomplete.html('');
                        $.each( response['data'], function(key, value){
                            autocomplete.append( "<li>" + ( value ) + "</li>" );
                        });
                        
                        autocomplete.css( 'display', 'block' );
                    }
                }, 'json')
                .fail(function() {})
                .done(function() {})
                .always(function() {});
            });
            
            autocomplete.on('click', 'li', function(){
                var that = $(this),
                    text = that.text();
                
                maincontainer.find("input#WooZoneLite-search-keyword").val( text );
                autocomplete.html("");
                autocomplete.hide(); 
            });
            
            $("body").on('click', maincontainer, function(){
                if( autocomplete.html() != "" ){
                    autocomplete.html("");
                    autocomplete.hide();
                }
            });
        }

        function get_category_params( category ) {
            loading( 'show', lang.loading );
            
            var box             = containers.loadprods.search,
                categ_wrap      = box.find('#WooZoneLite-select-on-category'),
                dropdown        = categ_wrap.find("#WooZoneLite-search-category"),
                category        = category || dropdown;

            var data = {
                'action'        : 'WooZoneLiteIM_getCategoryParams',
                'category'      : category.find('option:selected').val(),
                'nodeid'        : category.find('option:selected').data('nodeid'),
                'debug_level'   : debug_level
            };
    
            $.post(ajaxurl, data, function(response) {
                // success
                //if (response.status == 'valid') {
                if ( misc.hasOwnProperty( response, 'status') ) {
                    // remove current parameters
                    box.find('li.WooZoneLite-param-optional').remove(); //detach()
                    
                    // add new parameters next to category wrapper
                    categ_wrap.after( response.html );
                    
                    (function(){
                        var search_wrap     = containers.loadprods.search,
                            that            = search_wrap.find('select[name="WooZoneLite-search[Sort]"]');
                        sort_tooltip( that );
                    })();
                }
                loading( 'close' );
                return true;

            }, 'json')
            .fail(function() { loading( 'close' ); })
            .done(function() {})
            .always(function() {});            
        };

        function get_browse_nodes( that, category ) {
            loading( 'show', lang.loading );
 
            var box             = containers.loadprods.search,
                categ_wrap      = box.find('#WooZoneLite-select-on-category'),
                dropdown        = categ_wrap.find("#WooZoneLite-search-category"),
                category        = category || dropdown,
                parent_li       = that.parent(),
                ascensor_value  = that.val(),
                len             = box.find('.WooZoneLite-param-optional.WooZoneLite-param-node').length; // prev element value
    
            // max deep
            if ( len >= 10 ){
                loading( 'close' );
                return false;
            }
  
            // remove all browse nodes after current one
            var next = null;
            while( (next = parent_li.next('.WooZoneLite-param-optional.WooZoneLite-param-node')).length > 0 ) {
                next.remove();
            }
  
            // store current childrens into array
            if( ascensor_value != "" ){
                var data = {
                    'action'        : 'WooZoneLiteIM_getBrowseNodes',
                    'category'      : category.find('option:selected').val(),
                    'nodeid'        : ascensor_value,
                    'debug_level'   : debug_level
                };

                // make the import
                $.post(ajaxurl, data, function(response) {
                    if( response.status == 'valid' ){
                        parent_li.after( response.html );
                    }
                    loading( 'close' );
                }, 'json')
                .fail(function() { loading( 'close' ); })
                .done(function() {})
                .always(function() {});
    
            }else{
                loading( 'close' );
            }
        }

        function is_empty_queue( show ) {
            var show        = show || true,
                msgbox      = containers.queueprods.wrap.find('#WooZoneLite-queued-message'),
                queue       = containers.queueprods.prods.find('ul li');

            var status = queue.length <= 0 ? true : false;
            show && ( status == true ) ? msgbox.show() : msgbox.hide();
        }

        // external usage
        return {
            // attributes
            'v'                     : get_vars,
            
            // methods
            '__'                    : __,
            'search_select_pages'   : search_select_pages,
            'loading'               : loading,
            'set_status_msg'        : set_status_msg,
            'set_results'           : set_results,
            'is_empty_queue'        : is_empty_queue
        };
    })();


    // :: IMPORT PRODUCTS
    var importprod = (function() {
        
        var DEBUG                   = false,
            TEST                    = false;
        var s                       = {},
        	gs							= {},
            wrap                    = null,
            big_parent              = null,
            screen                  = null,
            process_status          = null, // values: start | run | stop | finished
            import_params           = {}, // the import parameters
            current_progress        = {}, // current step progress: progress & time elapsed
            current_estimate        = {}, // current step estimate: speed & time elapsed
            current_prods_data      = {}, // current step: number of: products, product images, product variations...
            total_prods_data        = {}, // total per import: number of: products, product images, product variations...
            elapsed_prods_data      = {}, // elapsed till current: number of: products, product images, product variations...
            current_prod            = {}, // current & next product to be imported
            current_asin            = '', // current product ASIN 
            logo_level              = 1,
            time_start              = null, // import process start time
            test_loop_current       = 0, // testing to simulate ajax requests
            test_loop_max           = 1000,
            is_auto                 = false, // is auto import activated
            current_auto_queue      = {}, // current products to be queued for auto import
            auto_queue_step_nb      = 10; // how many products to put in queue per step for auto import

        // Test!
        function __() { console.log('__ method'); };
        
        // get public vars
        function get_vars() {
            return $.extend( {}, {
                s                   : s,
                process_status      : process_status,
                import_params       : import_params,
                current_prods_data  : current_prods_data,
                total_prods_data    : total_prods_data,
                elapsed_prods_data  : elapsed_prods_data,
                current_prod        : current_prod,
                current_asin        : current_asin,
                time_start          : time_start
            } );
        };
        
        // init function, autoload
        (function init() {
            // load the triggers
            $(document).ready(function() {
                s           		= default_import_settings;
                gs          		= general_settings;
                wrap        		= containers.importprods.wrap;
                big_parent  	= wrap.parent().parent();
                triggers();
            });
        })();
        
        // init when starting new import
        function init_onstart() {
            time_start          = new Date().getTime(); // in miliseconds
            process_status      = 'start';            
            
            // get the import parameters
            get_parameters();
            
            // get the products data: number of: products, product images, product variations...
            get_products_data({
                'params' : import_params
            });
            // verify if there are products selected for import!
            if ( current_prods_data.nb_prods == 0 ) {
                process_status = 'finished';
                return false;
            }
            total_prods_data = current_prods_data;
            elapsed_prods_data = {
                'nb_prods'          : 0,
                'nb_variations'     : 0,
                'nb_images'         : 0
            };
            
            screen = loading( 'show', lang.loading );

            return true;
        }
        
        // Triggers
        function triggers() {
            // import products
            wrap.on('click', '#WooZoneLite-import-products-button', function(e) {
                e.preventDefault();
                
                var $prods_import_type  = $('input[name="import-parameters[prods_import_type]"]:checked'),
                    prods_import_type   = $prods_import_type.length ? $prods_import_type.val() : 'default';

                if ('default' == prods_import_type) {
                    import_products();
                }
                else {
                    is_auto = true;
                    auto__products_import();
                }
                return true;
            });
            
            // stop import
            $('body').on('click', '#WooZoneLite-import-stop-button', function(e) {
                e.preventDefault();

                var $this       = $(this),
                    is_close    = $this.data('is_close');
 
                if ( is_close ) {
                    screen.remove();
                    loadprod.loading( 'close', lang.importprods_inprogress, 'external' );
                    return false;
                }
 
                stop_import();
                return true;
            });
            
            // estimate when click on import parameters
            wrap.on('click', 'input[name^="import-parameters"]', function(e) {
                estimate( {
                    speed          : 105,
                    time           : 25200000 // 1000 * 60 * 60 * 7 => 60 min * 7 => 7 hours
                });
            });
            // default estimate
            estimate( {
                speed          : 105,
                time           : 50400000 // 1000 * 60 * 60 * 14 => 60 min * 14 => 14 hours
            });
        };
        
        // IMPORT PRODUCTS
        function import_products() {

            var operation_id = new Date().getTime(); // in miliseconds
            heartbeat.start( operation_id, 'import' );

            function do_import( is_init ) {
                //console.log( 'import: ', new Date().getTime() );

                var is_init = is_init || false;
                if ( is_init ) {
                    var init_status = init_onstart();
                    if ( !init_status ) {
                        heartbeat.stop();
                        alert( lang.import_empty );
                        return false;
                    }
                }
                
                // remove old imported product from queue
                if ( !$.isEmptyObject(current_prod) && current_prod.current.length ) {
                    current_prod.current.remove();
                }
                
                // verify if empty queue - no more products!
                loadprod.is_empty_queue();

                // current estimate
                estimate({
                    'box_screen'    : screen,
                    'show_screen'   : true,
                    'params'        : import_params
                });

                // current progress bars
                calculate_progress({
                    'box_screen'    : screen,
                    'show_screen'   : true,
                    'params'        : import_params
                });
     
                if ( TEST ) {
                    if ( test_loop_current >= test_loop_max ) {
                        process_status = 'stop';
                        show_process_status( screen, process_status );
                        heartbeat.stop();
                        alert( 'TEST Mode: loop max reached!' );
                        return false;
                    }
                    ++test_loop_current;
                }

                // import process is Stoped!
                if ( $.inArray(process_status, ['stop', 'finished']) > -1 ) {
                    screen.find('.WooZoneLite-iip-tail ul.WZC-keyword-attached').html('');
                    show_process_status( screen, process_status );
                    heartbeat.stop();
                    return false;
                }

                // current & next product data
                current_prod = get_current_next({
                    'box_screen'    : screen
                });

                // import process is Finished!
                if ( current_prod.current.length <=0 ) {
                    screen.find('.WooZoneLite-iip-tail ul.WZC-keyword-attached').html('');
                    process_status = 'finished';
                    show_process_status( screen, process_status );
                    heartbeat.stop();
                    return false;
                }

                // here - IMPORT CURRENT PRODUCT
                current_asin = current_prod.current.data('asin');
                process_status = 'run';
                var current_prod_data = get_prod_data( current_prod.current, {
                    'params' : import_params
                });

                // TESTING
                if ( TEST ) {
                    // add product ASIN to imported list!
                    loadprod.set_results( [current_asin], 'imported' );
                
                    // elapsed
                    calculate_products_data( 'elapsed', current_prod_data, 'add' );
                    //console.log( total_prods_data, elapsed_prods_data );
                
                    // sleep - in seconds
                    sleep( 2, function() {
                        do_import();
                    });
                }
                // REAL AJAX IMPORT
                else {
                    
                    var data        = [],
                        params      = import_params;
  
                    params = $.param( params ); // turn the result into a query string
                    data.push(
                        {name: 'debug_level',       value: debug_level},
                        {name: 'action',            value: 'WooZoneLiteIM_ImportProduct'},
                        {name: 'asin',              value: current_asin},
                        {name: 'operation_id',      value: operation_id},
                        {name: 'params',            value: params}
                    );
                    
                    data = $.param( data ); // turn the result into a query string
                    //console.log( data ); return false;
                    
                    // ajax request
                    $.post(ajaxurl, data, function(response) {
                    	
						// stop import? demo keys allowed number of imports  
                    	if ( misc.hasOwnProperty(response, 'do_import')
                    		&& response.do_import != true ) {
                    		
                    		set_status_msg( response.status, response.msg, 'import' );
                    		
                    		screen.find('.WooZoneLite-iip-tail ul.WZC-keyword-attached').html('');
                    		process_status = 'stop';
                    		show_process_status( screen, process_status );
                    		heartbeat.stop();
                    		
                    		WooZoneLite.verify_products_demo_keys();

                    		return false;
                    	}

                        set_status_msg( response.status, response.msg, 'import' );

                        // elapsed
                        calculate_products_data( 'elapsed', current_prod_data, 'add' );
                        //console.log( total_prods_data, elapsed_prods_data );

                        // reset import settings
                        if ( misc.hasOwnProperty(response, 'import_settings') ) {
                            s = response.import_settings;
                        }
                        if ( misc.hasOwnProperty(response, 'general_settings') ) {
                            gs = response.general_settings;
                        }

                        // success
                        if (response.status == 'valid') {
                            
                            // add product ASIN to imported list!
                            loadprod.set_results( [current_asin], 'imported' );
                            
                            // assets download lightbox
                            if ( misc.hasOwnProperty(response, 'show_download_lightbox')
                                && response.show_download_lightbox == true ) {
                                if ( 1 ) {
                                    big_parent.prepend( response.download_lightbox_html );

                                    WooZoneLiteAssetDownload.download_asset( 
                                        $('.WooZoneLite-images-tail').find('li').eq(0), undefined, 100, function() {
                                           $(".WooZoneLite-asset-download-lightbox").remove();

                                            do_import();
                                        }
                                    );
                                    return true;
                                }
                            }
                        }
                        // error occured
                        else {
                            // add product ASIN to NOT Imported (with Errors) list!
                            loadprod.set_results( [current_asin], 'import_errors' );
                        }
                        
                        do_import();
                        return true;
                        
                    }, 'json')
                    .fail(function() { heartbeat.stop(); })
                    .done(function() {})
                    .always(function() {});
            
                }
            }
            do_import( true ); // init cycle
        }
        
        function stop_import() {
            process_status = 'stop';
            show_process_status( screen, 'stop_' );
        }
        
        function get_current_next( pms ) {
            var pms          = typeof pms == 'object' ? pms : {},
                box_screen   = misc.hasOwnProperty(pms, 'box_screen') ? pms.box_screen : screen,
                show         = misc.hasOwnProperty(pms, 'show') ? pms.show : true;
            
            var _current     = containers.queueprods.prods.find('ul li.selected').eq(0),
                current      = _current.clone(),
                _next        = _current.nextAll('.selected').eq(0),
                next         = _next.clone();
 
            if ( show ) {

                // mark current imported product in queue 
                _current.addClass('imported')
                    .find('input').prop('disabled', true);

                // update import screen interface: current & next product
                var ul = box_screen.find('.WooZoneLite-iip-tail ul.WZC-keyword-attached');
                if ( current.length ) {
                    current.removeClass('selected').addClass('imported')
                        .find('.WooZoneLite-checked-product').before(
                            '<div class="WZC-product-current">' + lang.current_product_title + '</div>'
                        );
                    current.find('.WooZoneLite-checked-product input').prop('disabled', true);
                    ul.html( current );
                }
                if ( next.length ) {
                    next.removeClass('selected')
                        .find('.WooZoneLite-checked-product').before(
                            '<div class="WZC-product-current">' + lang.next_product_title + '</div>'
                        );
                    next.find('.WooZoneLite-checked-product input').prop('disabled', true);
                    ul.append( next );
                }
            }
            return {
                'current'   : _current,
                'next'      : _next
            };
        }
        
        // PROGRESS
        function calculate_progress( pms ) {
            var pms          = typeof pms == 'object' ? pms : {},
                box_screen   = misc.hasOwnProperty(pms, 'box_screen') ? pms.box_screen : screen,
                show_screen  = misc.hasOwnProperty(pms, 'show_screen') ? pms.show_screen : true,
                params       = {},
                progress     = (TEST == 2) && misc.hasOwnProperty(pms, 'progress') ? pms.progress : 0,
                time         = (TEST == 2) && misc.hasOwnProperty(pms, 'time') ? pms.time : 0,
                progress     = {
                    'nb_prods'          : { 'procent' : 0, 'total' : 0, 'elapsed' : 0 },
                    'nb_variations'     : { 'procent' : 0, 'total' : 0, 'elapsed' : 0 },
                    'nb_images'         : { 'procent' : 0, 'total' : 0, 'elapsed' : 0 }
                };
            var ret = {
                box_screen      : box_screen,
                params          : params,
                progress        : progress,
                time            : time
            };

            // get the import parameters
            params = misc.hasOwnProperty(pms, 'params') ? pms.params : get_parameters( pms );
            ret.params = params;
            //console.log( params );

            var time_end = new Date().getTime(),  // in miliseconds
                duration = time_end - time_start;
                
            for (var i in total_prods_data) {
                if ( misc.hasOwnProperty( total_prods_data, i ) ) {
                    progress[i]['total']    = total_prods_data[i];
                    progress[i]['elapsed']  = elapsed_prods_data[i];
                    progress[i]['procent']  = ( elapsed_prods_data[i] * 100 ) / total_prods_data[i];
                }
            }
            ret.progress = progress;
            ret.time     = duration;
            // show on importing screen
            if ( show_screen ) {
                show_progress( ret );
            }
            current_progress = ret;
            //console.log( current_progress ); 
            return ret;
        }

        function show_progress( pms ) {
            var pms         = typeof pms == 'object' ? pms : {},
                box_screen  = misc.hasOwnProperty(pms, 'box_screen') ? pms.box_screen : screen,
                params      = pms.params,
                progress    = pms.progress,
                time        = pms.time,
                time_txt    = get_time_converted( time );

            var im = {
                'prods'         : box_screen.find('.WooZoneLite-iip-process-progress-bar.im-products'),
                'images'        : box_screen.find('.WooZoneLite-iip-process-progress-bar.im-images'),
                'variations'    : box_screen.find('.WooZoneLite-iip-process-progress-bar.im-variations')
            };
 
            for (var i in im) {
                if ( misc.hasOwnProperty( im, i ) ) {
                    var __im = im[i];
                    
                    if ( __im.length ) {
                        var im_text     = __im.find('.WooZoneLite-iip-process-progress-text'),
                            im_marker   = __im.find('.WooZoneLite-iip-process-progress-marker');
 
                        if ( i == 'prods' ) {
                            im_text.find('> span').eq(2).find('span').html( time_txt );
                        }
                        im_text.find('> span').eq(1).find('span').html(
                            lang['parsed_'+i]
                                .replace('%s', progress['nb_'+i]['elapsed'])
                                .replace('%s', progress['nb_'+i]['total'])
                        );
                        im_text.find('> span').eq(0).find('span').html( Math.ceil(progress['nb_'+i]['procent']) + '%' );
                        
                        im_marker.width( progress['nb_'+i]['procent'] + '%' );
                    }
                }
            }
        }
        
        // ESTIMATE
        function estimate( pms ) {
            var pms          = typeof pms == 'object' ? pms : {},
                box          = misc.hasOwnProperty(pms, 'box') ? pms.box : wrap,
                show         = misc.hasOwnProperty(pms, 'show') ? pms.show : true,
                box_screen   = misc.hasOwnProperty(pms, 'box_screen') ? pms.box_screen : screen,
                show_screen  = misc.hasOwnProperty(pms, 'show_screen') ? pms.show_screen : false,
                params       = {},
                prods_data   = {},
                speed        = (TEST == 2) && misc.hasOwnProperty(pms, 'speed') ? pms.speed : 0,
                time         = (TEST == 2) && misc.hasOwnProperty(pms, 'time') ? pms.time : 0;
            var ret = {
                box             : box,
                box_screen      : box_screen,
                speed           : speed,
                time            : time
            };
            //console.log( s );
 
            // get the import parameters
            params = misc.hasOwnProperty(pms, 'params') ? pms.params : get_parameters( pms );
            //console.log( params );
            
            // current step: number of: products, product images, product variations...
            pms.params = params;
            prods_data = misc.hasOwnProperty(pms, 'prods_data') ? pms.prods_data : get_products_data( pms );
            //console.log( prods_data );
            
            // no products in queue or DEBUGing
            if ( prods_data.nb_prods == 0 || (TEST == 2) ) {
                // set speedometer
                if ( show ) {
                    show_estimate( ret );
                }
                // show on importing screen
                if ( show_screen ) {
                    show_estimate_screen( ret );
                }
                current_estimate = ret;
                return ret;
            }
            
            // ESTIMATE
            var _e = []; // estimate rules
            
            // products
            if ( misc.hasOwnProperty( s, 'last_product' ) ) {
                _e.push ( prods_data.nb_prods * s.last_product.media.duration );
            }

            // spin post content
            if ( misc.hasOwnProperty( s, 'last_import_spin' ) ) {
                var found = find_parameter( params, 'spin' );
                if ( found.status && found.value == 'added' ) {
                    _e.push ( prods_data.nb_prods * s.last_import_spin.media.duration );
                }
            }
            
            // import attributes
            if ( misc.hasOwnProperty( s, 'last_import_attributes' ) ) {
                var found = find_parameter( params, 'attributes' );
                if ( found.status && found.value == 'added' ) {
                    _e.push ( prods_data.nb_prods * s.last_import_attributes.media.duration );
                }
            }

            // variations
            if ( misc.hasOwnProperty( s, 'last_import_variations' ) && prods_data.nb_variations > 0 ) {
                _e.push ( prods_data.nb_variations * s.last_import_variations.media.duration );
            }
 
            // images download
            if ( prods_data.nb_images > 0 ) {
            	// remote images
            	if ( misc.hasOwnProperty( gs, 'remote_amazon_images' ) && gs.remote_amazon_images ) {
		            if ( misc.hasOwnProperty( s, 'last_import_images_remote' ) ) {
		                if ( 1 ) {
		                    _e.push ( prods_data.nb_images * s.last_import_images_remote.media.duration );
		                
		                    // we import only 1 image per variation
		                    _e.push ( prods_data.nb_variations * s.last_import_images_remote.media.duration );
		                }
		            }
            	}
            	// download images
            	else {
		            if ( misc.hasOwnProperty( s, 'last_import_images_download' ) ) {
		                var found = find_parameter( params, 'import_type' );
		                if ( found.status && found.value == 'default' ) {
		                    _e.push ( prods_data.nb_images * s.last_import_images_download.media.duration );
		                
		                    // we import only 1 image per variation
		                    _e.push ( prods_data.nb_variations * s.last_import_images_download.media.duration );
		                }
		            }
            	}
            }

            // built estimate results
            ret.time = misc.arraySum( _e );
            ret.time += ( ret.time / 3 ); // add an extra safe estimation time: ajax requests etc!
            ret.speed = calculate_speed( ret.time, prods_data.nb_prods );
            //console.log( ret, prods_data, s ); 
            
            // set speedometer
            if ( show ) {
                show_estimate( ret );
            }
            // show on importing screen
            if ( show_screen ) {
                show_estimate_screen( ret );
            }
            current_estimate = ret;
            return ret;
        }
        
        // number of products per minute
        function calculate_speed( time, nb_prods ) {
            var ret     = 0,
                _time   = time;

            _time   = Math.ceil(_time / 1000); // in seconds
            ret     = parseInt( nb_prods * 60 / _time ); // products per minute ( 60 = 60 sec = 1 minute )
            ret     = ret <= 0 ? 1 : ret;
            return ret; 
        }
        
        // get import parameters
        function get_parameters( pms ) {
            var pms          = typeof pms == 'object' ? pms : {},
                box          = misc.hasOwnProperty(pms, 'box') ? pms.box : wrap,
                params       = misc.hasOwnProperty(pms, 'params') ? pms.params : [];
            
            // use cached params
            if ( $.isArray(params) && params.length > 0 ) {
                import_params = params;
                return params;
            }
 
            //import-parameters[import_type]: input, output
            box.find('input[name^="import-parameters"]').each(function (i) {
                var $this   = $(this),
                    type    = $this.prop('type'),
                    name    = $this.prop('name').replace('import-parameters[', '').replace(']', ''),
                    value   = $this.val(),
                    param   = {};

                var add = true;
                if ( type == 'radio' || type == 'checkbox' ) {
                    if ( !$this.prop('checked') ) add = false;
                } else if ( type == 'range' ) {
                    if ( value >= 100 ) value = 'all';
                }

                param = { 'name': name, 'value': value };
                if ( add ) {
                    params.push( param );
                }
            });

            // import in
            params.push( { 'name': 'to-category', 'value': box.find('select#WooZoneLite-to-category').val() } );

            //console.log( params );
            import_params = params;
            return params;
        }
        
        // current step: number of: products, product images, product variations...
        function get_products_data( pms ) {
            var pms          = typeof pms == 'object' ? pms : {},
                box          = misc.hasOwnProperty(pms, 'box') ? pms.box : wrap,
                params       = misc.hasOwnProperty(pms, 'params') ? pms.params : get_parameters( pms ),
                ret          = {
                    'nb_prods'          : 0,
                    'nb_variations'     : 0,
                    'nb_images'         : 0
                };

            // selected products in queue
            var found_images        = find_parameter( params, 'nbimages' ),
                found_variations    = find_parameter( params, 'nbvariations' );
            containers.queueprods.prods.find('ul li.selected').each(function(i) {
                var __ret = get_prod_data( $(this), {
                    'params'            : params,
                    'found_images'      : found_images,
                    'found_variations'  : found_variations
                });
                for (var i in ret) {
                    if ( misc.hasOwnProperty( ret, i ) ) {
                        ret[i] += __ret[i];
                    }
                }
            });
            current_prods_data = ret;
            return ret;
        }
        
        function get_prod_data( that, pms ) {
            var $this               = that,
                pms                 = typeof pms == 'object' ? pms : {},
                params              = misc.hasOwnProperty(pms, 'params') ? pms.params : get_parameters( pms ),
                found_images        = misc.hasOwnProperty(pms, 'found_images')
                    ? pms.found_images : find_parameter( pms.params, 'nbimages' ),
                found_variations    = misc.hasOwnProperty(pms, 'found_variations')
                    ? pms.found_variations : find_parameter( pms.params, 'nbvariations' ),
                asin                = $this.data('asin'),
                s                   = $this.data('settings'),
                nb_images           = s.nb_images,
                nb_variations       = s.nb_variations,
                ret                 = {
                    'nb_prods'          : 0,
                    'nb_variations'     : 0,
                    'nb_images'         : 0
                };

            //console.log( asin, s );
            if ( found_images.status && found_images.value != 'all' ) {
                nb_images = parseInt( nb_images > found_images.value ? found_images.value : nb_images );
            }
            if ( found_variations.status && found_variations.value != 'all' ) {
                nb_variations = parseInt( nb_variations > found_variations.value ? found_variations.value : nb_variations );
            }
            nb_images += nb_variations;
            ret.nb_images       += parseInt( nb_images );
            ret.nb_variations   += parseInt( nb_variations );
            ++ret.nb_prods;
            return ret;
        }
        
        function calculate_products_data( what, current, operation ) {
            var ret = ( what == 'total' ? total_prods_data : elapsed_prods_data );
  
            for (var i in ret) {
                if ( misc.hasOwnProperty( ret, i ) ) {
                    if ( operation == 'add' ) {
                        ret[i] += current[i];
                    } else {
                        ret[i] -= current[i];
                    }
                }
            }
            
            // return
            what == 'total' ? total_prods_data = ret : elapsed_prods_data = ret;
            //console.log( total_prods_data, elapsed_prods_data );
            return ret;
        }

        function show_estimate( pms ) {
            var pms         = typeof pms == 'object' ? pms : {},
                speed       = pms.speed,
                time        = pms.time,
                level_prev  = logo_level,
                level       = build_logo_levels( speed ),
                cssClass    = 'WooZoneLite-insane-logo-level' + level,
                text        = typeof lang != 'undefined' ? lang['speed_level' + level] : '',
                time_txt    = get_time_converted( time );
            logo_level = level;
            
            if ( speed > 0 ) {
                text += ' ' + lang.speed_value.replace('%s', '<strong>'+speed+'</strong>');
            }

            // speedometer index/hand
            change_speedometer_value( speed );
            
            // estimated time text
            time_txt = $.trim( time_txt );
            containers.importprods.time.find('span').html( time_txt );

            // speed logo & text
            containers.importprods.logo.html( '<p>'+text+'</p>' );
            containers.importprods.logo
                .removeClass('WooZoneLite-insane-logo-level' + level_prev)
                .addClass('WooZoneLite-insane-logo-level' + level);
        }
        
        function show_estimate_screen( pms ) {
            var pms         = typeof pms == 'object' ? pms : {},
                box_screen  = misc.hasOwnProperty(pms, 'box_screen') ? pms.box_screen : screen,
                speed       = pms.speed,
                time        = pms.time,
                speed_text  = '',
                time_txt    = get_time_converted( time );
            
            //if ( speed > 0 ) {
                speed_text = lang.speed_value.replace('%s', '<strong>'+speed+'</strong>');
                box_screen.find('#WooZoneLite-iip-estimate-speed').html( speed_text );
            //}

            // estimated time text
            time_txt = $.trim( time_txt );
            box_screen.find('#WooZoneLite-iip-estimate-time span').html( time_txt );

            if ( time_txt == '' ) {
                //box_screen.find('#WooZoneLite-iip-estimate-time span').html( '--' );
                show_process_status( box_screen, process_status );
            }
        }
        
        function show_process_status( box_screen, status ) {
            var status      = status || 'run',
                status_html = '';
            
            status_html = lang.process_status_run;
            if ( status == 'stop' ) {
                status_html = lang.process_status_stop;
            } else if ( status == 'stop_' ) {
                status_html = is_auto ? lang.auto__process_status_stop_
                    : lang.process_status_stop_;
            } else if ( status == 'finished' ) {
                status_html = lang.process_status_finished;
            }
            
            var esb = is_auto ? '#WooZoneLite-iip-estimate-status-auto' : '#WooZoneLite-iip-estimate-status';

            box_screen.find(esb + ' span').html( status_html );
            if ( $.inArray(status, ['stop', 'finished', 'stop_']) > -1 ) {
                //box_screen.find('#WooZoneLite-iip-estimate-status input').addClass('gray').prop('disabled', true);
                box_screen.find(esb + ' input')
                    .data('is_close', true)
                    .addClass('green').val( lang.btn_close );
            }            
        }

        function build_logo_levels( speed ) {
            var level = 1;
            if ( speed <= 16 ) {
                level = 1;
            } else if ( speed > 16 && speed <= 29 ) {
                level = 2;
            } else if ( speed > 29 && speed <= 42 ) {
                level = 3;
            } else if ( speed > 42 && speed <= 70 ) {
                level = 4;
            } else if ( speed > 70 && speed <= 100 ) {
                level = 5;
            } else if ( speed > 100 ){
                level = 6;
            }
            return level;
        }
        
        // Loading
        function loading( status, msg, from ) {
            var msg         = msg || '',
                from        = from || '';

            if ( from == 'external' ) {
                //if (status == 'close') return false; //debug!
                row_loading( big_parent, status, {html: msg} );
            } else {
            
                var __screen = null;
                
                // close & remove old screen
                __screen = is_auto ? big_parent.find('#WooZoneLite-iip-screen-auto')
                    : big_parent.find('#WooZoneLite-iip-screen');
                if ( __screen.length ) {
                    __screen.remove();
                    screen = null;
                }

                // Load import screen                    
                if ( status == 'show' ) {
                    if ( is_auto ) {
                        __screen = containers.importprods.screen_tmp_auto.clone();
                        __screen.prop('id', 'WooZoneLite-iip-screen-auto');
                    }
                    else {
                        __screen = containers.importprods.screen_tmp.clone();
                        __screen.prop('id', 'WooZoneLite-iip-screen');
                    }
                    
                    if ( false == is_auto ) {
                        // import images
                        var found = find_parameter( import_params, 'import_type' );
                        if ( current_prods_data.nb_images <= 0 || ( found.status && found.value != 'default' ) ) {
                            __screen.find('.WooZoneLite-iip-process-progress-bar.im-images').remove();
                        }
                        // import variations
                        if ( current_prods_data.nb_variations <= 0 ) {
                            __screen.find('.WooZoneLite-iip-process-progress-bar.im-variations').remove();
                        }
                    }
                    
                    big_parent.prepend( __screen );
                    screen = is_auto ? big_parent.find('#WooZoneLite-iip-screen-auto')
                        : big_parent.find('#WooZoneLite-iip-screen');
                    screen.css({
                        'width'     : parseInt( big_parent.outerWidth() ),
                        'height'    : parseInt( big_parent.outerHeight() + 42 ),
                        'top'       : '-42px'
                    });
                    //console.log( screen );
                }
            }
            
            if ( from == '' ) {
                loadprod.loading( status, lang.importprods_inprogress, 'external' );
            }
            return screen;
        };

        function set_status_msg( status, msg, op ) {
            set_status_msg_generic( status, msg, op, 'importprod' );
        };

        // UTILS
        // return an object with properties d, h, m and s: the number of days, hours, minutes, and seconds
        function convert_miliseconds2time( ms ) {
            var d, h, m, s;
            s = Math.floor(ms / 1000);
            m = Math.floor(s / 60);
            s = s % 60;
            h = Math.floor(m / 60);
            m = m % 60;
            d = Math.floor(h / 24);
            h = h % 24;
            return { d: d, h: h, m: m, s: s };
        };
        
        function get_time_converted( ms ) {
            var ret = [],
                time = convert_miliseconds2time( ms );
            var l = typeof lang != 'undefined'
                    ? { d: lang.day, h: lang.hour, m: lang.min, s: lang.sec }
                    : { d: 'day', h: 'hour', m: 'min', s: 'sec' };
            
            // days
            for (var i in time) {
                if ( !misc.hasOwnProperty( time, i ) ) continue;
                if ( time[i] > 0 ) {
                    ret.push( time[i] + ' ' + ( time[i] > 1 ? l[i]+'s' : l[i] ) );
                }
            }
            return ret.join(' ');
        }

        function find_parameter( pms, key ) {
            var pms     = typeof pms == 'object' ? pms : {},
                ret     = { status: false, value: null };
            
            for (var i in pms) {
                var current = pms[i];
                if ( typeof current == 'object' && misc.hasOwnProperty( current, 'name' ) ) {
                    if ( current.name == key ) {
                        ret = {
                            status          : true,
                            value           : current.value
                        };
                        return ret;
                    }
                }
            }
            return ret;
        }
        
        function sleep( sec, callback ) {
            var sec         = sec || 1,
                milisec     = sec * 1000;

            if ( $.isFunction(callback) ) {
                var timer = setTimeout( function() {
                    callback();
                }, milisec );
            }
        }


        // ASYNCHRONOUS PRODUCTS IMPORT - inserted in the queue!

        // init when starting new import
        function auto__init_onstart() {
            time_start          = new Date().getTime(); // in miliseconds
            process_status      = 'start';
            
            // get the import parameters
            get_parameters();
            
            var nb_prods = containers.queueprods.prods.find('ul li.selected').length;            
            
            // verify if there are products selected for import!
            if ( nb_prods == 0 ) {
                process_status = 'finished';
                return false;
            }
            
            var __ = {
                'nb_prods'          : 0,
                'nb_variations'     : 0,
                'nb_images'         : 0
            };
            elapsed_prods_data = $.extend( {}, __ ); //Object.create( __ );
            total_prods_data = $.extend( {}, __ ); //Object.create( __ );
            total_prods_data.nb_prods = nb_prods;
            //console.log( total_prods_data, elapsed_prods_data ); 
            
            screen = loading( 'show', lang.loading );

            return true;
        }
        
        function auto__products_import() {
            var operation_id = new Date().getTime(); // in miliseconds
            heartbeat.start( operation_id, 'import', { 'is_auto' : true, 'msg_sep' : '' } );
            
            function do_import( is_init ) {
                //console.log( 'import: ', new Date().getTime() );

                var is_init = is_init || false;
                if ( is_init ) {
                    var init_status = auto__init_onstart();
                    if ( !init_status ) {
                        heartbeat.stop();
                        alert( lang.import_empty );
                        return false;
                    }
                }
                
                // remove old imported product from queue
                if ( !$.isEmptyObject(current_auto_queue) && current_auto_queue.list.length ) {
                    current_auto_queue.list.remove();
                }
                
                // verify if empty queue - no more products!
                loadprod.is_empty_queue();
                
                // current progress bars
                calculate_progress({
                    'box_screen'    : screen,
                    'show_screen'   : true,
                    'params'        : import_params
                });
     
                if ( TEST ) {
                    if ( test_loop_current >= test_loop_max ) {
                        process_status = 'stop';
                        show_process_status( screen, process_status );
                        heartbeat.stop();
                        alert( 'TEST Mode: loop max reached!' );
                        return false;
                    }
                    ++test_loop_current;
                }

                // import process is Stoped!
                if ( $.inArray(process_status, ['stop', 'finished']) > -1 ) {
                    //screen.find('.WooZoneLite-iip-tail ul.WZC-keyword-attached').html('');
                    show_process_status( screen, process_status );
                    heartbeat.stop();
                    return false;
                }

                // current & next product data
                current_auto_queue = auto__get_current_queue({
                    'box_screen'    : screen,
                    'show'          : true,
                    'nb'            : auto_queue_step_nb
                });

                // import process is Finished!
                if ( current_auto_queue.list.length <=0 ) {
                    //screen.find('.WooZoneLite-iip-tail ul.WZC-keyword-attached').html('');
                    process_status = 'finished';
                    show_process_status( screen, process_status );
                    heartbeat.stop();
                    return false;
                }

                // here - IMPORT CURRENT PRODUCT
                //current_asin = current_prod.current.data('asin');
                process_status = 'run';
                var current_prod_data = auto__get_prod_data({
                    'nb'            : current_auto_queue.list.length
                });
                //console.log( current_auto_queue, current_prod_data );

                // TESTING
                if ( TEST ) {
                    // add product ASIN to imported list!
                    for (var ii in current_auto_queue.asins) {
                        loadprod.set_results( [current_auto_queue.asins[ii]], 'imported' );
                    }
                
                    // elapsed
                    calculate_products_data( 'elapsed', current_prod_data, 'add' );
                    //console.log( total_prods_data, elapsed_prods_data );
                
                    // sleep - in seconds
                    sleep( 2, function() {
                        do_import();
                    });
                }
                // REAL AJAX IMPORT
                else {
                    var data        = [],
                        params      = import_params;
  
                    params = $.param( params ); // turn the result into a query string
                    data.push(
                        {name: 'debug_level',       value: debug_level},
                        {name: 'action',            value: 'WooZoneLite_AutoImportAjax'},
                        {name: 'sub_action',        value: 'auto_save_queue'},
                        {name: 'is_init',           value: is_init ? 1 : 0},
                        {name: 'asins',             value: current_auto_queue.asins},
                        {name: 'operation_id',      value: operation_id},
                        {name: 'params',            value: params},
                        {name: 'from_op',           value: 'auto#'+operation_id}
                    );
                    
                    data = $.param( data ); // turn the result into a query string
                    //console.log( data ); return false;
                    
                    // ajax request
                    $.post(ajaxurl, data, function(response) {

                        set_status_msg( response.status, response.msg, 'import' );
                        
                        // elapsed
                        calculate_products_data( 'elapsed', current_prod_data, 'add' );
                        //console.log( total_prods_data, elapsed_prods_data );

                        // reset import settings
                        if ( misc.hasOwnProperty(response, 'import_settings') ) {
                            s = response.import_settings;
                        }
                        if ( misc.hasOwnProperty(response, 'general_settings') ) {
                            gs = response.general_settings;
                        }
                        
                        // success
                        if (response.status == 'valid') {
                            // add product ASIN to imported list!
                            for (var ii in current_auto_queue.asins) {
                                loadprod.set_results( [current_auto_queue.asins[ii]], 'imported' );
                            }
                        }
                        // error occured
                        else {
                            // add product ASIN to NOT Imported (with Errors) list!
                            for (var ii in current_auto_queue.asins) {
                                loadprod.set_results( [current_auto_queue.asins[ii]], 'import_errors' );
                            }
                        }
                        
                        do_import();
                        return true;

                    }, 'json')
                    .fail(function() { heartbeat.stop(); })
                    .done(function() {})
                    .always(function() {});
                }
            }
            do_import( true ); // init cycle
        }
        
        function auto__get_current_queue( pms ) {
            var pms          = typeof pms == 'object' ? pms : {},
                box_screen   = misc.hasOwnProperty(pms, 'box_screen') ? pms.box_screen : screen,
                show         = misc.hasOwnProperty(pms, 'show') ? pms.show : true,
                nb           = misc.hasOwnProperty(pms, 'nb') ? parseInt( pms.nb ) : auto_queue_step_nb;
            
            var _current     = containers.queueprods.prods.find('ul li.selected');
            if ( _current.length ) {
                _current = _current.slice(0, nb);
            }
            
            var _asins = [];
            if ( _current.length ) {
                _current.each(function(i) {
                    var $this = $(this),
                        asin = $this.data('asin');
                    _asins.push( asin );
                });
            }
 
            if ( show ) {

                // mark current imported product in queue 
                //_current.addClass('imported')
                //    .find('input').prop('disabled', true);
            }
            return {
                'list'      : _current,
                'asins'     : _asins
            };
        }
        
        function auto__get_prod_data( pms ) {
            var pms               = typeof pms == 'object' ? pms : {},
                nb                = misc.hasOwnProperty(pms, 'nb') ? pms.nb : auto_queue_step_nb;
                
            var __ = {
                'nb_prods'          : nb,
                'nb_variations'     : 0,
                'nb_images'         : 0
            };
            return __;
        }

        // external usage
        return {
            // attributes
            'v'                         : get_vars,
            
            // methods
            '__'                        : __,
            'loading'                   : loading,
            'set_status_msg'            : set_status_msg,
            'estimate'                  : estimate,
            'calculate_progress'        : calculate_progress,
            'calculate_products_data'   : calculate_products_data,
            'get_prod_data'             : get_prod_data,
            'stop_import'               : stop_import
        };
    })();

    
    // :: Heartbeat
    var heartbeat = (function() {
        
        var DISABLED                = false; // disable this module!
        var DEBUG                   = false,
            TEST                    = 0,
            interval                = 1000, // interval in miliseconds
            timer                   = null, // timer
            for_module              = null, // for which module
            current_status          = null, // current process status
            operation_id            = null, // current operation id
            inner_method            = 'ajax', // posible values: ajax | cookie
            is_auto                 = false, // is auto import
            msg_sep                 = false; // messages separator

        // Test!
        function __() { console.log('__ method'); };
        
        // get public vars
        function get_vars() {
            return $.extend( {}, {
                current_status : current_status,
                operation_id   : operation_id
            } );
        };
        
        function start( id, mod, pms ) {
            for_module      = mod || 'load';
            operation_id    = id;
            
            var pms         = typeof pms == 'object' ? pms : {};
            is_auto         = misc.hasOwnProperty(pms, 'is_auto') ? pms.is_auto : false;
            msg_sep         = misc.hasOwnProperty(pms, 'msg_sep') ? pms.msg_sep : false;
 
            if ( DISABLED ) return false;

            var _status = current_status;
            if ( _status != 'run' ) { // not started yet!
                current_status = 'run';
                doit();
            }
        }
        
        function stop() {
            show_response( 'close', lang.closing );
            current_status = 'stop';
            clearTimeout( timer );
            timer = null;
        }
        
        function doit() {
            function _doit() {
                //console.log( 'heartbeat: ', new Date().getTime() );

                if ( current_status == 'stop' ) {
                    show_response( 'close', lang.closing );
                    return false;
                }
                current_status = 'run';

                if ( inner_method == 'ajax' ) {

                    var data        = [];
                    data.push(
                        {name: 'debug_level',       value: debug_level},
                        {name: 'action',            value: 'WooZoneLiteIM_InsaneAjax'},
                        {name: 'sub_action',        value: 'heartbeat'},
                        {name: 'operation',         value: for_module},
                        {name: 'operation_id',      value: operation_id}
                    );
                    if ( false !== msg_sep ) {
                        data.push( {name: 'msg_sep',      value: msg_sep} );
                    }
                        
                    data = $.param( data ); // turn the result into a query string
                    //console.log( data ); return false;
                        
                    // ajax request
                    $.post(ajaxurl, data, function(response) {
                            
                        // success
                        if (response.status == 'valid') {
                        }
                        // error occured
                        else {
                        }
                        
                        // safe verify!
                        if ( current_status == 'stop' ) {
                            show_response( 'close', lang.closing );
                            return false;
                        }
    
                        show_response( 'show', response.msg );
    
                        timer = setTimeout( function() {
                            _doit();
                        }, interval );
                
                        return true;
                            
                    }, 'json')
                    .fail(function() {})
                    .done(function() {})
                    .always(function() {});
                    
                } else if ( inner_method == 'cookie' ) {

                    var resp_msg = cookies.read('WooZoneLite_opStatusMsg');
                    //console.log( resp_msg );
                    if ( $.trim(resp_msg) != '' ) {
                        resp_msg = decodeURIComponent( resp_msg );
                        resp_msg = JSON && JSON.parse(resp_msg) || $.parseJSON(resp_msg);
                    }

                    show_response( 'show', resp_msg );

                    timer = setTimeout( function() {
                        _doit();
                    }, interval );
                }
            };
            _doit();
        }
        
        function show_response( status, msg ) {
            var container   = null,
                loader      = null,
                loader_txt  = null;

            if ( for_module == 'load' ) {
                container   = containers.loadprods.wrap.parent().parent();
                loader      = container.find('.WooZoneLite-row-loading-marker');
                loader_txt  = loader.find('div.WooZoneLite-loading-text');
            
            } else if ( for_module == 'import' ) {
                container   = containers.importprods.wrap.parent().parent();
                loader      = is_auto ? container.find('#WooZoneLite-iip-screen-auto')
                    : container.find('#WooZoneLite-iip-screen');
                loader_txt  = is_auto ? loader.find('.WZC-keyword-attached-auto')
                    : loader.find('.WooZoneLite-iip-log');
            }
  
            if ( loader.length ) {
                if ( $.trim( msg ) != '' ) {
                    if ( for_module == 'load' ) {
                        loadprod.loading( status, msg, 'external' );

                    } else if ( for_module == 'import' ) {
                        if ( status == 'close' && !is_auto ) {
                            msg = '';
                        }
                        if ( status != 'close' || !is_auto ) {
                            loader_txt.html( msg );
                        }
                    }
                    misc.scrollBottom( loader_txt );
                }
            }
        };
        
        // external usage
        return {
            // attributes
            'v'                     : get_vars,
            
            // methods
            '__'                    : __,
            'start'                 : start,
            'stop'                  : stop
        };
    })();
    
    
    // :: COOKIES
    var cookies = (function(){
        var cookies;
    
        function read(name){
            if(cookies){ return cookies[name]; }
    
            var c = document.cookie.split('; ');
            cookies = {};
    
            for(var i=c.length-1; i>=0; i--){
               var C = c[i].split('=');
               cookies[C[0]] = C[1];
            }
            return cookies[name];
        }
    
        // external usage
        return {
            // attributes
            'read'                     : read
        };
    })();
    
  
    // :: MISC
    var misc = {
    
        hasOwnProperty: function(obj, prop) {
            var proto = obj.__proto__ || obj.constructor.prototype;
            return (prop in obj) &&
            (!(prop in proto) || proto[prop] !== obj[prop]);
        },
    
        arrayHasOwnIndex: function(array, prop) {
            return array.hasOwnProperty(prop) && /^0$|^[1-9]\d*$/.test(prop) && prop <= 4294967294; // 2^32 - 2
        },
    
        arrayIntersect: function(a, b) {
            return $.grep(a, function(i) {
                return $.inArray(i, b) > -1;
            });
        },
        
        arrayUnique: function(array) {
            var a = array.concat();
            for(var i=0; i<a.length; ++i) {
                for(var j=i+1; j<a.length; ++j) {
                    if(a[i] === a[j])
                        a.splice(j--, 1);
                }
            }
            return a;
        },
       
        arrayGetElement: function(array, type) { // second parameter possible values: key | value
            for (var i in array) {
                if (misc.hasOwnProperty(array, i)) {
                    if ( type == 'key' ) return i;
                    return array[i];
                }
            }
        },
       
        arrayRemoveElement: function(array, value) {
            var idx = array.indexOf(value);
            if (idx != -1) array.splice(idx, 1);
            return array;
        },
        
        arraySum: function(array) {
            var total = 0;
            for (var i = 0, n = array.length; i < n; ++i) {
                total += array[i];
            }
            return total;
        },
        
        size: function(obj) {
            var size = 0;
            for (var key in obj) {
                if (misc.hasOwnProperty(obj, key)) size++;
            }
            return size;
        },
        
        get_current_date: function() {
            var UTCstring = (new Date()).toUTCString();
            return UTCstring;
        },
        
        scrollBottom: function( el ) {
            var height = el.scrollHeight || el.prop('scrollHeight');
            el.scrollTop( height );
        }
    };

	// external usage
	return {
		"background_loading": background_loading
	}
})(jQuery);

