	$.fbuilder.typeList.push(
		{
			id:"fButton",
			name:"Button",
			control_category:1
		}
	);
	$.fbuilder.controls['fButton']=function(){};
	$.extend(
		true,
		$.fbuilder.controls['fButton'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			ftype:"fButton",
            sType:"button", // submit, button, reset, calculate, print
            sValue:"button",
            sOnclick:"",
            sOnmousedown:"",
			sLoading:false,
			sMultipage:false,
			userhelp:"A description of the section goes here.",
			initAdv:function(){
					delete this.advanced.css.input;
					delete this.advanced.css.label;
					if ( ! ( 'button' in this.advanced.css ) ) this.advanced.css.button = {label: 'Button',rules:{}};
					if ( ! ( 'button_hover' in this.advanced.css ) ) this.advanced.css.button_hover = {label: 'Button hover',rules:{}};
			},
			display:function( css_class )
				{
					css_class = css_class || '';
					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="field'+this.form_identifier+'-'+this.index+'" title="'+this.controlLabel('Button')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+this.showColumnIcon()+'<input type="button" class="button-secondary disabled" disabled value="'+cff_esc_attr(this.sValue)+'"><div>'+cff_sanitize(this.userhelp, true)+'</div><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					var evt=[
						{s:"#sValue",e:"change keyup", l:"sValue"},
						{s:"#sLoading",e:"click", l:"sLoading",f:function(el){return el.is(':checked');}},
						{s:"#sMultipage",e:"click", l:"sMultipage",f:function(el){return el.is(':checked');}},
						{s:"#sOnclick",e:"change keyup", l:"sOnclick"},
						{s:"#sOnmousedown",e:"change keyup", l:"sOnmousedown"},
						{
							s:"[name='sType']",e:"click",
							l:"sType",
							f:function(e)
							{
								var v = e.val(),
									l = $('#sLoading').closest('div'),
									p = $('#sMultipage').closest('div');
									l.hide();
								p.hide();
								if(v == 'calculate') l.show();
								if(v == 'print') p.show();
								return v;
							}
						}
					];
					$.fbuilder.controls['ffields'].prototype.editItemEvents.call(this,evt);
				},
            showSpecialDataInstance: function()
                {
                    return this._showTypeSettings()+this._showValueSettings()+this._showOnclickSettings();
                },
            _showTypeSettings: function()
                {
                    var l = ['submit', 'calculate', 'print', 'reset', 'button'],
                        r  = "", v;
					for(var i = 0, h = l.length; i < h; i++)
                    {
                        v = cff_esc_attr(l[i]);
                        r += '<label class="column width20"><input type="radio" name="sType" value="'+v+'" '+((this.sType == v) ? 'CHECKED' : '')+' >'+v+'</label>';
                    }
					r += '<div class="clear"></div>';
					r += '<div '+((this.sType != 'calculate') ? 'style="display:none;"' : '')+'><label><input type="checkbox" id="sLoading" '+((this.sLoading) ? 'CHECKED' : '')+' > display "calculation in progress" indicator</label></div>';
					r += '<div '+((this.sType != 'print') ? 'style="display:none;"' : '')+'><label><input type="checkbox" id="sMultipage" '+((this.sMultipage) ? 'CHECKED' : '')+' > print all pages in multipage form</label><br>'+
					'<b>(*)</b> <i>Assign the class names <b>cff-page-break-before</b> and <b>cff-page-break-after</b> to the fields where page breaks are to be included in the printed version of the form.</i><br>'+
					'<b>(*)</b> <i>Assign the <b>cff-no-print</b> class name to the fields you want to hide from the form printed version.</i>'+
					'</div>';
                    return '<label>Select button type</label>'+r+'<div class="clearer"></div>';
                },
            _showValueSettings: function()
                {
                    return '<label for="sValue">Value</label><input type="text" class="large" name="sValue" id="sValue" value="'+cff_esc_attr(this.sValue)+'" />';
                },
            _showOnclickSettings: function()
                {
                    return '<label for="sOnclick">OnClick event</label><textarea class="large" name="sOnclick" id="sOnclick">'+cff_esc_attr(this.sOnclick)+'</textarea><div class="clearer"><i>To transform the button into a submit button, enter the onclick event: <b>jQuery(this.form).submit();</b></i></div>'+
                    '<label for="sOnmousedown">OnMouseDown event</label><textarea class="large" name="sOnmousedown" id="sOnmousedown">'+cff_esc_attr(this.sOnmousedown)+'</textarea>';
                },
            showTitle: function(){ return ''; },
            showShortLabel: function(){ return ''; }
	});