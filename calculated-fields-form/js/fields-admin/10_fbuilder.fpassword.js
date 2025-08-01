	$.fbuilder.typeList.push(
		{
			id:"fpassword",
			name:"Password",
			control_category:1
		}
	);
	$.fbuilder.controls[ 'fpassword' ] = function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'fpassword' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"Untitled",
			ftype:"fpassword",
			predefined:"",
			predefinedClick:false,
			required:false,
			exclude:false,
			store:'no', // no, hash, plain
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
			display:function( css_class )
				{
					css_class = css_class || '';
					let id = 'field'+this.form_identifier+'-'+this.index;
					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="'+id+'" title="'+this.controlLabel('Password')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+'<label for="'+id+'-box">'+cff_sanitize(this.title, true)+''+((this.required)?"*":"")+'</label><div class="dfield">'+this.showColumnIcon()+'<input id="'+id+'-box" class="field disabled '+this.size+'" type="password" value="'+cff_esc_attr(this.predefined)+'"/><span class="uh">'+cff_sanitize(this.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					var evt = [
							{s:"#sUnmaskedOnFocus", e:"click", l:"unmaskedonfocus", f:function(el){return el.is(':checked');}},
							{s:"#sMinlength",e:"change keyup", l:"minlength", x:1},
							{s:"#sMaxlength",e:"change keyup", l:"maxlength", x:1},
							{s:"#sLowercase", e:"click", l:"lowercase", f:function(el){return el.is(':checked');}},
							{s:"#sUppercase", e:"click", l:"uppercase", f:function(el){return el.is(':checked');}},
							{s:"#sDigit", e:"click", l:"digit", f:function(el){return el.is(':checked');}},
							{s:"#sSymbol", e:"click", l:"symbol", f:function(el){return el.is(':checked');}},
							{s:"[name='sStore']", e:"click", l:"store", f:function(){return $('[name="sStore"]:checked').val();}},
							{s:"#sRegExp",e:"change keyup", l:"regExp"},
							{s:"#sRegExpMssg",e:"change keyup", l:"regExpMssg"},
							{s:"#sEqualTo",e:"change", l:"equalTo", x:1}
						],
						items = this.fBuild.getItems();
					$('.equalTo').each(function()
						{
							var str = '<option value="" '+(("" == $(this).attr("dvalue"))?"selected":"")+'></option>';
							for (var i=0;i<items.length;i++)
							{
								if (
									$.inArray(items[i].ftype, ['ftext', 'femail', 'fpassword', 'ftextds', 'femailds']) != -1 &&
									items[i].name != $(this).attr("dname")
								)
								{
									str += '<option value="'+items[i].name+'" '+((items[i].name == $(this).attr("dvalue"))?"selected":"")+'>'+cff_esc_attr(items[i].title)+'</option>';
								}
							}
							$(this).html(str);
						});
					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this,evt);
				},
			showSpecialDataInstance: function()
				{
					return '<label><input type="checkbox" name="sUnmaskedOnFocus" id="sUnmaskedOnFocus" '+((this.unmaskedonfocus)?"checked":"")+'>Unmasked on focus</label>'+
					'<hr>'+
					'<label><input type="radio" name="sStore" value="no" '+(this.store == 'no' ? 'checked' : '')+'> Do not store password in database (<i><b>Recommended</b> - uses password for registration and processes without saving it</i>)</label>'+
					'<label><input type="radio" name="sStore" value="hash" '+(this.store == 'hash' ? 'checked' : '')+'> Store password hash (<i>Stores only the hashed password-never plaintext</i>)</label>'+
					'<label><input type="radio" name="sStore" value="plain" '+(this.store == 'plain' ? 'checked' : '')+'> Store password in plain text (<i><b>Not recommended</b> - stores a sanitized copy of the password in plain text</i>)</label>'+
					'<hr>'+
					'<div class="column width50"><label for="sMinlength">Min length/characters</label><input type="text" name="sMinlength" id="sMinlength" value="'+cff_esc_attr(this.minlength)+'" class="large"></div><div class="column width50"><label for="sMaxlength">Max length/characters</label><input type="text" name="sMaxlength" id="sMaxlength" value="'+cff_esc_attr(this.maxlength)+'" class="large"></div><div class="clearer"></div>'+
					'<div style="margin-top:20px;font-size:1.4em">Password Rules</div>'+
					'<label for="sRegExp">Validate against a regular expression</label><div style="display:flex;"><input type="text" name="sRegExp" id="sRegExp" value="'+cff_esc_attr(this.regExp)+'" class="large" /><input type="button" onclick="window.open(\'https://cff-bundles.dwbooster.com/product/regexp\');" value="+" title="Resources" class="button-secondary" /></div>'+
					'<label><input type="checkbox" name="sLowercase" id="sLowercase" '+(this.lowercase ? 'checked': '')+'> Include at least one lowercase character (a-z)</label>'+
					'<label><input type="checkbox" name="sUppercase" id="sUppercase" '+(this.uppercase ? 'checked': '')+'> Include at least one uppercase character (A-Z)</label>'+
					'<label><input type="checkbox" name="sDigit" id="sDigit" '+(this.digit ? 'checked': '')+'> Include at least one digit (0-9)</label>'+
					'<label><input type="checkbox" name="sSymbol" id="sSymbol" '+(this.symbol ? 'checked': '')+'> Include at least one symbol (e.g., !@#$%)</label>'+
					'<label for="sRegExpMssg">Error message when the previous rules fail</label><input type="text" name="sRegExpMssg" id="sRegExpMssg" value="'+cff_esc_attr(this.regExpMssg)+'" class="large" />';
				}
	});