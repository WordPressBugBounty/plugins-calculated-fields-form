jQuery(function(){
	var $ = jQuery;

	window['cff_load_template_thumbnail'] = function () {
		var v = $('.cff-template-selector select').val(),
			c = $('.cff-thumbnail-container');

		c.html('');

		if (
			typeof cff_template_thumbnail_list != 'undefined' &&
			v in cff_template_thumbnail_list
		) {
			c.html( '<img src="' + cff_template_thumbnail_list[v] + '" style="width: 150px !important;margin-left: auto !important;margin-right: auto !important;">' );
		}
	};

	try
	{
		elementor.channels.editor.on('cff_open_form_editor', function(){
			try
			{
				if(typeof cp_calculatedfieldsf_elementor != 'undefined')
				{
					window.open(
						cp_calculatedfieldsf_elementor.url+$('[data-setting="form"] option:selected').attr('value'),
						'_blank'
					);
				}
			}
			catch(err){}
		});
	}
	catch(err){}

	$(document).on( 'change', '.cff-template-selector select',  cff_load_template_thumbnail);

	if ( typeof elementor != 'undefined') {
		elementor.hooks.addAction( 'panel/open_editor/widget/calculated-fields-form', function( panel, model, view ){
			cff_load_template_thumbnail();
		});
	}
});