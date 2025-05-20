jQuery(function () {
	if ( typeof cpcff_forms_library_config == 'undefined' ) return;
	function getApiKey() {
		let api_key = localStorage.getItem('CFFAIFORMGENERATORAPIKEY');
		if(api_key) return api_key;
		return '';
    }

	function saveApiKey(api_key) {
		if(api_key) {
			localStorage.setItem('CFFAIFORMGENERATORAPIKEY', api_key);
		} else {
			this.clearApiKey();
		}
	}

	function clearApiKey() {
		localStorage.removeItem('CFFAIFORMGENERATORAPIKEY');
	}

	// Texts
	let video_tutorial_url			= 'https://www.youtube.com/embed/KB4VOFrbAT0?start=48',
		txt_api_key_placeholder 	= cpcff_forms_library_config['texts']['api_key_placeholder'],
		txt_form_descritpion_placeholder = cpcff_forms_library_config['texts']['form_descritpion_placeholder'],
		txt_search_placeholder 		= cpcff_forms_library_config['texts']['search_placeholder'],
		txt_form_name_placeholder 	= cpcff_forms_library_config['texts']['form_name_placeholder'],

		txt_form_descritpion_label = cpcff_forms_library_config['texts']['form_descritpion_label'],
		txt_no_form_label 		   = cpcff_forms_library_config['texts']['no_form_label'],
		txt_video_label 		   = cpcff_forms_library_config['texts']['video_label'],

		txt_api_key_instruct 	   = cpcff_forms_library_config['texts']['api_key_instruct'],

		txt_api_key_requirement_error	  = cpcff_forms_library_config['texts']['api_key_requirement_error'],
		txt_description_requirement_error = cpcff_forms_library_config['texts']['description_requirement_error'],

		txt_ai_form_generator_menu 	= cpcff_forms_library_config['texts']['ai_form_generator_menu'],
		txt_website_forms_menu		= cpcff_forms_library_config['texts']['website_forms_menu'],
		txt_all_categories_menu		= cpcff_forms_library_config['texts']['all_categories_menu'],

		txt_save_api_key_btn 		= cpcff_forms_library_config['texts']['save_api_key_btn'],
		txt_saving_api_key_btn 		= cpcff_forms_library_config['texts']['saving_api_key_btn'],
		txt_clear_api_key_btn 		= cpcff_forms_library_config['texts']['clear_api_key_btn'],
		txt_generate_form_btn 		= cpcff_forms_library_config['texts']['generate_form_btn'],
		txt_use_it_btn 				= cpcff_forms_library_config['texts']['use_it_btn'],
		txt_create_form_btn 		= cpcff_forms_library_config['texts']['create_form_btn'],
		txt_back_btn 				= cpcff_forms_library_config['texts']['back_btn'],

	// Variables
		$ = jQuery,
		categories 	= {},
		form_name_library_field,

    /* Templates */
		ai_generator_tpl = `
			<div class="cff-ai-form-generator" style="display:none;">
				<div class="cff-ai-form-description-container">
					<div class="cff-ai-api-key-container">
						<input type="password" id="cff-ai-api-key" name="cff-ai-api-key" placeholder="${txt_api_key_placeholder}" value="${getApiKey()}" />
						<button id="cff-ai-save-btn" class="button-primary" title="">${txt_save_api_key_btn}</button>
						<button id="cff-ai-clear-btn" class="button-secondary">${txt_clear_api_key_btn}</button>
					</div>
					<div class="cff-ai-video-tutorial">
						<div style="text-align:right;">
							<div class="cff-form-library-close-back cff-form-library-close-video" onclick="document.getElementsByClassName(\'cff-ai-video-tutorial\')[0].remove();"></div>
						</div>
						<iframe width="1360" height="507" src="${video_tutorial_url}" title="Form AI Generator" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
					</div>
					<i>${txt_api_key_instruct} <a href="${video_tutorial_url}" target="_blank">${txt_video_label}</a></i>
					<div class="cff-form-library-form-title">${txt_form_descritpion_label}</div>
					<textarea id="cff-ai-form-description" rows="4" placeholder="${txt_form_descritpion_placeholder}"></textarea>
					<div>
						<input type="button" class="button-primary cff-ai-generate" value="${txt_generate_form_btn}" />
					</div>
				</div>
				<div class="cff-ai-form-preview-container">
					<div class="cff-form-library-close-back cff-form-library-back" data-label="&lt; ${txt_back_btn}"></div>
					<div class="cff-ai-form-preview">
						<iframe></iframe>
					</div>
					<input type="button" class="button-primary cff-select-form" value="${txt_use_it_btn}"  onclick="cff_getTemplate('ai-generator');" />
				</div>
			</div>
		`,

		dialog_tpl = `
			<div class="cff-form-library-cover">
				<div class="cff-form-library-container">
					<div class="cff-form-library-column-left">
						<div class="cff-form-library-search-box">
							<div class="cff-form-library-close-back cff-form-library-close"></div>
							<input type="search" placeholder="${txt_search_placeholder}" oninput="cff_filteringFormsByText(this)">
						</div>
						<div class="cff-form-library-ai-forms">
							<ul>
								<li><a href="javascript:void(0);" onclick="cff_displayAIGenerator(this);">${txt_ai_form_generator_menu}</a></li>
							</ul>
						</div>
						<div class="cff-form-library-website-forms">
							<ul>
								<li><a href="javascript:void(0);" onclick="cff_templatesInCategory(this,-1);">${txt_website_forms_menu}</a></li>
							</ul>
						</div>
						<div class="cff-form-library-categories">
							<ul>
								<li><a href="javascript:void(0);" onclick="cff_templatesInCategory(this);" class="cff-form-library-active-category">${txt_all_categories_menu}</a></li>
							</ul>
						</div>
					</div>
					<div class="cff-form-library-column-right">
						<div style="display:flex;">
							<div class="cff-form-library-blank-form">
								<input type="text" placeholder="${txt_form_name_placeholder}" id="cp_itemname_library">
								<input type="button" value="${txt_create_form_btn}" class="button-primary" onclick="cff_getTemplate(0);">
							</div>
							<div class="cff-form-library-close-back cff-form-library-close"></div>
						</div>
						<div class="cff-form-library-main">
							<div class="cff-form-library-no-form">${txt_no_form_label}</div>
						</div>
						${ai_generator_tpl}
					</div>
				</div>
			</div>
		`,

		form_tpl = `
			<div class="cff-form-library-form">
				<div class="cff-form-library-form-top">
					<div class="cff-form-library-form-title"></div>
					<div class="cff-form-library-form-description"></div>
				</div>
				<div class="cff-form-library-form-bottom">
					<div class="cff-form-library-form-category"></div>
					<div>
						<input type="button" class="button-primary cff-select-form" value="${txt_use_it_btn}" />
						<!--<input type="button" class="button-secondary cff-preview-form" value="Preview" />-->
					</div>
				</div>
			</div>
		`;

    $.expr.pseudos.contains = $.expr.createPseudo(function (arg) {
        return function (elem) {
            return $(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
        };
    });

	function openDialog(explicit) {

        var version 	= 'free',
			version_n 	= {'free': 1, 'pro': 2, 'dev': 3, 'plat': 4},
			form_name_field = $('[id="cp_itemname"]'),
			form_tag 	= form_name_field.closest('form')[0],
			data 		= [];

		form_name_field.val(form_name_field.val().replace(/^\s*/, '').replace(/\s*$/, ''));

		if( ( typeof explicit == 'undefined' || !explicit ) && 'reportValidity' in form_tag && !form_tag.reportValidity()) return;

        if (!$('.cff-form-library-container').length) {
            $('body').append(dialog_tpl);

            if (typeof cpcff_forms_library_config != 'undefined' && 'version' in cpcff_forms_library_config) {
                version = cpcff_forms_library_config['version'];
            }

            if (typeof cff_forms_templates != 'undefined') {
				for(var j in cff_forms_templates ) {
					data = cff_forms_templates[j];
					for (var i in data) {

						let templates_categories = data[i]['category'].split('|');
						for ( let j in templates_categories ) {
							categories[templates_categories[j]] = '<li><a href="javascript:void(0);" onclick="cff_templatesInCategory(this,\'' + templates_categories[j] + '\')">' + templates_categories[j] + '</a></li>';
						}

						let tmp = $(form_tpl);
						if (version_n[version] < version_n[j]) {
							tmp.addClass( 'cff-form-library-form-disabled' ).append('<div class="cff-form-library-form-lock"></div>').on('click', function(){window.open('https://cff.dwbooster.com/download', '_blank');});
							tmp.find('[type="button"]')
								.prop( 'disabled', true )
								.on(
									'click',
									function(){ window.open('https://cff.dwbooster.com/download', '_blank'); }
								);
						} else {
							tmp.find('[type="button"].cff-select-form').on(
								'click',
								(function (id) {
									return function () {
										cff_getTemplate(id);
									};
								})(data[i]['id'])
							);
						}
						tmp.attr('data-category', data[i]['category']);
						if ( 'thumb' in data[i] ) {
							tmp.find('.cff-form-library-form-title').before('<div class="cff-form-library-form-thumb"><img src="https://cdn.statically.io/gh/cffdwboostercom/formtemplates/main/'+data[i]['thumb']+'"></div>');
						}
						tmp.find('.cff-form-library-form-title').text(data[i]['title']);
						tmp.find('.cff-form-library-form-description').text(data[i]['description']);
                        tmp.find('.cff-form-library-form-category').text(data[i]['category'].replace(/\|/g, ', '));

						tmp.appendTo('.cff-form-library-main');
					}
				}
			}

			for (var i in categories) {
				$(categories[i]).appendTo('.cff-form-library-categories ul');
			}

			// Website forms.
			if (typeof cpcff_forms_library_config != 'undefined' && 'website_forms' in cpcff_forms_library_config) {
				let data  = cpcff_forms_library_config['website_forms'];
				for ( let i in data ) {
					let tmp = $(form_tpl);
					tmp.find('[type="button"].cff-select-form').on(
						'click',
						(function (id) {
							return function () {
								cff_getTemplate(id, true);
							};
						})(data[i]['id'])
					);
					tmp.attr('data-category', '-1');
					tmp.find('.cff-form-library-form-title').text( '('+data[i]['id']+') ' + data[i]['form_name']);
					tmp.find('.cff-form-library-form-description').text(data[i]['description']);
					tmp.find('.cff-form-library-form-category').text(data[i]['category']);
					tmp.appendTo('.cff-form-library-main');
				}
            }
        };

		$(document).on('keyup', '[id="cp_itemname_library"]', function(evt){
			var keycode = (evt.keyCode ? evt.keyCode : evt.which);
            if(keycode == 13){
                cff_getTemplate(0);
            }
		});

        // Initialize
        showNoFormMessage();
        $('.cff-form-library-search-box input').val('');
        $('.cff-form-library-categories ul>li:first-child a').trigger('click');
        $('.cff-form-library-cover').show();

		form_name_library_field = $('[id="cp_itemname_library"]');
		form_name_library_field.val(form_name_field.val());
    };

    function closeDialog() {
		$('.cff-form-library-cover').animate({ opacity: 0 }, 'slow', function() {
			$(this).remove();
		});
    };

    function showNoFormMessage() {
        $('.cff-form-library-no-form').show();
    };

    function hideNoFormMessage() {
        $('.cff-form-library-no-form').hide();
    };

    function displayTemplates(me, category) {
        $('.cff-ai-form-generator').hide();
		$('.cff-form-library-main').show();
        hideNoFormMessage();
        $('.cff-form-library-search-box input').val('');
        $('.cff-form-library-active-category').removeClass('cff-form-library-active-category');
        $(me).addClass('cff-form-library-active-category');

        if (typeof category == 'undefined') {
            $('.cff-form-library-form').show();
            $('.cff-form-library-form[data-category="-1"]').hide();
        } else {
            $('.cff-form-library-form').hide();
            $('.cff-form-library-form[data-category*="' + category + '"]').show();
        }
    };

	function displayAIGenerator(me) {
		$('.cff-form-library-search-box input').val('');
        $('.cff-form-library-active-category').removeClass('cff-form-library-active-category');
		$(me).addClass('cff-form-library-active-category');
		$('.cff-form-library-main').hide();
		$('.cff-ai-form-generator').show();
		if ( getApiKey() == '' ) {
			$('.cff-ai-video-tutorial').css('display', 'flex');
		}
	};

    function formsByText(me) {
		$('.cff-ai-form-generator').hide();
		$('.cff-form-library-main').show();
        var v = String(me.value).trim();

        $('.cff-form-library-active-category').removeClass('cff-form-library-active-category');

        $('.cff-form-library-form').hide();

        $('.cff-form-library-form:contains("' + v + '")').each(function () {
            $(this).show();
        });

        if ($('.cff-form-library-form:visible').length) {
            hideNoFormMessage();
        } else {
            showNoFormMessage();
        }
    };

    function getTemplate(id, is_website_form) {
		is_website_form = is_website_form || false;
        var form_name = encodeURIComponent(form_name_library_field.val() || ''),
        category_name = encodeURIComponent($('[id="calculated-fields-form-category"]').val() || ''),
        url;

        if (typeof cpcff_forms_library_config != 'undefined' && 'website_url' in cpcff_forms_library_config) {
            url = cpcff_forms_library_config['website_url'] + '&name=' + form_name + '&category=' + category_name;
            if (id) url += '&ftpl=' + encodeURIComponent(id);
			if (is_website_form) url += '&from_website=1';
            document.location.href = url;
            closeDialog();
            return;
        }

        if ('cp_addItem' in window)
            cp_addItem();
    };

	$(document).on('keyup', function(evt){ if ( evt.keyCode == 27 ) { cff_closeLibraryDialog(); } });
	$(document).on('focus', '#cff-ai-api-key', function(){ this.type="text"; })
			   .on('blur' , '#cff-ai-api-key', function(){ this.type="password"; });
	$(document).on('click', '#cff-ai-save-btn', function(){
		let api_key 		  = String($('#cff-ai-api-key').val()).trim();
		if ( '' == api_key ) {
			alert( txt_api_key_requirement_error );
			return;
		}
		let me = this;
		let width = me.offsetWidth;
		me.style.width = width + 'px';
		me.textContent = txt_saving_api_key_btn;
		me.disabled = true;
		saveApiKey(api_key);
		setTimeout(function(){me.disabled = false; me.textContent = txt_save_api_key_btn;}, 2000);
	});
	$(document).on('click', '#cff-ai-clear-btn', function(){
		$('#cff-ai-api-key').val('');
		clearApiKey();
	});
	$(document).on('click', '.cff-form-library-close', closeDialog);
	$(document).on('click', '.cff-form-library-back', function(){ $('.cff-ai-form-preview-container').hide(); });
	$(document).on('click', '.cff-ai-generate', async function(){
		// Check API Key and form description.
		let api_key 		  = String($('#cff-ai-api-key').val()).trim(),
			description_field = $('#cff-ai-form-description'),
			description 	  = description_field.val().split(/[\n\r]/g).map((n)=>String(n).trim()).filter((n)=>n != '').join("\n");

		if ( '' == api_key ) {
			alert( txt_api_key_requirement_error );
			return;
		}

		if ( '' == description ) {
			alert( txt_description_requirement_error );
			return;
		}

		// Display the loading process and call the server side code.
		$('.cff-ai-form-description-container').append('<div class="cff-processing-form"></div>');
		description_field.prop('disabled', true);
		this.disabled = true;

		let formData = new FormData();
		formData.append('cff_ai_form_generator_description', description);
		formData.append('cff_ai_form_generator_api_key', api_key);
		const response = await fetch(
			cpcff_forms_library_config['ai_form_generator_url'],
			{
				'method' : 'POST',
				'body'   : formData
			}
		);

		if ( response.ok ) {
			const output = await response.text();
			try {
				let result = JSON.parse( output );
				if ( 'error' in result ) {
					throw new Error( result['error'] );
				}

				if ( 'success' in result ) {

					// Display the form preview.
					const cacheBuster = Date.now();
					$('.cff-ai-form-preview>iframe').attr('src', cpcff_forms_library_config['ai_form_generator_url'] + '&cff_ai_form_preview=1&_=' + cacheBuster);
					$('.cff-ai-form-preview-container').css('display','flex');

				}
			} catch ( err ) {
				alert( err.message );
			}
		}

		// Hide the loading animation.
		$('.cff-processing-form').remove();
		description_field.prop('disabled', false);
		this.disabled = false;
	});

	// Export
    window['cff_openLibraryDialog'] = openDialog;
    window['cff_closeLibraryDialog'] = closeDialog;
    window['cff_getTemplate'] = getTemplate;
    window['cff_templatesInCategory'] = displayTemplates;
    window['cff_filteringFormsByText'] = formsByText;
	window['cff_displayAIGenerator'] = displayAIGenerator;
});