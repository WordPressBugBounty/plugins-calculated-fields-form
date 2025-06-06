	$.fbuilder.controls['fdate'] = function(){};
	$.extend(
		$.fbuilder.controls['fdate'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Date",
			ftype:"fdate",
			predefined:"",
			predefinedClick:false,
			size:"medium",
			required:false,
			readonly:false,
            disableKeyboardOnMobile:false,

			// Date component
			showDatepicker: true,
			dformat:"mm/dd/yyyy",
			showFormatOnLabel:1,
			dseparator:"/",
			showDropdown:false,
			dropdownRange:"-10:+10",
            invalidDates:"",
            validDates:"",
            mondayFirstDay:false,
            alwaysVisible:false,
			working_dates:[true,true,true,true,true,true,true],
			minDate:"",
			maxDate:"",
			currentDate:0,
			defaultDate:"",

			// Time component
			showTimepicker: false,
			tformat:"24",
			minHour:0,
			maxHour:23,
			minMinute:0,
			maxMinute:59,
			stepHour: 1,
			stepMinute: 1,
			defaultTime:"",
			timeErrorMssg:"",

			// Labels
			ariaHourLabel: 'hours',
			ariaMinuteLabel: 'minutes',
			ariaAMPMLabel: 'am or pm',

            // Error message
            errorMssg:'',

			_get_regexp : function()
				{
					var me = this,
						df = me.dformat.replace(new RegExp('\\'+me.dseparator, 'g'), '/'),
						rt;

					if(/^y/i.test(df)) rt = '(\\d{4})[^\\d](\\d{1,2})[^\\d](\\d{1,2})';
					else rt = '(\\d{1,2})[\\/\\-\\.](\\d{1,2})[\\/\\-\\.](\\d{4})';

					return {d:df, r:rt};
				},
			_set_Events : function()
				{
					var me = this,
						f  = function(){
							me.set_dateTime();
							$('#'+me.name+'_date').valid();
						};

					$(document).off('change', '#'+me.name+'_date').on('change', '#'+me.name+'_date', function(){
						if( me.showDatepicker && me.alwaysVisible )
							$('#'+me.name+'_datepicker_container').datepicker('setDate', this.value);
						f();
					});
					$(document).off('change', '#'+me.name+'_hours').on('change', '#'+me.name+'_hours', function(){f();});
					$(document).off('change', '#'+me.name+'_minutes').on('change', '#'+me.name+'_minutes', function(){f();});
					$(document).off('change', '#'+me.name+'_ampm').on('change', '#'+me.name+'_ampm', function(){f();});

					$('#cp_calculatedfieldsf_pform'+me.form_identifier).on('reset', function(){
                        setTimeout(function(){me.set_DefaultDate(true); me.set_DefaultTime(); me.set_dateTime();},500);
                    });
				},
			_validateDate: function(d)
				{
					try{
						var e = this,
							w = e.working_dates,
							i = e.invalidDates,
							v = e.validDates,
							n = ( e.alwaysVisible && e.showDatepicker ) ? $('#'+e.name+'_datepicker_container') : $('#'+e.name+'_date'),
							isValid = Array.isArray(v) && v.length ? false : true;

						d = d || n.datepicker('getDate');

						if(d === null || !w[d.getDay()]) return false;
						if(v !== null)
						{
							for(var j = 0, h = v.length; j < h; j++)
							{
								if(d.getDate() == v[j].getDate() && d.getMonth() == v[j].getMonth() && d.getFullYear() == v[j].getFullYear()) {
									isValid = true;
									break;
								};
							}
						}
						if(!isValid) return false;
						if(i !== null)
						{
							for(var j = 0, h = i.length; j < h; j++)
							{
								if(d.getDate() == i[j].getDate() && d.getMonth() == i[j].getMonth() && d.getFullYear() == i[j].getFullYear()) return false;
							}
						}

						var _d	= $.datepicker,
							_i  = _d._getInst(n[0]),
							_mi = _d._determineDate(_i, _d._get(_i, 'minDate'), null),
							_ma = _d._determineDate(_i, _d._get(_i, 'maxDate'), null);

						if((_mi != null && d < _mi) || (_ma != null && _ma < d)) return false;
					}
					catch(_err){return false;}
					return true;
				},
			_validateTime : function()
				{
					var i = this;
					if(i.showTimepicker)
					{
						var n = i.name,
							h = $('#'+n+'_hours').val(),
							m = $('#'+n+'_minutes').val();
						if(i.tformat == 12)
						{
							var x = $('#'+n+'_ampm').val()
							if(x == 'pm' && h != 12) h = h*1+12;
							if(x == 'am' && h == 12) h = 0;
						}
						if(
							h < i.minHour ||
							i.maxHour < h ||
							(h == i.minHour && m < i.minMinute) ||
							(h == i.maxHour && i.maxMinute < m)
						) return false;
					}
					return true;
				},
			init:function()
				{
					var me 			= this,
						_checkValue = function(v, min, max)
						{
							v = parseInt(v);
							v = (isNaN(v)) ? max : v;
							return Math.min(Math.max(v,min),max);
						},
						_preprocessDates = function( v ){
							var	dateRegExp = new RegExp(/^\d{1,2}\/\d{1,2}\/\d{4}$/),
								counter    = 0,
								dates      = v.split(','),
								result     = [];

							for(var i = 0, h = dates.length; i < h; i++)
							{
								var range = dates[i].split('-');
								if(range.length == 2 && range[0].match(dateRegExp) != null && range[1].match(dateRegExp) != null)
								{
									var fromD = new Date(range[0]),
										toD = new Date(range[1]);
									while(fromD <= toD)
									{
										result[counter] = fromD;
										var tmp = new Date(fromD.valueOf());
										tmp.setDate(tmp.getDate()+1);
										fromD = tmp;
										counter++;

									}
								}
								else
								{
									for(var j = 0, k = range.length; j < k; j++)
									{
										if(range[j].match(dateRegExp) != null)
										{
											result[counter] = new Date(range[j]);
											counter++;
										}
									}
								}
							}
							return result;
						};

					// Date
					me.dformat		= me.dformat.replace(/\//g, me.dseparator);
                    me.invalidDates = _preprocessDates(me.invalidDates.replace(/\s+/g, ''));
                    me.validDates   = _preprocessDates(me.validDates.replace(/\s+/g, ''));

					if(me.dropdownRange.indexOf(':') == -1) me.dropdownRange = '-10:+10';

					// Time
					me.minHour 		= _checkValue(me.minHour, 0, 23);
					me.maxHour 		= _checkValue(me.maxHour, 0, 23);
					me.minMinute 	= _checkValue(me.minMinute, 0, 59);
					me.maxMinute 	= _checkValue(me.maxMinute, 0, 59);
					me.stepHour 	= _checkValue(me.stepHour, 1, Math.max(1, Math.abs(me.maxHour - me.minHour)+1));
					me.stepMinute 	= _checkValue(me.stepMinute, 1, Math.max(1, Math.abs(me.maxHour-me.minHour)*60+Math.abs(me.maxMinute - me.minMinute)+1));

					// Set handles
					me._setHndl('minDate');
					me._setHndl('maxDate');
                },
			get_hours:function()
				{
					var me = this,
						str = '',
						i = 0,
						h,
						from = (me.tformat == 12) ? 1  : me.minHour,
						to   = (me.tformat == 12) ? 12 : me.maxHour;

					while((h = from+me.stepHour * i) <= to)
					{
						if(h < 10) h = '0'+''+h;
						str += '<option value="'+h+'">'+h+'</option>';
						i++;
					}
					return '<select id="'+me.name+'_hours" name="'+me.name+'_hours" class="hours-component" aria-label="'+cff_esc_attr(me.ariaHourLabel)+'" '+((me.readonly) ? 'DISABLED' : '')+' style="'+cff_esc_attr(me.getCSSComponent('hour'))+'">'+str+'</select>:';
				},
			get_minutes:function()
				{
					var me = this,
						str = '',
						i = 0,
						m,
						n = (me.minHour == me.maxHour)?me.minMinute*1 : 0,
						x = (me.minHour == me.maxHour)?me.maxMinute*1 : 59;

					while((m = n+me.stepMinute * i) <= x)
					{
						if(m < 10) m = '0'+''+m;
						str += '<option value="'+m+'">'+m+'</option>';
						i++;
					}
					return '<select id="'+me.name+'_minutes" name="'+me.name+'_minutes" class="minutes-component" aria-label="'+cff_esc_attr(me.ariaMinuteLabel)+'" '+((me.readonly) ? 'DISABLED' : '')+' style="'+cff_esc_attr(me.getCSSComponent('minute'))+'">'+str+'</select>';
				},
			get_ampm:function()
				{
					var str = '';
					if(this.tformat == 12)
					{
						return '<select id="'+this.name+'_ampm" class="ampm-component"  aria-label="'+cff_esc_attr(this.ariaAMPMLabel)+'" '+((this.readonly) ? 'DISABLED' : '')+' style="'+cff_esc_attr(this.getCSSComponent('ampm'))+'"><option value="am">am</option><option value="pm">pm</option></select>';
					}
					return str;
				},
			set_minTime:function(v, ignore)
				{
					let me = this;
					if(me.showTimepicker && typeof v == 'object' )
					{
						if ( 'hour' in v && ! isNaN( v.hour * 1 ) ) me.minHour = Math.min(23, Math.max(0,v.hour * 1));
						if ( 'minutes' in v && ! isNaN( v.minutes * 1 ) ) me.minMinute = Math.min(59, Math.max(0,v.minutes * 1));
						$('#'+me.name+'_hours,#'+me.name+'_minutes,#'+me.name+'_ampm').each((i,e)=>{$(e).valid();});
					}
				},
			set_maxTime:function(v, ignore)
				{
					let me = this;
					if(me.showTimepicker && typeof v == 'object' )
					{
						if ( 'hour' in v && ! isNaN( v.hour * 1 ) ) me.maxHour = Math.min(23, Math.max(0,v.hour * 1));
						if ( 'minutes' in v && ! isNaN( v.minutes * 1 ) ) me.maxMinute = Math.min(59, Math.max(0,v.minutes * 1));
						$('#'+me.name+'_hours,#'+me.name+'_minutes,#'+me.name+'_ampm').each((i,e)=>{$(e).valid();});
					}
				},
			set_dateTime:function(nochange)
				{
					var me  = this,
						str = $('#'+me.name+'_date').val(),
                        e   = $('#'+me.name),
						bk  = e.val();
					if(me.showTimepicker)
					{
						str += ' '+$('#'+me.name+'_hours').val();
						str += ':'+$('#'+me.name+'_minutes').val();
						if($('#'+me.name+'_ampm').length) str += $('#'+me.name+'_ampm').val();
					}
                    e.val(str);
					if ( ! nochange && bk !== e.val() ) e.trigger('change');
				},
			set_minDate:function(v, ignore)
				{
					var e = $('[id*="'+this.name+'_"].hasDatepicker'), f;
					if(e.length)
					{
						try {
							e.datepicker('option', 'minDate', (ignore) ? null : ( (typeof v == 'string') ? cff_esc_attr(v) : v ));
						}catch(err){ e.datepicker('option', 'minDate', null); }
						if( e.hasClass('datepicker-container') ) { f = e; e = e.siblings('.date-component'); }
						if(e.val() != '') e.trigger('change');
						else if( f ) f.find('.ui-state-active').removeClass('ui-state-active');
					}
				},
			set_maxDate:function(v, ignore)
				{
					var e = $('[id*="'+this.name+'_"].hasDatepicker'), f;
					if(e.length)
					{
						try {
							e.datepicker('option', 'maxDate', (ignore) ? null : ( (typeof v == 'string') ? cff_esc_attr(v) : v ));
						}catch (err){ e.datepicker('option', 'maxDate', null); }
						if( e.hasClass('datepicker-container') ) { f = e; e = e.siblings('.date-component'); }
						if(e.val() != '') e.trigger('change');
						else if( f ) f.find('.ui-state-active').removeClass('ui-state-active');
					}
				},
			set_DefaultDate : function(init)
				{
					var me = this,
						p  = {
							dateFormat: me.dformat.replace(/yyyy/g,"yy"),
							minDate: me._getAttr('minDate'),
							maxDate: me._getAttr('maxDate'),
                            firstDay: (me.mondayFirstDay ? 1 : 0),
							disabled: me.readonly,
							beforeShow: function() {
								// Patch for elementor popup issue.
								let e = $('[name="'+me.name+'"]');
								if ( e.closest('.elementor-popup-modal').length ) {
									e.closest('form').after($('#ui-datepicker-div'));
								}
							}
						},
						dp = $("#"+me.name+"_date"),
						// dd = (me.defaultDate != "") ? me.defaultDate : ((me.predefined != "") ? me.predefined : new Date());
						dd = me.currentDate && init ? new Date() : ((me.defaultDate != "") ? me.defaultDate : ((me.predefined != "" && !me.predefinedClick) ? me._getAttr('predefined') : ''));

					if( me.alwaysVisible && me.showDatepicker ) {
						dp = $("#"+me.name+"_datepicker_container");
						p['altField'] = $("#"+me.name+"_date");
						p['altFormat'] = p['dateFormat'];
						p['onSelect'] = function( dateText, inst ){
							$("#"+me.name+"_date").trigger('change');
						};
					}

					dp.on( 'click', function(){ $(document).trigger('click'); $(this).trigger('focus'); });
					if(me.showDropdown) p = $.extend(p,{changeMonth: true,changeYear: true,yearRange: me.dropdownRange});
					p = $.extend(p, {beforeShowDay:function(d){return [me._validateDate(d), ""];}});
					if(me.defaultDate != "") p.defaultDate = me.defaultDate;
					try {
						dp.datepicker(p);
					} catch(err) {}
                    if(!me.predefinedClick || !!init == false) dp.datepicker("setDate", dd);
                    if(!me._validateDate()){ dp.datepicker("setDate", ''); $("#"+me.name+"_datepicker_container .ui-state-active").removeClass('ui-state-active');}
				},
			set_DefaultTime : function()
				{
					var me 			= this,
						_setValue 	= function(f, v, m)
						{
							if ( !isNaN(v*1) ) {
								v = Math.min(v*1, m*1);
								v = (v < 10) ? 0+''+v : v;
								$('#'+f+' [value="'+v+'"]').prop('selected', true);
							}
						};

					if(me.showTimepicker)
					{
						var parts, time = {}, tmp = 0, max_minutes = 59;
						if((parts = /(\d{1,2}):(\d{1,2})\s*([ap]m)?/gi.exec(me.defaultTime)) != null)
						{
							time['hour'] = parts[1]*1+((parts.length == 4 && /pm/i.test(parts[3]) && parts[1] != 12) ? 12 : 0);
							time['minute'] = parts[2];
						}
						else
						{
							var d = new Date();
							time['hour'] = d.getHours();
							time['minute'] = d.getMinutes();
						}

						time['hour'] = Math.min(Math.max(time['hour'], me.minHour), me.maxHour);
						if(time['hour'] <= me.minHour) time['minute'] = Math.max(time['minute'],me.minMinute);
						if(me.maxHour <= time['hour']) time['minute'] = Math.min(time['minute'],me.maxMinute);

						_setValue(
							me.name+'_hours',
							(me.tformat == 12) ? ((time['hour'] > 12) ? time['hour'] - 12 : ((time['hour'] == 0) ? 12 : time['hour'])) : time['hour'],
							(me.tformat == 12) ? 12 : me.maxHour
						);

						_setValue(me.name+'_minutes', time['minute'], (time['hour'] == me.maxHour) ? me.maxMinute : 59);

						$('#'+me.name+'_ampm'+' [value="'+((time['hour'] < 12) ? 'am' : 'pm')+'"]').prop('selected', true);
					}
				},
			show:function()
				{
                    var me				= this,
						n 				= me.name,
						attr 			= 'value',
						format_label   	= [],
						date_tag_type  	= 'text',
						disabled		= '',
						date_tag_class 	= 'field date'+me.dformat.replace(/[^a-z]/ig,"")+' '+((me.required && me.showDatepicker)?' required': '');

                    if(me.predefinedClick) attr = 'placeholder';
                    if(me.showDatepicker && ! me.alwaysVisible) format_label.push(me.dformat);
					else{ date_tag_type = 'hidden'; if( ! me.alwaysVisible ) disabled='disabled';}
                    if(me.showTimepicker) format_label.push('HH:mm');
					let predefined = this._getAttr('predefined') || '';
					return '<div class="fields '+cff_esc_attr(me.csslayout)+' '+n+' cff-date-field" id="field'+me.form_identifier+'-'+me.index+'" style="'+cff_esc_attr(me.getCSSComponent('container'))+'"><label '+(me.showDatepicker ? 'for="'+n+'_date"' : '')+' style="'+cff_esc_attr(me.getCSSComponent('label'))+'">'+cff_sanitize(me.title, true)+''+((me.required)?"<span class='r'>*</span>":"")+((format_label.length && me.showFormatOnLabel) ? ' <span class="dformat" style="'+cff_esc_attr(me.getCSSComponent('dformat'))+'">('+cff_sanitize(format_label.join(' '), true)+')</span>' : '')+'</label>'+
					'<div class="dfield"><input id="'+n+'" name="'+n+'" type="hidden" value="'+cff_esc_attr(predefined)+'"/>'+
					'<div class="cff-date-field-components '+me.size+'">'+
						'<input aria-label="'+cff_esc_attr(me.title)+'" id="'+n+'_date" name="'+n+'_date" class="'+cff_esc_attr(date_tag_class)+' date-component" type="'+date_tag_type+'" '+attr+'="'+cff_esc_attr(predefined)+'" '+disabled+(me.disableKeyboardOnMobile ? ' inputmode="none"' : '')+(me.errorMssg != '' ? ' data-msg="'+cff_esc_attr(me.errorMssg)+'"' : '')+' style="'+cff_esc_attr(me.getCSSComponent('date'))+'" />'+

						(me.alwaysVisible && me.showDatepicker ? '<div id="'+n+'_datepicker_container" class="datepicker-container"></div>' : '')+
						((me.showTimepicker) ? ' <div class="time-component">'+me.get_hours()+me.get_minutes()+' '+me.get_ampm()+'</div>' : '')+
					'</div>'+
					'<span class="uh" style="'+cff_esc_attr(me.getCSSComponent('help'))+'">'+cff_sanitize(me.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
			after_show:function()
				{
					var me = this,
						date_format = 'date'+me.dformat.replace(/[^a-z]/ig,""),
						_aux = function( e ){
							let p = e.id.replace(/_(date|hours|minutes|ampm)/i, '').split('_'),
								o = $.fbuilder.forms['_'+p[1]].getItem(p[0]);
							return o;
						},
						date_validator = function(v, e)
						{
							try
							{
								var i = _aux( e ),
									o = i._get_regexp(),
									d = $($(e).hasClass('hasDatepicker') ? e : $(e).siblings('.hasDatepicker:eq(0)')).datepicker('getDate');

								if(i) return this.optional(e) ||
								(
									i._validateDate() &&
									(new RegExp(o.r)).test(v) &&
									DATEOBJ(v, me.dformat).getTime() == d.getTime()
								);
								return true;
							}
							catch(er)
							{
								return false;
							}
						},
						time_validator = function(v, e)
						{
							try
							{
								var i = _aux( e );
								if(i && i.showTimepicker) return i._validateTime();
								return true;
							}
							catch(er)
							{
								return false;
							}
						};

                    if(!(date_format in $.validator.methods)) $.validator.addMethod(date_format, date_validator);
                    if(!('time_component' in $.validator.methods)) $.validator.addMethod('time_component', time_validator);
					$('#'+me.name+'_hours,#'+me.name+'_minutes,#'+me.name+'_ampm').each(function(){$(this).rules('add', {'time_component': true, 'messages' : {'time_component': me.timeErrorMssg}});});

					me.set_DefaultDate(true);
					me.set_DefaultTime();
					me._set_Events();
					me.set_dateTime();

					$('#'+me.name).on('change', function(){
						$('#'+me.name+'_date,#'+me.name+'_hours,#'+me.name+'_minutes,#'+me.name+'_ampm').each((i,e)=>{$(e).valid();});
					});

				},
			val:function(raw, no_quotes)
				{
					raw = raw || false;
                    no_quotes = no_quotes || false;
					var me = this,
						e  = $('[id="'+me.name+'"]:not(.ignore)'),
						o  = me._get_regexp();

					if(e.length)
					{
						var v  = e.val();
						if(raw) return $.fbuilder.parseValStr(v, raw, no_quotes);

						v  = String(e.val()).trim();
						var re = new RegExp('('+o.r+')?(\\s*(\\d{1,2})[:\\.](\\d{1,2})\\s*([amp]{2})?)?'),
							d  = re.exec(v),
							h  = 0,
							m  = 0,
							date;

						if(d)
						{
							if(typeof d[6] != 'undefined') h = d[6]*1;
							if(typeof d[7] != 'undefined') m = d[7]*1;
							if(typeof d[8] != 'undefined')
							{
								var am = d[8].toLowerCase();
								if(am == 'pm' && h < 12) h += 12;
								if(am == 'am' && h == 12) h -= 12;
							}
							switch(o.d)
							{
								case 'yyyy/dd/mm':
									date = new Date(d[2], (d[4] * 1 - 1), d[3], h, m, 0, 0);
								break;
								case 'yyyy/mm/dd':
									date = new Date(d[2], (d[3] * 1 - 1), d[4], h, m, 0, 0);
								break;
								case 'dd/mm/yyyy':
									date = new Date(d[4], (d[3] * 1 - 1), d[2], h, m, 0, 0);
								break;
								case 'mm/dd/yyyy':
									date = new Date(d[4], (d[2] * 1 - 1), d[3], h, m, 0, 0);
								break;
							}

							if(isFinite(date))
							{
								// if(me.showTimepicker) return date.valueOf()/86400000;
								// else return Math.ceil(date.valueOf()/86400000);
								return date.valueOf()/86400000;
							}
							else if(
								(
									! me.showDatepicker
									|| '' == String( $('[id="'+me.name+'_date"]').val() ).trim()
								) && me.showTimepicker
							) return (h*3600000+m*60000)/86400000;
						}
					}
					return 0;
				},
			setVal:function(v, nochange, init)
				{
					init = init || false;
					try
					{
						v = String(v).trim()
							 .replace(/\s+/g, ' ')
							 .split(' ');
						if(this.showDatepicker)
						{
							this.defaultDate = v[0];
							this.set_DefaultDate(init && ! this.defaultDate.length);
						}
						if(this.showTimepicker)
						{
							var t = (v.length == 2) ? v[1] : ((!this.showDatepicker) ? v[0] : false);
							if(t !== false)
							{
								this.defaultTime = t;
								this.set_DefaultTime();
							}
						}
						this.set_dateTime(nochange);
					}
					catch(err){}
				}
		}
	);