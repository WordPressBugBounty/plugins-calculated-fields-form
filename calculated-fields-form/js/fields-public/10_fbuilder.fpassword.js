	$.fbuilder.controls['fpassword'] = function(){};
	$.extend(
		$.fbuilder.controls['fpassword'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Untitled",
			ftype:"fpassword",
			predefined:"",
			predefinedClick:false,
			required:false,
			unmaskedonfocus:false,
			size:"medium",
			minlength:"",
			maxlength:"",
			lowercase:false,
			uppercase:false,
			digit:false,
			symbol:false,
			equalTo:"",
			regExp:"",
			regExpMssg:"",
			show:function()
				{
					let minlength = String(this.minlength).trim();
					let maxlength = String(this.maxlength).trim();

					this.minlength = (!isNaN(minlength*1) && 0 < minlength*1) ? minlength*1 : '';
					this.maxlength = (!isNaN(maxlength*1) && 0 < maxlength*1) ? maxlength*1 : '';

					this.equalTo = cff_esc_attr(String(this.equalTo).trim());
					this.predefined = this._getAttr('predefined', true);
					return '<div class="fields '+cff_esc_attr(this.csslayout)+' '+this.name+' cff-password-field" id="field'+this.form_identifier+'-'+this.index+'" style="'+cff_esc_attr(this.getCSSComponent('container'))+'"><label for="'+this.name+'" style="'+cff_esc_attr(this.getCSSComponent('label'))+'">'+cff_sanitize(this.title, true)+''+((this.required)?"<span class='r'>*</span>":"")+'</label><div class="dfield"><input aria-label="'+cff_esc_attr(this.title)+'" id="'+this.name+'" name="'+this.name+'"'+((this.minlength) ? ' minlength="'+cff_esc_attr(this.minlength)+'"' : '')+((this.maxlength) ? ' maxlength="'+cff_esc_attr(this.maxlength)+'"' : '')+((this.equalTo.length) ? ' equalTo="#'+this.equalTo+this.form_identifier+'"' : '')+' class="field '+this.size+((this.required)?" required":"")+'" type="password" autocomplete="new-password" value="'+cff_esc_attr(this.predefined)+'" style="'+cff_esc_attr(this.getCSSComponent('input'))+'" /><span class="uh" style="'+cff_esc_attr(this.getCSSComponent('help'))+'">'+cff_sanitize(this.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
			after_show:function()
				{
					if(typeof $['validator'] != 'undefined')
					{
						try {
							if (this.regExp != "") {
								var parts 	= this.regExp.match(/(\/)(.*)(\/)([gimy]{0,4})$/i);
								this.regExp = (parts === null) ? new RegExp(this.regExp) : new RegExp(parts[2],parts[4].toLowerCase());
							}

							if(!('password' in $.validator.methods))
								$.validator.addMethod('password', function(value, element, param)
									{
										let valid = true;

										if(param.regExp != '') valid = param.regExp.test(value);
										if(valid && param.lowercase) valid = /[a-z]/.test(value);
										if(valid && param.uppercase) valid = /[A-Z]/.test(value);
										if(valid && param.digit) valid = /[0-9]/.test(value);
										if(valid && param.symbol) valid = /[^a-zA-Z0-9\s]/.test(value);

										try{
											return this.optional(element) || valid;
										}
										catch(err){return true;}
									}
								);

							$('#'+this.name).rules('add', {
								'password': this,
								'messages': {
									'password': cff_sanitize(this.regExpMssg, true)
								}
							});
						} catch ( err ) {}
					}
					if(this.unmaskedonfocus)
					{
						$('#'+this.name).on('focus', function(){this.type="text";}).on('blur', function(){this.type="password";});
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