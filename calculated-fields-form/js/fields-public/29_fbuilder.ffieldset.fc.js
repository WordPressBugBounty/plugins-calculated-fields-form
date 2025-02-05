	$.fbuilder.controls['ffieldset']=function(){};
	$.extend(
		$.fbuilder.controls['ffieldset'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Untitled",
			ftype:"ffieldset",
			fields:[],
			columns:1,
			align:"top",
			collapsible:false,
			defaultCollapsed:true,
            selfClosing:false,
			rearrange: 0,
			show:function()
				{
                    return '<div class="fields '+cff_esc_attr(this.csslayout)+' '+this.name+' cff-fieldset-field cff-container-field '+((this.collapsible) ? 'cff-collapsible'+((this.selfClosing) ? ' cff-selfclosing' : '')+((this.defaultCollapsed) ?  ' cff-collapsed' : '') : '')+'" id="field'+this.form_identifier+'-'+this.index+'"><FIELDSET style="'+cff_esc_attr(this.getCSSComponent('container'))+'">'+((!/^\s*$/.test(this.title) || this.collapsible) ? '<LEGEND style="'+cff_esc_attr(this.getCSSComponent('legend'))+'">'+cff_sanitize(this.title, true)+'</LEGEND>' : '')+'<div id="'+this.name+'" class="'+( this.align == 'bottom' ? 'cff-align-container-bottom' : '' )+'"></div></FIELDSET><div class="clearer"></div></div>';
				},
			after_show: function()
				{
					var me = this;
					$.fbuilder.controls['fcontainer'].prototype.after_show.call(this);
					if(me.collapsible){
						function collapseApply(p){
							if(p.length)
                            {
                                p.toggleClass('cff-collapsed');
								p.trigger('cff-collapsible', ! p.hasClass('cff-collapsed')); // Attribute is_open
                                if(!p.hasClass('cff-collapsed'))
                                {
                                    p.siblings('.cff-selfclosing').each(function(){
										let e = $(this);
										if ( ! e.hasClass('cff-collapsed') )
											$(this).addClass('cff-collapsed').trigger('cff-collapsible', false);
									});
                                }
                            }
						}
                        $('.'+me.name+'>fieldset>legend').on('click', function(evt){
                            collapseApply($(this).closest('.cff-collapsible'));
							evt.preventDefault();
							evt.stopPropagation();
                        });
						$('.'+me.name).on('click', function(){
							var e = $(this);
                            if(e.hasClass('cff-collapsed')) collapseApply(e);
                        });
                    }
				},
			showHideDep:function(toShow, toHide, hiddenByContainer)
				{
					return $.fbuilder.controls['fcontainer'].prototype.showHideDep.call(this, toShow, toHide, hiddenByContainer);
				}
		}
	);