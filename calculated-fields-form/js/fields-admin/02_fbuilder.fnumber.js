		$.fbuilder.typeList.push(
			{
				id:"fnumber",
				name:"Number",
				control_category:1
			}
		);
        $.fbuilder.controls[ 'fnumber' ] = function(){};
		$.extend(
			true,
			$.fbuilder.controls[ 'fnumber' ].prototype,
			$.fbuilder.controls[ 'ffields' ].prototype,
			{
				title:"Number",
				ftype:"fnumber",
				predefined:"",
				predefinedClick:false,
				required:false,
				exclude:false,
				readonly:false,
                numberpad:false,
				spinner:false,
				size:"small",
				prefix:"",
				postfix:"",
				thousandSeparator:"",
				decimalSymbol:".",
				min:"",
				max:"",
				step:"",
				formatDynamically:false,
				twoDecimals:false,
				dformat:"digits",
				formats:new Array("digits","number", "percent"),
				initAdv: function() {
					if ( ! ( 'spinner_left' in this.advanced.css ) ) this.advanced.css.spinner_left = {label: 'Left spinner',rules:{}};
					if ( ! ( 'spinner_right' in this.advanced.css ) ) this.advanced.css.spinner_right = {label: 'Right spinner',rules:{}};
				},
				getFormattedValue:function(value)
				{
					value = String(value).trim();
					if(value == '') return value;
					if(/^fieldname\d+$/i.test(value)) return value.toLowerCase();
					var ts = this.thousandSeparator,
						ds = ((ds=String(this.decimalSymbol).trim()) !== '') ? ds : '.',
						v = $.fbuilder.parseVal(value, ts, ds),
						s = '',
						counter = 0,
						str = '',
						parts = [],
						step  = $('[id="'+this.name+'"]').attr('step'),
						prefix  = this.dformat == 'number' ? this.prefix : '',
						postfix = this.dformat == 'number' ? this.postfix : '';

					if(!isNaN(v))
					{
						if(v < 0) s = '-';
						v = Math.abs(v);
						if(this.twoDecimals && Math.floor(v) != v) v = v.toFixed(2);
						parts = v.toString().split(".");

						for(var i = parts[0].length-1; i >= 0; i--){
							counter++;
							str = parts[0][i]+str;
							if(counter%3 == 0 && i != 0) str = ts+str;

						}
						parts[0]  = str;
						if(
							typeof parts[1] != 'undefined' &&
							parts[1]*1 &&
							typeof step != 'undefined' &&
							! isNaN(step*1)
						){
							var l = (new String(step)).split('.');
							if(l.length == 2){
								l = Math.max(l.length-(new String(parts[1])).length, 0);
								for(var i = 0; i < l; i++) parts[1] += '0';
							}
						}
						return prefix+s+parts.join(ds)+((this.dformat == 'percent') ? '%':'')+postfix;
					}
					else
					{
						return value;
					}
				},
				display:function( css_class )
					{
						css_class = css_class || '';
						let id = 'field'+this.form_identifier+'-'+this.index;
						return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="'+id+'" title="'+this.controlLabel('Number')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+'<label for="'+id+'-box">'+this.title+''+((this.required)?"*":"")+'</label><div class="dfield">'+this.showColumnIcon()+'<input id="'+id+'-box" class="field disabled '+this.size+'" type="text" value="'+cff_esc_attr(this.getFormattedValue(this.predefined))+'"/><span class="uh">'+this.userhelp+'</span></div><div class="clearer"></div></div>';
					},
				editItemEvents:function()
					{
						var f   = function(el){return el.is(':checked');},
							evt = [
							{s:"#sFormat",e:"change", l:"dformat", f:function(el){
								var v = el.val();
								$( '.fnumber-symbols' )[(v == 'digits')?'hide':'show']();
								$( '.fnumber-hint' )[(v == 'percent')?'show':'hide']();
								$( '.fnumber-prefix-postfix-symbols' )[(v == 'number') ?'show':'hide']();
								return v;
								}, x:1
							},
							{s:"#sPrefix",e:"change keyup", l:"prefix", x:1},
							{s:"#sPostfix",e:"change keyup", l:"postfix", x:1},
							{s:"#sMin",e:"change keyup", l:"min", x:1},
							{s:"#sMax",e:"change keyup", l:"max", x:1},
							{s:"#sStep",e:"change keyup", l:"step", x:1},
							{s:"#sThousandSeparator",e:"change keyup", l:"thousandSeparator", x:1},
							{s:"#sDecimalSymbol",e:"change keyup", l:"decimalSymbol", x:1},
							{s:"#sSpinner",e:"click", l:"spinner",f:f},
							{s:"#sFormatDynamically",e:"click", l:"formatDynamically",f:f},
							{s:"#sTwoDecimals",e:"click", l:"twoDecimals",f:f},
						];
						$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this, evt);
					},
				showFormatIntance: function()
					{
						var str = "", df = this.dformat;
						for (var i=0;i<this.formats.length;i++)
						{
							str += '<option value="'+this.formats[i]+'" '+((this.formats[i]==df)?"selected":"")+'>'+this.formats[i]+'</option>';
						}

						return '<div><label><input type="checkbox" name="sSpinner" id="sSpinner" '+( (this.spinner) ? 'CHECKED' : '')+'> Display spinner buttons</label></div>'+
						'<div><label for="sFormat">Number Format</label><select name="sFormat" id="sFormat">'+str+'</select></div>'+
						'<div class="fnumber-hint" '+((df != 'percent') ? 'style="display:none;"' : '')+'><i>The field value in the equations would be its decimal representation. Ex. 10% would be 0.1</i></div>'+
						'<div class="fnumber-symbols" '+((df == 'digits') ? 'style="display:none;"' : '')+'><label for="sDecimalSymbol">Decimals separator symbol (Ex: 25.20)</label><input type="text" name="sDecimalSymbol" id="sDecimalSymbol" class="large" value="'+cff_esc_attr(this.decimalSymbol)+'" /><label for="sThousandSeparator">Symbol for grouping thousands (Ex: 3,000,000)</label><input type="text" name="sThousandSeparator" id="sThousandSeparator" class="large" value="'+cff_esc_attr(this.thousandSeparator)+'" /></div>'+
						'<div class="fnumber-prefix-postfix-symbols" '+((df != 'number') ? 'style="display:none;"' : '')+'>'+
						'<label for="sPrefix">Prefix Symbol</label><input type="text" name="sPrefix" id="sPrefix" value="'+cff_esc_attr(this.prefix)+'" class="large">'+
						'<label for="sPostfix">Postfix Symbol</label><input type="text" name="sPostfix" id="sPostfix" value="'+cff_esc_attr(this.postfix)+'" class="large">'+
						'</div>'+
						'<div class="fnumber-symbols" '+((df == 'digits') ? 'style="display:none;"' : '')+'><label class="column width50"><input type="checkbox" name="sFormatDynamically" id="sFormatDynamically" '+( (this.formatDynamically) ? 'CHECKED' : '')+'> Format dynamically to</label>'+
						'<label class="column width50"><input type="checkbox" name="sTwoDecimals" id="sTwoDecimals" '+( (this.twoDecimals) ? 'CHECKED' : '')+'> two decimal places</label>'+
						'<div class="clearer"></div></div>';
					},
				showRangeIntance: function()
					{
						return '<div class="column width30"><label for="sMin">Min</label><input type="text" name="sMin" id="sMin" value="'+cff_esc_attr(this.min)+'" class="large"></div><div class="column width30"><label for="sMax">Max</label><input type="text" name="sMax" id="sMax" value="'+cff_esc_attr(this.max)+'" class="large"></div><div class="column width30"><label for="sStep">Step</label><input type="text" name="sStep" id="sStep" value="'+cff_esc_attr(this.step)+'" placeholder="1 by default" class="large"></div><div class="clearer"><i>It is possible to associate other fields in the form to the attributes "min" and "max". Ex: fieldname1</i></div>';
					}
		});