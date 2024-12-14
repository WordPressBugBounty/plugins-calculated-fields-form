	$.fbuilder.typeList.push(
		{
			id:"facceptance",
			name:"Acceptance (GDPR)",
			control_category:1
		}
	);
	$.fbuilder.controls[ 'facceptance' ] = function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'facceptance' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"Accept terms and conditions",
			ftype:"facceptance",
			value:"I accept",
			url:"",
			message:"",
			required:true,
			exclude:false,
            onoff:0,
			initAdv:function(){
					delete this.advanced.css.label;
					delete this.advanced.css.input;
					delete this.advanced.css.help;
					if ( ! ( 'choice' in this.advanced.css ) ) this.advanced.css.choice = {label: 'Choice text',rules:{}};
					if ( ! ( 'text' in this.advanced.css ) ) this.advanced.css.text = {label: 'Acknowledgement text',rules:{}};
				},
			display:function( css_class )
				{
					css_class = css_class || '';
					var	str = '<div class="one_column"><label><input class="field disabled" disabled="true" type="checkbox"/> '+cff_sanitize(this.title, true)+((this.required)?"*":"")+'</label></div>';
					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="field'+this.form_identifier+'-'+this.index+'" title="'+this.controlLabel('Acceptance (GDPR)')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+'<div class="dfield">'+this.showColumnIcon()+str+'<span class="uh">'+cff_sanitize(this.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					var me 		= this;
						evt 	= [
							{s:"#sValue",e:"change keyup", l:"value"},
							{s:"#sURL",e:"change keyup", l:"url"},
                            {s:'[name="sOnOff"]', e:"change", l:"onoff", f: function(el){return (el.is(':checked')) ? 1 : 0;}},
							{s:"#sMessage",e:"change keyup", l:"message"}
						];
					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this, evt);
				},
			showTitle: function()
				{
					return '<label for="sTitle">Field Label</label><textarea class="large" name="sTitle" id="sTitle">'+cff_esc_attr(this.title)+'</textarea>';
				},
			showRequired: function(v)
				{
					return '<label><input type="checkbox" checked disabled>Acceptance fields are always required</label>'+
                    '<div class="choicesSet"><label><input type="checkbox" name="sOnOff" '+((this.onoff) ? ' CHECKED ' : '')+'/> Display as on/off switch.</label></div>';
				},
			showUserhelp: function(){ return ''; },
			showValue:function()
				{
					return '<label for="sValue">Value</label><input class="large" type="text" name="sValue" id="sValue" value="'+cff_esc_attr(this.value)+'">';
				},
			showURL:function()
				{
					return '<label for="sURL">URL to the Consent and Acknowledgement page</label><input class="large" type="text" name="sURL" id="sURL" value="'+cff_esc_attr(this.url)+'">';
				},
			showMessage:function()
				{
					return '<label for="sMessage">- or - enter the Consent and Acknowledgement text</label><textarea class="large" name="sMessage" id="sMessage" style="height:150px;">'+cff_esc_attr(this.message)+'</textarea>';
				},
			showCsslayout:function()
				{
					return $.fbuilder.controls[ 'ffields' ].prototype.showCsslayout.call(this)+'<div style="color: #666;border: 1px solid #EF7E59;display: block;padding: 5px;background: #FBF0EC;border-radius: 4px;text-align: center;margin-top:20px;">The Acceptance control helps to make the form comply with one of requirements of the General Data Protection Regulation (GDPR)</div>';
				},
			showSpecialDataInstance: function()
				{
					return this.showValue()+this.showURL()+this.showMessage();
				}
	});