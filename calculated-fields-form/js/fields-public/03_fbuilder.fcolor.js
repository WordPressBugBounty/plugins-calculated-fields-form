	$.fbuilder.controls['fcolor']=function(){};
	$.extend(
		$.fbuilder.controls['fcolor'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Untitled",
			ftype:"fcolor",
			predefined:"",
			predefinedClick:false,
			required:false,
			readonly:false,
			size:"medium",
			show:function()
				{
					this.predefined = this._getAttr('predefined', true);
					return '<div class="fields '+cff_esc_attr(this.csslayout)+' '+this.name+' cff-color-field" id="field'+this.form_identifier+'-'+this.index+'" style="'+cff_esc_attr(this.getCSSComponent('container'))+'"><label for="'+this.name+'" style="'+cff_esc_attr(this.getCSSComponent('label'))+'">'+cff_sanitize(this.title, true)+''+((this.required)?"<span class='r'>*</span>":"")+'</label><div class="dfield"><input aria-label="'+cff_esc_attr(this.title)+'" id="'+this.name+'" name="'+this.name+'"'+' class="field '+cff_esc_attr(this.size)+((this.required)?" required":"")+'" '+((this.readonly)?'readonly':'')+' type="color" '+this._getValueAttr()+' style="'+cff_esc_attr(this.getCSSComponent('input'))+'" /><span class="uh" style="'+cff_esc_attr(this.getCSSComponent('help'))+'">'+cff_sanitize(this.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
			after_show:function(){},
			val:function(raw, no_quotes, disable_ignore_check)
				{
					raw = raw || false;
                    no_quotes = no_quotes || false;
					var e = (disable_ignore_check) ? $('[id="' + this.name + '"]') : $('[id="' + this.name + '"]:not(.ignore)');
					if(e.length) return $.fbuilder.parseValStr(e.val(), raw, no_quotes);
					return 0;
				}
		}
	);