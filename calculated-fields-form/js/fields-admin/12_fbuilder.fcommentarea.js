	$.fbuilder.typeList.push(
		{
			id:"fCommentArea",
			name:"Instruct. Text",
			control_category:1
		}
	);
	$.fbuilder.controls[ 'fCommentArea' ]=function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'fCommentArea' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"Comments here",
			ftype:"fCommentArea",
			userhelp:"A description of the section goes here.",
			initAdv: function(){
				delete this.advanced.css.input;
			},
			display:function( css_class )
				{
					css_class = css_class || '';
					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="field'+this.form_identifier+'-'+this.index+'" title="'+this.controlLabel('Instruct. Text')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+this.showColumnIcon()+'<label>'+cff_sanitize(this.title, true)+'</label><span class="uh">'+cff_sanitize(this.userhelp, true)+'</span><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this);
				}
	});