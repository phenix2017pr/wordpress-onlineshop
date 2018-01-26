// Initialization and events code for the app
WooZoneLite_wizard = (function ($) {
	"use strict";

	var ajaxurl				= woozonelite_vars.ajax_url,
		lang				= woozonelite_vars.lang;

	var t 					= null,
		ajaxBox 			= null,
		logStatusBox 		= null,
		section             = 'index',
		in_loading_section  = null,
		loading 			= null,
		step_pms			= {};

	var questions			= {
		'site_info'				: {
			'values'				: ['blog', 'store', 'personal', 'other']
		},
		'site_install'			: {
			'values'				: ['fresh_install', 'have_content']
		},
		'site_purpose'			: {
			'values'				: ['have_website', 'new_store', 'have_store', 'custom_checkout']
		}
	};


	// :: init function, autoload
	(function init() {
		
		// load the triggers
		$(document).ready(function(){
			console.log( 'WooZoneLite wizard script is loaded!' );

			t = $("div#WooZoneLiteWizard");
			ajaxBox = t.find('#WooZoneLiteWizard-ajax-response');

			if ( ajaxBox.length ) {
				step_pms = ajaxBox.find('> .subcontainer').data('step_pms');
			}
			//console.log( 'step_pms', step_pms );

			triggers();

			//responsive on small windows
			if( $(window).width() <= 992 ) {
				wz_responsive();
			}
		});

		//responsive on resize window
		$(window).resize(function(){
			wz_responsive();
		});
		
	})();


	// :: TRIGGERS
	function triggers() {
		/*$('div.checkbox label').each(function(){
			var that = $(this);
			that.click(function(){
				that.toggleClass('wz-checked');
			});
		});
		var rangeSlider = function(){
			var slider = $('.range-slider'),
				range = $('.range-slider__range'),
				value = $('.range-slider__value');
			
			slider.each(function(){
		
				//value.each(function(){
				//	var value = $(this).prev().attr('value');
				//	$(this).html(value);
				//});
			
				range.on('input', function(){
					$(this).next(value).html(this.value);
				});
		  
			});
		};
		rangeSlider();*/
		
		// checkbox
		(function() {
			$(document).on('click', 'div.checkbox label', function(e) {
				var that 		= $(this),
					$input 		= that.parent().find('input'),
					is_readonly = $input.length ? $input.is('[readonly]') : false,
					is_checked 	= $input.length ? $input.is(':checked') : false;

				if ( is_readonly ) {
					return false;
				}

				that.toggleClass('wz-checked');
			});
		})();

		// range sliders
		(function(){
			$(document).on('input', 'input[type="range"]', function(e) {
				var that 		= $(this),
					id 			= that.prop('id'),
					val 		= that.val(),
					min 		= that.prop('min'),
					max 		= that.prop('max'),
					step 		= that.prop('step'),
					data_pms 	= that.data('pms') || {},
					$output 	= that.parent().find('output').eq(0),
					$hidden 	= that.parent().find('input[type="hidden"]').eq(0);

				var foundit 	= false,
					val_output 	= val,
					val_hidden 	= val,
					min_to 		= misc.hasOwnProperty( data_pms, 'min_to') ? data_pms.min_to : false,
					max_to 		= misc.hasOwnProperty( data_pms, 'max_to') ? data_pms.max_to : false,
					val_to 		= misc.hasOwnProperty( data_pms, 'val_to') ? data_pms.val_to : false;

				if ( false !== min_to ) {
					if ( ! foundit && min === val ) {
						val_output = typeof min_to[1] !== 'undefined' ? min_to[1] : val;
						val_hidden = min_to[0];
						foundit = true;
					}
				}
				if ( false !== max_to ) {
					if ( ! foundit && max === val ) {
						val_output = typeof max_to[1] !== 'undefined' ? max_to[1] : val;
						val_hidden = max_to[0];
						foundit = true;
					}
				}
				if ( false !== val_to ) {
					if ( ! foundit ) {
						val_output = typeof val_to[1] !== 'undefined' ? val_to[1].replace('%s', val) : val;
						val_hidden = val_to[0].replace('%s', val);
						foundit = true;
					}
				}

				if ( $output.length ) {
					$output.val( val_output );
				}
				if ( $hidden.length ) {
					$hidden.val( val_hidden );
				}
			});
		})();

		// requirements go back
		$('body').on('click', '.wz-requirements-back', function (e) {
			e.preventDefault();

			window.history.back();
		});
		

		// check amazon keys
		$('body').on('click', '.WooZoneLiteWizardCheckAmzKeys', function (e) {
			e.preventDefault();

			amzCheckAWS( $(this) );
		});

		// form next button/link
		///*
		$('body').on('click', 'form .wz-next', function (e) {
			var current_step = step_pms['current'];

			// no validation needed outside these steps
			if ( $.inArray(current_step, ['questions', 'questions2', 'questions3', 'amazon_config']) <= -1 ) {
				return true;
			}

			var validateStat 	= true,
				msg 			= '';

			if ( 'questions' == current_step ) {
				validateStat = validate_questions( 'site_info' );
			}
			else if ( 'questions2' == current_step ) {
				validateStat = validate_questions( 'site_install' );
			}
			else if ( 'questions3' == current_step ) {
				validateStat = validate_questions( 'site_purpose' );
			}
			else if ( 'amazon_config' == current_step ) {
				validateStat = validate_amazon_keys();
			}

			if ( $.inArray(current_step, ['questions', 'questions2', 'questions3', 'amazon_config']) > -1 ) {
				msg = validateStat['msg'];
				validateStat = validateStat['status'];
			}

			if ( validateStat ) {
				log_status_message( '', 'close' );
				return true;
			}

			msg = '<div class="wz-log-message wz-log-error">' + msg + '</div>';
			log_status_message( msg );

			e.preventDefault();
			return false;
			//save_form( $(this) );
		});
		//*/

		///*
		// Bind the hashchange event.
		// Alerts every time the hash changes!
		$(window).hashchange(function () {
			hashChange();
		});
		// Trigger the event (useful on page load).
		$(window).hashchange();
		//*/
	}


	// :: SAVE FORM
	/*
	function save_form( that ) {
		var $form = that.parents('form').eq(0);

		//var data = build_form_data( $form );
		//console.log( data ); return false;

		$form.submit();
	}

	function build_form_data( $form ) {
		// because serializeArray() ignores: unset checkboxes, radio buttons, empty simple selects, empty multiple selects
		var elem = {
			checkbox 	: null,
			radio 		: null,
			select 		: null,
			mselect 	: null
		};

		//input[type="checkbox"]:not(:checked)
		elem.checkbox = $form.find('input[type="checkbox"]:not(:checked)');

		//NOT NEEDED ANYMORE/ select:not(:selected)
		//elem.select = $form.find('select').filter(function(i) {
		//	return $(this).val() == '';
		//});

		//TODO/ input[type="radio"]:not(:checked)
		//elem.radio = $form.find('input[type="radio"]:not(:checked)');

		//TODO/ select[multiple]:not(:selected)
		//elem.mselect = $form.find('select[multiple]:not(:selected)'); //TODO

		//console.log( elem.select ); return false;

		//elem.checkbox.each();


		//:: NON AJAX - form submit with refresh
		if (0) {
			$form.find('input[type="checkbox"]').each(function (i) {
				var $this 		= $(this),
					id 			= $this.prop('id'),
					name 		= $this.prop('name'),
					$hidden 	= $('<input type="hidden">');

				var _pms 		= $this.data('pms') || {},
					value_not 	= misc.hasOwnProperty( _pms, 'value_not') ? _pms.value_not : false;

				if ( false !== value_not ) {
					// add hidden input for checkbox unchecked case => when submitting
					if ( ! $this.parent().find('input[type="hidden"][name="' + name + '"]').length ) {
						$hidden.attr({
							'id'		: id,
							'name'		: name,
							'value'		: value_not
						});
						$this.before( $hidden );
					}
				}
			});
		}

		//:: AJAX - form data serialize
		if (0) {
			var data = $form.serializeArray();
			//console.log( $form, data ); return false;

			for (var type in elem) {
				var _elem = elem[type];

				if ( ! _elem ) continue;

				var new_value = false;
				if ( 'checkbox' == type ) {
					var _pms 		= _elem.data('pms') || {},
						value_not 	= misc.hasOwnProperty( _pms, 'value_not') ? _pms.value_not : false;

					new_value = value_not;
				}

				data = data.concat(elem[type].map(
					function() {
						return {
							"name" 	: this.name,
							"value" : false !== new_value ? new_value : this.value
						};
					}).get()
				);
			} // end for
			console.log( data ); return false;

			data = $.param( data );
			return data;
		} // end form data serialize - for ajax

		return true;
	}
	*/


	// :: VALIDATE QUESTIONS
	function validate_questions( question_key ) {
		var ret = {
			'status' 	: true,
			'msg' 		: ''
		};

		for (var key in questions) {
			if ( key != question_key ) {
				continue;
			}
			if ( ! misc.hasOwnProperty( questions, key ) ) {
				continue;
			}

			var question 	= questions[ key ],
				$answer		= $('input[name="WooZoneLite-wizard[' + key + ']"]:checked'),
				answer		= $answer.length ? $answer.val() : '';

			if ( $.inArray(answer, question['values']) <= -1 ) {
				ret['status'] = false;
				ret['msg'] = lang.questions_answer_invalid;
				return ret;
			}
		} // end for

		return ret;
	}


	// :: AMAZON CHECK KEYS
	function validate_amazon_keys() {
		var ret = {
			'status' 	: true,
			'msg' 		: ''
		};

		//console.log( $('input[name*="AccessKeyID"]'), $('input[name*="SecretAccessKey"]') );
		//console.log( $('input[name*="AffiliateID"]') );

		// clean
		$('input[name*="AccessKeyID"]').val(
			$.trim( $('input[name*="AccessKeyID"]').val() )
		);
		$('input[name*="SecretAccessKey"]').val(
			$.trim( $('input[name*="SecretAccessKey"]').val() )
		);
		$('input[name*="AffiliateID"]').each(function() {
			$(this).val(
				$.trim( $(this).val() )
			);
		});

		// validate & autocomplete
		if ( '' == $('input[name*="AccessKeyID"]').val() ) {
			$('input[name*="AccessKeyID"]').val( '' );
		}
		if ( '' == $('input[name*="SecretAccessKey"]').val() ) {
			$('input[name*="SecretAccessKey"]').val( '' );
		}
		//$('input[name*="AffiliateID"]').each(function() {
		//	if ( '' == $(this).val() ) {
		//		$(this).val( 'aateam' );
		//	}
		//});

		//var aff_all = $('input[name*="AffiliateID"]');
		var aff_ok = $('input[name*="AffiliateID"]').filter(function() {
			return $.trim( $(this).val() ) != '';
		});
		//console.log( aff_all, aff_ok  );
		if ( ! aff_ok.length ) {
			ret['status'] = false;
			ret['msg'] = lang.check_keys_affid_invalid;
		}

		return ret;
	}

	function amzCheckAWS( that )
	{
		// validate
		var validateStat 	= validate_amazon_keys(),
			msg 			= '';

		msg = validateStat['msg'];
		validateStat = validateStat['status'];

		if ( ! validateStat ) {
			msg = '<div class="wz-log-message wz-log-error">' + msg + '</div>';
			log_status_message( msg );
			return false;
		}


		// do it
		var old_value 	= that.html();
		//var submit_btn 	= that.parents('form').eq(0).find('input[type=submit]');

		that.addClass('checking');
		that.html( lang.check_keys_check ); 

		(function() {
			jQuery.post(ajaxurl, {
					'action' 			: 'WooZoneLiteWizard',
					'sub_action' 		: 'check_amz_keys',
					'AccessKeyID'		: $('input[name*="AccessKeyID"]').val(),
					'SecretAccessKey'	: $('input[name*="SecretAccessKey"]').val(),
					'country'			: $('select[name*="country"]').val(),
					'main_aff_id'		: $('select[name*="main_aff_id"]').val()
				}, function(response) {

					var msg = response.msg + "\n\n";

					if( response.status == 'valid' ){
						msg += lang.check_keys_success;
						msg = '<div class="wz-log-message wz-log-success">' + msg + '</div>';
					}
					else{
						msg += lang.check_keys_error;
						msg = '<div class="wz-log-message wz-log-error">' + msg + '</div>';
					}

					log_status_message( msg );

					that.html( old_value ).removeClass('checking');

			}, 'json');
		})();
	}


	// :: LOG STATUS MESSAGE
	function log_status_message( msg, status ) {
		var status = status || 'show';

		logStatusBox = t.find('.wz-log-status');
		//console.log( logStatusBox, msg );
		if ( logStatusBox.length ) {
			logStatusBox.html(
				msg
				.replace(/\n/gi, "<br/>")
				// \$& = whole string matched ; \$1 = first string matched...
				.replace(/(https?[^\s]+)(?:\.html)/gi, "<a href='\$1' target='_blank'>\$1</a>")
			);

			if ( 'close' == status ) {
				logStatusBox.hide();
			} else {
				logStatusBox.show();
			}
		}
		else {
			if ( 'close' != status ) {
				alert( msg );
			}
		}
	}


	// :: RESPONSIVE
	function wz_responsive(){
		$('td.wz-tcolspan, td.wz-tselect, td.wz-trange, td.wz-ttextarea, td.wz-ttext').each(function(){
			var that = $(this),
				wz_toption = that.parent().find('.wz-toption').text();

			that.attr('colspan', '');
			that.prepend( '<p>' + wz_toption + '</p>' );
			that.parent().find('.wz-toption').remove();
		});
	}


	// :: LOADER
	function ajax_loading( label ) 
	{
		// append loading
		loading = $('<div class="WooZoneLite-loader-holder"><div class="WooZoneLite-loader"></div> ' + ( label ) + '</div>');
		ajaxBox.html(loading);
	}

	function take_over_ajax_loader( label, target )
	{
		loading = $('<div class="WooZoneLite-loader-holder-take-over"><div class="WooZoneLite-loader"></div> ' + ( label ) + '</div>');
		
		if( typeof target != 'undefined' ) {
			target.append(loading);
		}else{
			t.append(loading);
	   }
	}

	function take_over_ajax_loader_close()
	{
		t.find(".WooZoneLite-loader-holder-take-over").remove();
	}


	//:: HASH CHANGE
	function hashChange() {
		// main container exists?
		if ( t.size() <= 0 ) {
			return false;
		}

		if ( location.hash != "" ) {
			section = location.hash.replace("#!/", '');

			var current_step 	= step_pms['current'],
				step_next 		= step_pms['next'];

			var $form 		= ajaxBox.find('form').eq(0),
				_wz_nonce 	= $form.find('#_wz_nonce').val();

			// next is hit => save settings too
			if ( (section == step_next) && ('index' != current_step) ) {
				var form_data = $form.serialize();

				makeRequest({
					'_wz_nonce'	: _wz_nonce,
					'tosave'	: form_data
				});
			}
			else {
				makeRequest({
					'_wz_nonce'	: _wz_nonce
				});
			}
		}
		//else {
		//	if ( location.search == "?page=WooZoneLite_wizard" ) {
		//		makeRequest();
		//	}
		//}
	}


	// :: AJAX REQUEST
	function makeRequest( pms ) {
		var pms 		= pms || {},
			_wz_nonce 	= misc.hasOwnProperty( pms, '_wz_nonce' ) ? pms._wz_nonce : '',
			tosave 		= misc.hasOwnProperty( pms, 'tosave' ) ? pms.tosave : '';

		// fix for duble loading of js function
		if( in_loading_section == section ){
			return false;
		}
		in_loading_section = section;

		// do not exect the request if we are not into our ajax request pages
		if( ajaxBox.size() == 0 ) return false;

		take_over_ajax_loader( "loading..." );
		//ajax_loading( "Loading section: " + section );

		var data = {
			'action' 			: 'WooZoneLiteWizard',
			'sub_action' 		: 'load_step',
			'step'		 		: section,
			'_wz_nonce'			: _wz_nonce,
			'tosave'			: tosave
		};

		jQuery.post(ajaxurl, data, function (response) {

			if( response.status == 'redirect' ){
				window.location = response.url;
				return;
			}
			//console.log( response  ); return false;

			if (response.status == 'valid') {

				setTimeout(function() {
				//loading.fadeOut( 10, function() {

					ajaxBox.html( response.html );

					setTimeout(function() {
						if ( ajaxBox.length ) {
							step_pms = ajaxBox.find('> .subcontainer').data('step_pms');
						}
						//console.log( 'step_pms', step_pms );
					}, 10);
				//});
				}, 10);
			}

			take_over_ajax_loader_close();
		}, 'json')
		.fail(function() { take_over_ajax_loader_close(); })
		.done(function() {})
		.always(function() {});
	}


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
	}
})(jQuery);