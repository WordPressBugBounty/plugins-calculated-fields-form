	$.fbuilder.controls['fCommentArea']=function(){};
	$.extend(
		$.fbuilder.controls['fCommentArea'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Comments here",
			ftype:"fCommentArea",
			userhelp:"A description of the section goes here.",
			show:function()
				{
                    return '<div class="fields '+cff_esc_attr(this.csslayout)+' '+this.name+' comment_area cff-instruct-text-field" id="field'+this.form_identifier+'-'+this.index+' style="'+cff_esc_attr(this.getCSSComponent('container'))+'""><label id="'+this.name+'" style="'+cff_esc_attr(this.getCSSComponent('label'))+'">'+cff_sanitize(this.title, true)+'</label><span class="uh" style="'+cff_esc_attr(this.getCSSComponent('help'))+'">'+cff_sanitize(this.userhelp, true)+'</span><div class="clearer"></div></div>';
				}
		}
	);