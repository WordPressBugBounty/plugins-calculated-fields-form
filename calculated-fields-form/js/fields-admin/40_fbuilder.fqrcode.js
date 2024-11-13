	$.fbuilder.typeList.push(
		{
			id:"fqrcode",
			name:"QRCode",
			control_category:1
		}
	);
	$.fbuilder.controls[ 'fqrcode' ]=function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'fqrcode' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"",
			ftype:"fqrcode",
			predefined:"",
			required:false,
			exclude:false,
			readonly:false,
			size:"medium",
			display:function( css_class )
				{
					css_class = css_class || '';
					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="field'+this.form_identifier+'-'+this.index+'" title="'+this.controlLabel('QRCode')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+'<label>'+this.title+''+((this.required)?"*":"")+'</label><div class="dfield">'+this.showColumnIcon()+'<span class="uh">'+this.userhelp+'</span></div><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					var me = this, evt = [];
					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this,evt);
				},
			showSpecialDataInstance: function()
				{
					return '';
				}
	});