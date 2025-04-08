	$.fbuilder.controls['fsummary'] = function(){};
	$.extend(
		$.fbuilder.controls['fsummary'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Summary",
			ftype:"fsummary",
			fields:"",
			exclude_empty: false,
			titleClassname:"summary-field-title",
			valueClassname:"summary-field-value",
			fieldsArray:[],
			show:function()
				{
					var me = this;
					if('string' != typeof me.fields) return;
                    var p = String(me.fields.replace(/\,+/g, ',')).trim().split(','),
					    l = p.length;
					if(l)
					{
						var str = '<div class="fields '+cff_esc_attr(me.csslayout)+' '+me.name+' cff-summary-field" id="field'+me.form_identifier+'-'+me.index+'" style="'+cff_esc_attr(me.getCSSComponent('container'))+'">'+((!/^\s*$/.test(me.title)) ? '<h2 style="'+cff_esc_attr(me.getCSSComponent('label'))+'">'+cff_sanitize(me.title, true)+'</h2>': '')+'<div id="'+me.name+'"></div></div>';

						return str;
					}
				},
			after_show: function(){
                    var me = this;
					if('string' != typeof me.fields) return;
                    var p = String(me.fields.replace(/\,+/g, ',')).trim().split(','),
                        l = p.length,
						str = '';

                    if(l)
                    {
                        for(var i = 0; i < l; i++)
                        {
                            if(!/^\s*$/.test(p[i]))
                            {
								p[i] = String(p[i]).trim()+me.form_identifier;
								try {
									if ( $( '.'+p[i] ).length ) {
										str += '<div ref="'+cff_esc_attr(p[i])+'" class="cff-summary-item" style="'+cff_esc_attr(me.getCSSComponent('fields_rows'))+'"><span class="'+cff_esc_attr(me.titleClassname)+' cff-summary-title" style="'+cff_esc_attr(me.getCSSComponent('fields_labels'))+'"></span><span class="'+cff_esc_attr(me.valueClassname)+' cff-summary-value" style="'+cff_esc_attr(me.getCSSComponent('fields_values'))+'"></span></div>';

										me.fieldsArray.push(p[i]);
										$(document).on('change', '.'+p[i]+' [id*="'+p[i]+'"]', function(){ me.update(); });
									}
								} catch( err ) {}

                            }
                        }
                        $(document).on('showHideDepEvent', function(evt, form_identifier)
                        {
						    me.update();
                        });

                        $('#cp_calculatedfieldsf_pform'+me.form_identifier).on('reset', function(){ setTimeout(function(){ me.update(); }, 10); });
                    }
					$('[id="'+me.name+'"]').html(str);
                },
			update:function()
				{
					let me = this;
					for (let j in me.fieldsArray )
					{
						try {
							var i  = me.fieldsArray[j],
								e  = $('[id="'+i+'"],[id^="'+i+'_rb"],[id^="'+i+'_cb"]:not([type="number"])'),
								tt = $('[ref="'+i+'"]');

							if(e.length && tt.length)
							{
								var l  = $('[id="'+i+'"],[id^="'+i+'_rb"],[id^="'+i+'_cb"]')
										.closest('.fields')
										.find('label:first')
										.clone()
										.find('.r,.dformat')
										.remove()
										.end(),
									t  = String(l.text()).trim()
										.replace(/\:$/,''),
									v  = [];

								e.each(
									function(){
										var e = $(this);
										if(/(checkbox|radio)/i.test(e.attr('type')) && !e.is(':checked'))
										{
											return;
										}
										else if(e[0].tagName == 'SELECT')
										{
											var vt = [];
											e.find('option:selected').each(function(){vt.push($(this).attr('vt'));});
											v.push(vt.join(', '));
										}
										else
										{
											if(e.attr('vt'))
											{
												let q = $('[id="'+e.attr('id')+'_quantity"]');
												v.push(e.attr('vt')+(q.length ? ' ('+Math.max(q.val(),1)+')' : ''));
											}
											else if( e.attr( 'summary' ) )
											{
												v.push( $( '#' + i ).closest( '.fields' ).find( '.'+e.attr( 'summary' )+i ).html() );
											}
											else
											{
												var d = $('[id="'+i+'_date"]');
												if(d.length)
												{
													if(d.is(':disabled'))
													{
														v.push(e.val().replace(d.val(),''));
													}
													else v.push(e.val());
												}
												else
												{
													if(e.attr('type') == 'file')
													{
														var f = [];
														$.each(e[0].files, function(i,o){f.push(o.name);});
														v.push(f.join(', '));
													}
													else if( ! e.hasClass( 'cpefb_error message' ) )
													{
														var c = $('[id="'+i+'_caption"]');
														if(c.length && !/^\s*$/.test(c.html())) {
															v.push(c.html());
														} else if(e.closest('.cff-phone-field').length) {
															v.push(
																$('[id^="'+e.attr('id')+'_"]')
																 .map(function(){return String($(this).val()).trim();})
																 .get()
																 .filter(function(value){return value.length>0;}).join('-')
															);
														} else {
															v.push(e.val());
														}
													}
												}
											}
										}
									}
								);
								v = v.join(', ');
								tt.find('.cff-summary-title')[(/^\s*$/.test(t)) ? 'hide' : 'show']().html(cff_sanitize(t, true));

								tt.find('.cff-summary-value').html(cff_sanitize(v, true));

								if(e.hasClass('ignore') || (this.exclude_empty && v == ''))
								{
									tt.hide();
								}
								else
								{
									tt.show();
								}
							}
						} catch(err) {}
					}
					$('[id="' + this.name + '"]').trigger( 'cff-summary-update' );
				}
	});
