	$.fbuilder.controls['ftimeslots'] = function(){};
	$.extend(
		$.fbuilder.controls['ftimeslots'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Date/Timeslots",
			ftype:"ftimeslots",
			predefined:"",
			predefinedClick:false,
			size:"medium",
			required:false,
			readonly:false,
            disableKeyboardOnMobile:false,

			// Date component
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

			// Time slots component
			minHour:0,
			maxHour:23,
			minMinute:0,
			maxMinute:59,
			timeslotsDuration:"",
			sameForAllDays:true,
			preventEarlierSlots:true,
			maxSlotsPerSubmission:"",
			timeslots:[],
			timeslotsSelected:{},
			maxSlotsErrorMssg:"",

			// Error message
            errorMssg:'',
			_two_digits : function(v)
				{
					if ( ! isNaN(v*1) ) {
						v = parseInt(v);
						if (v < 10) return '0'+v;
					}
					return v;
				},
			_slot_components : function( slot )
				{
					let me = this;
					let normalize_slot = function(v) {
						const match = v.match(/^(\d{1,2})\:(\d{1,2})\s*\-\s*(\d{1,2})\:(\d{1,2})$/);
						return me._two_digits(match[1])+':'+me._two_digits(match[2])+' - '+me._two_digits(match[3])+':'+me._two_digits(match[4]);
					};
					try {
						slot = String(slot).trim();
						if ( slot == '' ) return false;
						let c = slot.split(/\:(.*)/, 2);
						if ( c.length != 2 ) return false;
						let d = c[0].trim(),
							s = c[1].trim();
						if (
							! me._validate_dateText( d ) ||
							! me._validate_slotText( s )
						) return false;
						d = DATEOBJ( d, me.dformat );
						s = normalize_slot(s);
						if ( me._validate_date( d ) && me._validate_slot(d, s) ) {
							return {
								key : d.valueOf()+'|'+s,
								slot: GETDATETIMESTRING( d,me.dformat)+': '+s
							};
						}
					} catch (err) {}
					return false;
				},
			_set_events : function()
				{
					var me = this;

					$(document).off('change', '#'+me.name).on('change', '#'+me.name, function(evt){$(evt.target).valid();});
					$(document).off('change', '#'+me.name+'_date').on('change', '#'+me.name+'_date', function(){
						if( me.alwaysVisible ) $('#'+me.name+'_datepicker_container').datepicker('setDate', this.value);
						me.set_fieldVal();
						$('#'+me.name+'_date').valid();
					});

					$('#cp_calculatedfieldsf_pform'+me.form_identifier).on('reset', function(){
						$( '#'+me.name+'-error').remove();
						$( '#'+me.name).removeClass('cpefb_error');
						me.timeslotsSelected = {};
                        setTimeout(function(){
							me.set_defaultDate(true);
							me.set_fieldVal();
						},500);
                    });

					let slots_selector = '.'+me.name+' .timeslots-component .cff-timeslot, .'+me.name+' .timeslots-selected-component .cff-timeslot-selected';
					$(document).off('click', slots_selector).on( 'click', slots_selector, function(){
						me._change_slotsStatus(this);
						me.set_fieldVal();
					})

					$('#'+me.name).off('change').on('change', function(){
						$('#'+me.name+'_date').valid();
					});

					let datepicker_selector = '#'+me.name+'_date,#'+me.name+'_datepicker_container';
					$(document).off('click', datepicker_selector).on('click', datepicker_selector, function(){ $(this).trigger('focus'); });
				},
			_validate_date: function(d)
				{
					try{
						var me = this,
							w = me.working_dates,
							i = me.invalidDates,
							v = me.validDates,
							e = ( me.alwaysVisible ) ? $('#'+me.name+'_datepicker_container') : $('#'+me.name+'_date'),
							isValid = Array.isArray(v) && v.length ? false : true;

						d = d || e.datepicker('getDate');

						if(
							d === null ||
							!w[d.getDay()]
						) return false;

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
							_i  = _d._getInst(e[0]),
							_mi = _d._determineDate(_i, _d._get(_i, 'minDate'), null),
							_ma = _d._determineDate(_i, _d._get(_i, 'maxDate'), null);

						if((_mi != null && d < _mi) || (_ma != null && _ma < d)) return false;
					}
					catch(_err){console.log(_err);return false;}
					return d;
				},
			_validate_dateText : function( v )
				{
					let me = this,
						f  = me.dformat.toLowerCase().replace(/[^dmy]/g, '/'),
						r = /^y/i.test( f ) ? /^\d{4}[^\d]\d{1,2}[^\d]\d{1,2}$/ : /^\d{1,2}[^\d]\d{1,2}[^\d]\d{4}$/;

					v = v || $('#'+me.name+'_date').val();
					if ( ! r.test(v) ) return false;

					v = v.replace(/[^\d]/g,'/').split('/');
					let y, m, d;
					switch(f) {
						case 'dd/mm/yyyy': d = v[0]*1; m = v[1]*1; y = v[2]*1; break;
						case 'mm/dd/yyyy': d = v[1]*1; m = v[0]*1; y = v[2]*1; break;
						case 'yyyy/dd/mm': d = v[1]*1; m = v[2]*1; y = v[0]*1; break;
						case 'yyyy/mm/dd': d = v[2]*1; m = v[1]*1; y = v[0]*1; break;
						default: false;
					}

					if (m < 1 || 12 < m || d < 1 || 31 < d) return false;

					let	o = new Date(y, m - 1, d);
					return o.getFullYear() === y &&
						   o.getMonth() === m - 1 &&
						   o.getDate() === d;
				},
			_validate_slot: function(d, s) // d - date, s - slot.
				{
					let me = this,
						l  = me.timeslots[d.getDay()],
						_s = me.minHour*60+me.minMinute*1,
						_e = me.maxHour*60+me.maxMinute*1,
						n  = new Date();

					if ( me.preventEarlierSlots ) {
						let ts = GETDATETIMESTRING(TODAY(), 'yyyy-mm-dd');
						let ds = GETDATETIMESTRING(d, 'yyyy-mm-dd');
						if ( ds < ts ) return false;
						if ( ds == ts ) {
							_s = MAX(_s, SUM(HOURS(NOW())*60, MINUTES(NOW())));
						}
					}

					for ( let j in l ) {
						if (s == l[j]['slot']) {
							return l[j]['active'] && _s <= l[j]['start'] && l[j]['end'] <= _e;
						}
					}
					return false;
				},
			_validate_slotText : function( s )
				{
					return /^\d{1,2}\:\d{1,2}\s*\-\s*\d{1,2}\:\d{1,2}$/.test(s);
				},
			_is_slotSelected: function(d, s) // d - date, s - slot.
				{
					if ( d instanceof Date ) d = d.valueOf();
					return (d+'|'+s) in this.timeslotsSelected;
				},
			_get_slotsSelected: function()
				{
					let me = this,
						slots = me.timeslotsSelected,
						keys  = Object.keys(slots).sort(),
						max   = me.maxSlotsPerSubmission * 1,
						result = {};

					if ( max ) keys = keys.slice(0, max);

					for ( let k in keys ) {
						try {
							let d = new Date( keys[k].split('|')[0] * 1 ),
								s = keys[k].split('|')[1];
							if ( me._validate_date(d) && me._validate_slot(d, s) ) {
								result[keys[k]] = slots[keys[k]];
							}
						} catch (err) { console.log(err); }
					}

					return result;
				},
			_change_slotsStatus: function( slot )
				{
					let me = this,
						e  = $(slot),
						d  = e.attr('data-day'),
						s  = e.attr('data-slot'),
						k  = d+'|'+s;
					if ( e.hasClass('cff-timeslot') ) {
						if ( e.attr('data-selected') * 1 ) {
							e.attr('data-selected', 0);
							delete me.timeslotsSelected[k];
							$('.'+me.name+' .cff-timeslot-selected[data-day="' + d + '"][data-slot="' + s + '"]').remove();
						} else {
							let n = me.maxSlotsPerSubmission * 1;
							if (
								n &&
								n <= Object.keys( me.timeslotsSelected ).length
							) {
								if ( me.maxSlotsErrorMssg.length ) alert( me.maxSlotsErrorMssg.replace( /\{0\}/g, n) );
								return;
							}
							d = new Date(d * 1);
							if ( me._validate_date(d) && me._validate_slot(d, s) ) {
								e.attr('data-selected', 1);
								me.timeslotsSelected[k] = GETDATETIMESTRING( d, me.dformat )+': '+s;
							}
						}
					} else {
						delete me.timeslotsSelected[k];
						$('.'+me.name+' .cff-timeslot-selected[data-day="' + d + '"][data-slot="' + s + '"]').remove();
						$('.'+me.name+' .cff-timeslot[data-day="' + d + '"][data-slot="' + s + '"]').attr('data-selected', 0);
					}
					me.set_fieldVal();
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

					// Timeslots.
					for ( let d in me.timeslots ) {
						for (  let s in me.timeslots[d] ) {
							me.timeslots[d][s]['slot'] = me._two_digits(me.timeslots[d][s]['start']/60)+':'+me._two_digits(me.timeslots[d][s]['start']%60)+' - '+me._two_digits(me.timeslots[d][s]['end']/60)+':'+me._two_digits(me.timeslots[d][s]['end']%60);
						}
					}

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

					// Set handles
					me._setHndl('minDate');
					me._setHndl('maxDate');
                },
			show_timeslots:function()
				{
					let me = this,
						output = '',
						_aux = function(v) { return ! isNaN(v) && v < 10 ? '0'+v : v; };

					if( me.timeslots.length ) {
						let timeslots = [],
							d  = me._validate_dateText() && me._validate_date();

						if ( d ) {
							let w = d.getDay(),
								timeslots = me.timeslots[w];

							for ( let i in timeslots ) {
								let _timeslot = timeslots[i],
									_active    = me._validate_slot(d, _timeslot['slot']) ? 1 : 0,
									_selected  = _active && me._is_slotSelected( d, _timeslot['slot'] ) ? 1 : 0,
									_style 	   = '';

								if ( ! _active ) _style='style="'+cff_esc_attr(me.getCSSComponent('disabledtimeslot'))+'"';
								else if ( ! _selected ) _style='style="'+cff_esc_attr(me.getCSSComponent('activetimeslot'))+'"';
								else _style='style="'+cff_esc_attr(me.getCSSComponent('selectedtimeslot'))+'"';

								output += '<span class="cff-timeslot" data-active="' + _active + '" data-selected="' + _selected + '" data-day="' + d.valueOf() + '" data-slot="' + _timeslot['slot'] + '" ' + _style + '>' + _timeslot['slot'] + '</span>';
							}
						}
					}

					$( '.' + me.name + ' .timeslots-component' ).html( output );
				},
			show_timeslotsSelected:function()
				{
					let me 		= this,
						slots 	= me._get_slotsSelected(),
						output 	= '',
						_style	='style="'+cff_esc_attr(me.getCSSComponent('selectedtimeslot'))+'"';

					for ( let i in slots ) {
						try {
							output += '<span class="cff-timeslot-selected" data-day="' + i.split('|')[0] + '" data-slot="' + i.split('|')[1] + '" ' + _style + '>' + slots[i] + '</span>';
						} catch (err) { console.log(err); }
					}

					$( '.' + me.name + ' .timeslots-selected-component' ).html( output );
				},
			set_fieldVal:function(nochange)
				{
					var me    = this,
						e     = $('#'+me.name),
						bk    = e.val(),
						str   = '',
						sep   = '',
						slots = me._get_slotsSelected();

					for ( let i in slots ) {
						try {
							str += sep + slots[i];
							sep = ', ';
						} catch (err) { console.log(err); }
					}

					e.val(str);

                    if ( ( typeof nochange == 'undefined' || ! nochange ) &&  bk !== str ) e.trigger('change');

					me.show_timeslots();
					me.show_timeslotsSelected();

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
						this.set_fieldVal();
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
						this.set_fieldVal();
					}
				},
			set_defaultDate : function(init)
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
						dd = me.currentDate && init ? new Date() : me.defaultDate,
						predefined = me._getAttr('predefined') || '';

					if( me.alwaysVisible ) {
						dp = $("#"+me.name+"_datepicker_container");
						p['altField'] = $("#"+me.name+"_date");
						p['altFormat'] = p['dateFormat'];
						p['onSelect'] = function( dateText, inst ){
							$("#"+me.name+"_date").trigger('change');
						};
					}

					if(me.showDropdown) p = $.extend(p,{changeMonth: true,changeYear: true,yearRange: me.dropdownRange});
					p = $.extend(p, {beforeShowDay:function(d){return [me._validate_date(d), ""];}});
					if(me.defaultDate != "") p.defaultDate = me.defaultDate;

					if ( dp.length ) {
						try {
							dp.datepicker(p);
						} catch(err) {}
						dp.datepicker("setDate", dd);
						if(!me._validate_date()){
							dp.datepicker("setDate", ''); $("#"+me.name+"_datepicker_container .ui-state-active").removeClass('ui-state-active');
						}
					}

					if ( me.predefinedClick ) {
						$("#"+me.name+"_date").attr('placeholder', predefined );
					} else {
						me.setVal( predefined );
					}
				},
			show:function()
				{
                    var me				= this,
						n 				= me.name,
						format_label   	= [],
						date_tag_type  	= 'text',
						disabled		= '';

                    if(! me.alwaysVisible) format_label.push(me.dformat);
					else{ date_tag_type = 'hidden'; if( ! me.alwaysVisible ) disabled='disabled';}
					let predefined = this._getAttr('predefined') || '';
					return '<div class="fields '+cff_esc_attr(me.csslayout)+' '+n+' cff-timeslots-field" id="field'+me.form_identifier+'-'+me.index+'" style="'+cff_esc_attr(me.getCSSComponent('container'))+'">'+
					'<label for="'+n+'_date" style="'+cff_esc_attr(me.getCSSComponent('label'))+'">'+cff_sanitize(me.title, true)+''+((me.required)?"<span class='r'>*</span>":"")+((format_label.length && me.showFormatOnLabel) ? ' <span class="dformat" style="'+cff_esc_attr(me.getCSSComponent('dformat'))+'">('+cff_sanitize(format_label.join(' '), true)+')</span>' : '')+'</label>'+
					'<div class="dfield"><input id="'+n+'" name="'+n+'" type="hidden" value="'+cff_esc_attr(predefined)+'" class="'+( ( me.required ) ? ' required': '' )+'" />'+
					'<div class="cff-date-field-components '+me.size+'">'+
						'<input aria-label="'+cff_esc_attr(me.title)+'" id="'+n+'_date" name="'+n+'_date" class="field timeslots'+me.dformat.replace(/[^dmy]/ig,"")+' date-component" type="'+date_tag_type+'" '+disabled+(me.disableKeyboardOnMobile ? ' inputmode="none"' : '')+'data-msg="'+cff_esc_attr(me.errorMssg)+'" style="'+cff_esc_attr(me.getCSSComponent('date'))+'" />'+
						(me.alwaysVisible ? '<div id="'+n+'_datepicker_container" class="datepicker-container"></div>' : '')+
						'<div class="timeslots-component"></div>'+
						'<div class="timeslots-selected-component"></div>'+
					'</div>'+
					'<span class="uh" style="'+cff_esc_attr(me.getCSSComponent('help'))+'">'+cff_sanitize(me.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
			after_show:function()
				{
					var me = this,
						date_format = 'timeslots'+me.dformat.replace(/[^dmy]/ig,"");

					// Time slot validator.
                    if(!('timeslots' in $.validator.methods)) {
						$.validator.addMethod('timeslots', function(v, e, p) {
							try{
								return this.optional(e) || ! p || String(v).split(',').length <= p;
							}
							catch(err){return true;}
						});
					}
					if(!(date_format in $.validator.methods)) {
						$.validator.addMethod(date_format, function(v, e) {
							try
							{
								let f = getField(e.id.replace(/_date$/i, ''));
									d = $($(e).hasClass('hasDatepicker') ? e : $(e).siblings('.hasDatepicker:eq(0)')).datepicker('getDate');

								return this.optional(e) ||
								(
									f._validate_dateText(v) &&
									f._validate_date(d)
								);
							}
							catch(er)
							{
								return false;
							}
						});
					}

					$('#'+me.name).rules('add',{'timeslots':me.maxSlotsPerSubmission*1, messages:{'timeslots':cff_sanitize(me.maxSlotsErrorMssg, true)}});

                    me.set_defaultDate(true);
					me._set_events();
					me.set_fieldVal();
				},
			addSlot:function(slot,nochange)
				{
					let me = this;
					slot = me._slot_components(slot);
					if ( slot ) {
						me.timeslotsSelected[slot['key']] = slot['slot'];
						me.set_fieldVal(nochange);
					}
				},
			deleteSlot: function(slot, nochange)
				{
					let me = this;
					slot = me._slot_components(slot);
					if ( slot ) {
						delete me.timeslotsSelected[slot['key']];
						me.set_fieldVal(nochange);
					}
				},
			deleteSlots: function(nochange)
				{
					this.timeslotsSelected = {};
					this.set_fieldVal(nochange);
				},
			val:function(raw, no_quotes)
				{
					raw = raw || false;
                    no_quotes = no_quotes || false;
					var me = this,
						e  = $('[id="'+me.name+'"]:not(.ignore)');

					if(e.length)
					{
						var v  = e.val();
						if(raw) return $.fbuilder.parseValStr(v, raw, no_quotes);

						// timeslots
						return Object.values(me._get_slotsSelected());
					}
					return 0;
				},
			setVal:function(v, nochange)
				{
					let me = this;
					try
					{
						if ( typeof v == 'string') {
							v = String(v).trim().replace(/\s+/g, ' ').split(',');
						}

						if (Array.isArray(v)) {
							for ( let i in v ) {
								me.addSlot( v[i], nochange );
							}
						}
					}
					catch(err){}
				}
		}
	);