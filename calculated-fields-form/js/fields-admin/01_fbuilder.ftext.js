	$.fbuilder.typeList.push(
		{
			id:"ftext",
			name:"Single Line Text",
			control_category:1
		}
	);
	$.fbuilder.controls[ 'ftext' ]=function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'ftext' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"Untitled",
			ftype:"ftext",
			autocomplete:"off",
			predefined:"",
			predefinedClick:false,
			required:false,
			exclude:false,
			accept_html:false,
			readonly:false,
			size:"medium",
			minlength:"",
			maxlength:"",
			equalTo:"",
			regExp:"",
			regExpMssg:"",
			display:function( css_class )
				{
					css_class = css_class || '';
					let id = 'field'+this.form_identifier+'-'+this.index;
					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="'+id+'" title="'+this.controlLabel('Single Line Text')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+'<label for="'+id+'-box">'+cff_sanitize(this.title, true)+''+((this.required)?"*":"")+'</label><div class="dfield">'+this.showColumnIcon()+'<input id="'+id+'-box" class="field disabled '+this.size+'" type="text" value="'+cff_esc_attr(this.predefined)+'"/><span class="uh">'+cff_sanitize(this.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					var me = this, evt = [
						{s:"#sMinlength",e:"change keyup", l:"minlength", x:1},
						{s:"#sMaxlength",e:"change keyup", l:"maxlength", x:1},
						{s:"#sRegExp",e:"change keyup", l:"regExp"},
						{s:"#sRegExpMssg",e:"change keyup", l:"regExpMssg"},
						{s:"#sEqualTo",e:"change", l:"equalTo", x:1}
					],
					items = this.fBuild.getItems();
					$('.equalTo').each(function(){
						var str = '<option value="" '+(("" == $(this).attr("dvalue"))?"selected":"")+'></option>';
						for (var i=0;i<items.length;i++)
						{
							if (
								$.inArray(items[i].ftype, ['ftext', 'femail', 'fpassword', 'ftextds', 'femailds']) != -1 &&
								items[i].name != $(this).attr("dname")
							)
							{
								str += '<option value="'+cff_esc_attr(items[i].name)+'" '+((items[i].name == $(this).attr("dvalue"))?"selected":"")+'>'+cff_esc_attr(items[i].title)+'</option>';
							}
						}
						$(this).html(str);
					});
					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this,evt);
				},
			showSpecialDataInstance: function()
				{
					return '<div class="with100" style="margin-top:10px;">Apply <a href="https://wordpress.org/plugins/autocomplete-for-calculated-fields-form/" target="_blank">Smart Auto Complete</a> to the entry box.</div>'+
					'<div class="column width50"><label for="sMinlength">Min length/characters</label><input type="text" name="sMinlength" id="sMinlength" value="'+cff_esc_attr(this.minlength)+'" class="large"></div><div class="column width50"><label for="sMaxlength">Max length/characters</label><input type="text" name="sMaxlength" id="sMaxlength" value="'+cff_esc_attr(this.maxlength)+'" class="large"></div><div class="clearer"></div><label for="sRegExp">Validate against a regular expression</label><div style="display:flex;"><input type="text" name="sRegExp" id="sRegExp" value="'+cff_esc_attr(this.regExp)+'" class="large" /><input type="button" onclick="window.open(\'https://cff-bundles.dwbooster.com/product/regexp\');" value="+" title="Resources" class="button-secondary" /></div><label for="sRegExpMssg">Error message when the regular expression fails</label><input type="text" name="sRegExpMssg" id="sRegExpMssg" value="'+cff_esc_attr(this.regExpMssg)+'" class="large" />';
				}
	});