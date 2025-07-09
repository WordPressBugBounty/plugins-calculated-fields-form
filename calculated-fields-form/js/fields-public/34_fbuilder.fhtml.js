	$.fbuilder.controls['fhtml']=function(){};
	$.extend(
		$.fbuilder.controls['fhtml'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			ftype:"fhtml",
			fcontent:"",
			allowscript:-1,
			replaceShortcodes:0,
			show:function()
				{
					var me = this,
						content = me.fcontent;

					if ( me.replaceShortcodes ) {
						$( document ).on('formReady', function(evt, form_identifier){
							if ( form_identifier == 'cp_calculatedfieldsf_pform' + me.form_identifier ) {
								$( 'template[id*="cff-embedded-shortcode"]', '[id="' + me.name + '"]' ).each( function(){
									let id = this.id;
									$(this).after('<div id="'+id+'"></div>');
									$('div[id="'+id+'"]').html(this.innerHTML);
									$(this).remove();
								});
							}
						});
					}

					content = content
							.replace(/\(\s*document\s*\)\.one\(\s*['"]showHideDepEvent['"]/ig,
								'(window).one("showHideDepEvent"')
							.replace(/\bcurrentFormId\b/ig,
								'cp_calculatedfieldsf_pform' + me.form_identifier);

					content = (
						( me.allowscript == -1 || me.allowscript ) ?
						content :
						(
							me.replaceShortcodes  ?
							cff_sanitize( content.replace( /<template\s/ig, '<x-template ').replace( /<\/template\s/ig, '</x-template '), true, true ).replace( /<x\-template\s/ig, '<template ').replace( /<\/x\-template>/ig, '</template>'):
							cff_sanitize( content, true )
						)
					);

					return '<div class="fields '+cff_esc_attr(me.csslayout)+' '+me.name+' cff-html-field" id="field'+me.form_identifier+'-'+me.index+'" style="'+cff_esc_attr(me.getCSSComponent('container'))+'"><div id="'+me.name+'" class="dfield">'+content+'</div><div class="clearer"></div></div>';
				}
		}
	);