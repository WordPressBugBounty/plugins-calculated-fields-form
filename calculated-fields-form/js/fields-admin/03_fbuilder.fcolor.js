	$.fbuilder.typeList.push(
		{
			id:"fcolor",
			name:"Color",
			control_category:1
		}
	);
	$.fbuilder.controls[ 'fcolor' ]=function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'fcolor' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"Untitled",
			ftype:"fcolor",
			predefined:"",
			predefinedClick:false,
			required:false,
			exclude:false,
			readonly:false,
			size:"default",
            display:function( css_class )
				{
				css_class = css_class || '';
				let id = 'field'+this.form_identifier+'-'+this.index;
				return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="'+id+'" title="'+this.controlLabel('Color')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+'<label for="'+id+'-box">'+this.title+''+((this.required)?"*":"")+'</label><div class="dfield">'+this.showColumnIcon()+'<input id="'+id+'-box" class="field disabled '+this.size+'" type="color" '+( /^\#[0-9a-f]{6}$/i.test( this.predefined ) && ! this.predefinedClick ? 'value="'+cff_esc_attr(this.predefined)+'"' : '' )+' /><span class="uh">'+this.userhelp+'</span></div><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					var me = this, evt = [];
					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this,evt);
				},
			showSize:function()
			{
                var bk = $.fbuilder.showSettings.sizeList.slice();
                $.fbuilder.showSettings.sizeList.unshift({id:"default",name:"Default"});
				var output = $.fbuilder.showSettings.showSize(this.size);
                $.fbuilder.showSettings.sizeList = bk;
                return output;
			}
	});