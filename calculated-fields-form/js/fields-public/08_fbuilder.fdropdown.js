	$.fbuilder.controls['fdropdown']=function(){};
	$.extend(
		$.fbuilder.controls['fdropdown'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Select a Choice",
			ftype:"fdropdown",
			size:"medium",
			required:false,
			toSubmit:"text",
			merge:0,
			choiceSelected:"",
            select2:false,
			multiple:false,
			first_choice:false,
			first_choice_text:"",
			vChoices:1,
			showDep:false,
			nextPage:false,
			show:function()
				{
					this.choicesVal = ((typeof(this.choicesVal) != "undefined" && this.choicesVal !== null)?this.choicesVal:this.choices)

					var c	 = this.choices,
						cv	 = this.choicesVal,
						og   = (typeof this.optgroup == 'undefined') ? new Array() : this.optgroup,
						op_o = false,
						l 	 = c.length,
						classDep = '',
						str  = ( this.first_choice ? '<option value="">'+cff_esc_attr(this.first_choice_text)+'</option>' : ''),
						n 	 = this.name.match(/fieldname\d+/)[0];

					if (typeof this.choicesDep == "undefined" || this.choicesDep == null)
						this.choicesDep = new Array();

					for (var i=0;i<l;i++)
					{
						if(typeof this.choicesDep[i] != 'undefined' && (typeof og[i] == 'undefined' || !og[i]))
							this.choicesDep[i] = $.grep(this.choicesDep[i],function(x){ return x != "" && x != n; });
						else
							this.choicesDep[i] = [];

						if(this.choicesDep[i].length && (typeof og[i] == 'undefined' || !og[i]))
							classDep = 'depItem';
					}

					for (var i=0;i<l;i++)
					{
						if(og[i])
						{
							if(op_o) str += '</optgroup>';
							str += '<optgroup label="'+cff_esc_attr(c[i])+'">';
							op_o = true;
						}
						else
						{
							str += '<option '+((this.choiceSelected == c[i]+' - '+cv[i])?"selected":"")+' '+((classDep != '') ? 'class="'+classDep+'"' : '')+' value="'+cff_esc_attr(cv[i])+'" vt="'+cff_esc_attr((this.toSubmit=='text') ? c[i] : cv[i])+'" data-i="'+i+'">'+cff_esc_attr(c[i])+'</option>';
						}
					}
					if(op_o) str += '</optgroup>';
					return '<div class="fields '+cff_esc_attr(this.csslayout)+' '+this.name+' cff-dropdown-field" id="field'+this.form_identifier+'-'+this.index+'" style="'+cff_esc_attr(this.getCSSComponent('container'))+'"><label for="'+this.name+'" style="'+cff_esc_attr(this.getCSSComponent('label'))+'">'+cff_sanitize(this.title, true)+''+((this.required)?"<span class='r'>*</span>":"")+'</label>'+
					'<div class="dfield"><select aria-label="'+cff_esc_attr(this.title)+'" id="'+this.name+'" name="'+this.name+((this.multiple)? '[]':'')+'" class="field '+((classDep != '') ? ' depItemSel ' : '')+cff_esc_attr(this.size)+((this.required)?' required':'')+'" '+((this.multiple == true)?' multiple="multiple" size="'+((this.vChoices) ? cff_esc_attr(this.vChoices) : 1)+'"':'')+' style="'+cff_esc_attr(this.getCSSComponent('dropdown'))+'">'+str+'</select><span class="uh" style="'+cff_esc_attr(this.getCSSComponent('help'))+'">'+cff_sanitize(this.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
            after_show:function()
                {
                    var me = this;
					if ( me.nextPage && ! me.multiple ) {
						$('#'+me.name).on('change select2:select', function(evt){
							if ( evt.type != 'select2:select' && ( ! ( 'originalEvent' in evt ) || ! evt.originalEvent.isTrusted ) ) return;
							if ( ! me.first_choice || this.selectedIndex ) {
								let p = $( this ).closest('.pbreak:not(.pbEnd):visible');
								if ( p.length && $(this.form).validate().checkForm() ) {
									let i = p.attr('page');
									GOTOPAGE(SUM(i, 1), this.form);
								}
							}
						});
					}
                    if(me.select2)
                    {
                        function formatState(state)
                        {
							return !state.id ? state.text : $('<span>'+state.text+'</span>');
                        };

                        $('#'+me.name).after('<span class="cff-select2-container '+me.size+'"></span>');
                        $('#'+me.name).on('change', function(){ $(this).valid(); });
                        if('select2' in $.fn) {
                            $('#'+me.name).select2({
                                'templateResult': formatState,
                                'templateSelection': formatState,
                                'dropdownParent':$('#'+me.name).next('.cff-select2-container')
                            });
							$('#'+me.name).parent().find('.select2-container').addClass(me.size);
						}
                        else
                            $(document).ready(function(){
								if('select2' in $.fn) {
									$('#'+me.name).select2({'dropdownParent':$('#'+me.name).next('.cff-select2-container')});
									$('#'+me.name).parent().find('.select2-container').addClass(cff_esc_attr(me.size));
								}
							});
                    }
                },
			showHideDep:function(toShow, toHide, hiddenByContainer, interval)
				{
                    if(typeof hiddenByContainer == 'undefined') hiddenByContainer = {};
					var me = this,
						item = $('#'+me.name+'.depItemSel'),
						form_identifier = me.form_identifier,
						formObj	= item.closest('form'),
						isHidden = (typeof toHide[me.name] != 'undefined' || typeof hiddenByContainer[me.name] != 'undefined'),
						result = [];

					try
					{
						if(item.length)
						{
							var selected = [];
							$(item).find(':selected').each(function(){selected.push($(this).data('i'));});

							for(var i = 0, h = me.choices.length; i < h; i++)
							{
								if(typeof me.choicesDep[i] != 'undefined' && me.choicesDep[i].length)
								{
									for(var j = 0, k = me.choicesDep[i].length; j < k; j++)
									{
										if(!/fieldname/i.test(me.choicesDep[i][j])) continue;
										var dep = me.choicesDep[i][j]+form_identifier;
										if(isHidden || $.inArray(i,selected) == -1)
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
							}
						}
					}
					catch(e){}
					return result;
				},
			val:function(raw, no_quotes)
				{
					raw = raw || false;
                    no_quotes = no_quotes || false;
					var e = $('[id="'+this.name+'"]:not(.ignore) option:selected'),
						v,
						m = this.multiple,
						g = this.merge && !raw;

					if(m && !g)	v = [];
					if(e.length)
					{
						e.each(function(){
							var t = $.fbuilder.parseValStr((raw == 'vt') ? this.getAttribute('vt') : this.value, raw, no_quotes);
							if(!$.fbuilder.isNumeric(t)) t = t.replace(/^"/,'').replace(/"$/,'');
							if(!m || g) v = (v)?v+t:t;
							else v.push(t);
						});
					}
					return (typeof v == 'object' && typeof v['length'] !== 'undefined') ? v : (v ? (($.fbuilder.isNumeric(v) || no_quotes) ? v : '"'+v+'"') : (raw == 'vt' ? (no_quotes ? '' : '""') : 0));
				},
			setVal:function(v, nochange, _default)
				{
                    _default = _default || false;
                    nochange = nochange || false;
					if(!Array.isArray(v)) v = [v];
					var t, e, n = this.name, selector;

					let bk = JSON.stringify(this.val(true)),
						bk_vt = JSON.stringify(this.val('vt'));

					$('[id="'+n+'"] OPTION').prop('selected', false);
					for(var i in v)
					{
						t = (new String(v[i])).replace(/(['"])/g, "\\$1");
                        if(_default) e = $('[id="'+n+'"] OPTION[vt="'+t+'"]');
                        if(!_default || !e.length) e = $('[id="'+n+'"] OPTION[value="'+t+'"]');
                        if(e.length) e.prop('selected', true);
					}
					if (
						! nochange &&
						(
							bk !== JSON.stringify( this.val( true ) ) ||
							bk_vt !== JSON.stringify( this.val( 'vt' ) )
						)
					) $('[id="'+n+'"]').trigger('change');
				},
			setChoices:function(choices)
				{
					if($.isPlainObject(choices))
					{
						var me = this,
                            bk = me.val(true);

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
						if('optgroup' in choices && Array.isArray(choices.optgroup)) me.optgroup = choices.optgroup;
						var html = me.show(),
							e = $('.'+me.name);
						e.find('select').html($(html).find('select').html());
						if(!Array.isArray(bk)) bk = [bk];
                        for(var j in bk)
                        {
                            try{ bk[j] = JSON.parse(bk[j]); }catch(err){}
                        }
						me.setVal(bk, bk.every(function(e){ return me.choicesVal.indexOf(e) > -1; }));
					}
				},
			getIndex:function()
				{
					var f = $('[name*="'+this.name+'"]');
					if(this.multiple){
						var i = [];
						f.find('option').each(function(j,v){if(this.selected) i.push(j);});
						return i;
					}
					else return f[0].selectedIndex;
				}
		}
	);