		$.fbuilder.controls['fslider'] = function(){};
		$.extend(
			$.fbuilder.controls['fslider'].prototype,
			$.fbuilder.controls['ffields'].prototype,
			{
				title:"Slider",
				ftype:"fslider",
				exclude:false,
				readonly:false,
				predefined:"",
				predefinedMin:"",
				predefinedMax:"",
				predefinedClick:false,
				size:"small",
				thousandSeparator:",",
				centSeparator:".",
				typeValues:false,
				min:0,
				max:100,
				step:1,
				marks:false,
				divisions:5,
				range:false,
				logarithmic:false,
				minCaption:"",
				maxCaption:"",
				caption:"{0}",
				_expon : function(v)
					{
						if(v == 0) return v;
						var el   = $('#'+this.name+'_slider'),
							step = this.calc_step, // el.slider('option', 'step'),
							min  = Math.max(el.slider('option', 'min'), step),
							max  = FLOOR(el.slider('option', 'max')/step)*step,

							minv = Math.log(Math.max(min, 0.01)),
							maxv = Math.log(max),
							scale = (maxv - minv) / (max - min);

						return ROUND(Math.exp( minv + scale * ( v - min ) ), step);
					},
				_inverse : function(v)
					{
						var el   = $('#'+this.name+'_slider'),
							step = this.calc_step, // el.slider('option', 'step'),
							min  = Math.max(el.slider('option', 'min'), step),
							max  = FLOOR(el.slider('option', 'max')/step)*step,

							minv = Math.log(Math.max(min, 0.1)),
							maxv = Math.log(max),
							scale = (maxv - minv) / (max - min);

						return ROUND( ( ( Math.log( v ) - minv ) / scale + min ), step );
					},
				_setThousandsSeparator : function(v)
					{
						let l = (new String(this.step)).split('.');
						l = l.length == 2 ? l[1].length : 0;
						v = $.fbuilder.parseVal(v, this.thousandSeparator, this.centSeparator);
						if(!isNaN(v))
						{
							v = PREC(v, l);
							var parts = v.toString().split("."),
								counter = 0,
								str = '';

							for(var i = parts[0].length-1; i >= 0; i--)
							{
								counter++;
								str = parts[0][i] + str;
								if(counter%3 == 0 && i != 0) str = this.thousandSeparator + str;

							}
							parts[0] = str;
							return parts.join(this.centSeparator);
						}
						else
						{
							return v;
						}
					},
				_setFieldValue:function(val, nochange)
					{
						var me = this;
						if(me.range)
						{
							var values = ( typeof val != 'undefined' && val !== null ) ? val : $('#'+me.name+'_slider').slider('values'),
								vl  = values[0], vr = values[1],
								vlf = me._setThousandsSeparator(vl),
								vrf = me._setThousandsSeparator(vr);

							$('.'+me.name).find('.cff-slider-tooltip-value').first().html(cff_sanitize(vlf, true));
							$('.'+me.name).find('.cff-slider-tooltip-value').last().html(cff_sanitize(vrf, true));
							$('#'+me.name+'_component_left').val(cff_esc_attr(vlf));
							$('#'+me.name+'_component_right').val(cff_esc_attr(vrf));
							$('#'+me.name).val('['+vl+','+vr+']').attr('vt', '['+cff_esc_attr(vlf)+','+cff_esc_attr(vrf)+']');
							$('#'+me.name+'_caption').html(
								cff_sanitize(
									me.caption
									.replace(/\{\s*0\s*\}/, vlf)
									.replace(/\{\s*0\s*\}/, vrf)
									, true
								 )
							);
						}
						else
						{
							var v  = ( typeof val != 'undefined' && val !== null ) ? val : $('#'+me.name+'_slider').slider('value'),
								vf = me._setThousandsSeparator(v);

							$('.'+me.name).find('.cff-slider-tooltip-value').first().html(cff_sanitize(vf, true));
							$('#'+me.name).val(v).attr('vt', cff_esc_attr(vf));
							$('#'+me.name+'_component_center').val(cff_esc_attr(vf));
							$('#'+me.name+'_caption').html(
								cff_sanitize(
									me.caption.replace(/\{\s*0\s*\}/g, vf)
									, true
								)
							);
						}
						if(!nochange) $('#'+me.name).trigger('change');
					},
				_toNumber:function(n){
						n = $.fbuilder.parseVal(n, this.thousandSeparator, this.centSeparator);
						return (new String(n)).replace(/[^\-\d\.]/g,'')*1;
					},
				init:function()
					{
						this.min  = (/^\s*$/.test(this.min)) ? 0   : String(this.min).trim();
						this.max  = (/^\s*$/.test(this.max)) ? 100 : String(this.max).trim();
						this.step = (/^\s*$/.test(this.step)) ? 1   : String(this.step).trim();
						this.predefinedMin = (/^\s*$/.test(this.predefinedMin))? this.min : String(this.predefinedMin).trim();
						this.predefinedMax = (/^\s*$/.test(this.predefinedMax))? this.max : String(this.predefinedMax).trim();
						this._setHndl('min');
						this._setHndl('max');
						this._setHndl('step');
						this._setHndl('predefinedMin');
						this._setHndl('predefinedMax');
						this.centSeparator = (/^\s*$/.test(this.centSeparator)) ? '.' : String(this.centSeparator).trim();
						if(this.logarithmic && !isNaN(this.step)) { // TO CHECK
							this.calc_step = this.step;
							this.step = Math.min(this.step, 1);
						}

						this.getCSSComponent('slider', true, '#fbuilder .'+this.name+' .ui-slider', this.form_identifier);
						this.getCSSComponent('slider_handle', true, '#fbuilder .'+this.name+' .ui-slider-handle', this.form_identifier);
						this.getCSSComponent('slider_range', true, '#fbuilder .'+this.name+' .ui-slider-range', this.form_identifier);
					},
				show:function()
					{
						var me = this;
						function typeValuesComponents()
						{
							function component(c)
							{
								var min = cff_esc_attr(me.min),
									max = cff_esc_attr(me.max),
									step = cff_esc_attr(me.step),
									predefined = cff_esc_attr(
										(c == 'left') ? me.predefinedMin :
										((c == 'right') ? me.predefinedMax : me.predefined)
									),
									timeoutId;

								$(document).on('keyup change', '#'+me.name+'_component_'+c, function(evt){
                                    function stepRound(v)
                                    {
                                        var _e    = $('#'+me.name+'_slider'),
                                            _max  = _e.slider('option', 'max'),
                                            _step = _e.slider('option', 'step');
                                        return MIN(CEIL(v, _step), _max);
                                    };
									var v = $('#'+me.name+'_component_center').val(),
										v1 = $('#'+me.name+'_component_left').val(),
										v2 = $('#'+me.name+'_component_right').val(),
										t = 0;
									clearTimeout(timeoutId);
									if(evt.type == 'keyup') t = 2500;
									timeoutId = setTimeout(function(){
										if(v != undefined)
										{
											v = $.fbuilder.parseVal(v, me.thousandSeparator, me.centSeparator);
											if(isNaN(v)) v = 0;
										}
										if(v1 != undefined)
										{
											v1 = $.fbuilder.parseVal(v1, me.thousandSeparator, me.centSeparator);
											if(isNaN(v1)) v1 = 0;
										}
										if(v2 != undefined)
										{
											v2 = $.fbuilder.parseVal(v2, me.thousandSeparator, me.centSeparator);
											if(isNaN(v2)) v2 = 0;
										}
										$('#'+me.name+'_slider').slider(
											((v != undefined) ? 'value' : 'values'),
											((v != undefined) ? (me.logarithmic ? me._inverse(v*1) : stepRound(v*1)) : [stepRound(Math.min(v1*1,v2*1)), stepRound(Math.max(v1*1,v2*1))])
										);
										me._setFieldValue(me.logarithmic ? v : null);
									}, t);
								});
								return '<div class="slider-type-'+c+'-component"><input aria-label="'+cff_esc_attr(me.title)+'" id="'+me.name+'_component_'+c+'" class="large" type="text" value="'+cff_esc_attr(
									/fieldname/.test(predefined) &&
									getField(predefined) &&
									'val' in getField(predefined)
									? getField(predefined).val()
									: predefined
								)+'" '+((me.readonly) ? 'readonly' : '')+' style="'+cff_esc_attr(me.getCSSComponent('input'))+'" /></div>';
							};

							var str = '';
							if(me.typeValues)
								str += '<div class="slider-type-components '+cff_esc_attr(me.size)+'">'+
								((me.range) ? component('left')+component('right') : component('center'))+
								'</div>';
							return str;
						};
						me.predefined = (/^\s*$/.test(me.predefined)) ? me.min : me._toNumber(me._getAttr('predefined'));
						return '<div class="fields '+cff_esc_attr(me.csslayout)+' '+me.name+' cff-slider-field" id="field'+me.form_identifier+'-'+me.index+'" style="'+cff_esc_attr(me.getCSSComponent('container'))+'">'+
							'<label style="'+cff_esc_attr(me.getCSSComponent('label'))+'">'+cff_sanitize(me.title, true)+'</label>'+
							'<div class="dfield slider-container">'+
								typeValuesComponents()+
								'<input id="'+me.name+'" name="'+me.name+'" class="field" type="hidden" value="'+cff_esc_attr(me.predefined)+'"/>'+
								'<div id="'+me.name+'_slider" class="slider '+me.size+'"></div>'+
								'<div class="corner-captions '+cff_esc_attr(me.size)+'">'+
									'<span class="left-corner" style="'+cff_esc_attr(me.getCSSComponent('caption_left'))+'">'+cff_sanitize(me.minCaption, true)+'</span>'+
									'<span class="right-corner" style="'+cff_esc_attr(me.getCSSComponent('caption_right'))+'">'+cff_sanitize(me.maxCaption, true)+'</span>'+
									'<div id="'+me.name+'_caption" class="slider-caption" style="'+cff_esc_attr(me.getCSSComponent('caption'))+'"></div>'+
									'<div class="clearer"></div>'+
								'</div>'+
								'<span class="uh" style="'+cff_esc_attr(me.getCSSComponent('help'))+'">'+cff_sanitize(me.userhelp, true)+'</span>'+
							'</div>'+
							'<div class="clearer"></div>'+
						'</div>';
					},
				get_min:function(){ return $('[id="'+this.name+'_slider"]').slider('option', 'min'); },
				get_max:function(){ return $('[id="'+this.name+'_slider"]').slider('option', 'max'); },
				set_min:function(v, ignore)
					{
						try{
							var e = $('[id="'+this.name+'_slider"]'), c = this.val(), r = false;
							if(ignore) v = 0;
							v = this._toNumber(v);
							e.slider('option', 'min', v);
							if(Array.isArray(c)){if(c[0] < v){c[0] = v; r = true;}}
							else if(c < v){c = v; r = true;}
							if(r) this.setVal(c);
							this.set_min_caption(v);
						}
						catch(err){}
					},
				set_max:function(v, ignore)
					{
						try{
							var e = $('[id="'+this.name+'_slider"]'), c = this.val(), r = false;
							if(ignore) v = 100;
							v = this._toNumber(v);
							e.slider('option', 'max', v);
							if(Array.isArray(c)){if(v < c[1]){c[1] = v; r = true;}}
							else if(v < c){c = v; r = true;}
							if(r) this.setVal(c);
							this.set_max_caption(v);
						}
						catch(err){}
					},
				set_min_caption:function(v)
					{
						try{
							var e = $('.'+this.name+' .left-corner');
							e.html(cff_sanitize(this.minCaption.replace(/\{\s*0\s*\}/, v), true));
						}
						catch(err){}
					},
				set_max_caption:function(v)
					{
						try{
							var e = $('.'+this.name+' .right-corner');
							e.html(cff_sanitize(this.maxCaption.replace(/\{\s*0\s*\}/, v), true));
						}
						catch(err){}
					},
				set_step:function(v, ignore)
					{
						try{
							if(ignore) v = this.step;
							else this.step = this._toNumber(v);

							if(this.logarithmic) { // TO CHECK
								this.calc_step = v;
								v = Math.min(v,1);
							}
							$('[id="'+this.name+'_slider"]').slider("option", "step", cff_esc_attr(v));
						}
						catch(err){}
					},
				set_marks:function(s,v){
					try {
						var me = this,
							e  = $( '.'+me.name+' .ui-slider-range');
						e.find('.mark').remove();
						if(s){
							for(let i = 0; i <= v; i++) {
								if( i && i != v ) {
									e.after('<span class="mark" style="position:absolute;left:calc('+PREC(i*(100/v), 4, true)+'% );"></span>');
								}
							}
						}
					} catch( err ){}
				},
				after_show:function()
					{
						var me  = this,
							opt = {
								range: (me.range != false) ? me.range : "min",
								min  : me._toNumber(me._getAttr('min')),
								max  : me._toNumber(me._getAttr('max')),
								step : me._toNumber(me._getAttr('step'))
							};

						me.set_min_caption(opt.min);
						me.set_max_caption(opt.max);

						if(me.range)
						{
							var _min = Math.min(Math.max(me._getAttr('predefinedMin'), opt.min), opt.max),
								_max = Math.min(Math.max(me._getAttr('predefinedMax'), opt.min), opt.max);
							opt['values'] = [_min, _max];
						}
						else opt['value'] = Math.min(Math.max(me._getAttr('predefined'), opt.min), opt.max);

						opt['disabled'] = me.readonly;

						opt['slide'] = opt['stop'] = (function(e){
							return function(event, ui)
								{
									var v = (typeof ui.value != 'undefined') ? ui.value : ui.values;

									if ( me.logarithmic ) {
										v = me._expon(v);
										e._setFieldValue(v);
									} else {
										$(this).slider( Array.isArray(v) ? 'values' : 'value', v);
										e._setFieldValue();
									}
								}
						})(me);
						$('#'+this.name+'_slider').slider(opt);
						$('#'+this.name+'_slider').find( '.ui-slider-handle' ).each( function(){
							$(this).append(
								'<div class="cff-slider-tooltip">'+
								'<div class="cff-slider-tooltip-arrow">'+
								'</div><div class="cff-slider-tooltip-value"></div>'+
								'</div>'
							);
						});
						me._setFieldValue();
						$('#cp_calculatedfieldsf_pform'+me.form_identifier).on('reset', function(){
							setTimeout(function(){
								$('#'+me.name+'_slider').slider(opt); me._setFieldValue();
							}, 20);
						});

						try{
							var divisions = parseInt(me.divisions);
							me.set_marks( me.marks, (isNaN(divisions) || ! divisions) ? (me.max-me.min)/me.step : divisions );
						} catch (err){}
					},
				val:function(raw)
					{
						try{
							raw = raw || false;
							var e = $('[id="' + this.name + '"]:not(.ignore)');
							return (e.length) ? ((raw) ? e.val() : JSON.parse(e.val())) : 0;
						}
						catch(err){return 0;}
					},
				setVal:function(v, nochange)
					{
						let me = this;
						function _aux(v) { return Math.min( Math.max( v, me.get_min() ), me.get_max() ); };
						try{ v = JSON.parse(v); }catch(err){}
						try{

							// Fix values format.
							if ( Array.isArray(v) ) {
								v = v.map(k => _aux(me._toNumber(k)));
								v.sort((a,b)=>a-b).splice(2);
							} else {
								v = me._toNumber(v);
								v = _aux(v);
							}

							let e = $('[name="'+me.name+'"]'),
								bk = e[0].value;

							e.val(v);

							$('#'+me.name+'_slider').slider(((Array.isArray(v)) ? 'values' : 'value'), (me.logarithmic ? me._inverse(v) : v) );
							me._setFieldValue(v, nochange || bk === e[0].value || bk === '['+e[0].value+']');
						}catch(err){}
					}
		});