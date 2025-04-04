	$.fbuilder.controls['femail'] = function(){};
	$.extend(
		$.fbuilder.controls['femail'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Email",
			ftype:"femail",
            autocomplete:"off",
			predefined:"",
			predefinedClick:false,
			required:false,
			readonly:false,
			size:"medium",
			equalTo:"",
			regExp:"",
			regExpMssg:"",
			show:function()
				{
					this.predefined = this._getAttr('predefined');
					return '<div class="fields '+cff_esc_attr(this.csslayout)+' '+this.name+' cff-email-field" id="field'+this.form_identifier+'-'+this.index+'" style="'+cff_esc_attr(this.getCSSComponent('container'))+'"><label for="'+this.name+'" style="'+cff_esc_attr(this.getCSSComponent('label'))+'">'+cff_sanitize(this.title, true)+''+((this.required)?"<span class='r'>*</span>":"")+'</label><div class="dfield"><input aria-label="'+cff_esc_attr(this.title)+'" id="'+this.name+'" name="'+this.name+'" '+((this.equalTo!="")?"equalTo=\"#"+cff_esc_attr(this.equalTo+this.form_identifier)+"\"":"")+' class="field email '+cff_esc_attr(this.size)+((this.required)?" required":"")+'" type="email" value="'+cff_esc_attr(this.predefined)+'" '+((this.readonly)?'readonly':'')+' autocomplete="'+this.autocomplete+'" style="'+cff_esc_attr(this.getCSSComponent('input'))+'" /><span class="uh" style="'+cff_esc_attr(this.getCSSComponent('help'))+'">'+cff_sanitize(this.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
			after_show:function()
				{
					if(this.regExp != "" && typeof $['validator'] != 'undefined')
					{
						try {
							var parts 	= this.regExp.match(/(\/)(.*)(\/)([gimy]{0,4})$/i);
							this.regExp = (parts === null) ? new RegExp(this.regExp) : new RegExp(parts[2],parts[4].toLowerCase());

							if(!('pattern' in $.validator.methods))
								$.validator.addMethod('pattern', function(value, element, param)
									{
										try{
											return this.optional(element) || param.test(value);
										}
										catch(err){return true;}
									}
								);
							$('#'+this.name).rules('add',{'pattern':this.regExp, messages:{'pattern':cff_sanitize(this.regExpMssg, true)}});
						} catch ( err ) {}
					}
				},
			val:function(raw, no_quotes)
				{
					raw = raw || false;
                    no_quotes = no_quotes || false;
					var e = $('[id="'+this.name+'"]:not(.ignore)');
					if(e.length) return $.fbuilder.parseValStr(e.val(), raw, no_quotes);
					return 0;
				}
		}
	);