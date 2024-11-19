	$.fbuilder.typeList.push(
		{
			id:"fhidden",
			name:"Hidden",
			control_category:1
		}
	);
	$.fbuilder.controls[ 'fhidden' ]=function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'fhidden' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"Hidden",
			ftype:"fhidden",
			exclude:false,
			accept_html:false,
			predefined:"",
			initAdv: function(){ delete this.advanced; },
			display:function( css_class )
				{
					css_class = css_class || '';
					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="field'+this.form_identifier+'-'+this.index+'" title="'+this.controlLabel('Hidden')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+this.showColumnIcon()+'<label>'+this.title+'</label><span class="uh">'+this.predefined+'</span><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this);
				},
			showTitle: function(f)
				{
					return '<div><label for="sTitle">Field Label</label><textarea class="large" name="sTitle" id="sTitle">'+cff_esc_attr(this.title)+'</textarea><span>The field label is only for the admin section, never for the public page</span></div>';
				},
			showUserhelp:function(){ return ''; },
			showPredefined: function()
				{
					return '<div><label for="sPredefined">Value</label><textarea class="large" name="sPredefined" id="sPredefined">'+cff_esc_attr(this.predefined)+'</textarea></div>';
				}
	});