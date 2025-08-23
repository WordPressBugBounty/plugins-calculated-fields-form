	$.fbuilder['version'] = '5.3.87';
	$.fbuilder['controls'] = $.fbuilder['controls'] || {};
	$.fbuilder['forms'] = $.fbuilder['forms'] || {};
	$.fbuilder['css'] = $.fbuilder['css'] || {};
	$.fbuilder['isMobile'] = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
	$.fbuilder['isIOS'] = /iPhone|iPad|iPod/i.test(navigator.userAgent);

	$.fbuilder['isNumeric'] = function(n){return !isNaN(parseFloat(n)) && isFinite(n);};

	$.fbuilder['htmlEncode'] = window['cff_esc_attr'] = function(value)
	{
		return $('<div/>').text(value).html()
				.replace(/"/g, "&quot;");
				//.replace(/&amp;lt;/g, '&lt;')
				//.replace(/&amp;gt;/g, '&gt;');
	};

	$.fbuilder['htmlDecode'] = window['cff_html_decode'] = function(value)
	{
		value = String(value)
				.replace(/<script\b[^>]*>([\s\S]*?)<\/script>/gi, '')
				.replace(/<style\b[^>]*>([\s\S]*?)<\/style>/gi, '')
				.replace(/(\b)(on[a-z]+)\s*=/gi, "$1_$2=");
		return cff_sanitize(String((/&(?:#x[a-f0-9]+|#[0-9]+|[a-z0-9]+);?/ig.test(value)) ? $('<div/>').html(value).html() : value).replace(/(\b)\_style(\b)/gi, '$1style$2'), true);
	};

	if ('DOMPurify' in window) {
		DOMPurify.addHook('uponSanitizeAttribute', function(currentNode, hookEvent, config) {
			if (currentNode.tagName === 'A' && currentNode.hasAttribute('target')) {
				// Preserve the target attribute.
				hookEvent.forceKeepAttr = true;
				currentNode.setAttribute('rel', 'noopener noreferrer');
			}
		});
	}

	$.fbuilder['sanitize'] = window['cff_sanitize'] = function(value, controls, templates)
	{
        if(typeof value == 'string') {
			if(typeof controls != 'undefined' && controls) value = value.replace(/<\/?(textarea|input|button|checkbox|radio|select|option)[^>]*>/gi, '');

			if ('DOMPurify' in window) {
				let forbid_tags = ['style', 'script', 'link'];
				if (typeof controls != 'undefined' && controls) {
					forbid_tags = forbid_tags.concat(['textarea', 'input', 'button', 'checkbox', 'radio', 'select', 'option']);
				}
				let args = {FORBID_TAGS: forbid_tags};
				if ( typeof templates != 'undefined' && templates ) args['ADD_TAGS'] = ['x-template'];
				value = DOMPurify.sanitize(value, args);
			} else if ( 'DOMParser' in window ) {
				const parser = new DOMParser();
				const doc = parser.parseFromString(value, 'text/html');

				// Remove all script, style, and link tags.
				const tags = doc.querySelectorAll('script,style,link');
				tags.forEach(tag => tag.remove());

				// Remove all form controls.
				if (typeof controls != 'undefined' && controls) {
					const ctr_tags = doc.querySelectorAll('textarea,input,button,checkbox,radio,select,option');
					ctr_tags.forEach(tag => tag.remove());
				}

				// Remove all event handlers and inline style attributes.
				const elements = doc.querySelectorAll('*');
				elements.forEach(element => {
					for (const attr of element.getAttributeNames()) {
						if (attr.startsWith('on')) {
							element.removeAttribute(attr);
						}
					}
				});

				value = doc.documentElement.getElementsByTagName('BODY')[0].innerHTML;
			} else {
				value = value.replace(/<script\b.*\bscript>/ig, '')
							 .replace(/<script[^>]*>/ig, '')
							 .replace(/<link[^>]*>/ig, '')
							 .replace(/(\b)(on[a-z]+)\s*=/ig, "$1_$2=")
							 .replace(/<style\b.*\bstyle>/ig, '')
							 .replace(/<style[^>]*>/ig, '');

				value = $('<div></div>').append(value).html();
			}
		}
		return value;
	};

    $.fbuilder['escapeSymbol'] = function( value ) // Escape the symbols used in regulars expressions
	{
		return value.replace(/([\^\$\-\.\,\[\]\(\)\/\\\*\?\+\!\{\}])/g, "\\$1");
	};

	$.fbuilder[ 'parseValStr' ] = function( value, raw, no_quotes )
	{
		raw = raw || false;
        no_quotes = no_quotes || false;
		value = String(value || '').trim();
		value = value.replace(/\\/g, "\\\\").replace(/'/g, "\\'").replace(/"/g, '\\"');
		var r = ($.fbuilder.isNumeric(value)) ? ((raw) ? value : value*1) : ((no_quotes) ? value : '"' + value + '"');
		return raw ? r : ( window.cffsanitize != undefined ? cffsanitize( r, true ) : r );
	};

	$.fbuilder[ 'parseVal' ] = function( value, thousand, decimal, no_quotes )
	{
		if(!!value == false) return 0;
        no_quotes = no_quotes || false;
		/* date */
		if(/(\d{1,2}[\/\.\-]\d{1,2}[\/\.\-]\d{4})|(\d{4}[\/\.\-]\d{1,2}[\/\.\-]\d{1,2})/.test(value))
			return $.fbuilder[ 'parseValStr' ]( value, false, no_quotes );

		/* number */
		thousand = $.fbuilder.escapeSymbol(String((typeof thousand != 'undefined') ? thousand : ',').trim());
		decimal  = String((!!!decimal || /^\s*$/.test(decimal)) ? '.': decimal).trim();

		var p, _thousand = /^\s*$/.test(thousand) ? '\,' : thousand,
			t = new String(value);

		try {
			if ( 1 == t.match(new RegExp( _thousand, 'g' ) ).length ) {
				t = t.replace(new RegExp(_thousand+'\(\\d{1,2}\)$' ), decimal+'$1');
			}
		} catch(err){}

		t = t.replace( new RegExp(thousand, 'g'), '' )
			 .replace( new RegExp($.fbuilder.escapeSymbol(decimal), 'g' ), '.' )
			 .replace( /\s/g, '' );
		p = /[+\-]?((\d+(\.\d+)?)|(\.\d+))(?:[eE][+\-]?\d+)?/.exec( t );

		return (p) ? ((/^0\d/.test(p[0])) ? p[0].substr(1) : p[0])*1 : $.fbuilder['parseValStr'](value, false, no_quotes);
	};

	$.fbuilder[ 'isMobile' ] = function() {
        try{ document.createEvent("TouchEvent"); return true; }
        catch(e){ return false; }
    };

	$.fbuilder[ 'setBrowserHistory' ] = function(r)
	{
		if('history' in window)
		{
			var b = '#',
				s = '';
			for(var id in $.fbuilder.forms)
			{
				b += s+'f'+id.replace(/[^\d]/g,'')+'p'+($.fbuilder.forms[id]['currentPage'] || 0);
				s = '|';
			}
			history[(r) ? 'replaceState' : 'pushState']({}, document.title, b);
		}
	};

	$.fbuilder[ 'manageHistory' ] = function(onload)
	{
		var b = (document.URL.split('#')[1] || null),
			m, f, t, flag = false;

		if(b)
		{
			while(m = b.match(/f(\d+)p(\d+)\|?/))
			{
				f = '_'+m[1];
				t = onload ? 0 : m[2]*1;
				b = b.replace(m[0],'');

				flag = (
					!(f in $.fbuilder.forms) ||
					t != $.fbuilder['goToPage'](
						{
							'formIdentifier' : f,
							'from' 			 : 0,
							'to'			 : t,
                            'animate'        : false
						}
					)
				);
			}
		}
		else
		{
			for(f in $.fbuilder.forms)
				if('currentPage' in $.fbuilder.forms[f])
					$.fbuilder['goToPage']({'formIdentifier' : f, 'from' : 0, 'to' : 0, 'animate': false});
		}
		if(flag) $.fbuilder.setBrowserHistory(true);
	};

	$.fbuilder[ 'goToPage' ] = function( config )
	{
        function swapPages(pageToHide, pageToShow, callback)
        {
            var t  = 300,
				w  = pageToHide.width(),
				f  = pageToHide.closest('form'), // Form
				fx = f.data('animation_effect') == 'slide' ? 'slide' : 'fade'; // Effect

            if(
				('animate' in config && config.animate == false) ||
				(f.data('animate_form') == undefined || f.data('animate_form')*1 == 0)
			) t = 0;

			if ( fx == 'fade' ) {
				// Fade effect
				pageToHide.fadeOut(t, function(){
					pageToHide.find(".field,[name='bccf_payment_option_paypal']").addClass("ignorepb");
					pageToShow.fadeIn(t, function(){
						pageToShow.find(".ignorepb").removeClass("ignorepb");
						callback();
						if('callback' in config) config.callback();
					});
				});
			} else {
				f.css('overflow-x','hidden');
				// Slide effect
				var d = pageToHide.attr('page')*1 < pageToShow.attr('page')*1 ? -1 : 1;
				pageToHide.animate({width:w, marginLeft:d*w}, t, 'linear', function(){
					pageToHide.hide().find(".field,[name='bccf_payment_option_paypal']").addClass("ignorepb");
					pageToShow.css({width:w, marginLeft:-1*d*w}).show().animate({width:w, marginLeft:0}, t, 'linear', function(){
						pageToShow.css('width', '100%');
						pageToShow.find(".ignorepb").removeClass("ignorepb");
						callback();
						if('callback' in config) config.callback();
					});
				});
			}
        };

		if(
			('formIdentifier' in config || 'form' in config) &&
			'to' in config
		)
		{
			var id 	= (config['form']) ?  $('[name="cp_calculatedfieldsf_pform_psequence"]', config['form']).val() : config['formIdentifier'],
				formObj 	= $.fbuilder.forms[id],
				_from		= (config['from'] || formObj['currentPage'] || 0)*1,
				_to			= config['to']*1,
				direction  	= (_from < _to) ? 1 : -1,
				formDom		= $(config['form'] || '[id="'+formObj.formId+'"]'),
				pageDom, i  = _from;

			_from = isNaN(_from) ? 0 : _from;
			_to   = isNaN(_to)   ? 0 : _to;

			while(i != _to)
			{
				if(direction == 1 && ( ! ( 'forcing' in config ) ||  config[ 'forcing' ] == false ) && !formDom.valid() ) break;
				i += direction;
			}
			formObj['currentPage'] = i;
			pageDom = $(".pbreak.pb"+i,formDom);

			if(i != _from) {
				swapPages(
					$(".pbreak:visible",formDom),
					pageDom,
					function()
					{
						try
						{
							if(!$.fbuilder.isMobile())
							{
								var ff  = pageDom.find(":focusable:first");
								if( ff &&
									!ff.hasClass('hasDatepicker') &&
									ff.attr('type') != 'button' &&
									ff.attr('type') != 'radio' &&
									ff.attr('type') != 'checkbox' &&
									ff.closest('[uh]').length == 0 /* FIXES AUTO-OPEN TOOLTIPS */
								) ff.trigger('focus');
							}
							var _wScrollTop = $(window).scrollTop(),
								_viewportHeight = $(window).height(),
								_scrollTop  = formDom.offset().top;
							if(_scrollTop < _wScrollTop || (_wScrollTop+_viewportHeight)<_scrollTop )
								$( 'html, body' ).animate({scrollTop:  _scrollTop}, 0);
						}
						catch(e){}
						$(document).trigger('cff-gotopage', {'from': _from, 'to': i, 'form': formDom});
					}
				);

			} else {
				if( pageDom.find(':input.cpefb_error:hidden').length ) {
					let mssg = [];

					pageDom.find('.cpefb_error.message:not(:empty)').each(function(){
						let e = $(this),
							l = e.closest('.fields').children('label'),
							t = l ? l.text() : '';

						mssg.push( '<b>'+t+(t.length ? ': ' : '')+'</b>'+e.text());
					});

					if(mssg.length) {
						$( 'body' ).append( '<div class="cff-error-dlg">'+cff_sanitize(mssg.join('<br>'), true)+'</div>' ).one('click', $.fbuilder.closeErrorDlg);
					}
				}

				formDom.validate().focusInvalid();
			}

			return i;
		}
	}; // End goToPage

	$.fbuilder[ 'showHideDep' ] = function( config )
	{
        // If isNotFirstTime the enqueue the equations associated to the fields
		var processItems = function( items, isNotFirstTime )
		{
			for( var i = 0, h = items.length; i < h; i++ )
			{
				if(typeof items[i] == 'string') items[i] = $.fbuilder['forms'][id].getItem(items[i]);
				if(items[i])
				{
					if(isNotFirstTime)
					{
						$('[name="'+items[i].name+'"]').trigger('depEvent');
						if(items[i].usedInEquations) {
							var equations = [];
							for( var j in items[i].usedInEquations )
								if(
									getField(items[i].usedInEquations[j].result)['dynamicEval'] ||
									(
										'force_all' in $.fbuilder['forms'][id] &&
										$.fbuilder['forms'][id]['force_all']
									)
								) equations.push( items[i].usedInEquations[j] );
							if ( equations.length )
								$.fbuilder['calculator'].enqueueEquation(id, equations);
						}
					}
					if('showHideDep' in items[i])
					{
						var list = items[i]['showHideDep']( toShow, toHide, hiddenByContainer, interval );
						if(list && list.length) processItems( list, true );
					}
				}
			}
		};

		if('formIdentifier' in config)
		{
			var id = config['formIdentifier'];

			if(id in $.fbuilder['forms'])
			{
				var interval = $('#'+$.fbuilder['forms'][id]['formId']).data('animate_form') ? 250 : 0,
                    toShow = $.fbuilder['forms'][id]['toShow'],
					toHide = $.fbuilder['forms'][id]['toHide'],
					hiddenByContainer = $.fbuilder['forms'][id]['hiddenByContainer'],
					items = ('fieldIdentifier' in config) ? [$.fbuilder['forms'][id].getItem(config['fieldIdentifier'].replace(/_[cr]b\d+$/i, ''))] : $.fbuilder['forms'][id].getItems();

				processItems(items);
				$('[id="'+$.fbuilder['forms'][id]['formId']+'"]').trigger('showHideDepEvent', $.fbuilder['forms'][id]['formId']);
			}
		}
	};

	// Load default values
	$.fbuilder[ 'cpcffLoadDefaults' ] = function( o )
	{
		if( typeof cpcff_default != 'undefined' )
		{
			var $ = fbuilderjQuery,
				id = o.identifier.replace(/[^\d]/g, ''),
				item, data, formObj, f;

			if(id in cpcff_default)
			{
				// Initialize variables.
				data = cpcff_default[id];
				id = '_'+id;
				formObj = $.fbuilder['forms'][id];
				f = $('#'+formObj['formId']);
				f.attr('data-evalequations', 0);
				f.attr('data-loadingdefaults', 1);

				// Selecting the default options in DS fields while they load.
				let still_loading = true;

				$(document).on('input', '#fbuilder :input', function(evt){
					still_loading = false;
				});

				$(document).on('cff-data-filled', function(evt){
					if ( ! still_loading ) return true; // The data are being loaded from data source by the user action.
					try {
						let n = $(evt.target).attr('id').match( /(fieldname\d+)_(\d+)/);
						if (
							n &&
							n[2] in cpcff_default &&
							n[1] in cpcff_default[n[2]]
						) {
							let f = getField( n[1], '#cp_calculatedfieldsf_pform_'+n[2] );
							if(
								f &&
								'setVal' in f &&
								JSON.stringify(f.val('vt', true)) != JSON.stringify(cpcff_default[n[2]][n[1]])
							) {
								f.setVal( cpcff_default[n[2]][n[1]], false, true );
							}
						}
					} catch (err){ if('console' in window) console.log(err); }
				});
				// ...End.

				// Assign the values to non-ds fields.
				for( var fieldId in data )
				{
					item = formObj.getItem(fieldId+id);
					try {
						if(
							item &&
							! ( 'isDatasource' in item ) &&
							'setVal' in item &&
							JSON.stringify(item.val('vt', true)) != JSON.stringify(data[fieldId])
						) item.setVal(data[fieldId], false, true);
					} catch(err){}
				}

				// Assign the values to ds fields.
				for( var fieldId in data )
				{
					item = formObj.getItem(fieldId+id);
					try {
						if(
							item &&
							'isDatasource' in item &&
							'setVal' in item &&
							JSON.stringify(item.val('vt', true)) != JSON.stringify(data[fieldId])
						) {
							item.setVal(data[fieldId], false, true);
							$('[name*="'+item.name+'"]').trigger('trigger_ds');
						}
					} catch(err){}
				}

				f.trigger('cff-loaded-defaults');
			}
		}
	};

	$.fbuilder[ 'getCSSComponent' ] = function( o, c, i, s, f ) // o: form or field object, c: component, i: !important, s: selector, f: form
	{
		// Initialize variables
		i = i || false;
		s = s || false;
		f = f || false;

		let output = '';
		if ( 'advanced' in o ) {
			if ( 'css' in o.advanced ) {
				if ( c in o.advanced.css ) {
					if ( 'rules' in o.advanced.css[c] ) {
						let rules = o.advanced.css[c].rules,
							v;
						for ( let r in rules ) {
							r = String( r ).trim().replace(/\:$/, '');
							v = String( rules[r] ).trim().replace(/\;$/, '');
							if ( '' !== r && '' !== v ) {
								if (i) {
									v = v.replace(/\!\s*important/i, '')+' !important';
								}
								output += r+':'+v+';';
							}
						}
					}
				}
			}
		}
		if ( f && s && output !== '' ) {
			if ( ! ( f in $.fbuilder.css ) ) $.fbuilder.css[f] = [];
			$.fbuilder.css[f].push( s+'{'+output+'}');
		}
		return output;
	};

	$.fn.fbuilder = function(options){
		var opt = $.extend({},
					{
						pub:false,
						identifier:"",
						title:""
					},options, true);

		opt.messages = $.extend(
			{
				previous: "Previous",
				next: "Next",
				pageof: "Page {0} of {0}",
				required: "This field is required.",
				email: "Please enter a valid email address.",
				datemmddyyyy: "Please enter a valid date with this format(mm/dd/yyyy)",
				dateddmmyyyy: "Please enter a valid date with this format(dd/mm/yyyy)",
				number: "Please enter a valid number.",
				digits: "Please enter only digits.",
				maxlength: "Please enter no more than {0} characters.",
				minlength: "Please enter at least {0} characters.",
				equalTo: "Please enter the same value again.",
				max: "Please enter a value less than or equal to {0}.",
				min: "Please enter a value greater than or equal to {0}.",
				currency: "Please enter a valid currency value."
			},
			(opt.messages || {})
		);

		opt.messages.max = $.validator.format(opt.messages.max);
		opt.messages.min = $.validator.format(opt.messages.min);
		opt.messages.maxlength = $.validator.format(opt.messages.maxlength);
		opt.messages.minlength = $.validator.format(opt.messages.minlength);
		opt.messages.dateyyyymmdd = opt.messages.datemmddyyyy;
		opt.messages.dateyyyyddmm = opt.messages.dateddmmyyyy;

		for( let message in opt.messages ) {
			opt.messages[message] = cff_sanitize(opt.messages[message], true);
		}

		$.extend($.validator.messages, opt.messages);

		$("#cp_calculatedfieldsf_pform"+opt.identifier).validate({
			ignore:".ignore,.ignorepb",
			errorClass: 'cpefb_error',
			errorElement: "div",
			errorPlacement: function(e, element)
				{
					var _parent = element.closest( '.uh_phone,.dfield' ),
						_uh =  _parent.find( 'span.uh:visible' ),
						_arg = {'position' : 'absolute'},
						_t  = _parent.find('input[type="button"],input[type="reset"],input[type="text"],input[type="number"]:not([id$="_quantity"]),input[type="email"],input[type="file"],input[type="color"],input[type="date"],input[type="password"],input[type="email"],select,textarea');

						try{
							if(_t.length) _arg['left'] = _t.first()[0].offsetLeft;
						} catch (err) {}
					e.addClass( 'message' ).css( _arg ).appendTo( ( _uh.length ) ? _uh : _parent );
				}
		}).messages = opt.messages;

		var items = [],
			fieldsIndex = {},
			reloadItemsPublic = function()
			{
				var form_tag 		= $("#cp_calculatedfieldsf_pform"+opt.identifier),
                    header_tag      = $("#formheader"+opt.identifier),
					fieldlist_tag 	= $("#fieldlist"+opt.identifier),
					page_tag,
					i = 0,
					page = 0,
					getCaptchaHTML = function(){
						var captcha_tag = $( "#cpcaptchalayer"+opt.identifier+':not(:empty)')
							html = '';
						if( captcha_tag.length )
						{
							html += '<div class="captcha">'+captcha_tag.html()+'</div><div class="clearer"></div>';
							captcha_tag.remove();
						}
						return html;
					},
					getSubmitHTML = function(){
						var submit_tag = $("#cp_subbtn"+opt.identifier+':not(:empty)'),
							html = '';
						if( submit_tag.length )
						{
							html += '<div class="pbSubmit" tabindex="0">'+submit_tag.html()+'</div>';
							submit_tag.remove();
						}
						return html;
					};

				form_tag.addClass( theForm.formtemplate );
				theForm.form_tag = form_tag;
				if( !opt.cached )
				{
					page_tag = $('<div class="pb'+page+' pbreak" page="'+page+'"></div>');
                    header_tag.html(theForm.show( opt.identifier ));
					fieldlist_tag.addClass(theForm.formlayout).append(page_tag);

					for(i; i<items.length; i++)
					{
						items[i].index = i;
						if (items[i].ftype=="fPageBreak")
						{
							page++;
							page_tag = $('<div class="pb'+page+' pbreak" page="'+page+'"></div>');
							fieldlist_tag.append(page_tag);
						}
						else
						{
							// For hidden fields add the hide-strong class name.
							if( 'hidefield' in items[i] && items[i]['hidefield'] && 'csslayout' in items[i] ) items[i]['csslayout'] += ' hide-strong';

							page_tag.append(items[i].show());
							if (items[i].predefinedClick)
							{
								page_tag.find("#"+items[i].name).attr({placeholder: items[i].predefined, value: ""});
							}
							if(items[i].exclude)
							{
								page_tag.find('.'+items[i].name).addClass('cff-exclude');
							}
							if('audiotutorial' in items[i] && !/^\s*$/.test(items[i].audiotutorial))
							{
								(function(){
									var t = ( typeof opt != 'undefined' && 'messages' in opt && 'audio_tutorial' in opt.messages) ? opt.messages.audio_tutorial : false,
										e = items[i].jQueryRef(),
										c = $('<span class="cff-audio-icon" '+(t ? 'uh="'+cff_esc_attr(t)+'"' : '')+'></span>'),
										a = $('<audio src="'+cff_esc_attr(items[i].audiotutorial)+'" class="cff-audio-tutorial"></audio>');

									a.appendTo(e.find('.dfield'));
									c.appendTo($(e.children('label')[0] || e));

									c.on( 'click', function(evt){
										var e = $(this);
										if(e.hasClass('cff-audio-stop-icon')) {
											e.removeClass('cff-audio-stop-icon');
											a[0].pause();
											a[0].currentTime = 0;
										} else {
											$('.cff-audio-stop-icon').trigger('click');
											e.addClass('cff-audio-stop-icon');
											a[0].play();
										}
										evt.stopPropagation();evt.preventDefault();return false;
									});
								})()
							}
							if (items[i].userhelpTooltip)
							{
								var uh = items[i].jQueryRef();
								if(items[i].userhelp && items[i].userhelp.length)
								{
									var uh_content = '<div data-uh-styles="'+cff_esc_attr(items[i].getCSSComponent('help').replace(/<[^>]*>/g, ''))+'">'+cff_sanitize(items[i].userhelp, true)+'</div>';

									if(items[i].tooltipIcon) $('<span class="cff-help-icon"></span>').attr('uh', uh_content).appendTo($(uh.children('label')[0] || uh));
									else{
                                        var target = uh.find('input[type="button"],input[type="reset"],input[type="text"],input[type="number"],input[type="email"],input[type="file"],input[type="color"],input[type="date"],input[type="password"],input[type="email"],select,textarea');
                                        if(!target.length) target = uh.find('.slider');
                                        if(!target.length) target = uh.find('.dfield label');
                                        if(!target.length) target = uh.find('.dfield');
                                        if(!target.length) target = uh;
                                        $(target).attr('uh', uh_content);
                                    }
								}
								uh.find(".uh").remove();
							}
						}
					}
                }
				else
				{
					page = fieldlist_tag.find( '.pbreak' ).length;
					i	 = items.length;
				}

				if (page>0)
				{
					if( !opt.cached ) // Check if the form is cached
					{
						$(".pb"+page, fieldlist_tag).addClass("pbEnd");
						$(".pbreak", fieldlist_tag).each(function(index) {
							var code = '',
								bSubmit = '';

							if (index == page)
							{
								code += getCaptchaHTML();
								bSubmit = getSubmitHTML();
							}

                            $(this).wrapInner('<fieldset></fieldset>')
                            .find('fieldset:eq(0)')
                            .prepend('<legend>'+cff_sanitize(opt.messages.pageof.replace( /\{\s*\d+\s*\}/, (index+1) ).replace( /\{\s*\d+\s*\}/, (page+1) ), true)+'</legend>')
                            .append(code+'<div class="cff-form-buttons-container"><div class="pbPrevious" tabindex="0">'+cff_sanitize(opt.messages.previous, true)+'</div><div class="pbNext" tabindex="0">'+cff_sanitize(opt.messages.next, true)+'</div>'+cff_sanitize(bSubmit, true)+'</div><div class="clearer"></div>');
						});
					}

					fieldlist_tag.find(".pbPrevious,.pbNext").on("keyup", function(evt){
						if(evt.which == 13 || evt.which == 32) $(this).trigger('click');
					}).on("click", {'identifier' : opt.identifier}, function(evt){
						var _from = ($.fbuilder.forms[evt.data.identifier]['currentPage'] || 0),
							_inc  = ($(this).hasClass("pbPrevious")) ? -1 : 1,
							_p = $.fbuilder['goToPage'](
                                {
                                    'formIdentifier' : evt.data.identifier,
                                    'from'			 : _from,
                                    'to'			 : _from+_inc,
                                    'callback'       : function()
                                    {
                                        setTimeout(function(){
                                            if(_from != _p) $.fbuilder.setBrowserHistory();
                                            if(_pDom.find('.fields:visible').length == 0)
                                                if(_inc == -1 && 0 < _p) _pDom.find('.pbPrevious').trigger('click');
                                                else if(!_pDom.hasClass('pbEnd')) _pDom.find('.pbNext').trigger('click');
                                        }, 10);
                                    }
                                }),
                            _pDom = $('.pb'+_p);

                        return false;
					});
                }
				else
				{
					if( !opt.cached ) $(".pb"+page, fieldlist_tag).append(getCaptchaHTML()+'<div class="cff-form-buttons-container">'+getSubmitHTML()+'</div>');
				}

				if( !opt.cached && opt.setCache)
				{
					// Set Cache
					var url  = document.location.href.split('?')[0],
						data = {
							'cffaction' : 'cff_cache',
							'cache'	 : form_tag.html().replace( /\n+/g, '' ),
							'form'	 : form_tag.find( '[name="cp_calculatedfieldsf_id"]').val()
						};
					$.post( url, data, function( data ){ if(typeof console != 'undefined' )console.log( data ); } );
				}

                // Set icon event
				jQuery(document).on('click', '.cff-help-icon', function(evt){evt.stopPropagation(); evt.preventDefault();});

				// Set Captcha Event
				$(document).on('click', '#fbuilder .captcha img', function(evt){
					try {
						var e = $( this ), src = e.attr('src');
						// Check URL, and replace it if different from website domain
						if(
							!(new RegExp('^http(s)?\:\/\/'+$.fbuilder.escapeSymbol(window.location.host), 'i')).
							test(src)
						) src = document.location.href.split('?')[0]+'?'+src.split('?')[1];
						e.attr('src', src.replace(/&\d+$/, '') + '&' + Math.floor(Math.random()*1000));
					} catch (err) { if('console' in window) console.log(err); }
					evt.preventDefault();
					evt.stopPropagation();
					return false;
                });
				$( form_tag ).find( '.captcha img' ).trigger('click');

				$( '#fieldlist'+opt.identifier).find(".pbSubmit").off('click').on("keyup", function(evt){
					if(evt.which == 13 || evt.which == 32) $(this).trigger('click');
				}).on("click", { 'identifier' : opt.identifier }, function(evt){
					$(this).closest("form").trigger('submit');
				});

				if (i>0)
				{
                    theForm.after_show( opt.identifier );
					for (var i=0;i<items.length;i++)
					{
						items[i].after_show();
                        if('csslayout' in items[i] && /\bignorefield\b/i.test(items[i]['csslayout']))
                            IGNOREFIELD(items[i].name, items[i].form_identifier);
					}

					theForm.form_tag.removeData('first_time');
					// Evaluate delayed script in cached forms:
					$('script[type="cff-script"]').each(function(){
						$(this).after(this.outerHTML.replace('cff-script','text/javascript')).remove();
					});

					$(document).on(
						'change',
						'#fieldlist'+opt.identifier+' .depItemSel,'+'#fieldlist'+opt.identifier+' .depItem',
						{ 'identifier' : opt.identifier },
						function( evt )
						{
							$.fbuilder.showHideDep(
								{
									'formIdentifier' : evt.data.identifier,
									'fieldIdentifier': evt.target.id
								}
							);
						}
					);

					setTimeout(
						function(){
							if ( 'form_tag' in theForm ) {
								theForm.form_tag.trigger( 'formReady', [theForm.form_tag.attr('id'), theForm.form_tag, theForm] );
							}
						}, 50
					);

					try
					{
						$.widget.bridge('uitooltip', $.ui.tooltip);
						$( "#fbuilder"+opt.identifier ).uitooltip(
							{
								show: false,
								hide: false,
								tooltipClass: "uh-tooltip cff-form-"+('form' in opt ? opt.form : ''),
								position: { my: "left top", at: "left bottom+5", collision: "flipfit" },
								items: "[uh]",
								content: function (){return $(this).attr("uh");},
								open: function( evt, ui ) {
									try {
										let styles = ( ui.tooltip.attr('style') || '' ) +
													 ( $(ui.tooltip).find('[data-uh-styles]').attr( 'data-uh-styles' ) || '' );

										ui.tooltip.attr('style', styles );
										if(
											! $(evt.originalEvent.target).hasClass('cff-help-icon') &&
											window.matchMedia("screen and (max-width: 640px)").matches &&
											window.orientation != undefined
										){
											var duration = ('undefined' != typeof tooltip_duration && /^\d+$/.test(tooltip_duration)) ? tooltip_duration : 3000;
											setTimeout( function(){$(ui.tooltip).hide('fade'); }, duration );
										}
									} catch ( err ){}
								}
							}
						);
					} catch(e){}
                }
                $("#fieldlist"+opt.identifier+" .pbreak:not(.pb0)").find(".field,[name='bccf_payment_option_paypal']").addClass("ignorepb");
                $("#fieldlist"+opt.identifier).find('[type="date"],[type="hidden"]').each(function(){
					$(this).rules('add', {step:false});
				});
			};

		var fform=function(){};
		$.extend(fform.prototype,
			{
				title:"Untitled Form",
				titletag: 'H2',
				textalign: 'default',
				headertextcolor:'',
				description:"This is my form. Please fill it out. It's awesome!",
				formlayout:"top_aligned",
				formtemplate:"",
                evalequations:1,
				evalequations_delay:0,
                evalequationsevent:2,
                loading_animation:0,
                animate_form:0,
				animation_effect:'fade',
                autocomplete:1,
				show:function( id ){
					let form_style = this.form_tag.attr('style') || '',
						form_id    = this.form_tag.attr('id'),
						css 	   = (this.textalign != 'default') ? 'text-align:'+this.textalign+';' : '';

					if(this.headertextcolor != '') css+='color:'+this.headertextcolor+';';

					// Form styles
					this.form_tag.attr('style', form_style+';' + $.fbuilder['getCSSComponent'](this, 'form'));

					// Common buttons styles
					$.fbuilder['getCSSComponent'](this, 'buttons', true, '#'+form_id+' .pbNext,#'+form_id+' .pbPrevious,#'+form_id+' .pbSubmit', id);

					$.fbuilder['getCSSComponent'](this, 'buttons_hover', true, '#'+form_id+' .pbNext:hover,#'+form_id+' .pbPrevious:hover,#'+form_id+' .pbSubmit:hover', id);

					$.fbuilder['getCSSComponent'](this, 'tooltips_icons', true, '#'+form_id+' #fbuilder .fields .cff-help-icon::before', id);

					$.fbuilder['getCSSComponent'](this, 'tooltips', true, '.uh-tooltip.cff-form-'+('form' in opt ? opt.form : ''), id);

					$.fbuilder['getCSSComponent'](this, 'error_bubble', true, '#'+form_id+' div.cpefb_error.message', id);

					$.fbuilder['getCSSComponent'](this, 'error_bubble_arrow', true, '#'+form_id+' div.cpefb_error.message::after', id);

					$.fbuilder['getCSSComponent'](this, 'required_field_asterisk', true, '#'+form_id+' .r', id);

					return ( id in $.fbuilder.css ? '<style>' + cff_sanitize($.fbuilder.css[id].join(''), true) + '</style>' : '') + // Include the fields CSS
					'<div class="fform" id="field" style="'+cff_esc_attr($.fbuilder['getCSSComponent'](this, 'header'))+'">'+( !/^\s*$/.test( this.title ) ? '<'+this.titletag+' class="cff-form-title" style="'+css+cff_esc_attr($.fbuilder['getCSSComponent'](this, 'title'))+'">'+cff_sanitize(this.title, true)+'</'+this.titletag+'>' : '' )+( !/^\s*$/.test( this.description ) ? '<span class="cff-form-description" style="'+css+cff_esc_attr($.fbuilder['getCSSComponent'](this, 'description'))+'">'+cff_sanitize(this.description, true)+'</span>' : '' )+'</div>';
				},
                after_show:function( id ){
                    // Common validators
                    if( typeof $[ 'validator' ] != 'undefined' )
					{
						if(!('cffcurrency' in $.validator.methods))
							$.validator.addMethod(
								'cffcurrency',
								function(v, el)
								{
									var f = el.id.match( /_\d+$/),
										esc = $.fbuilder.escapeSymbol,
										r;

									e = $.fbuilder['forms'][f[0]].getItem( el.name );
									r = new RegExp('^\\s*('+esc(e.currencySymbol)+')?\\s*\\-?\\d+('+esc(e.thousandSeparator)+'\\d{3})*'+((e.noCents) ? '': '('+e.centSeparator+'\\d+)?')+'\\s*('+esc(e.currencyText)+')?\\s*$','i');

									return this.optional(el) || r.test(v) || ($.fbuilder.isNumeric(v) && (!e.noCents || v === FLOOR(v)));
								},
								cff_sanitize( $.validator.messages['currency'], true )
							);
                        $.validator.methods.number = function(v, el)
							{
								var f = el.id.match(/_\d+$/),
									esc = $.fbuilder.escapeSymbol,
									e, r;

								if(f && el.id.match(/fieldname/i)) e = $.fbuilder['forms'][f[0]].getItem(el.name);
								if(!e) e = {thousandSeparator: ',', decimalSymbol: '.'};
								else v = e.val();

								r = new RegExp('^\\s*\\-?\\d+('+esc(e.thousandSeparator)+'\\d{3})*('+esc(e.decimalSymbol)+'\\d+)?\\s*\\%?\\s*$','i');
								return this.optional(el) || r.test(v) || $.fbuilder.isNumeric(v);
							};
                        $.validator.methods.min = function(v, el, p)
							{
								var f = el.id.match( /_\d+$/), e;
								if(f && el.id.match(/fieldname/i)) e = $.fbuilder['forms'][f[0]].getItem(el.name);
								if(e){
									v = e.val();
									if('dformat' in e && e.dformat == 'percent') v*=100;
								}
								return this.optional(el) || v >= p;
							};
						$.validator.methods.max = function(v, el, p)
							{
								var f = el.id.match( /_\d+$/), e;
								if(f && el.id.match(/fieldname/i)) e = $.fbuilder['forms'][f[0]].getItem(el.name);
								if(e){
									v = e.val();
									if('dformat' in e && e.dformat == 'percent') v*=100;
								}
								return this.optional(el) || v <= p;
							};
					}

					var form = $( '#cp_calculatedfieldsf_pform'+id );

                    // Disabling enter key
                    form.on('keydown keyup keypress', '[type="text"],[type="number"],[type="password"],[type="email"]',
                        function(evt){
                            if (evt.keyCode === 13)
                            {
                                evt.preventDefault();
                                evt.stopPropagation();
                                return false;
                            }
                        });

					if(typeof $.fn.fbuilder_localstorage != 'undefined' && form.hasClass('persist-form'))
					{
						form.fbuilder_localstorage();
					}

                    form.attr( 'data-evalequations', ('evalequations_delay' in this && this.evalequations_delay) ? 0 : this.evalequations )
						.attr( 'data-evalequationsevent', this.evalequationsevent )
						.attr( 'data-animate_form', this.animate_form )
						.attr( 'data-animation_effect', this.animation_effect )
						.attr( 'autocomplete', ( ( this.autocomplete ) ? 'on' : 'off' ) )
						.find( 'input,select,textarea' )
						.on( 'blur change',  function(evt){
							if( 'name' in evt.target ) { // Prevent processing validation in both events at once.
								if ( window['cff_error_processing'+evt.target.name] ) return;
								window['cff_error_processing'+evt.target.name] = true;
								setTimeout(function(){ delete window['cff_error_processing'+evt.target.name]; }, 10);
							}
							try{ if(!$(this).is(':file')) $(this).valid(); }catch(e){};
						});

					if(!this.autocomplete) form.find('input[name*="fieldname"]:not([autocomplete])').attr('autocomplete', 'new-password');

					// For users that insert the form into a DIV tag or another clickable tag, capture bubbling click and stop propagation.
					form.parents('a').attr('href', 'javascript:void(0);').removeAttr('target').css('all', 'unset');
                }
			});

		var theForm,
			ffunct = {
				toShow : {},
				toHide : {},
				hiddenByContainer : {},
				isRTL  : false,
				getItem: function( name )
					{
						if(name in fieldsIndex) return items[fieldsIndex[name]];
						var regExp = new RegExp((parseInt(name,10) == name) ? 'fieldname'+name+'_' : name+'_', i);
						for( var i in items )
						{
							if( items[ i ].name == name || regExp.test(items[ i ].name))
							{
								return items[ i ];
							}
						}
						return false;
					},
				getItems: function()
					{
					   return items;
					},
				loadData:function(f)
					{
						var d =  window[ f ];
						if ( typeof d != 'undefined' )
						{
							if( typeof d == 'object' && ( typeof d.nodeType !== 'undefined' || d instanceof jQuery ) ){ d = JSON.parse( jQuery(d).val() ); }
							else if( typeof d == 'string' ){ d = JSON.parse( d ); }

							if (d.length == 2)
							{
							   this.isRTL = ( 'direction' in d[1][0] && d[1][0]['direction'] == 'rtl' ) ? true : false;
							   this.formId = d[ 1 ][ 'formid' ];
							   items = [];
							   for (var i=0;i<d[0].length;i++)
							   {
								   var obj = new $.fbuilder.controls[d[0][i].ftype]();
								   obj = $.extend(true, {}, obj, d[0][i]);
								   obj.name = obj.name+opt.identifier;
								   obj.form_identifier = opt.identifier;
								   if( 'fieldlayout' in obj && obj.fieldlayout != 'default' )
									   obj.csslayout = ('csslayout' in obj ? obj.csslayout+' ' : '' )+obj.fieldlayout;
								   if('predefinedClick' in obj && obj.predefinedClick && 'predefined' in obj && obj.predefined) {
									   obj.placeholder = obj.predefined;
									   obj._setHndl('placeholder');
								   }
								   obj.init();
								   items[i] = obj;
								   fieldsIndex[obj.name] = i;
							   }
							   theForm = new fform();
							   theForm = $.extend(theForm,d[1][0]);

							   opt.evalequations = 'evalequations' in d[1][0] ? d[1][0][ 'evalequations' ] : 1;
							   opt.evalequations_delay = 'evalequations_delay' in d[1][0] ? d[1][0][ 'evalequations_delay' ] : 0;
							   opt.cached   = (typeof d[ 1 ][ 'cached' ] != 'undefined' && d[ 1 ][ 'cached' ] ) ? true : false;
							   opt.setCache = (!this.cached && typeof d[ 1 ][ 'setCache' ] != 'undefined' && d[ 1 ][ 'setCache' ]) ? true : false;

							   reloadItemsPublic();
						    }

							$(document).on('formReady', 'form#'+this.formId, ( function( opt, fid ){
								function resizeIframe(f) {
									let h = f.outerHeight()+40
									if (frameElement) {
										frameElement.height = h;
									}
									// Update iframe height
									if( 'parent' in window && window.parent != window ) {
										parent.postMessage({cff_height: h, cff_iframe: getURLParameter('cff_iframe', 0)}, '*');
									}
								};
								return function(evt, fid2, form_tag, form_obj){
									if ( fid == fid2 ){
										let f = form_tag;

										// Disable the dynamic evaluation of the equations until complete the loading process.
										let eval_equations_bk = f.attr('data-evalequations') ?  Math.max( opt.evalequations*1, f.attr( 'data-evalequations')*1 ) : opt.evalequations; // 2024-12-16

										$.fbuilder.cpcffLoadDefaults( opt );

										$.fbuilder.showHideDep( { 'formIdentifier' : opt.identifier } );
										f.removeAttr('data-loadingdefaults');

										f.css({'height':'auto', 'minHeight':'auto'});

										if( opt.evalequations ) {
											fbuilderjQuery.fbuilder.calculator.defaultCalc(this, false, false);
										}

										f.attr('data-evalequations', eval_equations_bk);
										$('.cff-processing-form', f).remove(); // 2024-12-16

										try {
											$.post(
												document.location.href.split('?')[0],
												{
													'cffaction'     : 'cff_register_height',
													'form_height'	: f.height(),
													'screen_width'	: $(window).width(),
													'form'	 		: f.find('[name="cp_calculatedfieldsf_id"]').val(),
													'_nonce'		: f.attr('data-nonce') || ''
												}
											);
										} catch(err){}

										// Resize iframe when form or window resize
										resizeIframe(f);
										(new ResizeObserver(function() {
											try {
												resizeIframe(f);
											} catch(err){}
										})).observe(f[0]);
									}
								};
							} )( opt, this.formId ) );
						}
					}
			};

		$.fbuilder[ 'forms' ][ opt.identifier ] = ffunct;
	    this.fBuild = ffunct;
	    return this;
	}; // End fbuilder plugin

	$.fbuilder.controls[ 'ffields' ] = function(){};
	$.extend($.fbuilder.controls[ 'ffields' ].prototype,
		{
				form_identifier:"",
				name:"",
				shortlabel:"",
				index:-1,
				ftype:"",
				userhelp:"",
				audiotutorial:"",
				userhelpTooltip:false,
				csslayout:"",
				init:function(){},
				_getAttr:function(attr, raw)
					{
						var me = this, f, v = String(me[attr]).trim(), raw = raw || false;
						if(!raw && $.fbuilder.isNumeric(v)) return parseFloat(v);
						f = (/^fieldname\d+$/i.test(v)) ? me.getField(v) : false;
						if(f)
						{
							v = f.val(raw, true);
							if(!raw && $.fbuilder.isNumeric(v)) v = parseFloat(v);
							if(f.ftype == 'fdate' && $.fbuilder.isNumeric(v) && v) v = CDATE(v, me.dformat);
						}
						return v;
					},
				_setHndl:function(attr, one)
					{
						var me = this, v = String(me[attr]).trim();
						if($.fbuilder.isNumeric(v)) return;
						var s = (/^fieldname\d+$/i.test(v)) ? '.'+v+me.form_identifier+' [id*="'+v+me.form_identifier+'"]' : v,
							i = (one) ? 'one' : 'on';
						if('string' == typeof s && !/^\s*$/.test(s))
						{
							s = String(s).trim();
							if(!$.fbuilder.isNumeric(s.charAt(0)))
							{
								try {
									$(document)[i]('change depEvent', s, function(evt){
										if(me['set_'+attr]) me['set_'+attr](me._getAttr(attr), $(evt.target).hasClass('ignore'));
									});
								} catch( err ) {}

								try {
									$(document)['one']('showHideDepEvent', function(evt,formId){
										try
										{
											if(me['set_'+attr])
											{
												me['set_'+attr](me._getAttr(attr), $(s).hasClass('ignore'));
												$('#'+formId+' .cpefb_error.message').remove();
												$('#'+formId+' .cpefb_error').removeClass('cpefb_error');
											}
										}
										catch(err){}
									});
								} catch( err ) {}
							}
						}
					},
				getField: function(f){return $.fbuilder['forms'][this.form_identifier].getItem(f);},
				jQueryRef: function(){return $('.'+this.name);},
				domRef: function(){return this.jQueryRef()[0];},
				show:function()
					{
						return 'Not available yet';
					},
				after_show:function(){},
				val:function(raw, no_quotes){
					raw = raw || false;
                    no_quotes = no_quotes || false;
					var e = $( "[id='" + this.name + "']:not(.ignore)" );
					if( e.length )
					{
						var v = e.val();
						if(raw) return $.fbuilder.parseValStr(v, raw, no_quotes);

						v = String(v).trim();
						return ($.fbuilder.isNumeric(v)) ? $.fbuilder.parseVal(v) : $.fbuilder.parseValStr(v, raw, no_quotes);
					}
					return 0;
				},
				setVal:function( v, nochange )
				{
					let e = $( "[id='" + this.name + "']" ),
						bk = e.val();

					e.val( cff_sanitize(v) );
					if ( !nochange && bk !== e.val() ) e.trigger('change');
				},
				set_placeholder:function( v ) {
					$( '[id="' + this.name + '"]' ).attr( 'placeholder', v );
				},
				setPlaceholder:function( v )
				{
					$( '[id="' + this.name + '"][type="text"]' ).attr( 'placeholder', v );
				},
				getCSSComponent:function( c, i, s, f ) // c: component, i: !important, s: selector, f: form
				{
					return $.fbuilder[ 'getCSSComponent' ](this, c, i, s, f );
				}
		}
	);

	$.fbuilder['doValidate'] = function(form) {
		form = $(form);

		let enabling_form = function () {
				form.validate().settings.ignore = '.ignore,.ignorepb';
				form.removeData('being-submitted');
				form.find('.submitbtn-disabled').removeClass('submitbtn-disabled').prop('disabled', false);
				form.find('.cff-processing-form').remove();
			},
			disabling_form = function () {
				if (form_disabled()) return;
				form.find('.pbSubmit,:submit').addClass('submitbtn-disabled').prop('disabled', true);
				form.data('being-submitted', 1);
				form.find('#fbuilder').append('<div class="cff-processing-form"></div>');
			},
			form_disabled = function () {
				return ('undefined' != typeof form.data('being-submitted'));
			},
			processing_form = function () {
				try{
					form.find('[name="cp_ref_page"]').val(parent.window.document.location.href);
				} catch (err) {
					form.find('[name="cp_ref_page"]').val(document.location.href);
				}
				form.find("[name$='_date'][type='hidden']").each(function () {
					let v = $(this).val(),
					name = $(this).attr('name').replace('_date', ''),
					e = $('[name="' + name + '"]');
					if (e.length && !$('[id="' + name + '_datepicker_container"]').length) {
						e.val(String(e.val().replace(v, '')).trim());
					}
				});
				form.find('select option[vt]').each(function () {
					let e = $(this);
					e.attr('cff-val-bk', e.val()).val( cff_sanitize(e.attr('vt'), true) );
				});
				form.find('input[vt]').each(function () {
					let e = $(this),
						q = $('[id="'+e.attr('id')+'_quantity"]');
                    e.attr('cff-val-bk', e.val()).val(cff_sanitize(e.attr('vt'), true)+(q.length ? ' ('+Math.max(1, q.val())+')' : ''));
				});
				form.find('.cpcff-recordset,.cff-exclude :input,[id^="form_structure_"]')
					.add(form.find('.ignore')).attr('cff-disabled', 1).prop('disabled', true);
				disabling_form();
				if ($('#cff_iframe_for_submission'+form_identifier).length) {
					form.attr('target', 'cff_iframe_for_submission'+form_identifier);
					$(document).one('cff-form-submitted', function(){
						form.find( '.cff-thanks-message' ).fadeIn(400);
						$(document).one('click', function(){ $('.cff-thanks-message').hide(); });
						if ( $('#cff_iframe_for_submission'+form_identifier).attr('data-cff-reset') == 1 ) {
							RESETFORM( form );
						}
					});
				}
				if ( form.attr( 'target' ) == undefined && window.self !== window.top ) {
					form.attr( 'target', '_top' );
				}
				if (form.attr('target') != undefined && NOT(IN(form.attr('target').toLowerCase(), ['_blank', '_self', '_top', '']))) {
					$('[name="' + form.prop('target') + '"]').one('load', function () {
						form.find('[cff-val-bk]').each(function () {
							let e = $(this);
							e.val(e.attr('cff-val-bk')).removeAttr('cff-val-bk');
						});
						form.find('[cff-disabled]').prop('disabled', false).removeAttr('cff-disabled');
						if (!/^(\s*|_self|_top|_parent)$/i.test(form.prop('target'))) {
							enabling_form();
						}
						$(document).trigger('cff-form-submitted', form);
					});
				}
				form[0].submit();
			},
			form_identifier = form.find('[name="cp_calculatedfieldsf_pform_psequence"]').val();

		if (form_disabled()) return false;

		form.find('[max=""]').removeAttr('max');
		form.find('[min=""]').removeAttr('min');
		form.find('[step=""]').removeAttr('step');

		form.validate().settings.ignore = '.ignore';
		if (!form.valid()) {
			let page = $('.cpefb_error:not(.message):not(.ignore):eq(0)').closest('.pbreak').attr('page') * 1,
				mssg = [];
			gotopage(page, form);
			form.trigger('cff-form-validation', false);
			enabling_form();
			$( '.cff-error-dlg' ).remove();
			$( document ).off('click', $.fbuilder.closeErrorDlg);
			setTimeout(function(){
				if ( mssg.length ) {
					$( 'body' ).append( '<div class="cff-error-dlg">'+mssg.join('<br>')+'</div>' );
				}
				$( document ).on('click', $.fbuilder.closeErrorDlg); }, 50);
			try {
				let errorList = form.validate().errorList;
				errorList.forEach( (e) => {
					try {
						let l = getField( e.element.name.match(/fieldname\d+_\d+/)[0] ).title;
						l = cff_sanitize(l, true).replace(/\:\s*$/, '');
						l = '<b>'+(l.length  ? l+': ' : '')+'</b>'+cff_sanitize(e.message, true);
						mssg.push( l );
					} catch(err){}
				} );
			} catch ( err ) {}
			return false;
		}

		if (
			(
				form_identifier in $.fbuilder.calculator.processing_queue &&
				$.fbuilder.calculator.processing_queue[form_identifier]) ||
			$.fbuilder.calculator.thereIsPending(form_identifier)) {
			$(document).on('equationsQueueEmpty', function (evt, formId) {
				if (formId == form_identifier) {
					$(document).off('equationsQueueEmpty');
					processing_form();
				}
			});
			enabling_form();
			return false;
		}

		processing_form();
		return false;
	};

	// Read history
	window.addEventListener('popstate', function(){
		try
		{
			// Solves an issue with the datepicker if it is opened and back/next buttons in browser are pressed
			$(".ui-datepicker").hide();
			$.fbuilder.manageHistory();
		}
		catch(err){}
	});

	$(window).on('load', function(){
		$.fbuilder.manageHistory(true);
	});

	$(document).on('mousedown', '#fbuilder .cff-spinner-down,#fbuilder .cff-spinner-up', function(){
		var u = $(this).hasClass('cff-spinner-up'),
			e = $(this)[u ? 'prev' : 'next']('input'),
			o, s, m, v, l;

		if(e.length) {
			e.attr('data-indeasing-decreasing', 1);
			o = getField(e.attr('id'), e[0].form);
			s = e.attr('step');
			if(isNaN(s*1)) s = 1;
			l = (new String(s)).split('.');
			s *= 1;
			l = l.length == 2 ? l[1].length : 0;
			m = e.attr(u ? 'max' : 'min');

			function increase() {
				if ( typeof e.attr('data-indeasing-decreasing') != 'undefined' ) {
					v = o.val();
					if(e.hasClass('percent')){ v = PREC(v*100, 4)*1; }
					if(u) v += s;
					else v -= s;
					if(m) v = u ? MIN(v,m) : MAX(v,m);
					v = PREC(v,l);
					o.setVal(v);
					e.valid();
					setTimeout(function(){ increase(); }, 150);
				}
			}
			increase();
		}
	});

	$(document).on('mouseup mouseleave', '#fbuilder .cff-spinner-down,#fbuilder .cff-spinner-up', function(){
		var u = $(this).hasClass('cff-spinner-up'),
			e = $(this)[u ? 'prev' : 'next']('input');

		if(e.length) {
			e.removeAttr('data-indeasing-decreasing');
		}
	});

	function assign_data_cff_field_content( field ) {
		try{
			field = $(field);

			var field_name  = field.attr('name').match(/fieldname\d+/)[0];
				form_obj	= field.closest('form'),
				tags 		= form_obj.find('[data-cff-field="'+field_name+'"]');
			if( tags.length ){
				var	ignore 		= field.hasClass('ignore'),
					field_obj 	= getField(field, form_obj),
					value 		= (ignore || ! field_obj) ? '' : field_obj.val(('toSubmit' in field_obj  ? 'vt' : true), true);

				value = Array.isArray(value) ? value.join(', ') : value;
				if(typeof value == 'string') {
					value = value.replace(/\\\\/g, "\\").replace(/\\'/g, "'").replace(/\\"/g, '"');
					// Patch for signatures and charts
					if( /^data\:image\/png;base64\,/i.test(value) ) {
						value = '<img src="'+value+'">';
					}
				}
				tags.each(function(){
					if ( field_obj && field_obj['ftype'].toLowerCase() == 'fpassword' ) {
						$(this).text(value);
					} else {
						$(this).html(cff_sanitize(value, true));
					}
				});
			}
		} catch( err ) { console.log(err); }
	};

	$(document).on('change keyup', '#fbuilder :input[name*="fieldname"]', function(){
		var me = this;
		setTimeout( function(){assign_data_cff_field_content(me);}, 50);
	});

	$(document).on('keyup', '#fbuilder :input[maxlength]', function(){
		var e = $( this ),
			v = new String( e.val() ),
			l = v.length,
			m = e.attr( 'maxlength' );
		if ( m*1 <= l ) {
			setTimeout( function(){ e.val( v.substring( 0, m ) ).trigger('change'); }, 5);
		}
	});

	$(document).on('formReady cff-loaded-defaults', 'form', function(evt){
		try{
			var form_obj = $(evt.target);
			form_obj.find( '[data-cff-field]' ).each(function(){
				try {
					var tag_obj 	= $(this),
						field_name 	= tag_obj.attr('data-cff-field'),
						field_obj;
					if( field_name.length && /fieldname\d+/.test(field_name) ) {
						field_obj = form_obj.find('[name*="'+field_name+'_"]');
						if( field_obj.length ) assign_data_cff_field_content( field_obj );
					}
				} catch( err ) {}
			} );
		} catch( err ){}
	});

	$.fbuilder.closeErrorDlg = function(){$('.cff-error-dlg').remove();};