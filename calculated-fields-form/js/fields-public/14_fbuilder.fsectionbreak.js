	$.fbuilder.controls['fSectionBreak'] = function(){};
	$.extend(
		$.fbuilder.controls['fSectionBreak'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Section Break",
			ftype:"fSectionBreak",
			userhelp:"A description of the section goes here.",
			show:function()
				{
					let ii = cff_esc_attr(this.form_identifier)+'-'+cff_esc_attr(this.index),
						id = 'cff-section-title-'+ii;
                    return '<div class="fields '+cff_esc_attr(this.csslayout)+' '+this.name+' section_breaks cff-sectionbreak-field" id="field'+ii+'" role="group" aria-labelledby="'+id+'"  style="'+cff_esc_attr(this.getCSSComponent('container'))+'"><div class="section_break" id="'+this.name+'" ></div><label id="'+id+'" style="'+cff_esc_attr(this.getCSSComponent('label'))+'">'+cff_sanitize(this.title, true)+'</label><span class="uh" style="'+cff_esc_attr(this.getCSSComponent('help'))+'">'+cff_sanitize(this.userhelp, true)+'</span><div class="clearer"></div></div>';
				}
		}
	);