	$.fbuilder.controls['fcheck']=function(){};
	$.extend(
		$.fbuilder.controls['fcheck'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Check All That Apply",
			ftype:"fcheck",
			layout:"one_column",
			required:false,
            readonly:false,
			merge:1,
            onoff:0,
			quantity:0,
			quantity_when_ticked:0,
			max:-1,
			min:-1,
			maxError:"Check no more than {0} boxes",
			minError:"Check at least {0} boxes",
			toSubmit:"text",
			showDep:false,
			init:function(){
				this.getCSSComponent('choice', true, '#fbuilder .'+this.name+' .dfield label', this.form_identifier);
				if(isNaN(this.min*1)) this.min = -1;
				this.min *= 1;
				if(isNaN(this.max*1)) this.max = -1;
				this.max *= 1;
			},
			show:function()
				{
					this.choicesVal = ((typeof(this.choicesVal) != "undefined" && this.choicesVal !== null)?this.choicesVal:this.choices);
					var str = "",
                        classDep,
						n 	= this.name.match(/fieldname\d+/)[0];

					if (typeof this.choicesDep == "undefined" || this.choicesDep == null)
						this.choicesDep = new Array();

					for (var i=0, h=this.choices.length; i<h; i++)
					{
						if(typeof this.choicesDep[i] != 'undefined')
							this.choicesDep[i] = $.grep(this.choicesDep[i],function(x){ return x != "" && x != n; });
						else
							this.choicesDep[i] = [];

						classDep = (this.choicesDep[i].length) ? 'depItem': '';

						str += '<div class="'+this.layout+(this.quantity_when_ticked ? ' cff-quantity-when-ticked' : '')+'"><label for="'+this.name+'_cb'+i+'" '+(!this.tooltipIcon && this.userhelpTooltip && this.userhelp && this.userhelp.length ? 'uh="'+cff_esc_attr(this.userhelp)+'"' : '')+'><input aria-label="'+cff_esc_attr(this.choices[i])+'" name="'+this.name+'[]" id="'+this.name+'_cb'+i+'" class="field '+classDep+' group '+((this.required || 0 < this.min)?" required":"")+'" value="'+cff_esc_attr(this.choicesVal[i])+'" vt="'+cff_esc_attr((this.toSubmit == 'text') ? this.choices[i] : this.choicesVal[i])+'" type="checkbox" '+(this.readonly ? ' onclick="return false;" ' : '')+((this.choiceSelected[i])?"checked":"")+'/> '+
                        (this.onoff ? '<span class="cff-switch"></span>': '') +
                        '<span>'+cff_html_decode(this.choices[i])+'</span>'+
						(
						    this.quantity ?
							'<span class="cff-checkbox-field-quantity"><input type="number" min="1" value="1" id="'+this.name+'_cb'+i+'_quantity" /></span>' : ''
						) +
						'</label></div>';
					}
					return '<div class="fields '+cff_esc_attr(this.csslayout)+(this.onoff ? ' cff-switch-container' : '')+' '+this.name+' cff-checkbox-field" id="field'+this.form_identifier+'-'+this.index+'" style="'+cff_esc_attr(this.getCSSComponent('container'))+'"><label style="'+cff_esc_attr(this.getCSSComponent('label'))+'">'+cff_sanitize(this.title, true)+''+((this.required || 0 < this.min)?"<span class='r'>*</span>":"")+'</label><div class="dfield">'+str+'<div class="clearer"></div>'+(!this.userhelpTooltip ? '<span class="uh" style="'+cff_esc_attr(this.getCSSComponent('help'))+'">'+cff_sanitize(this.userhelp, true)+'</span>' : '')+'</div><div class="clearer"></div></div>';
				},
            enable_disable:function( e )
                {
                    var m = this, d = true;
                    if(0 < m.max )
                    {
						if ( 1 == m.max ) { // Set radio button behavior.
							if ( !! e && e.checked ) {
								$('[id*="'+m.name+'_"]:checked').prop('checked', false);
								$(e).prop('checked', true);
							} else {
								$('[id*="'+m.name+'_"]:checked').each(function(){
									$(this).prop('checked', d);
									d = false;
								});
							}
						} else {
							if($('[id*="'+m.name+'_"]:checked').length < m.max) d = false;
							$('[id*="'+m.name+'_"]:not(:checked)').prop('disabled', d);
						}
                    }
                },
            after_show:function()
                {
                    var m = this, tmp;

                    $(document).off('click','[id*="'+m.name+'_"]')
					.on('click','[id*="'+m.name+'_"]', function(evt){m.enable_disable( evt.target );});
                    m.enable_disable();

					if( m.readonly ) {
						$('[id*="'+m.name+'_"][_onclick]').each(function(){$(this).attr('onclick', $(this).attr('_onclick'));});
					}

					if( m.quantity ) {
						$(document).on('input', '[type="number"][id*="'+m.name+'_"]', function(){
							let base_id = $(this).attr('id').replace(/_quantity/, '');
							$('#'+base_id).trigger('change');
						});
					}

					if(0 < m.max && 0 < m.min && m.max < m.min){
						tmp = m.min;
						m.min = m.max;
						m.max = tmp;
					}

                    if(0 < m.max)
                        $('[id*="'+m.name+'_"][type="checkbox"]').rules('add',{maxlength:m.max, messages:{maxlength:cff_sanitize(m.maxError, true)}});
                    if(0 < m.min)
                        $('[id*="'+m.name+'_"][type="checkbox"]').rules('add',{minlength:m.min, messages:{minlength:cff_sanitize(m.minError, true)}});
                },
			showHideDep:function(toShow, toHide, hiddenByContainer, interval)
				{
                    if(typeof hiddenByContainer == 'undefined') hiddenByContainer = {};
					var me		= this,
						item 	= $('input[id*="'+me.name+'_"]'),
						formObj	= item.closest('form'),
						form_identifier = me.form_identifier,
						isHidden = (typeof toHide[me.name] != 'undefined' || typeof hiddenByContainer[me.name] != 'undefined'),
						result 	= [];

					try
					{
						item.each(function(i,e){
							if(typeof me.choicesDep[i] != 'undefined' && me.choicesDep[i].length)
							{
								var checked = e.checked;
								for(var j = 0, k = me.choicesDep[i].length; j < k; j++)
								{
									if(!/fieldname/i.test(me.choicesDep[i][j])) continue;
									var dep = me.choicesDep[i][j]+form_identifier;
									if(isHidden || !checked)
									{
										if(typeof toShow[dep] != 'undefined')
										{
											delete toShow[dep]['ref'][me.name+'_'+i];
											if($.isEmptyObject(toShow[dep]['ref']))
											delete toShow[dep];
										}

										if(typeof toShow[dep] == 'undefined')
										{
											$('[id*="'+dep+'"],.'+dep, formObj).closest('.fields').addClass('ignorefield').hide();
											$('[id*="'+dep+'"]:not(.ignore)', formObj).addClass('ignore').trigger('add-ignore');
											toHide[dep] = {};
										}
									}
									else
									{
										delete toHide[dep];
										if(typeof toShow[dep] == 'undefined')
										toShow[dep] = { 'ref': {}};
										toShow[dep]['ref'][me.name+'_'+i]  = 1;
										if(!(dep in hiddenByContainer))
										{
											$('[id*="'+dep+'"],.'+dep, formObj).closest('.fields').removeClass('ignorefield').fadeIn(interval || 0);
											$('[id*="'+dep+'"].ignore', formObj).removeClass('ignore').trigger('remove-ignore');
										}
									}
									if($.inArray(dep,result) == -1) result.push(dep);
								}
							}
						});
					}
					catch(e){  }
					return result;
				},
			val:function(raw, no_quotes)
				{
					raw = raw || false;
                    no_quotes = no_quotes || false;
					var v, me = this, m = me.merge && !raw, q = me.quantity,
						e = $('[id*="'+me.name+'_"]:checked:not(.ignore)');

					if(!m) v = [];
					if(e.length)
					{
						if ( raw == 'q' ) {
							v = me.getQuantity();
						} else {
							e.each(function(){
								var t = (m) ? $.fbuilder.parseVal(this.value) : $.fbuilder.parseValStr((raw == 'vt') ? this.getAttribute('vt') : this.value, raw, no_quotes);
								if(!$.fbuilder.isNumeric(t)) t = t.replace(/^"/,'').replace(/"$/,'');

								if( q && $.fbuilder.isNumeric(t) ) t = t*Math.max( $('#'+this.id+'_quantity').val(), 1 );
								else if( q ) t += ' ('+ Math.max( $('#'+this.id+'_quantity').val(), 1 ) +')';

								if(m) v = (v)?v+t:t;
								else v.push(t);
							});
						}
					}
					return (typeof v == 'object' && typeof v['length'] !== 'undefined') ? v : ((v) ? (($.fbuilder.isNumeric(v)) ? v : '"'+v+'"') : 0);
				},
			setVal:function(v, nochange, _default)
				{
					let t, me = this, n = me.name, c = 0, e;
					let aux	= function(v, attr) {
						let result;
						if (me.quantity) {
							let v_parts = /^(.*)(\s*\((\d+)\))$/.exec(v);
							if( v_parts && typeof v_parts[3] != 'undefined' ) {
								v_parts[1] = String(v_parts[1]).replace(/\s$/, '');
								result = $('[type="checkbox"][id*="'+n+'_"]['+attr+'="'+v_parts[1]+'"]');
								if ( result.length ) {
									$( '[id="'+result.attr('id')+'_quantity"]' ).val(v_parts[3]);
								}
							}
						}

						if (!result || !result.length) {
							result = $('[type="checkbox"][id*="'+n+'_"]['+attr+'="'+v+'"]');
						}

						return result;
					};

                    _default = _default || false;
                    nochange = nochange || false;

					if(!Array.isArray(v)) v = [v];

					let bk = JSON.stringify(me.val(true)),
						bk_vt = JSON.stringify(me.val('vt'));

					$('[id*="'+n+'_"]').prop('checked', false);
					for(let i in v)
					{
						t = (new String(v[i])).replace(/(['"])/g, "\\$1");
                        if(0 < me.max && me.max < c+1) break;
                        if(_default) e = aux(t, 'vt');
                        if(!_default || !e.length)  e = aux(t, 'value');
                        if(e.length){ e.prop('checked', true);c++;}
					}
                    me.enable_disable();
					if (
						! nochange &&
						(
							bk !== JSON.stringify( me.val( true ) ) ||
							bk_vt !== JSON.stringify( me.val( 'vt' ) )
						)
					) $('[id*="'+n+'_"]:eq(0)').trigger('change');
				},
			setChoices:function(choices)
				{
					if($.isPlainObject(choices))
					{
						var me = this,
                            bk = me.val('vt');
						if('texts' in choices && Array.isArray(choices.texts)) me.choices = choices.texts;
						if('values' in choices && Array.isArray(choices.values)) me.choicesVal = choices.values;
						if('dependencies' in choices && Array.isArray(choices.dependencies))
                        {
                            me.choicesDep = choices.dependencies.map(
                                function(x){
                                    return (Array.isArray(x)) ? x.map(
                                        function(y){
                                            return (typeof y == 'number') ? 'fieldname'+parseInt(y) : y;
                                        }) : x;
                                }
                          );
                        }
						var html = me.show(),
							e = $('.'+me.name),
							i = e.hasClass('ignorefield') || e.find('.ignore').length,
							ipb = e.find('.ignorepb').length || e.closest('.pbreak:hidden').length;
						e.find('.dfield').replaceWith($(html).find('.dfield'));
						if(i) e.find('input').addClass('ignore');
						if(ipb) e.find('input').addClass('ignorepb');
						if(!Array.isArray(bk)) bk = [bk];
                        for(var j in bk)
                        {
                            try{ bk[j] = JSON.parse(bk[j]); }catch(err){}
                        }
						me.setVal(bk, bk.every(function(e){ return me.choicesVal.indexOf(e) > -1; }), true);
						if(me.quantity && 'quantities' in choices && Array.isArray(choices.quantities)) {
							$('[type="number"][id*="'+me.name+'"]').each(function(i, e){
								if( !isNaN(choices.quantities[i]) && 1 < choices.quantities[i]*1 ) {
									$(this).val(choices.quantities[i]*1).change();
								}
							});
						}
					}
				},
			getIndex:function() // Get an array of the ticked choices indexes.
				{
					var i = [];
					$('input[type="checkbox"][name*="'+this.name+'"]').each(function(j,v){if(this.checked){i.push(j);}});
					return i;
				},
			getQuantity:function() // Get an array of the ticked choices quantities, or empty array.
				{
					var q = [];
					if ( this.quantity ) {
						$('input[type="checkbox"][name*="'+this.name+'"]:checked').each(function(){
							let e = $('#'+this.id+'_quantity');
							if ( e.length ) {
								let v = parseFloat(e.val());
								if ( ! isNaN( v ) ) q.push( v );
							}
						});
					}
					return q;
				}
		}
	);