	$.fbuilder[ 'typeList' ] = [];
	$.fbuilder[ 'categoryList' ] = [];
	$.fbuilder[ 'controls' ] = {};
	$.fbuilder[ 'deletedFields' ] = {};

	window.addEventListener('beforeunload', function (e) {
		// Check if there are unsaved changes
		if ( $.fbuilder[ 'formWasModified' ] ) {
			const confirmationMessage = 'Changes you made may not be saved.';
			e.returnValue = confirmationMessage;
			return confirmationMessage;
		}
	});

	$.fbuilder[ 'formWasModified' ] = false;
	$.fbuilder[ 'displayedDuplicateContainerMessage' ] = false;
	$.fbuilder[ 'duplicateContainerMessage' ] = 'Note: If the container field being duplicated includes calculated fields or fields with dependency rules, the equations and dependencies rules in the new fields are exactly the same equations and dependency rules than in the original fields.';

	$.fbuilder['isNumeric'] = function(n){return !isNaN(parseFloat(n)) && isFinite(n);};

	$.fbuilder[ 'intializeDeletedFields' ] = function(){
		for(let i in $.fbuilder[ 'deletedFields' ] ) {
			$.fbuilder['deletedFields'][i] = 0;
		}
	};

	$.fbuilder[ 'checkDeletedFields' ] = function( v ){
		let result = '', separator = '';
		for(let i in $.fbuilder[ 'deletedFields' ] ) {
			if( (new RegExp('\\b'+i+'\\b', 'i')).test(v) ) {
				$.fbuilder['deletedFields'][i]++;
				result += separator+i;
				separator = ' | ';
			}
		}
		return result != '' ? ' [' + result + ']' : '';
	};

	$.fbuilder[ 'purgeDeletedFields' ] = function(){
		for(let i in $.fbuilder[ 'deletedFields' ] ) {
			if( ! $.fbuilder['deletedFields'][i] ) delete $.fbuilder['deletedFields'][i];
		}
	};

	$.fbuilder[ 'delete_form_preview_window' ] = function( e ){
		$.fbuilder['formWasModified'] = false; // Form changes where saved.
		if('cff_form_preview_window' in window) {
			cff_form_preview_window.close();
			delete cff_form_preview_window;
		}
	};

	$.fbuilder[ 'preview' ] = function( e )
	{
		if ( 'cff_form_preview_window_flag' in window ) return;
		cff_form_preview_window_flag = true;

		var w = screen.width*0.8,
			h = screen.height*0.7,
			l = screen.width/2 - w/2,
			t = screen.height/2 - h/2,
			f  = $( e.form );

		$.fbuilder[ 'delete_form_preview_window' ]();
		cff_form_preview_window = window.open('', 'cff_form_preview_window', 'resizeable,scrollbars,width='+w+',height='+h+',left='+l+',top='+t),

		f.attr( 'target', 'cff_form_preview_window' );
		f.attr( 'method', 'POST' );
		f.append('<input type="hidden" name="preview" value="1" />');

		setTimeout(function(){
			f.trigger('submit');
			$.fbuilder['formWasModified'] = false; // Form changes where saved.
			f.attr( 'target', '_self' ).find( 'input[name="preview"]').remove();
			delete cff_form_preview_window_flag;
			cff_form_preview_window.focus();
		}, 500);
	};

    $.fbuilder['printFields'] = function(){
		let s = '<span class="separator">|</span>',
			h = '<style>'+
			'body::-webkit-scrollbar{width:10px;height:7px;background:transparent;}'+
			'body::-webkit-scrollbar-thumb{background:#D0D5DD;-webkit-border-radius:35px;-webkit-box-shadow:none;}'+
			'body::-webkit-scrollbar-corner{background:transparent;}'+
			'*{font-family:sans-serif;font-size:14px;}'+
			'[type="search"]{border:1px solid #333;padding:5px 15px;border-radius:50px;width:100%;min-height:32px;line-height:32px;}'+
			'.cff-field-information{padding:5px 10px;}'+
			'a{text-decoration:none;}'+
			'.cff-field-information:nth-child(odd){background:#efefef;}'+
			'.field-information{display:flex;}'+
			'.field-type{font-weight:600;white-space:nowrap;}'+
			'.field-excluded{white-space:nowrap;}'+
			'.field-label{flex-grow:1;padding-left:10px;}'+
			'.separator{padding-left:5px;padding-right:5px;font-weight:normal;color:#666;}'+
			'.field-label .separator{margin-left:-10px;}'+
			'.developer-note:not(:empty){font-style:italic;margin-top:5px;display:block;font-size:90%;clear:both;color:#555;}'+
			'.developer-note:not(:empty)::before{content:\'Developer note: \';font-weight:600;}'+
			'</style>'+
			'<div><input type="search" name="cff-field-filter" placeholder="Type field name or label" /></div><hr />'+
			'<div class="cff-field-information header"><div class="field-information" style="font-weight:600;"><span class="field-name">Field name</span><span class="field-type">'+s+'Control type</span><span class="field-label">'+s+'Title</span><span class="field-excluded">'+s+'Exclude from submission</span></div></div><hr />'+
			'<div class="cff-field-information-list">',
			w, o = {};

        $.each(window.cff_form.fBuild.getItems(), function(i, item){
			let l = '', n = '', c = '', t = '';
			t = document.querySelector('.fields.'+item.name).getAttribute('title').split( /\s\-\s(.*)/,2);
			n = t[0]; c = 1 in t ? t[1] : '';
			l = ( 'title' in item ) ? String( item.title ).trim() : '';
			l = ( '' == l && 'shortlabel' in item ) ? String( item.shortlabel ).trim() : l;

			o[item.name] =
			'<div class="cff-field-information">'+
				'<div class="field-information">'+
					'<span class="field-name"><a href="javascript:e=window.opener.document.getElementsByClassName(\''+item.name+'\')[0];while(e.closest(\'.collapsed\')) e.closest(\'.collapsed\').classList.remove(\'collapsed\');e.scrollIntoView();e.click();">'+n+'</a></span>'+
					'<span class="field-type">'+('' != c ? s+cff_esc_attr(c) : '')+'</span>'+
					'<span class="field-label">'+('' != l ? s+cff_esc_attr(l) : '')+'</span>'+
					'<span class="field-excluded">'+('exclude' in item && item.exclude ? s+'X' : '' )+'</span>'+
				'</div>'+
				'<div class="field-note">'+
					('_developerNotes' in item && ! /^\s*$/.test(item._developerNotes) ? '<span class="developer-note">'+cff_esc_attr(item._developerNotes)+'</span>' : '')+
				'</div>'+
			'</div>';
        });

		$('#fieldlist [class*="fieldname"]').each(function(){
			h += o[this.className.match(/fieldname\d+/)[0]];
		});
		h += '</div>';

        w = window.open("","cff-fieldlist-popup", "width=500,height=400,scrollbars=1,resizable=1,toolbar=0,titlebar=0,menubar=0");
        w.document.title = 'Fields List';
        w.document.body.innerHTML = h;
		try {
			let f = w.document.getElementsByName('cff-field-filter')[0];
			f.addEventListener('input', function(evt){
				let v = String( evt.currentTarget.value ).toLowerCase();
				if ( v.length == 0 ) {
					$( '.cff-field-information-list .cff-field-information', w.document ).show();
				} else {
					$( '.cff-field-information-list .cff-field-information', w.document ).hide();
					$( '.cff-field-information-list .cff-field-information', w.document ).each(function(){
						let e = $(this);
						if(e.text().toLowerCase().indexOf(v) != -1) e.show();
					});
				}
			});
			f.focus();
			w.scrollTo(0,0);
		} catch ( err ) {}
    };

	$.fbuilder[ 'htmlEncode' ] = window[ 'cff_esc_attr' ] = function(value)
	{
		value = $('<div/>').text(value).html();
		value = value.replace(/&/g, '&amp;')
					 .replace(/"/g, "&quot;")
					 .replace(/&amp;lt;/g, '&lt;')
					 .replace(/&amp;gt;/g, '&gt;');
		value = value.replace(/&amp;/g, '&');
		return value;
	};

    $.fbuilder['sanitize'] = window['cff_sanitize'] = function(value, controls)
	{
        if(typeof value == 'string') {
			if(typeof controls != 'undefined' && controls) value = value.replace(/<\/?(textarea|input|button|checkbox|radio|select|option)[^>]*>/gi, '');

			if ('DOMPurify' in window) {
				let forbid_tags = ['style', 'script', 'link'];
				if (typeof controls != 'undefined' && controls) {
					forbid_tags = forbid_tags.concat(['textarea', 'input', 'button', 'checkbox', 'radio', 'select', 'option']);
				}
				value = DOMPurify.sanitize(value, {FORBID_TAGS: forbid_tags, FORBID_ATTR: ['style']});
			} else if ('DOMParser' in window) {
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
					element.removeAttribute('style');
				});

				value = doc.documentElement.getElementsByTagName('BODY')[0].innerHTML;
			} else {
				value = value.replace(/<script\b.*\bscript>/ig, '')
							 .replace(/<script[^>]*>/ig, '')
							 .replace(/<link[^>]*>/ig, '')
							 .replace(/(\b)(on[a-z]+)\s*=/ig, "$1_$2=")
							 .replace(/<style\b.*\bstyle>/ig, '')
							 .replace(/<style[^>]*>/ig, '')
							 .replace(/(\b)style\s*=/ig, "$1_style=")
							 .replace(/(\b)cff_style\s*=/ig, "$1style=");

				value = $('<div></div>').append(value).html();
			}
		}

		return value;
	};

	$.fbuilder['htmlDecode'] = window['cff_html_decode'] = function(value)
	{
		value = String(value)
				.replace(/<script\b[^>]*>([\s\S]*?)<\/script>/gi, '')
				.replace(/<style\b[^>]*>([\s\S]*?)<\/style>/gi, '')
				.replace(/(\b)(on[a-z]+)\s*=/gi, "$1_$2=");
		if( /&(?:#x[a-f0-9]+|#[0-9]+|[a-z0-9]+);?/ig.test( value ) ) value = $( '<div/>' ).html( value ).html();
		return value;
	};

	$.fbuilder[ 'escapeSymbol' ] = function( value ) // Escape the symbols used in regulars expressions
	{
		return value.replace(/([\^\$\-\.\,\[\]\(\)\/\\\*\?\+\!\{\}])/g, "\\$1");
	};

	$.fbuilder[ 'parseVal' ] = function( value, thousandSeparator, decimalSymbol )
	{
		if( value == '' ) return 0;
		value += '';

		thousandSeparator = new RegExp( $.fbuilder.escapeSymbol( ( typeof thousandSeparator == 'undefined' ) ? ',' : thousandSeparator ), 'g' );
		decimalSymbol = new RegExp( $.fbuilder.escapeSymbol( ( typeof decimalSymbol == 'undefined' || /^\s*$/.test( decimalSymbol ) ) ? '.' : decimalSymbol ), 'g' );

		var t = value.replace( thousandSeparator, '' ).replace( decimalSymbol, '.' ).replace( /\s/g, '' ),
			p = /[+\-]?((\d+(\.\d+)?)|(\.\d+))(?:[eE][+\-]?\d+)?/.exec( t );

		return ( p ) ? p[0]*1 : '"' + value.replace(/'/g, "\\'").replace( /\$/g, '') + '"';
	};

    $.fbuilder[ 'showErrorMssg' ] = function( str ) // Display an error message
    {
        $( '.form-builder-error-messages' ).html( '<div class="error-text">' + str + '</div>' );
    };

    // fbuilder plugin
	$.fn.fbuilder = function(){
		var typeList = 	$.fbuilder.typeList,
			categoryList = $.fbuilder.categoryList;

		$.fbuilder[ 'getNameByIdFromType' ] = function( id )
			{
				for ( var i = 0, h = typeList.length; i < h; i++ )
				{
					if ( typeList[i].id == id )
					{
						return  typeList[i].name;
					}
				}
				return "";
			};

		for ( var i=0, h = typeList.length; i < h; i++ )
		{
			var category_id = typeList[ i ].control_category;

			if( typeof categoryList[ category_id ]  == 'undefined' )
			{
				categoryList[ category_id ] = { title : '', description : '', typeList : [] };
			}
			else if( typeof categoryList[ category_id ][ 'typeList' ]  == 'undefined' )
			{
				categoryList[ category_id ][ 'typeList' ] = [];
			}

			categoryList[ category_id ].typeList.push( i );
		}

		let title_margin = '0';
		for ( var i in categoryList )
		{
			$("#tabs-1").append('<div style="clear:both;"></div><div style="margin-top:' + title_margin + ';font-size:14px;">'+cff_sanitize(categoryList[ i ].title, true)+'</div><hr />');

			title_margin = '20px;';

			if( typeof categoryList[ i ][ 'description' ] != 'undefined' && !/^\s*$/.test( categoryList[ i ][ 'description' ] ) )
			{
				$("#tabs-1").append('<div style="clear:both;"></div><div class="category-description">'+categoryList[ i ].description+'</div>');
			}

			let category_selector = '';
			if ( i == 20 ) {
				category_selector = ' .data-source-controls-category';
				$("#tabs-1").append('<div class="data-source-controls-category" style="cursor:pointer;" onclick="fbuilderjQuery(\'#cff-video-tutorial-ds-modal\').css({\'opacity\':0,\'display\':\'block\'}).animate({\'opacity\':1}, \'fast\');"></div>');
			}

			if( typeof categoryList[ i ][ 'typeList' ]  != 'undefined' )
			{
				for( var j = 0, k = categoryList[ i ].typeList.length; j < k; j++ )
				{
					var index = categoryList[ i ].typeList[ j ];
					$("#tabs-1"+category_selector).append('<div '+(i == 20 ? 'disabled style="pointer-events:none;"' : '')+' class="button itemForm width48" id="'+typeList[ index ].id+'" aria-label="'+cff_esc_attr(typeList[ index ].name)+'">'+cff_sanitize(typeList[ index ].name, true)+'</div>');
				}
			}
			if ( i == 20 ) {
				$("#tabs-1 .data-source-controls-category").append('<div style="clear:both;"></div>');
			}
		}

		$("#tabs-1").append('<div class="clearer"></div>');
		$( ".button").button();
        $(document).on('mousedown', function(){$.fbuilder.mousedown = 1;})
                   .on('mouseup', function(){$.fbuilder.mousedown = 0;})
                   .on('mouseover', '.ctrlsColumn .itemForm:not(#fCalculated)', function(){
                       $(this).addClass('button-primary');
                   })
                   .on('mouseout', '.ctrlsColumn .itemForm', function(){
                        if(!('mousedown' in $.fbuilder) || !$.fbuilder.mousedown)
                                $(this).removeClass('button-primary');
                   });

		// Create a items object
		var items = [],
            fieldsIndex = {},
            selected = -3;

		$.fbuilder[ 'editItem' ] = function( id )
			{
				let scrollTopFlag = (selected != id);
                selected = id;

                try
                {
					$('#tabs-2').html( items[id].showAllSettings() );
					if ( scrollTopFlag ) $('#tabs-2')[0].scrollTop = 0;
                } catch (e) { if( 'console' in window ) console.log(e); }
				items[id].editItemEvents();
				setTimeout(function(){try{$('#tabs-2 .choicesSet select:visible, #tabs-2 .cf_dependence_field:visible, #tabs-2 #sSelectedField, #tabs-2 #sFieldList').select2();}catch(e){}}, 10);
			};

		$.fbuilder[ 'removeItem' ] = function( index, confirm )
			{
				confirm = confirm || false;

				let itemToDelete = items[ index ],
					name = itemToDelete['name'];

				if ( confirm ) {
					// Check if the field is being used by another field.
					let aux = function(item) {
							return item['name'] + (item['title'] != '' ? ' ('+item['title']+')': '');
						},
						reg  = new RegExp("\\b"+name+"\\b"),
						elements = [],
						mssg = "";

					if ( 'fields' in itemToDelete && Array.isArray( itemToDelete['fields'] ) && itemToDelete['fields'].length ) {
						elements = itemToDelete['fields'];
						mssg = "This action will delete the current and contained fields:";
					} else {
						mssg = "The field to delete is used in:";
						for ( let i in items ) {
							let item = items[i];
							if (
								'name' in item &&
								item['name'] != name
							) {
								if ( 'eq' in item && reg.test( item.eq ) ) {
									elements.push( aux(item) + ' - EQUATION' );
								} else if (
									'isDataSource' in item &&
									'active' in item &&
									'list' in item &&
									item['active'] in item['list'] &&
									reg.test( JSON.stringify( item['list'][item['active']] ) )
								) {
									elements.push( aux(item) + ' - DS SETTINGS' );
								}
							}
						}
					}

					if (
						elements.length &&
						! window.confirm( "Warning: Deletion Confirmation. "+mssg+"\n\n" + elements.join("\n") + "\n\nAre you sure you want to proceed with the deletion? This action cannot be undone." )
					) {
						return false;
					}
				}

				$.fbuilder['deletedFields'][name] = 0;
				if( typeof items[ index ][ 'remove' ] != 'undefined' ) items[ index ][ 'remove' ]();
				items[ index ] = 0;
				selected = -2;
				$('#tabs').tabs("option", "active", 0);
				return true;
			};

		$.fbuilder[ 'duplicateItem' ] = function( index, parentItem )
			{
				var n = 0, i, h, item, nIndex, duplicate = items[index];
				for ( i in fieldsIndex ) if( /fieldname/.test( i ) ) n = Math.max( parseInt( i.replace( /fieldname/g,"" ) ), n );

				item = $.extend( true, {}, duplicate, { name:"fieldname"+(n+1) } );
				if( typeof item[ 'fields' ] != 'undefined' && typeof item[ 'duplicateItem' ] != 'undefined') item[ 'fields' ] = [];
				if( typeof parentItem != 'undefined' ) item[ 'parent' ] = parentItem;
				else
				{
					/* Check if the parent is a container, and insert the new item as child of parent */
					if(
						duplicate[ 'parent' ] != '' &&
						typeof items[ fieldsIndex[ duplicate[ 'parent' ] ] ][ 'duplicateItem' ] != 'undefined'
					)
					items[ fieldsIndex[ duplicate[ 'parent' ] ] ][ 'duplicateItem' ]( duplicate.name, item['name'] );
				}

				// Insert the duplicated item just below the original
				nIndex = index*1+1;
				items.splice( nIndex, 0,  item);
				fieldsIndex[ item[ 'name' ] ] = nIndex;
				i = nIndex; h = items.length;
				for ( i; i<h; i++ ) // Correct the rest of indices
				{
					items[i].index = i;
					fieldsIndex[ items[i].name ] = i;
				}

				// The duplicated item is a container
				if( typeof item[ 'duplicateItem' ] != 'undefined' )
				{
					// Alert Message
					if( !$.fbuilder[ 'displayedDuplicateContainerMessage' ] )
					{
						alert( $.fbuilder[ 'duplicateContainerMessage' ] );
						$.fbuilder[ 'displayedDuplicateContainerMessage' ] = true;
					}

					i = 0; h = duplicate[ 'fields' ].length;
					for( i; i < h; i++ )
					{
						item[ 'fields' ][ i ] = $.fbuilder[ 'duplicateItem' ]( fieldsIndex[duplicate[ 'fields' ][ i ]], item[ 'name' ] );
					}
				}
				return item[ 'name' ];
			};

		$.fbuilder[ 'editForm' ] = function()
			{
				$('#tabs-3').html(theForm.showAllSettings());
				$('#tabs-3')[0].scrollTop = 0;
				selected = -1;

				$("#calculated-fields-form-category-mirror").on( 'keyup', function()
				{
					$('[name="calculated-fields-form-category"]').val($(this).val());
				});

				$("#fTitle").on( 'keyup', function()
				{
					theForm.title = $(this).val();
					$.fbuilder.reloadItems({'form':1});
				});

				$("#fErrorBubbleArrow").on( 'keyup', function()
				{
					theForm.errorbubblearrow = $(this).val();
					$.fbuilder.reloadItems({'form':1});
				});

				$("#fTitleTag").on( 'change', function()
				{
					theForm.titletag = $(this).val();
					$.fbuilder.reloadItems({'form':1});
				});

				$("[name='fTextAlign']").on( 'click', function()
				{
					theForm.textalign = $("[name='fTextAlign']:checked").val();
					$.fbuilder.reloadItems({'form':1});
				});

				$("#fHeaderColor").on('change input', function()
				{
					theForm.headertextcolor = $(this).val();
					$.fbuilder.reloadItems({'form':1});
				});

				$("#fEvalEquations").on( 'click', function()
				{
					theForm.evalequations = ($(this).is( ':checked' )) ? 1 : 0;
					$.fbuilder.reloadItems({'form':1});
				});

				$("#fEvalEquationsDelay").on( 'click', function()
				{
					theForm.evalequations_delay = ($(this).is( ':checked' )) ? 1 : 0;
					$.fbuilder.reloadItems({'form':1});
				});

				$("#fAnimateForm").on( 'click', function()
				{
					theForm.animate_form = ($(this).is( ':checked' )) ? 1 : 0;
					$.fbuilder.reloadItems({'form':1});
				});

				$("#fAnimationEffect").on( 'change', function()
				{
					theForm.animation_effect = $(this).val();
					$.fbuilder.reloadItems({'form':1});
				});

				$("[name='fEvalEquationsEvent']").on( 'change', function()
				{
					theForm.evalequationsevent = $("[name='fEvalEquationsEvent']:checked").val();
					$.fbuilder.reloadItems({'form':1});
				});

				$("#fDirection").on( 'click', function()
				{
					theForm.direction = $(this).val();
					$.fbuilder.reloadItems({'form':1});
				});

				$("#fLoadingAnimation").on( 'click', function()
				{
					theForm.loading_animation = ($(this).is( ':checked' )) ? 1 : 0;
					$.fbuilder.reloadItems({'form':1});
				});

				$("#fAutocomplete").on( 'click', function()
				{
					theForm.autocomplete = ($(this).is( ':checked' )) ? 1 : 0;
					$.fbuilder.reloadItems({'form':1});
				});

				$("#fPersistence").on( 'click', function()
				{
					theForm.persistence = ($(this).is( ':checked' )) ? 1 : 0;
					$.fbuilder.reloadItems({'form':1});
				});

				$("#fDescription").on( 'keyup', function()
				{
					theForm.description = $(this).val();
					$.fbuilder.reloadItems({'form':1});
				});

				$("#fLayout").on( 'change', function()
				{
					theForm.formlayout = $(this).val();
					$.fbuilder.reloadItems();
				});

				$("#fTemplate").on( 'change', function()
				{
					theForm.formtemplate = $(this).val();
					var template 	= $.fbuilder.showSettings.formTemplateDic[ theForm.formtemplate ],
						thumbnail	= '',
						description = '';

					if( typeof template != 'undefined' )
					{
						if( typeof template[ 'thumbnail' ] != 'undefined' )
						{
							thumbnail = '<img src="' + cff_esc_attr(template[ 'thumbnail' ]) + '" alt="'+ cff_esc_attr( 'title' in template ? template['title'] : 'Template thumbnail' )+'">';
						}
						if( typeof template[ 'description' ] != 'undefined' )
						{
							description = template[ 'description' ];
						}
					}
					$( '#fTemplateThumbnail' ).html( thumbnail );
					$( '#fTemplateDescription' ).html( cff_sanitize(description, true) );
					$.fbuilder.reloadItems({'form':1});
				});

				$("#fCustomStyles").on( 'change', function()
				{
					theForm.customstyles = $(this).val();
					$.fbuilder.reloadItems({'form':1});
				});

				// CSS Editor
				$('.cff-form-settings-tabs-headers .cff-field-settings-tab-header-advanced').on('click', function(){
					setTimeout(function(){
						if( 'codeEditor' in wp)
						{
							if($('#tabs-3 .CodeMirror').length) return;
							var cssEditorSettings = wp.codeEditor.defaultSettings ? _.clone( wp.codeEditor.defaultSettings ) : {},
								editor;
							cssEditorSettings.codemirror = _.extend(
								{},
								cssEditorSettings.codemirror,
								{
									indentUnit: 2,
									tabSize: 2,
									mode: 'css'
								}
							);
							editor = wp.codeEditor.initialize( $('#fCustomStyles'), cssEditorSettings );
							editor.codemirror.on('change', function(cm){ $('#fCustomStyles').val(cm.getValue()).trigger('change');});
							editor.codemirror.on('keydown', function(cm, evt){
								if ( 'Escape' == evt.key && $('.CodeMirror-hint').length ) {
									evt.stopPropagation();
								}
							});

							$('.cff-editor-extend-shrink').on('click', function(){
								let e = $(this).closest('.cff-editor-container'),
									c = e.closest('.ctrlsColumn');
								e.toggleClass('fullscreen');
								if(e.hasClass('fullscreen')) c.css('z-index', 99991);
								else c.css('z-index', 999);
							});
						}
					}, 300);
				});

				// Advanced section
				$(document).off("keyup", '[name^="advanced[css]"]');
				$(document).on("keyup", '[name^="advanced[css]"]', function(e) {
					$.fbuilder['updateAdvancedSettings'](theForm, $(this).attr('data-cff-css-component'));
					$.fbuilder.reloadItems({'form':1});
				});
				$(document).off('click', '.cff-css-rule-delete');
				$(document).on('click', '.cff-css-rule-delete', function(e) {
					$(this).closest('.css-rule').remove();
					$.fbuilder['updateAdvancedSettings'](theForm, $(this).attr('data-cff-css-component'));
					$.fbuilder.reloadItems({'form':1});
				});
			};

		$.fbuilder[ 'defineGeneralEvents' ] = function()
			{
				// Fields settings tabs
				$( document ).on( 'click', '.cff-field-settings-tab-header-basic:not(.cff-field-settings-tab-active), .cff-field-settings-tab-header-advanced:not(.cff-field-settings-tab-active)' , function(){
					$('.cff-field-settings-tab-active').removeClass('cff-field-settings-tab-active');
					$(this).addClass('cff-field-settings-tab-active');
					$('.cff-field-settings-tab-body-active').removeClass('cff-field-settings-tab-body-active');
					if( $(this).hasClass('cff-field-settings-tab-header-basic') ) {
						$('.cff-field-settings-tab-body-basic').addClass('cff-field-settings-tab-body-active');
					} else {
						$('.cff-field-settings-tab-body-advanced').addClass('cff-field-settings-tab-body-active');
					}
				});

				// Fields events
				$(document).on(
					{
						'click' : function(evt){
							$.fbuilder[ 'editItem' ]($(this).attr("id").replace("field-",""));
							$( '#fieldlist .ui-selected' ).removeClass("ui-selected");
							$(this).addClass("ui-selected");
							$('#tabs').tabs("option", "active", 1);
							evt.stopPropagation();
						},
						'mouseover' : function(evt){
							$(this).addClass("ui-over");
							evt.stopPropagation();
						},
						'mouseout' : function(evt){
							$(this).removeClass("ui-over");
							evt.stopPropagation();
						}
					},
					'.fields'
				);

				$(document).on('focus', '.field', function(){$(this).trigger('blur');});

				// Handle events
				$(document).on('click', '.fields .remove', function(evt){
					evt.stopPropagation();
					if(
						! $.fbuilder[ 'removeItem' ]( $(this).closest('.fields').attr("id").replace("field-",""), true )
					) return;
					items = $.grep( items, function( e ){ return (e != 0 ); } );
					$.fbuilder.reloadItems();
				});

				$(document).on('click', '.fields .copy', function(evt){
					evt.stopPropagation();
					$.fbuilder[ 'duplicateItem' ]($(this).closest('.fields').attr("id").replace("field-",""));
					$('#tabs').tabs("option", "active", 0);
					$.fbuilder.reloadItems();
				});

				$(document).on('click', '.fields .collapse', function(evt){
					evt.stopPropagation();
					var f = $(this).closest('.fields'),
						i = f.attr("id").replace("field-",""),
						item = ffunct.getItems()[i];

					item['collapsed'] = true;
					f.addClass('collapsed');
					$.fbuilder.reloadItems({'field': item});
				});

				$(document).on('click', '.fields .uncollapse', function(evt){
					evt.stopPropagation();
					var f = $(this).closest('.fields'),
						i = f.attr("id").replace("field-",""),
						item = ffunct.getItems()[i];

					item['collapsed'] = false;
					f.removeClass('collapsed');
					$.fbuilder.reloadItems({'field': item});
				});

				// Title and subtitle section events
				$(document).on(
					{
						'mouseover' : function(){
							$(this).addClass("ui-over");
						},
						'mouseout' : function(){
							$(this).removeClass("ui-over");
						},
						'click' : function(evt){
							evt.stopPropagation();
							$('#tabs').tabs("option", "active", 2);
							$.fbuilder.editForm();
							$(this).siblings().removeClass("ui-selected");
							$(this).addClass("ui-selected");
						}
					},
					'.fform'
				);

				// Dashboard event
				$(document).on('click', '.expand-shrink', function(){
					$(this).toggleClass( 'ui-icon-triangle-1-e ui-icon-triangle-1-w' );
					$('.form-builder .ctrlsColumn').toggleClass( 'expanded' );
				});

				$(document).on('click', '#fbuilder', function(evt)
					{
						evt.stopPropagation();
						selected = -2;
						$(".fform").removeClass("ui-selected")
						$( '#fieldlist .ui-selected' ).removeClass("ui-selected");
						$('#tabs').tabs("option", "active", 0);
					}
				);

				$(document).on('click', '.cff-form-builder-extend-shrink [name="cff_expand_btn"]', function(){
					$('#metabox_form_structure').addClass('fullscreen');
					document.getElementById('metabox_form_structure').scrollIntoView();
				});

				$(document).on('click', '.cff-form-builder-extend-shrink [name="cff_shrink_btn"]', function(){
					$('#metabox_form_structure').removeClass('fullscreen');
				});

				$(document).on('keydown', function(evt){
					if ( 'Escape' == evt.key ) {
						if ( $('#cff-advanced-equation-editor:visible').length ) {
							return;
						} else if ( $('.cff-editor-container.fullscreen').length ) {
							$('.cff-editor-extend-shrink').trigger('click');
						} else if ( $('#metabox_form_structure.fullscreen').length ) {
							$('.cff-form-builder-extend-shrink [name="cff_shrink_btn"]').trigger('click');
						}
					}
				});
			};

		$.fbuilder[ 'reloadItems' ] = function( args )
			{

// console.time('debugging');

				function replaceFieldTags( field )
				{
					if( typeof field[ 'display' ] != 'undefined' )
					{
						var e  = $('.'+field['name']),
							n  = $(field.display().replace(/(\b)fields(\b)/i, '$1fields$2'+('fieldlayout' in field && field.fieldlayout != 'default' ? ' '+field.fieldlayout : ''))),
							as = true; // Call after_show

						if( n.find( '.dfield:eq(0)>.fcontainer>.fieldscontainer').length )
						{
							n.find( '.dfield:eq(0)>.fcontainer>.fieldscontainer')
							 .replaceWith( e.find( '.dfield:eq(0)>.fcontainer>.fieldscontainer' ) );
							as = false;
						}
						e.replaceWith(n);
						if( as && typeof field[ 'after_show' ] != 'undefined') field.after_show();
						$("#field-"+field.index).addClass("ui-selected");
					}
				} // End replaceFieldTags

				function replaceTitleDescTags()
				{
					$("#formheader").html(theForm.display());
				} // End replaceTitleDescTags

				var default_args = {
						'field' : {},
						'form'  : false
					};

				args = $.extend(true, {}, default_args, ( typeof args != 'undefined' ) ? args : {} );
				if( !$.isEmptyObject( args[ 'field' ] ) )
				{
					replaceFieldTags( args[ 'field' ] );
					if ( 'title' in args[ 'field' ] && 'name' in args[ 'field' ] ) {
						let text = cff_esc_attr(args[ 'field' ].name+' ('+cff_sanitize(args[ 'field' ].title, true)+')');
						$('#request_cost option[value="'+args[ 'field' ]['name']+'"]').text(text);
						$('[name="paypal_recurrent_field"] option[value="'+args[ 'field' ]['name']+'"]').text(text);
					}
				}
				else if( args['form'] )
				{
					replaceTitleDescTags();
				}
				else
				{
					var	email_str = '', // email fields list
						cu_user_email_field = $('#cu_user_email_field'),
						cu_user_email_fields_list = (cu_user_email_field.val() || cu_user_email_field.attr("def") || '').split( ',' ),

						cost_str = '', // fields list for paypal request
						request_cost = $('#request_cost').attr("def"),

						recurrent_str = '', // fields list for recurrent payments
						paypal_recurrent_field = $('[name="paypal_recurrent_field"]').attr("def"),

						paypal_price_field = $('#paypal_price_field').attr("def"), // fields for times intervals in recurrent payments
						interval_fields_str = '<option value="" '+( ('' == paypal_price_field ) ? "selected" : "" )+'> ---- No ---- </option>';

					// Set the correct fields alignment class
					for ( var i=0, h = $.fbuilder.showSettings.formlayoutList.length; i < h; i++ )
					{
						$("#fieldlist").removeClass( $.fbuilder.showSettings.formlayoutList[i].id );
					}
					$("#fieldlist").addClass(theForm.formlayout);

					replaceTitleDescTags();
					$("#fieldlist").html("");
					fieldsIndex = {};

					$.fbuilder[ 'intializeDeletedFields' ]();
					var item, playlist_html = '';
					for ( var i=0, h = items.length; i < h; i++ )
					{
						item = items[i];

						item.index = i;
						item.parent = '';
						fieldsIndex[ item.name ] = i;

						if( i == selected && $('#tabs').tabs("option", "active") != 1 ) $.fbuilder[ 'editItem' ]( i );
						playlist_html += item.display( i == selected ? 'ui-selected' : '' ).replace(/(\b)fields(\b)/i, '$1fields$2'+('fieldlayout' in item && item.fieldlayout != 'default' ? ' '+item.fieldlayout : ''));

						// Email fields
						if (item.ftype=="femail" || item.ftype=="femailds")
						{
							email_str += '<option value="'+cff_esc_attr(item.name)+'" '+( ( cu_user_email_fields_list.indexOf( item.name ) != -1 ) ? "selected" : "" )+'>'+cff_esc_attr(item.name+' ('+cff_sanitize(item.title, true)+')')+'</option>';
						}
						else
						{
							// Request cost fields
                            if(!/(femail)|(fdate)|(ffile)|(fpassword)|(fphone)|(fsectionbreak)|(fpagebreak)|(fsummary)|(fcontainer)|(ffieldset)|(fdiv)|(fmedia)|(fbutton)|(fhtml)|(frecordsetds)|(fcommentarea)/i.test(item.ftype))
							{
                                cost_str += '<option value="'+cff_esc_attr(item.name)+'" '+( ( item.name == request_cost ) ? "selected" : "" )+'>'+cff_esc_attr(item.name+'('+cff_sanitize(item.title, true)+')')+'</option>'
							}

							// Recurrent Payments
                            if (item.ftype=="fradio" || item.ftype=="fdropdown" || item.ftype=="fCalculated")
							{
                                recurrent_str += '<option value="'+cff_esc_attr(item.name)+'" '+( ( item.name == paypal_recurrent_field ) ? "selected" : "" )+'>'+cff_esc_attr(item.name+' ('+cff_sanitize(item.title, true)+')')+'</option>';
							}

							// Times Intervals
                            interval_fields_str += '<option value="'+cff_esc_attr(item.name)+'" '+( ( item.name == paypal_price_field ) ? "selected" : "" )+'>'+cff_esc_attr(cff_sanitize(item.title, true))+'</option>';
						}
					}
					$("#fieldlist").html( playlist_html );
					$.fbuilder[ 'purgeDeletedFields' ]();

					// Assign the email fields to the "cu_user_email_field" list
					cu_user_email_field.html(email_str);

					// Assign the fields to the "request_cost" list
					$('#request_cost').html(cost_str);

					// Assign the fields to the "paypal_recurrent_field" list
					$('[name="paypal_recurrent_field"]').html(recurrent_str);

					// Assign the fields to the "paypal_price_field" list
					$('#paypal_price_field').html(interval_fields_str);

					for ( var i=0, h = items.length; i < h; i++ )
					{
						if( typeof items[ i ].after_show != 'undefined' ) items[ i ].after_show();
					}
				}

				ffunct.saveData("form_structure");
// console.timeEnd('debugging');
                $(document).trigger('cff_reloadItems', items);
				$(document).on('mouseover', '.arrow.ui-icon.ui-icon-grip-dotted-vertical', function(){ $(this).attr('title', 'Drag and drop handler')});
				$(document).on('mouseover', '.sticker i', function(){ $(this).attr('title', 'Column identifier')});
			};

		var fform=function(){};
		$.extend(
			true,
			fform.prototype,
			{
				title:"Untitled Form",
				titletag:"H2",
				textalign:"default",
				headertextcolor:"",
				description:"This is my form. Please fill it out. It's awesome!",
				formlayout:"top_aligned",
				formtemplate:$.fbuilder.default_template,
                evalequations:1,
                evalequations_delay:0,
                evalequationsevent:2,
				direction:'ltr',
                loading_animation:0,
                autocomplete:1,
				persistence:0,
                animate_form:0,
                animation_effect:'fade',
				customstyles:"",
				advanced: { css : {} },
				initAdv: function(){
					if ( ! ( 'header' in this.advanced.css ) ) this.advanced.css.header = {label: 'Form header',rules:{}};
					if ( ! ( 'title' in this.advanced.css ) ) this.advanced.css.title = {label: 'Form title',rules:{}};
					if ( ! ( 'description' in this.advanced.css ) ) this.advanced.css.description = {label: 'Form description',rules:{}};
					if ( ! ( 'form' in this.advanced.css ) ) this.advanced.css.form = {label: 'Form area',rules:{}};
					if ( ! ( 'buttons' in this.advanced.css ) ) this.advanced.css.buttons = {label: 'Form context buttons (Next page, Previous page, Submit)',rules:{}};
					if ( ! ( 'buttons_hover' in this.advanced.css ) ) this.advanced.css.buttons_hover = {label: 'Form context buttons hover',rules:{}};
					if ( ! ( 'tooltips_icons' in this.advanced.css ) ) this.advanced.css.tooltips_icons = {label: 'Icons for instructions for users tooltips',rules:{}};
					if ( ! ( 'tooltips' in this.advanced.css ) ) this.advanced.css.tooltips = {label: 'Instructions for users tooltips',rules:{}};
					if ( ! ( 'thanks_popup' in this.advanced.css ) ) this.advanced.css.thanks_popup = {label: 'Thanks message popup',rules:{}};
					if ( ! ( 'close_thanks_popup' in this.advanced.css ) ) this.advanced.css.close_thanks_popup = {label: 'Thanks message popup close icon',rules:{}};
					if ( ! ( 'error_popup' in this.advanced.css ) ) this.advanced.css.error_popup = {label: 'Error message popup',rules:{}};
					if ( ! ( 'close_error_popup' in this.advanced.css ) ) this.advanced.css.close_error_popup = {label: 'Error popup close icon',rules:{}};
					if ( ! ( 'error_bubble' in this.advanced.css ) ) this.advanced.css.error_bubble = {label: 'Error bubbles',rules:{}};
					if ( ! ( 'error_bubble_arrow' in this.advanced.css ) ) this.advanced.css.error_bubble_arrow = {label: 'Error bubble arrow',rules:{'border-bottom-color':''}, 'restrict':true};
					if ( ! ( 'required_field_asterisk' in this.advanced.css ) ) this.advanced.css.required_field_asterisk = {label: 'Asterisk for required fields',rules:{}};
					let index = [ 'header', 'title', 'description', 'form', 'buttons', 'buttons_hover', 'tooltips_icons', 'tooltips', 'thanks_popup', 'close_thanks_popup', 'error_popup', 'close_error_popup', 'error_bubble', 'error_bubble_arrow', 'required_field_asterisk'];
					let css = {};

					for( let i in index ) {
						css[index[i]] = this.advanced.css[index[i]];
					}
					this.advanced.css = css;
				},
				display:function()
				{
					let css = '', empty_class = '', title = this.title, description = this.description;
					if(this.textalign != 'default') css+='text-align:'+this.textalign+';'
					if(this.headertextcolor != '') css+='color:'+this.headertextcolor+';';
					if( /^\s*$/.test(title+''+description) ) {
						empty_class = 'no_title';
						title = '{ Form Title and Description Here }';
					}

					return '<div class="fform '+empty_class+'" id="field"><div class="arrow ui-icon ui-icon-play "></div><'+this.titletag+' style="'+css+'">'+cff_sanitize(title, true)+'</'+this.titletag+'><span style="display:block;'+css+'">'+cff_sanitize(description, true)+'</span></div>';
				},

				showAllSettings:function()
				{
					this.initAdv();
					var me 			= this,
						layout 	    = '',
						template    = '<option value="">Use default template</option>',
						thumbnail   = '',
						description = '',
						selected    = '',
						str 		= '';

					for ( var i = 0; i< $.fbuilder.showSettings.formlayoutList.length; i++ )
					{
						layout += '<option value="'+cff_esc_attr($.fbuilder.showSettings.formlayoutList[i].id)+'" '+(($.fbuilder.showSettings.formlayoutList[i].id==me.formlayout)?"selected":"")+'>'+cff_esc_attr($.fbuilder.showSettings.formlayoutList[i].name)+'</option>';
					}

					for ( var i in $.fbuilder.showSettings.formTemplateDic )
					{
						if( /^\s*$/.test( i ) ) break;
						selected = '';
						if( $.fbuilder.showSettings.formTemplateDic[i].prefix==me.formtemplate )
						{
							selected = 'SELECTED';
							if( typeof $.fbuilder.showSettings.formTemplateDic[i].thumbnail != 'undefined' )
							{
								thumbnail = '<img src="'+cff_esc_attr($.fbuilder.showSettings.formTemplateDic[i].thumbnail)+'" alt="' + cff_esc_attr( 'title' in $.fbuilder.showSettings.formTemplateDic[i] ? $.fbuilder.showSettings.formTemplateDic[i]['title'] : 'Template thumbnail') + '">';
							}

							if( typeof $.fbuilder.showSettings.formTemplateDic[i].description != 'undefined' )
							{
								description = $.fbuilder.showSettings.formTemplateDic[i].description;
							}
						}

						template += '<option value="'+cff_esc_attr($.fbuilder.showSettings.formTemplateDic[i].prefix)+'" ' + selected + '>'+cff_esc_attr($.fbuilder.showSettings.formTemplateDic[i].title)+'</option>';
					}

					str = '<div class="cff-field-settings-tabs-headers cff-form-settings-tabs-headers">'+
						'<div class="cff-field-settings-tab-header-basic cff-field-settings-tab-active">Basic Settings</div>'+
						'<div class="cff-field-settings-tab-header-advanced">Advanced Settings</div>'+
						'<div class="cff-field-settings-tabs-border"></div>'+
					'</div>'+
					'<div class="cff-field-settings-tabs-bodies">'+
						'<div class="cff-field-settings-tab-body-basic cff-field-settings-tab-body-active">';

					str += '<div><label for="calculated-fields-form-category-mirror">Form Category</label><input type="text" class="large" name="calculated-fields-form-category-mirror" id="calculated-fields-form-category-mirror" value="'+cff_esc_attr( $('[name="calculated-fields-form-category"]').val() )+'" list="calculated-fields-form-categories" /></div>'+
					'<div><label for="fTitle">Form Name (Title)</label><input type="text" class="large" name="fTitle" id="fTitle" value="'+cff_esc_attr(me.title)+'" /></div>'+
					'<div><label for="fTitleTag">Form Name Tag</label><select class="large" id="fTitleTag" name="fTitleTag">'+
					['H1', 'H2', 'H3', 'H4', 'H5', 'H6'].reduce(function(o, t){ return o += '<option value="'+t+'" '+(t == me.titletag ? 'SELECTED' : '')+'>'+t+'</option>';}, '')+
					'</select></div>'+
					'<div><label for="fDescription">Description</label><textarea class="large" name="fDescription" id="fDescription">'+cff_esc_attr(me.description)+'</textarea></div>'+

					'<div><label>Text Align</label>'+
					'<div class="cff-radio-group-ctrl">'+
					'<label><input type="radio" name="fTextAlign" value="default" '+(me.textalign == 'default' ? 'checked' : '')+'><span>Default</span></label>'+
					'<label><input type="radio" name="fTextAlign" value="left" '+(me.textalign == 'left' ? 'checked' : '')+'><span>Left</span></label>'+
					'<label><input type="radio" name="fTextAlign" value="center" '+(me.textalign == 'center' ? 'checked' : '')+'><span>Center</span></label>'+
					'<label><input type="radio" name="fTextAlign" value="right" '+(me.textalign == 'right' ? 'checked' : '')+'><span>Right</span></label></div></div>'+

					'<div style="margin-top:10px;"><label for="fHeaderColor" style="display:inline-block;">Text Color</label> <input type="color" id="fHeaderColor" name="fHeaderColor" '+( me.headertextcolor !== '' ?  'value="'+cff_esc_attr( me.headertextcolor )+'"' : '')+'></div>'+
					/* General Settings */
					'<hr style="margin-top:10px;" />'+
					'<h3>Form Settings</h3>'+
					'<div><label for="fLayout">Label Placement</label><select name="fLayout" id="fLayout" class="large">'+layout+'</select></div>'+
					'<div><label for="fDirection">Direction</label><select name="fDirection" id="fDirection" class="large"><option value="ltr" '+(me.direction == 'ltr' ? 'SELECTED' : '')+'>LTR</option><option value="rtl" '+(me.direction == 'rtl' ? 'SELECTED' : '')+'>RTL</option></select></div>'+
					'<div><label for="fLoadingAnimation"><input type="checkbox" name="fLoadingAnimation" id="fLoadingAnimation" '+( ( me.loading_animation ) ? 'CHECKED' : '' )+' /> Display loading form animation</label></div><div><label><input type="checkbox" name="fAutocomplete" id="fAutocomplete" '+( ( me.autocomplete ) ? 'CHECKED' : '' )+' /> Enable autocompletion</label></div><div><label><input type="checkbox" name="fPersistence" id="fPersistence" '+( ( me.persistence ) ? 'CHECKED' : '' )+' /> Enable the browser\'s persistence (the data are stored locally on browser)</label></div>';

					if(typeof $.fbuilder.controls[ 'fCalculated' ] != 'undefined')
					{
						str += '<hr />';
						str += '<div><label><input type="checkbox" name="fEvalEquations" id="fEvalEquations" '+( ( me.evalequations ) ? 'CHECKED' : '' )+' /> Dynamically evaluate the equations associated with the calculated fields</label></div>';
						str += '<div><label><input type="checkbox" name="fEvalEquationsDelay" id="fEvalEquationsDelay" '+( ( me.evalequations_delay ) ? 'CHECKED' : '' )+' /> Delay the equations evaluation (evaluate the equations after rendering the form)</label></div>';

						str += '<div class="groupBox"><label><input type="radio" name="fEvalEquationsEvent" name="fEvalEquationsEvent" value="1" '+( ( me.evalequationsevent == 1 ) ? 'CHECKED' : '' )+' /> Eval the equations in the onchange events</label><label><input type="radio" name="fEvalEquationsEvent" name="fEvalEquationsEvent" value="2" '+( ( 'undefined' == typeof me.evalequationsevent || me.evalequationsevent == 2 ) ? 'CHECKED' : '' )+' /> Eval the equations in the onchange and keyup events</label></div>';
						str += '<hr />';
					}

					str += '<div><label for="fTemplate">Form Template</label><select name="fTemplate" id="fTemplate" class="large">'+template+'</select></div><div style="text-align:center;padding:10px 0;"><span id="fTemplateThumbnail">'+thumbnail+'</span><div></div><span  id="fTemplateDescription">'+cff_sanitize(description, true)+'</span></div>'+
                    '<div><label><input type="checkbox" name="fAnimateForm" id="fAnimateForm" '+( ( me.animate_form ) ? 'CHECKED' : '' )+' /> Animate page breaks in multipage forms, and dependencies</label></div>'+
                    '<div><label for="fAnimationEffect">Animation effect</label><select name="fAnimationEffect" id="fAnimationEffect" class="large">'+
					'<option value="fade" '+( (me.animation_effect == 'fade') ? 'SELECTED' : '' )+'>Fade</option>'+
					'<option value="slide" '+( (me.animation_effect == 'slide') ? 'SELECTED' : '' )+'>Slide</option>'+
					'</select></div>';

					str += '<!-- Embed Basic Extra --></div>'+
						'<div class="cff-field-settings-tab-body-advanced">' +
						$.fbuilder['showAdvancedSettings']( this ) +
						'<div class="cff-editor-container" style="padding-top:10px;"><div style="display:flex;flex-direction:row;align-items:end;"><label for="fCustomStyles" style="flex-grow:1;"><div class="cff-editor-extend-shrink" title="Fullscreen"></div>Customize Form Design<br><i>(Enter the CSS rules. <a href="http://cff.dwbooster.com/faq#q82" target="_blank">More information</a>)</i></label><input type="button" class="button cff-ai-assistant" value="AI" onclick="if(\'cff_ai_assistant_open\' in window) cff_ai_assistant_open(\'css\');"></div><div></iv><textarea id="fCustomStyles" style="width:100%;height:150px;">'+cff_esc_attr(me.customstyles)+'</textarea></div>'+
						'</div>'+
					'</div>';

					return str;
				}
			}
		);

		var theForm = new fform();
		$("#fieldlist").sortable(
			{
				'connectWith': '.ui-sortable',
				'delay': 500,
				'distance': 5,
				'items': '.fields',
				'placeholder': 'ui-state-highlight',
				'tolerance': 'pointer',
				'update': function( event, ui )
				{
                    var index = ui.item.index('#fieldlist>div');
                    if(0<=index)
                    {
                        if(ui.item.hasClass('cff-button-drag')) // It is a new control
                        {
                            ui.item = $('.'+window['cff_form'].fBuild.addItem(ui.item.data('control'), -3).name);
                        }
                        var i, h = items.length;
                        for( i = 0; i < h; i++ )
                        {
                            if( ui.item.hasClass(items[i].name)) break;
                        }

                        if( index )
                        {
                            var prev = $('#fieldlist>div:eq('+(index-1)+')');
                            for( var j = 0; j < h; j++ )
                            {
                                if( prev.hasClass(items[j].name) )
                                {
                                    index = (i<=j) ? j : ++j;
                                    break;
                                }
                            }
                        }

                        items.splice( index, 0,  items.splice( i, 1 )[ 0 ] );
                        $.fbuilder.reloadItems();
                        $('.'+/((fieldname)|(separator))\d+/.exec(ui.item.attr('class'))[0]).trigger('click');
                    }
                    else
                    {
                        // remove
                        try
                        {
                            var i, h = items.length;
                            for( i = 0; i < h; i++ ) if( ui.item.hasClass(items[i].name)) break;
                            items = items.concat( items.splice( i, 1 ) );
                        }
                        catch(err){}
                    }
				}
			}
		);

		$('#tabs').tabs(
			{
				activate: function(event, ui)
					{
						switch( $(this).tabs( "option", "active" ) )
						{
							case 0:
								$(".fform").removeClass("ui-selected");
							break;
							case 1:
								$(".fform").removeClass("ui-selected");
								if (selected < 0)
								{
								   $('#tabs-2').html('<b>No Field Selected</b><br />Please click on a field in the form preview on the right to change its properties.');
								}
							break;
							case 2:
								$(".fields").removeClass("ui-selected");
								$(".fform").addClass("ui-selected");
								$.fbuilder.editForm();
							break;
						}
					}
			}
		);

	    var ffunct = {
	        getFieldsIndex: function()
			{
			   return fieldsIndex;
		    },
		    getItems: function()
			{
			   return items;
		    },
		    addItem: function(id, _selected)
			{
			    var obj = new $.fbuilder.controls[id](),
					fBuild = this,
					n = 0;

                selected = _selected || selected;
				obj = $.extend(true, {}, obj);
                obj.init();
				for ( var i in fieldsIndex ) if( /fieldname/.test( i ) ) n = Math.max( parseInt( i.replace( /fieldname/g,"" ) ), n );
			    n++;

				obj.fBuild = fBuild;
			    $.extend(obj,{name:"fieldname"+n});

                if( selected >= 0 )
                {
					n =  (selected)*1+1;
                    items.splice( n, 0, obj );
					fieldsIndex[obj.name] = n;
					for(var i = n, h = items.length; i<h; i++) fieldsIndex[items[i].name] = i;

					if( id != 'fPageBreak' )
					{
						if( typeof items[ selected ][ 'addItem' ] != 'undefined' )
						{
							obj.name[ 'parent' ] = items[ selected ][ 'name' ];
							items[ selected ][ 'addItem' ]( obj.name );
						}
						else
						{
							// get the parent
							if( items[ selected ][ 'parent' ] !== '' )
							{
								items[ fieldsIndex[ items[ selected ][ 'parent' ] ] ][ 'addItem' ]( obj.name, items[ selected ][ 'name' ]);
							}

							selected++;
						}
					}
					else
					{
						selected++;
					}
                }
                else
                {
                    selected = items.length;
                    items[selected] = obj;
                }
				$.fbuilder.reloadItems();
				return obj;
		    },
		    saveData:function(f)
			{
				try{
					var itemsStringified   = JSON.stringify( items ),
						theFormStringified = JSON.stringify( theForm ),
						errorTxt = 'The entered data includes invalid characters. Please, if you are copying and pasting from another platform, be sure the data not include invalid characters.',
						str;

					if( typeof global_varible_save_data != 'undefined' )
					{
						// If the global_varible_save_data exists clear the form-builder-error-messages
						// And register the form has been edited.
						$( '.form-builder-error-messages' ).html( '' );
						$.fbuilder['formWasModified'] = true;
					}
					else
					{
						setTimeout(function(){ global_varible_save_data = true; }, 1000);
					}

					try{
						if( JSON.parse( itemsStringified ) != null && JSON.parse( theFormStringified ) != null )
						{
							str = "["+ itemsStringified +",["+ theFormStringified +"]]";
							$( "#"+f ).val( str );
						}
						else
						{
							$.fbuilder[ 'showErrorMssg' ]( errorTxt );
						}
					}
					catch( err )
					{
						$.fbuilder[ 'showErrorMssg' ]( errorTxt );
					}
				}catch( err ){}
		    },
		    loadData:function(form_structure, available_templates)
			{
				var structure,
					templates = null,
					fBuild = this;

				try{
					structure =  JSON.parse( $("#"+form_structure).val() );
				}
				catch(err)
				{
					structure = [];
					if(typeof console != 'undefined') console.log(err);
				}

			    try{
					 if( typeof available_templates != 'undefined' ) templates = JSON.parse( $("#"+available_templates).val() );
				}
				catch(err)
				{
					templates = null;
					if(typeof console != 'undefined') console.log(err);
				}

			    if ( structure )
				{
					$.fbuilder.defineGeneralEvents();
					if (structure.length==2)
					{
						items = [];
						for (var i=0;i<structure[0].length;i++)
						{
						   var obj = new $.fbuilder.controls[structure[0][i].ftype]();
						   obj = $.extend( true, {}, obj, structure[0][i] );
						   obj.fBuild = fBuild;
						   items[items.length] = obj;
						}
						theForm = (typeof theForm != 'undefined' ) ? theForm : new fform();
						theForm = $.extend(true, {}, theForm, structure[1][0]);
						$.fbuilder.reloadItems();
					}
				}

				if( templates )
				{
					$.fbuilder.showSettings.formTemplateDic = templates;
				}
		    },
		    removeItem: $.fbuilder[ 'removeItem' ],
		    editItem:   $.fbuilder[ 'editItem' ]
	    }

	    this.fBuild = ffunct;

		// Adjust form builder height on desktop
		if( 768 <= window.innerWidth || 768 <= screen.width ) {
			function updateFormBuilderHeight() {
				let newFormBuilderHeight = window.innerHeight - ( $('#wpadminbar').length ? $('#wpadminbar').outerHeight() : 0 );
				fbuilderjQuery('.form-builder .ctrlsColumn #tabs,.form-builder .dashboardColumn').height(newFormBuilderHeight);
			}

			updateFormBuilderHeight();
			$(window).on( 'resize', updateFormBuilderHeight );
		}

	    return this;
	};

    $.fbuilder[ 'showSettings' ] = {
		sizeList:new Array({id:"small",name:"Small"},{id:"medium",name:"Medium"},{id:"large",name:"Large"}),
		layoutList:new Array({id:"one_column",name:"One Column"},{id:"two_column",name:"Two Column"},{id:"three_column",name:"Three Column"},{id:"side_by_side",name:"Side by Side"}),
		formlayoutList:new Array({id:"top_aligned",name:"Top Aligned"},{id:"left_aligned",name:"Left Aligned"},{id:"right_aligned",name:"Right Aligned"}),
		formTemplateDic: {}, // Form Template dictionary
        showFieldType: function( v )
        {
            return '<label><b>Field Type: '+$.fbuilder[ 'getNameByIdFromType' ]( v )+'</b></label>';
        },
		showTitle: function(v, l)
		{
			l = l || 'default';
			return '<label for="sTitle">Field Label</label><textarea class="large" name="sTitle" id="sTitle">'+cff_esc_attr(v)+'</textarea>'+
					'<div><label>Label Placement</label>'+
					'<div class="cff-radio-group-ctrl">'+
					'<label><input type="radio" name="sFieldLayout" value="default" '+(l == 'default' ? 'checked' : '')+'><span>Default</span></label>'+
					'<label><input type="radio" name="sFieldLayout" value="top_aligned" '+(l == 'top_aligned' ? 'checked' : '')+'><span>Top</span></label>'+
					'<label><input type="radio" name="sFieldLayout" value="left_aligned" '+(l == 'left_aligned' ? 'checked' : '')+'><span>Left</span></label>'+
					'<label><input type="radio" name="sFieldLayout" value="right_aligned" '+(l == 'right_aligned' ? 'checked' : '')+'><span>Right</span></label></div></div>';
		},
		showShortLabel: function( v )
		{
			return '<div><label for="sShortlabel">Short label (optional) [<a class="helpfbuilder" text="The short label is used at title for the column when exporting the form data to CSV files.\n\nIf the short label is empty then, the field label will be used for the CSV file.">help?</a>] :</label><input type="text" class="large" name="sShortlabel" id="sShortlabel" value="'+cff_esc_attr(v)+'" /></div>';
		},
		showName: function( v )
		{
			return '<div><label for="sNametag">Field name, tag for the message:</label><input type="text" readonly="readonly" class="large" name="sNametag" id="sNametag" value="&lt;%'+cff_esc_attr(v)+'%&gt;" />'+
				   '<input style="display:none" readonly="readonly" class="large" name="sName" aria-label="Field name" id="sName" value="'+cff_esc_attr(v)+'" /></div>';
		},
		showPredefined: function(v,c)
		{
			return '<div><label for="sPredefined">Predefined Value</label><textarea class="large" name="sPredefined" id="sPredefined">'+cff_esc_attr(v)+'</textarea><br /><i>It is possible to use another field in the form as predefined value. Ex: fieldname1</i><label><input type="checkbox" name="sPredefinedClick" id="sPredefinedClick" '+((c)?"checked":"")+' value="1" > Use predefined value as placeholder.</label></div>';
		},
		showEqualTo: function(v,name)
		{
			return '<div><label for="sEqualTo">Equal to [<a class="helpfbuilder" text="Use this field to create password confirmation field or email confirmation fields.\n\nSpecify this setting ONLY into the confirmation field, not in the original field.">help?</a>]</label><select class="equalTo" name="sEqualTo" id="sEqualTo" dvalue="'+cff_esc_attr(v)+'" dname="'+cff_esc_attr(name)+'"></select></div>';
		},
		showAutocomplete: function(v)
		{
            var options = '', values = ['off', 'on', 'name', 'honorific-prefix', 'given-name', 'additional-name', 'family-name', 'honorific-suffix', 'nickname', 'email', 'username', 'new-password', 'current-password', 'one-time-code', 'organization-title', 'organization', 'street-address', 'address-line1', 'address-line2', 'address-line3', 'address-level4', 'address-level3', 'address-level2', 'address-level1', 'country', 'country-name', 'postal-code', 'cc-name', 'cc-given-name', 'cc-additional-name', 'cc-family-name', 'cc-number', 'cc-exp', 'cc-exp-month', 'cc-exp-year', 'cc-csc', 'cc-type', 'transaction-currency', 'transaction-amount', 'language', 'bday', 'bday-day', 'bday-month', 'bday-year', 'sex', 'tel', 'tel-country-code', 'tel-national', 'tel-area-code', 'tel-local', 'tel-extension', 'impp', 'url', 'photo'];

            for(var i = 0, h = values.length; i<h; i++)
            {
                options += '<option value="'+cff_esc_attr(values[i])+'" '+(values[i] == v ? 'SELECTED' : '')+'>'+cff_esc_attr(values[i])+'</option>';
            }
			return '<div><label for="sAutocomplete">Autocomplete</label>'+
            '<select class="large" name="sAutocomplete" id="sAutocomplete">'+options+'</select><br><i>The field attribute takes precedence over the form settings.</i></div>';
		},
		showRequired: function(v)
		{
			return '<label><input type="checkbox" name="sRequired" id="sRequired" '+((v)?"checked":"")+'>Required</label>';
		},
		showExclude: function(v)
		{
			return '<label><input type="checkbox" name="sExclude" id="sExclude" '+((v)?"checked":"")+'>Exclude from submission</label>';
		},
		showAcceptHTML: function(v)
		{
			return '<label><input type="checkbox" name="sAccept_HTML" id="sAccept_HTML" '+((v)?"checked":"")+'>Accept submitting HTML as field value</label>';
		},
		showSelect2: function(v)
		{
			return '<label><input type="checkbox" name="sSelect2" id="sSelect2" '+((v)?"checked":"")+'>Include a search box (Experimental)</label>';
		},
		showReadonly: function(v)
		{
			return '<label><input type="checkbox" name="sReadonly" id="sReadonly" '+((v)?"checked":"")+'>Read Only</label>';
		},
		showNextPage: function(v, toShow)
		{
			toShow = toShow || false;
			return '<label style="display:'+(toShow ? 'block' : 'none')+'"><input type="checkbox" id="sNextPage" name="sNextPage" ' + (v ? 'CHECKED' : '') + '> Auto-advance to next page in multi-page form on selection</label>';
		},
		showNumberpad: function(v)
		{
			return '<label><input type="checkbox" name="sNumberpad" id="sNumberpad" '+((v)?"checked":"")+'>Forcing numberpad on mobiles</label>';
		},
		showSize: function(v)
		{
			var str = '<div class="cff-radio-group-ctrl">';
			for (var i=0;i<this.sizeList.length;i++)
			{
				str += '<label><input type="radio" name="sSize" value="'+cff_esc_attr(this.sizeList[i].id)+'" '+( this.sizeList[i].id==v ? 'checked' : '')+'><span>'+cff_esc_attr(this.sizeList[i].name)+'</span></label>';
			}
			str += '</div>'
			return '<label>Field Size</label>'+str;
		},
		showLayout: function(v)
		{
			var str = "";
			for (var i=0;i<this.layoutList.length;i++)
			{
				str += '<option value="'+cff_esc_attr(this.layoutList[i].id)+'" '+((this.layoutList[i].id==v)?"selected":"")+'>'+cff_esc_attr(this.layoutList[i].name)+'</option>';
			}
			return '<label for="sLayout">Field Layout</label><select name="sLayout" id="sLayout">'+str+'</select>';
		},
		showUserhelp: function(v,a,c,i)
		{
			return '<hr>'+
			'<label for="sUserhelp">Instructions for User</label><textarea class="large" name="sUserhelp" id="sUserhelp">'+cff_esc_attr(v)+'</textarea><label class="column"><input type="checkbox" name="sUserhelpTooltip" id="sUserhelpTooltip" '+((c)?"checked":"")+' value="1" > Show as floating tooltip&nbsp;&nbsp;</label><label class="column"><input type="checkbox" name="sTooltipIcon" id="sTooltipIcon" '+((i)?"checked":"")+' value="1" > Display on icon</label><div class="clearer"></div>'+
			'<label for="sAudioSrc">Audio Tutorial</label>'+
			'<div><input type="text" style="width:70%;" name="sAudioSrc" id="sAudioSrc" value="'+cff_esc_attr(a)+'"><input id="sSelectAudioBtn" type="button" value="Browse" style="width:28%;" class="button-secondary" /></div>'+
			'<hr>';
		},
		showCsslayout: function(v)
		{
			return '<div><label for="sCsslayout">Add Css Layout Keywords</label><input type="text" class="large" name="sCsslayout" id="sCsslayout" value="'+cff_esc_attr(v)+'" /></div>';
		}
	};

	$.fbuilder['showAdvancedSettings'] = function( arg ) {
		function css_rules_datalist() {
			let css_rules = ['align-content', 'align-items', 'align-self', 'all', 'animation', 'animation-delay', 'animation-direction', 'animation-duration', 'animation-fill-mode', 'animation-iteration-count', 'animation-name', 'animation-play-state', 'animation-timing-function', 'backface-visibility', 'background', 'background-attachment', 'background-blend-mode', 'background-clip', 'background-color', 'background-image', 'background-origin', 'background-position', 'background-repeat', 'background-size', 'border', 'border-bottom', 'border-bottom-color', 'border-bottom-left-radius', 'border-bottom-right-radius', 'border-bottom-style', 'border-bottom-width', 'border-collapse', 'border-color', 'border-image', 'border-image-outset', 'border-image-repeat', 'border-image-slice', 'border-image-source', 'border-image-width', 'border-left', 'border-left-color', 'border-left-style', 'border-left-width', 'border-radius', 'border-right', 'border-right-color', 'border-right-style', 'border-right-width', 'border-spacing', 'border-style', 'border-top', 'border-top-color', 'border-top-left-radius', 'border-top-right-radius', 'border-top-style', 'border-top-width', 'border-width', 'bottom', 'box-shadow', 'box-sizing', 'caption-side', 'caret-color', 'clear', 'clip', 'clip-path', 'color', 'column-count', 'column-fill', 'column-gap', 'column-rule', 'column-rule-color', 'column-rule-style', 'column-rule-width', 'column-span', 'column-width', 'columns', 'content', 'counter-increment', 'counter-reset', 'cursor', 'direction', 'display', 'empty-cells', 'filter', 'flex', 'flex-basis', 'flex-direction', 'flex-flow', 'flex-grow', 'flex-shrink', 'flex-wrap', 'float', 'font', 'font-family', 'font-kerning', 'font-size', 'font-size-adjust', 'font-stretch', 'font-style', 'font-variant', 'font-weight', 'grid', 'grid-area', 'grid-auto-columns', 'grid-auto-flow', 'grid-auto-rows', 'grid-column', 'grid-column-end', 'grid-column-gap', 'grid-column-start', 'grid-gap', 'grid-row', 'grid-row-end', 'grid-row-gap', 'grid-row-start', 'grid-template', 'grid-template-areas', 'grid-template-columns', 'grid-template-rows', 'height', 'hyphens', 'justify-content', 'left', 'letter-spacing', 'line-height', 'list-style', 'list-style-image', 'list-style-position', 'list-style-type', 'margin', 'margin-bottom', 'margin-left', 'margin-right', 'margin-top', 'max-height', 'max-width', 'min-height', 'min-width', 'object-fit', 'object-position', 'opacity', 'order', 'outline', 'outline-color', 'outline-offset', 'outline-style', 'outline-width', 'overflow', 'overflow-x', 'overflow-y', 'padding', 'padding-bottom', 'padding-left', 'padding-right', 'padding-top', 'page-break-after', 'page-break-before', 'page-break-inside', 'perspective', 'perspective-origin', 'pointer-events', 'position', 'quotes', 'right', 'scroll-behavior', 'table-layout', 'text-align', 'text-align-last', 'text-decoration', 'text-decoration-color', 'text-decoration-line', 'text-decoration-style', 'text-indent', 'text-justify', 'text-overflow', 'text-shadow', 'text-transform', 'top', 'transform', 'transform-origin', 'transform-style', 'transition', 'transition-delay', 'transition-duration', 'transition-property', 'transition-timing-function', 'user-select', 'vertical-align', 'visibility', 'white-space', 'width', 'word-break', 'word-spacing', 'word-wrap', 'writing-mode', 'z-index'],
			output = '<datalist id="cff-css-rules-datalist">';

			for ( let i in css_rules )
				output += '<option value="'+cff_esc_attr( css_rules[i] )+'"></option>';
			output += '</datalist>';
			return output;
		};

		fbuilderjQuery.fbuilder['css_rule_pair'] = function( component, rule, value, restrict ) {
			let output = '';
			rule  = rule  || '';
			value = value || '';
			restrict = restrict || false;

			output += '<div class="css-rule" style="margin-top:3px;">'+
				'<div class="column width50"><input type="text" data-cff-css-component="'+cff_esc_attr(component)+'" name="advanced[css][css_rule][]" value="'+cff_esc_attr(rule)+'" class="large" placeholder="CSS rule" list="cff-css-rules-datalist" aria-label="CSS rule name" ' + ( restrict ? 'readonly' : '' )+ '></div>'+
				'<div class="column width50">'+
				'<input type="text" data-cff-css-component="'+cff_esc_attr(component)+'" name="advanced[css][css_value][]" value="'+cff_esc_attr(value)+'" placeholder="CSS value" style="width:'+ ( restrict ? '100%' : 'calc( 100% - 30px ) !important' )+';" aria-label="CSS rule value">'+
				(!restrict ? '<input type="button" data-cff-css-component="'+cff_esc_attr(component)+'" value="-" class="button-secondary cff-css-rule-delete">' : '')+
				'</div>'+
				'<div class="clearer"></div>'+
				'</div>';

			return output;
		};

		let output = '';
		if( 'advanced' in arg ) {
			if ( 'css' in arg.advanced ) {
				output += css_rules_datalist();

				if ( 'ftype' in arg ) {
					// Top Message fields
					output += '<p style="font-style:italic;padding-bottom:20px;">You can customize the appearance of a field\'s components by adding CSS rules. If you require more control over the field\'s styles, you can assign a class name to the field through the <a href="javascript:fbuilderjQuery(\'.cff-field-settings-tab-header-basic\').trigger(\'click\');fbuilderjQuery(\'#sCsslayout\').trigger(\'focus\');">"Add CSS Layout Keywords"</a> attribute in the "Basic Settings" tab and define it using the "Customize Form Design" attribute in the "Form Settings &gt; Advanced Settings" tab.</p>';

					if(
						'hidefield' in arg &&
						'ftype' in arg &&
						-1 == ['fCalculated'].indexOf( arg.ftype )
					) {
						output += '<div style="padding-bottom:20px;display:flex;align-items:start;"><input type="checkbox" id="sHideField" name="sHideField" ' + (arg.hidefield ? 'CHECKED' : '') + ' style="margin-top:0;"> <label for="sHideField" style="display:inline-block;margin-top:0;">Hide field. To display the field, you must use the SHOWFIELD("'+arg.name+'") operation, or SHOWFIELD('+arg.name+'|n) in the equations context.</label></div>';
					}
				} else {
					// Top Message form
				output += '<p style="font-style:italic;padding-bottom:20px;">CSS rules are made to the form preview and public website. To input CSS blocks directly, scroll to the <a href="javascript:void(0);" onclick="document.getElementsByClassName(\'cff-editor-container\')[0].scrollIntoView({behavior:\'smooth\', block: \'end\'});">"<span style="font-weight:bold;">Customize Form Design</span>" attribute</a> <span style="font-style:normal; font-weight:bold; font-size:1.3em;">&ShortDownArrow;</span></p>';
				}

				let components = arg.advanced.css,
					c;
				for ( let i in components ) {
					c = components[i];
					if ( 'label' in c ) {
						output += '<div style="margin-top:8px;">' + cff_sanitize(c['label'], true) + '</div>';
					}
					output += '<hr><div id="cff-css-rules-container-'+i+'">';
					if ( 'rules' in c ) {
						for ( let j in c['rules'] ) {
							output += fbuilderjQuery.fbuilder['css_rule_pair']( i, j, c['rules'][j], 'restrict' in c );
						}
					}
					if ( ! ('restrict' in c) ) {
						output += fbuilderjQuery.fbuilder['css_rule_pair']( i );
					}
					output += '</div>';
					if ( ! ('restrict' in c) ) {
						output += '<div style="text-align:right;margin-top:4px;"><input type="button" class="button-secondary" value="Add rule" onclick="fbuilderjQuery(\'#cff-css-rules-container-'+i+'\').append(fbuilderjQuery.fbuilder.css_rule_pair(\''+i+'\'));"></div>';
					}
				}
			}
		}
		return output;
	};

	$.fbuilder['updateAdvancedSettings'] = function( e, c ) {
		let css = e.advanced.css;

		css[c]['rules'] = {};
		$('[data-cff-css-component="'+c+'"][name^="advanced[css][css_rule]"]').each( function(i, e){
			let css_rule = cff_esc_attr( String(this.value).trim() ), v;
			if ( css_rule == '' ) return;
			v = String( $('[data-cff-css-component="'+c+'"][name^="advanced[css][css_value]"]:eq('+i+')').val() ).trim();
			v = cff_esc_attr( v.replace(/[\{\}]/g, '') );
			css[c]['rules'][css_rule] = v;
		});
		e.advanced.css = css;
	};

	$.fbuilder.controls[ 'ffields' ] = function(){};
	$.extend( $.fbuilder.controls[ 'ffields' ].prototype,
		{
			form_identifier:"",
			name:"",
			fieldlayout:"default",
			shortlabel:"",
			index:-1,
			ftype:"",
			userhelp:"",
			audiotutorial:"",
			userhelpTooltip:false,
			tooltipIcon:false,
			csslayout:"",
			hidefield:false,
			advanced:{
				css:{
					container:{
						label: 'Field container div tag',
						rules:{}
					},
					label:{
						label: 'Field label',
						rules:{}
					},
					input:{
						label: 'Input tag',
						rules:{}
					},
					help:{
						label: 'Instructions for users',
						rules:{}
					}
				}
			},
			controlLabel:function( l ){ return this.name + ' - ' + l; },
			iconsContainer: function( i, d ){
				i = i || '';
				d = d || true;
				return '<div class="cff-field-controls">'+i+(d ? '<div title="Duplicate" class="copy ui-icon ui-icon-copy "></div>' : '')+'<div title="Delete" class="remove ui-icon ui-icon-trash "></div></div>';
			},
			init:function(){},
			initAdv:function(){},
			editItemEvents:function( e )
			{
				if( typeof e != 'undefined' && typeof e.length != 'undefined' )
				{
					for( var i = 0, h = e.length; i<h; i++ )
					{
						/**
						* s -> selector
						* e -> event name
						* l -> element
						* f -> function to apply the value
                        * x -> escape
						*/
						$(e[i].s).on(e[i].e, {obj:this, i:e[i]}, function(e){
							var v = $(this).val();
							if(typeof e.data.i['f'] != 'undefined') v = e.data.i.f($(this));
							e.data.obj[e.data.i.l] = ('x' in e.data.i && e.data.i.x) ? cff_esc_attr(v) : v;
							$.fbuilder.reloadItems( {'field': e.data.obj} );
						});
					}
				}

				$("#sTitle").on("keyup", {obj: this}, function(e)
					{
						var str = $(this).val();
						e.data.obj.title = str.replace(/\n/g,"<br />");
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("[name='sFieldLayout']").on("click change", {obj: this}, function(e)
					{
						e.data.obj.fieldlayout = $("[name='sFieldLayout']:checked").val();
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("[name='sSize']").on("click change", {obj: this}, function(e)
					{
						e.data.obj.size = $("[name='sSize']:checked").val();
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sShortlabel").on("keyup", {obj: this}, function(e)
					{
						e.data.obj.shortlabel = $(this).val();
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sReadonly").on("click", {obj: this}, function(e)
					{
						e.data.obj.readonly = $(this).is(':checked');
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sNumberpad").on("click", {obj: this}, function(e)
					{
						e.data.obj.numberpad = $(this).is(':checked');
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sAutocomplete").on("change", {obj: this}, function(e)
					{
						e.data.obj.autocomplete = $(this).val();
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sPredefined").on("keyup", {obj: this}, function(e)
					{
						e.data.obj.predefined = $(this).val();
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sPredefinedClick").on("click", {obj: this}, function(e)
					{
						e.data.obj.predefinedClick = $(this).is(':checked');
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sRequired").on("click", {obj: this}, function(e)
					{
						e.data.obj.required = $(this).is(':checked');
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sHideField").on("click", {obj: this}, function(e)
					{
						e.data.obj.hidefield = $(this).is(':checked');
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sExclude").on("click", {obj: this}, function(e)
					{
						e.data.obj.exclude = $(this).is(':checked');
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sAccept_HTML").on("click", {obj: this}, function(e)
					{
						e.data.obj.accept_html = $(this).is(':checked');
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sUserhelp").on("keyup", {obj: this}, function(e)
					{
						e.data.obj.userhelp = $(this).val();
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sUserhelpTooltip").on("click", {obj: this}, function(e)
					{
						e.data.obj.userhelpTooltip = $(this).is(':checked');
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sTooltipIcon").on("click", {obj: this}, function(e)
					{
						e.data.obj.tooltipIcon = $(this).is(':checked');
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sAudioSrc").on("keyup change", {obj: this}, function(e)
					{
						e.data.obj.audiotutorial = $(this).val();
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				$("#sSelectAudioBtn").on("click", {obj: this}, function(e)
					{
						var media = wp.media({
									title: 'Select Source',
									button: {
										text: 'Select Source'
									},
									multiple: false
							}).on('select',
								function() {
									var regExp = new RegExp( 'audio', 'i'),
										attachment = media.state().get('selection').first().toJSON();
									if( !regExp.test( attachment.mime ) )
									{
										alert( 'Invalid mime type' );
										return;
									}
									$( '#sAudioSrc' ).val( attachment.url ).trigger('change');
								}
							).open();
						return false;
					});

				$("#sCsslayout").on("keyup", {obj: this}, function(e)
					{
						e.data.obj.csslayout = $(this).val().replace(/\,/g, ' ').replace(/\s+/g, ' ');
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

                $(".helpfbuilder").off('click');
				$(".helpfbuilder").on('click', function(evt)
					{
						evt.preventDefault();
						evt.stopPropagation();
						alert($(this).attr("text"));
					});
				$("#sDeveloperNotes").on("keyup", {obj: this}, function(e)
					{
						e.data.obj._developerNotes = $(this).val();
						$.fbuilder.reloadItems( {'field': e.data.obj} );
					});

				// Advanced section
				$(document).off("keyup", '[name^="advanced[css]"]');
				$(document).on("keyup", '[name^="advanced[css]"]', {obj: this}, function(e) {
					$.fbuilder['updateAdvancedSettings'](e.data.obj, $(this).attr('data-cff-css-component'));
					$.fbuilder.reloadItems({'field':e.data.obj});
				});
				$(document).off('click', '.cff-css-rule-delete');
				$(document).on('click', '.cff-css-rule-delete', {obj: this}, function(e) {
					$(this).closest('.css-rule').remove();
					$.fbuilder['updateAdvancedSettings'](e.data.obj, $(this).attr('data-cff-css-component'));
					$.fbuilder.reloadItems({'field':e.data.obj});
				});
			},

			showColumnIcon:function()
			{
				let output = '';
				if ( 'csslayout' in this ) {
					if ( /\b((col\-(lg|md|sm|xs)\-\d+)|(column\d+))\b/i.test( this.csslayout) ) {
						return '<div class="sticker" title="Has column classes applied"><i class="sticker-x"></i></div>';
					}
				}
				return output;
			},

			showSpecialData:function()
			{
				if(typeof this.showSpecialDataInstance != 'undefined')
				{
					return this.showSpecialDataInstance();
				}
				else
				{
					return "";
				}
			},

			showEqualTo:function()
			{
				if(typeof this.equalTo != 'undefined')
				{
					return $.fbuilder.showSettings.showEqualTo(this.equalTo,this.name);
				}
				else
				{
					return "";
				}
			},

			showPredefined:function()
			{
				if(typeof this.predefined != 'undefined')
				{
					return $.fbuilder.showSettings.showPredefined(this.predefined,this.predefinedClick);
				}
				else
				{
					return "";
				}
			},
			/** Modified for showing required and readonly attributes **/
			showRequired:function()
			{
				var result = '';
                if(typeof this.autocomplete != 'undefined') result += $.fbuilder.showSettings.showAutocomplete(this.autocomplete);
				if(typeof this.required != 'undefined') result += $.fbuilder.showSettings.showRequired(this.required);
				if(typeof this.accept_html != 'undefined') result += $.fbuilder.showSettings.showAcceptHTML(this.accept_html);
				if(typeof this.exclude != 'undefined')  result += $.fbuilder.showSettings.showExclude(this.exclude);
				if(typeof this.select2 != 'undefined')  result += $.fbuilder.showSettings.showSelect2(this.select2);
				if(typeof this.readonly != 'undefined') result += $.fbuilder.showSettings.showReadonly(this.readonly);
				if(typeof this.nextPage != 'undefined') result += $.fbuilder.showSettings.showNextPage(this.nextPage, (typeof this.multiple == 'undefined') ? true : !this.multiple );
				if(typeof this.numberpad != 'undefined') result += $.fbuilder.showSettings.showNumberpad(this.numberpad);
				return result;
			},

			showSize:function()
			{
				if(typeof this.size != 'undefined')
				{
					return $.fbuilder.showSettings.showSize(this.size);
				}
				else
				{
					return "";
				}
			},

			showLayout:function()
			{
				if(typeof this.layout != 'undefined')
				{
					return $.fbuilder.showSettings.showLayout(this.layout);
				}
				else
				{
					return "";
				}
			},

			showRange:function()
			{
				if(typeof this.min != 'undefined')
				{
					return this.showRangeIntance();
				}
				else
				{
					return "";
				}
			},

			showFormat:function()
			{
				if(typeof this.dformat != 'undefined')
				{
					try
					{
						return this.showFormatIntance();
					} catch(e) {return "";}
				}
				else
				{
					return "";
				}
			},

			showChoice:function()
			{
				if(typeof this.choices != 'undefined')
				{
					return this.showChoiceIntance();
				}
				else
				{
					return "";
				}
			},

			showUserhelp:function()
			{
				return $.fbuilder.showSettings.showUserhelp(this.userhelp,this.audiotutorial,this.userhelpTooltip,this.tooltipIcon);
			},

			showCsslayout:function()
			{
				return $.fbuilder.showSettings.showCsslayout(this.csslayout);
			},

			showDeveloperNotes:function()
			{
				if(typeof this._developerNotes != 'undefined')
				{
					return '<hr><label for="sDeveloperNotes">Developer Notes</label><textarea class="large" name="sDeveloperNotes" id="sDeveloperNotes">'+cff_esc_attr(this._developerNotes)+'</textarea><div class="clearer"><span class="uh">Developer notes. Only visible in the Form Builder</span></div><hr>';
				}
				else
				{
					return "";
				}
			},

			showAdvancedSettings:function()
			{
				return $.fbuilder['showAdvancedSettings'](this);
			},

			fieldSettingsTabs:function( basic )
			{
				this.initAdv();
				let output = basic,
					has_advanced = ( 'advanced' in this ) ? true : false;

				if ( has_advanced ) {
					output = '<div class="cff-field-settings-tabs-headers">'+
						'<div class="cff-field-settings-tab-header-basic cff-field-settings-tab-active">Basic Settings</div>'+
						'<div class="cff-field-settings-tab-header-advanced">Advanced Settings</div>'+
					'</div>'+
					'<div class="cff-field-settings-tabs-bodies">'+
						'<div class="cff-field-settings-tab-body-basic cff-field-settings-tab-body-active">' + output + '<!-- Embed Basic Extra --></div>'+
						'<div class="cff-field-settings-tab-body-advanced">' + this.showAdvancedSettings() + '</div>'+
					'</div>';
				} else {
					output += '<!-- Embed Basic Extra -->';
				}

				return output;
			},
			showAllSettings:function()
			{
				return this.fieldSettingsTabs(this.showFieldType()+this.showTitle()+this.showShortLabel()+this.showName()+this.showSize()+this.showLayout()+this.showFormat()+this.showRange()+this.showRequired()+this.showSpecialData()+this.showEqualTo()+this.showPredefined()+this.showChoice()+this.showUserhelp()+this.showCsslayout());
			},

			showFieldType:function()
			{
				return $.fbuilder.showSettings.showFieldType(this.ftype);
			},

			showTitle:function()
			{
				return $.fbuilder.showSettings.showTitle(this.title, 'fieldlayout' in this ? this.fieldlayout : null );
			},

			showName:function()
			{
				return $.fbuilder.showSettings.showName(this.name)+this.showDeveloperNotes();
			},

			showShortLabel:function()
			{
				return $.fbuilder.showSettings.showShortLabel(this.shortlabel);
			},

			display:function()
			{
				return 'Not available yet';
			},

			show:function()
			{
				return 'Not available yet';
			}
		}
	);

	$( '.cff-metabox .hndle' ).on( 'click', function(){
		var e = $( this ).closest('.cff-metabox');
		e.toggleClass( 'cff-metabox-opened cff-metabox-closed' );
		$.post(
			'admin.php?page=cp_calculated_fields_form',
			{
				'cff-metabox-id' : e.attr('id'),
				'cff-metabox-action' : e.hasClass( 'cff-metabox-opened' ) ? 'open' : 'close',
				'cff-metabox-nonce'  : cff_metabox_nonce || 0
			}
		);
	} );

	$( '.cff-attach-file' ).on( 'click', function(evt) {
		var field = $(this).closest('tr').find('[type="text"]'),
			media = wp.media(
						{
							title: 'Select file',
							multiple: false
						}
					).on(
						'select',
						(function( field ){
							return function() {
								var attachment = media.state().get('selection').first().toJSON(),
									url = attachment.url;
								field.val( url );
							};
						})( field )
					).open();
	} );

	// Redirect to the admin list sections
	$(window).on('load', function(){
		if ( /cp_calculated_fields_form_sub_addons/i.test(document.location.search) ) {
			$('#metabox_addons_area')[0].scrollIntoView();
		} else if ( /cp_calculated_fields_form_sub_troubleshoots_settings/i.test(document.location.search) ) {
			$('#metabox_troubleshoot_area')[0].scrollIntoView();
		} else if ( /cp_calculated_fields_form_sub_import_export/i.test(document.location.search) ) {
			$('#metabox_import_export_area')[0].scrollIntoView();
		} else if ( /cp_calculated_fields_form_sub_new/i.test(document.location.search) ) {
			cff_openLibraryDialog( true );
		}

		$( '#cff-ai-assistant-container' ).draggable({ handle: ".cff-ai-assistan-title", containment: "window",  drag: function(evt, ui) {
			ui.position.top = Math.max(35, ui.position.top);
		}});
	});

	$(document).on('keypress', '.cff_form_builder input[type="text"],.cff_form_builder input[type="number"]', function( evt ) {
		var keycode = (evt.keyCode ? evt.keyCode : evt.which);
		if( keycode == 13 ) {
			evt.preventDefault();
			evt.stopPropagation();
			return false;
		}
	});

	window['cff_getScrollForURL'] = function( symbol ) {
		symbol = symbol || '&';
		let output = '';
		if('scrollY' in window) output = symbol + 'scrolly='+window.scrollY+'&scrollx='+window.scrollX;
		return output;
	};

	// Scroll page if scrollx and scrolly parameters are availables in the URL
	(function(){
		try {
			const urlParams = new URLSearchParams(window.location.search);
			const scroll_x = urlParams.get('scrollx');
			const scroll_y = urlParams.get('scrolly');
			if ( scroll_x && scroll_y && 'scrollTo' in window && ( ! $('.form-builder-error-messages').length || $('.form-builder-error-messages:empty').length ) ) {
				window.scrollTo(scroll_x, scroll_y);
			}
		} catch(err){}
	})();