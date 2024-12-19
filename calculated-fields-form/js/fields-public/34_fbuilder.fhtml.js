	$.fbuilder.controls['fhtml']=function(){};
	$.extend(
		$.fbuilder.controls['fhtml'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			ftype:"fhtml",
			fcontent:"",
			allowscript:-1,
			show:function()
				{
					var content = this.fcontent;
					content = content
							.replace(/\(\s*document\s*\)\.one\(\s*['"]showHideDepEvent['"]/ig,
								'(window).one("showHideDepEvent"')
							.replace(/\bcurrentFormId\b/ig,
								'cp_calculatedfieldsf_pform' + this.form_identifier);

					content = ((this.allowscript == -1 || this.allowscript) ? content : cff_sanitize(content));

					return '<div class="fields '+cff_esc_attr(this.csslayout)+' '+this.name+' cff-html-field" id="field'+this.form_identifier+'-'+this.index+'" style="'+cff_esc_attr(this.getCSSComponent('container'))+'"><div id="'+this.name+'" class="dfield">'+content+'</div><div class="clearer"></div></div>';
				}
		}
	);