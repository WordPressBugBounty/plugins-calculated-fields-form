	$.fbuilder.typeList.push(
		{
			id:"fSectionBreak",
			name:"Section Break",
			control_category:1
		}
	);
	$.fbuilder.controls[ 'fSectionBreak' ] = function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'fSectionBreak' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"Section Break",
			ftype:"fSectionBreak",
			userhelp:"A description of the section goes here.",
			initAdv:function(){
				delete this.advanced.css.input;
			},
			display:function( css_class )
				{
					css_class = css_class || '';
					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="field'+this.form_identifier+'-'+this.index+'" title="'+this.controlLabel('Section Break')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+this.showColumnIcon()+'<div class="section_break"></div><label>'+cff_sanitize(this.title, true)+'</label><span class="uh">'+cff_sanitize(this.userhelp, true)+'</span><div class="clearer"></div></div>';
				},
			showTitle: function()
				{
					return '<label for="sTitle">Field Label</label><textarea class="large" name="sTitle" id="sTitle">'+cff_esc_attr(this.title)+'</textarea>';
				},
			editItemEvents:function()
				{
					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this);
				}
	});