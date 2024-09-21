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
                    return '<div class="fields '+cff_esc_attr(this.csslayout)+' '+this.name+' comment_area cff-instruct-text-field" id="field'+this.form_identifier+'-'+this.index+' style="'+cff_esc_attr(this.getCSSComponent('container'))+'""><label id="'+this.name+'" style="'+cff_esc_attr(this.getCSSComponent('label'))+'">'+this.title+'</label><span class="uh" style="'+cff_esc_attr(this.getCSSComponent('help'))+'">'+this.userhelp+'</span><div class="clearer"></div></div>';
				}
		}
	);