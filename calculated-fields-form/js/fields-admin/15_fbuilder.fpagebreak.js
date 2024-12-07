	$.fbuilder.typeList.push(
		{
			id:"fPageBreak",
			name:"Page Break",
			control_category:1
		}
	);
	$.fbuilder.controls[ 'fPageBreak' ] = function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'fPageBreak' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"Page Break",
			ftype:"fPageBreak",
			initAdv:function(){ delete this.advanced; },
			display:function( css_class )
				{
					css_class = css_class || '';
					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="field'+this.form_identifier+'-'+this.index+'" title="'+this.controlLabel('Page Break')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+'<div class="section_break"></div><label>'+cff_sanitize(this.title, true)+'</label><span class="uh">'+cff_sanitize(this.userhelp, true)+'</span><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this);
				},
			showTitle: function(){
                if(!$('[name="cff-progress-bar"]').length)
                    return '<br /><a href="https://cff-bundles.dwbooster.com/product/progress-bar" target="_blank">Include a progress bar on the form with links to the form pages</a>';
                return '';
            },
			showName: function(){ return ''; },
			showShortLabel: function(){ return ''; },
			showUserhelp: function(){ return ''; },
			showCsslayout: function(){ return ''; }
	});