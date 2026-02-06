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
			aiAssistant:false,
			display:function( css_class )
				{
					css_class = css_class || '';
					let id = 'field'+this.form_identifier+'-'+this.index;
					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="'+id+'" title="'+this.controlLabel('Single Line Text')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+'<label for="'+id+'-box">'+cff_sanitize(this.title, true)+''+((this.required)?"*":"")+'</label><div class="dfield">'+this.showColumnIcon()+'<input id="'+id+'-box" class="field disabled '+this.size+'" type="text" value="'+cff_esc_attr(this.predefined)+'"/><span class="uh">'+cff_sanitize(this.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					let evt = [
                            {s:"#sMinlength",e:"input", l:"minlength", x:1},
                            {s:"#sMaxlength",e:"input", l:"maxlength", x:1},
                            {s:"#sRegExp",e:"input", l:"regExp"},
                            {s:"#sRegExpMssg",e:"input", l:"regExpMssg"},
                            {s:"#sEqualTo",e:"change", l:"equalTo", x:1}
                        ],
                        items           = this.fBuild.getItems(),
                        allowedTypes    = { ftext: 1, femail: 1, fpassword: 1, ftextds: 1, femailds: 1 },
                        eligibleItems   = [],
                        elName          = this.name;

                    for (let i = 0; i < items.length; i++) {
                        if (allowedTypes[items[i].ftype]) eligibleItems.push(items[i]);
                    }

					$('.equalTo').each(function() {
                        let $el     = $(this),
                            dvalue  = $el.attr("dvalue"),
						    str = '<option value=""></option>';

						for (let i=0;i<eligibleItems.length;i++)
						{
							if (eligibleItems[i].name != elName)
							{
								str += '<option value="'+cff_esc_attr(eligibleItems[i].name)+'" '+((eligibleItems[i].name == dvalue)?"selected":"")+'>'+cff_esc_attr(eligibleItems[i].title+' ('+eligibleItems[i].name+')')+'</option>';
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