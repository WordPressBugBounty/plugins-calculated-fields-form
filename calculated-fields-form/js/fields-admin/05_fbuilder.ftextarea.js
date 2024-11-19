	$.fbuilder.typeList.push(
		{
			id:"ftextarea",
			name:"Text Area",
			control_category:1
		}
	);
	$.fbuilder.controls[ 'ftextarea' ] = function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'ftextarea' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"Untitled",
			ftype:"ftextarea",
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
            rows:4,
			initAdv: function() {
				delete this.advanced.css['input'];
				if ( ! ( 'textarea' in this.advanced.css ) ) this.advanced.css.textarea = {label: 'Text area',rules:{}};
			},
			display:function( css_class )
				{
					css_class = css_class || '';
					let id = 'field'+this.form_identifier+'-'+this.index;
					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="'+id+'" title="'+this.controlLabel('Text Area')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+'<label for="'+id+'-box">'+this.title+''+((this.required)?"*":"")+'</label><div class="dfield">'+this.showColumnIcon()+'<textarea id="'+id+'-box" '+((!/^\s*$/.test(this.rows)) ? 'rows='+cff_esc_attr(this.rows) : '' )+' class="field disabled '+this.size+'">'+cff_esc_attr(this.predefined)+'</textarea><span class="uh">'+this.userhelp+'</span></div><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					var evt = [
							{s:"#sMinlength",e:"change keyup", l:"minlength", x:1},
							{s:"#sMaxlength",e:"change keyup", l:"maxlength", x:1},
							{s:"#sRows",e:"change keyup", l:"rows", x:1}
						];
					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this, evt);
				},
			showSpecialDataInstance: function()
				{
					return '<div class="column width50"><label for="sMinlength">Min length/characters</label><input type="text" name="sMinlength" id="sMinlength" value="'+cff_esc_attr(this.minlength)+'" class="large"></div><div class="column width50"><label for="sMaxlength">Max length/characters</label><input type="text" name="sMaxlength" id="sMaxlength" value="'+cff_esc_attr(this.maxlength)+'" class="large"></div><div class="clearer"></div><label for="sRows">Number of rows</label><input type="text" name="sRows" id="sRows" value="'+cff_esc_attr(this.rows)+'" />';
				}
	});