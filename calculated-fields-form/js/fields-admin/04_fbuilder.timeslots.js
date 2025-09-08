	$.fbuilder.typeList.push(
		{
			id:"ftimeslots",
			name:"Date/Timeslots",
			control_category:1
		}
	);
	$.fbuilder.controls[ 'ftimeslots' ] = function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'ftimeslots' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"Date/Timeslots",
			ftype:"ftimeslots",
			predefined:"",
			predefinedClick:false,
			size:"medium",
			required:false,
			exclude:false,
			readonly:false,
            disableKeyboardOnMobile:false,
			dformat:"mm/dd/yyyy",
			showFormatOnLabel:1,
			dseparator:"/",
			showDropdown:false,
			dropdownRange:"-10:+10",

			minDate:"",
			maxDate:"",
            invalidDates:"",
            validDates:"",
            mondayFirstDay:false,
            alwaysVisible:false,
			minHour:0,
			maxHour:23,
			minMinute:0,
			maxMinute:59,

			currentDate:0,
			defaultDate:"",
			defaultTime:"",
			working_dates:[true,true,true,true,true,true,true],

			formats:['mm/dd/yyyy','dd/mm/yyyy','yyyy/mm/dd','yyyy/dd/mm'],
			separators: ['/','-','.'],

            errorMssg:'',

			timeslotsDuration:"",
			sameForAllDays:true,
			preventEarlierSlots:true,
			maxSlotsPerSubmission:"",
			maxSlotsErrorMssg:"",
			timeslots:[],

			initAdv: function() {
				delete this.advanced.css['input'];
				if ( ! ( 'date' in this.advanced.css ) ) this.advanced.css.date = {label: 'Date field',rules:{}};
				if ( ! ( 'dformat' in this.advanced.css ) ) this.advanced.css.dformat = {label: 'Date format label',rules:{}};
				if ( ! ( 'activetimeslot' in this.advanced.css ) ) this.advanced.css.activetimeslot = {label: 'Active timeslot',rules:{}};
				if ( ! ( 'selectedtimeslot' in this.advanced.css ) ) this.advanced.css.selectedtimeslot = {label: 'Selected timeslot',rules:{}};
				if ( ! ( 'disabledtimeslot' in this.advanced.css ) ) this.advanced.css.disabledtimeslot = {label: 'Disabled timeslot',rules:{}};
			},
			display:function( css_class )
				{
					css_class = css_class || '';
					let me = this,
						dformat = me.dformat.replace(/\//g, me.dseparator);
					let id = 'field'+me.form_identifier+'-'+me.index;
					return '<div class="fields '+me.name+' '+me.ftype+' '+css_class+'" id="'+id+'" title="'+me.controlLabel('Date/Timeslots')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+me.iconsContainer()+'<label for="'+id+'-box">'+cff_sanitize(me.title, true)+''+((me.required)?"*":"")+' ('+dformat+')</label><div class="dfield">'+me.showColumnIcon()+'<input id="'+id+'-box" class="field disabled '+me.size+'" type="text" value="'+cff_esc_attr(me.predefined)+'"/><div class="'+me.size+'">'+me.displayTimeslots( true )+'</div><span class="uh">'+cff_sanitize(me.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
					var me = this,
						evt = [
							{s:"#sDropdownRange",e:"keyup", l:"dropdownRange", x:1},
							{s:"#sFormat",e:"change", l:"dformat", x:1},
							{s:"#sSeparator",e:"change", l:"dseparator", x:1},
							{s:"#sShowFormatOnLabel",e:"click", l:"showFormatOnLabel", f:function(el){return el.is(':checked');}},
							{s:"#sMinDate",e:"change keyup", l:"minDate", x:1},
							{s:"#sMaxDate",e:"change keyup", l:"maxDate", x:1},
							{s:"#sInvalidDates",e:"change keyup", l:"invalidDates", x:1},
							{s:"#sValidDates",e:"change keyup", l:"validDates", x:1},
							{s:"#sErrorMssg",e:"change keyup", l:"errorMssg", x:1},
							{s:"#sDefaultDate",e:"change keyup", l:"defaultDate", x:1},
							{s:"#sShowDropdown",e:"click", l:"showDropdown", f:function(el){
								var v = el.is(':checked');
								$("#divdropdownRange")[( v ) ? 'show' : 'hide']();
								return v;
								}
							},
							{s:"#sCurrentDate",e:"click", l:"currentDate", f:function(el){
								var v = el.is(':checked');
								$('#sDefaultDate').prop('readonly', v ? 1 : 0);
								return v;
								}
							},
							{s:"#sMaxSlots",e:"change keyup", l:"maxSlotsPerSubmission", f:function(el){
								let v = String(el.val()).trim();
								if ( ! isNaN(v) ) {
									v = Math.max(parseInt(v), 0);
								}
								return v ? v : '';
							}, x:1},
							{s:"#sTimeslotsDuration",e:"change keyup", l:"timeslotsDuration", f:function(el){
								let v = String(el.val()).trim();
								if ( ! isNaN(v) ) {
									v = Math.max(parseInt(v), 0);
								}
								return v ? v : '';
							}, x:1},
							{s:"#sMaxSlotsErrorMssg",e:"change keyup", l:"maxSlotsErrorMssg"},
							{s:"#sSameForAllDays",e:"click", l:"sameForAllDays", f:function(el){return el.is(':checked');}},
							{s:"#sPreventEarlierSlots",e:"click", l:"preventEarlierSlots", f:function(el){return el.is(':checked');}},
							{s:"#sDisableKeyboardOnMobile",e:"click", l:"disableKeyboardOnMobile", f:function(el){return el.is(':checked');}},
							{s:"#sMondayFirstDay",e:"click", l:"mondayFirstDay", f:function(el){return el.is(':checked');}},
							{s:"#sAlwaysVisible",e:"click", l:"alwaysVisible", f:function(el){return el.is(':checked');}},
							{s:"#sMinHour",e:"keyup", l:"minHour", x:1},
							{s:"#sMaxHour",e:"keyup", l:"maxHour", x:1},
							{s:"#sMinMinute",e:"keyup", l:"minMinute", x:1},
							{s:"#sMaxMinute",e:"keyup", l:"maxMinute", x:1},
							{s:"#sMinHour",e:"change", l:"minHour", f:function(el){
								let v = el.val();
								if( isNaN(v*1) ) { el.val(0); return 0; }
								let bk = v*1;
								v = Math.min(23, Math.max(0,bk));
								if(me.maxHour !== '' && !isNaN(me.maxHour*1)) {
									me.maxHour = Math.max(v, me.maxHour*1);
									$('#sMaxHour').val(me.maxHour);
								}
								if( v != bk ) el.val(v);
								return v;
							}, x:1},
							{s:"#sMaxHour",e:"change", l:"maxHour", f:function(el){
								let v = el.val();
								if( isNaN(v*1) ) { el.val(23); return 23; }
								let bk = v*1;
								v = Math.min(23, Math.max(0,bk));
								if(me.minHour !== '' && !isNaN(me.minHour*1)) {
									me.minHour = Math.min(v, me.minHour*1);
									$('#sMinHour').val(me.minHour);
								}
								if( v != bk ) el.val(v);
								return v;
							}, x:1},
							{s:"#sMinMinute",e:"change", l:"minMinute",  f:function(el){
								let v = el.val();
								if( isNaN(v*1) ) { el.val(0); return 0; }
								let bk = v*1;
								v = Math.min(59, Math.max(0,bk));
								if( v != bk ) el.val(v);
								return v;
							}, x:1},
							{s:"#sMaxMinute",e:"change", l:"maxMinute", f:function(el){
								let v = el.val();
								if( isNaN(v*1) ) { el.val(50); return 59; }
								let bk = v*1;
								v = Math.min(59, Math.max(0,bk));
								if( v != bk ) el.val(v);
								return v;
							}, x:1}
						];
					$(".working_dates input").on("click", {obj: this}, function(e) {
						e.data.obj.working_dates[$(this).val()] = $(this).is(':checked');
						$.fbuilder.reloadItems({'field':e.data.obj});
					});

					$(".working_dates input, #sMinHour, #sMinMinute, #sMaxHour, #sMaxMinute, #sTimeslotsDuration, #sSameForAllDays").on("input", function() {
						setTimeout(function(){
							me.generateTimeslots();
							$.fbuilder.reloadItems({'field':me});
						}, 500);
					});

					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this, evt);
				},
			generateTimeslots: function()
				{
					let duration 	= parseInt(this.timeslotsDuration);
					let week_days   = this.working_dates;
					let all_days    = this.sameForAllDays;
					let bk_timeslots= this.timeslots;
					let timeslots 	= [];

					if ( duration ) {
						for ( let i = 0, h = week_days.length; i < h; i++ ) {
							let timeslots_day = [];
							let start = this.minHour * 60 + this.minMinute * 1;
							let end = this.maxHour * 60 + this.maxMinute * 1;
							let bk_index = 0;
							let bk_length = i in bk_timeslots ? bk_timeslots[i].length : 0;

							while ( start < end ) {
								let _start 		= start;
								let _end   		= Math.min(_start+duration, end);
								let _active     = true;
								while ( bk_index < bk_length ) {
									if (
										bk_timeslots[i][bk_index]['start'] <= _start && _start < bk_timeslots[i][bk_index]['end'] ||
										_start <= bk_timeslots[i][bk_index]['start'] && bk_timeslots[i][bk_index]['end'] <= _end ||
										bk_timeslots[i][bk_index]['start'] < _end && _end <= bk_timeslots[i][bk_index]['end']
									) {
										_active = bk_timeslots[i][bk_index]['active'];
										break;
									}

									bk_index++;
								}

								let _timeslot 	= {start: _start, end : _end, duration: _end - _start, active: _active };

								timeslots_day.push( _timeslot );
								start = _end;
							}
							timeslots.push( timeslots_day );
							if ( all_days ) {
								timeslots.push( timeslots_day );
								timeslots.push( timeslots_day );
								timeslots.push( timeslots_day );
								timeslots.push( timeslots_day );
								timeslots.push( timeslots_day );
								timeslots.push( timeslots_day );
								break;
							}
						} // End timeslots generation.
					}

					this.timeslots = timeslots;
					this.displayTimeslots();
				},
			displayTimeslots: function( display )
				{
					display = display || false;

					let me  		= this;
					let output		= '';
					let week_days   = me.working_dates;
					let all_days    = me.sameForAllDays;
					let timeslots 	= me.timeslots;
					let days_names  = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
					let _aux		= function(v) { return ! isNaN(v) && v < 10 ? '0'+v : v; };

					if ( week_days.filter(item=>item == true).length ) {
						for ( let i = 0, h = timeslots.length; i < h; i++ ) {
							let timeslots_day = timeslots[i];
							let _output = '';
							for ( let j = 0, k = timeslots_day.length; j < k; j++ ) {
								let _timeslot  = timeslots_day[j];
								let _start_txt = _aux(Math.floor( _timeslot.start / 60 )) + ':' + _aux( _timeslot.start % 60 );
								let _end_txt   = _aux(Math.floor( _timeslot.end / 60 )) + ':' + _aux( _timeslot.end % 60 );
								_output += '<span class="cff-timeslot" data-active="' + (_timeslot.active && ! display ? 1 : 0) + '" data-day="' + i + '" data-slot="' + j + '">' + _start_txt + ' - ' + _end_txt + '</span>';
							}

							if ( ! display ) {
								if ( all_days ) {
									output += '<label style="font-weight:bold;">Every active day slots</label><div class="cff-timeslots-container">' + _output + '</div>';
									break;
								} else if ( week_days[i] ) {
									if ( !i ) {
										_output += '<div style="padding-top:10px;text-align:right;"><input type="button" class="button-secondary cff-duplicate-slots" value="Apply these settings to every weekday"></div>';
									}
									output += '<label>' + days_names[i] + '</label><div>' + _output + '</div>';
								}
							} else {
								output += _output;
								break;
							}
						}
					}

					if ( ! display ) {
						$(document).off( 'click', '.cff-timeslot' ).on( 'click', '.cff-timeslot', function() {
							let e = $(this);
							let d = e.attr('data-day');
							let s = e.attr('data-slot');
							let a = Math.abs(e.attr('data-active') - 1);

							if(all_days) {
								for ( let i = 0, h = week_days.length; i < h; i++ ) {
									me.timeslots[i][s]['active'] = a;
								}
							} else {
								me.timeslots[d][s]['active'] = a;
							}
							$.fbuilder.reloadItems({'field':me});
							e.attr('data-active', me.timeslots[d][s]['active']);
						});

						$(document).off( 'click', '.cff-reset-slots' ).on( 'click', '.cff-reset-slots', function(){
							for( let i in me.timeslots ) {
								for ( let j in me.timeslots[i] ) {
									me.timeslots[i][j]['active'] = true;
									$('[data-active]').attr('data-active', 1);
								}
							}
							$.fbuilder.reloadItems({'field':me});
						});

						$(document).off( 'click', '.cff-duplicate-slots' ).on( 'click', '.cff-duplicate-slots', function(){
							for( let i = 1, h = me.timeslots.length; i < h; i++ ) {
								me.timeslots[i] = JSON.parse(JSON.stringify(me.timeslots[0]));
							}
							$.fbuilder.reloadItems({'field':me});
							me.displayTimeslots();
						});

						if ( output != '' ) {
							output = '<div style="padding-top:10px;"><input type="button" class="button-secondary cff-reset-slots" value="Reset Inactive Slots"></div>' + output;
						} else {
							output += '<p>Please ensure that you have selected active days for the week, entered the correct minimum and maximum time intervals, and specified the slot duration.</p>';
						}
						$('.timeslots-container').html( output );
					}
					return output;
			},
			showFormatIntance: function()
				{
					var me = this,
						formatOpts = "",
						separatorOpts = "";

					for (var i in me.formats)
						formatOpts += '<option value="'+cff_esc_attr(me.formats[i])+'" '+((me.formats[i]==me.dformat)?"selected":"")+'>'+cff_esc_attr(me.formats[i])+'</option>';

					for (var i in me.separators)
						separatorOpts += '<option value="'+cff_esc_attr(me.separators[i])+'" '+((me.separators[i]==me.dseparator)?"selected":"")+'>'+cff_esc_attr(me.separators[i])+'</option>';

					return '<div class="width40 column"><label for="sFormat">Date Format</label><select name="sFormat" id="sFormat" class="large">'+formatOpts+'</select></div>'+
					'<div class="width25 column"><label for="sSeparator">Parts separator</label><select name="sSeparator" id="sSeparator" class="large">'+separatorOpts+'</select></div>'+
					'<div class="width25 column" style="margin-top: 25px;margin-left: 3%;"><label><input type="checkbox" id="sShowFormatOnLabel" name="sShowFormatOnLabel" '+(me.showFormatOnLabel ? 'CHECKED' : '' )+'>Show on label</label></div>'+
					'<div class="clearer"></div>';
				},
			showSlotsInstance: function()
				{
					return '<div class="width50 column"><label for="sTimeslotsDuration">Slots Duration In Minutes</label><input type="text" class="large" name="sTimeslotsDuration" id="sTimeslotsDuration" value="'+cff_esc_attr(this.timeslotsDuration)+'" /></div>'+
					'<div class="width50 columnr"><label for="sMaxSlots">Max Slots Per Submission</label><input type="text" class="large" name="sMaxSlots" id="sMaxSlots" value="'+cff_esc_attr(this.maxSlotsPerSubmission)+'" /></div><div class="clearer"></div>'+
					'<label for="sMaxSlotsErrorMssg">Max Slots Per Submission Error Message</label><input type="text" class="large" name="sMaxSlotsErrorMssg" id="sMaxSlotsErrorMssg" value="'+cff_esc_attr(this.maxSlotsErrorMssg)+'" />'+
					'<label><input type="checkbox" name="sPreventEarlierSlots" id="sPreventEarlierSlots" '+( ( this.preventEarlierSlots ) ? 'CHECKED' : '' )+' > Prevent selection of time slots earlier than the current time</label>'+
					'<label><input type="checkbox" name="sSameForAllDays" id="sSameForAllDays" '+( ( this.sameForAllDays ) ? 'CHECKED' : '' )+' > Apply the same time slots for every active day on the week</label>'+
					'<div class="timeslots-container">'+
					this.displayTimeslots()+
					'</div>';
				},
			showSpecialDataInstance: function()
				{
					return '<label><input type="checkbox" name="sDisableKeyboardOnMobile" id="sDisableKeyboardOnMobile" '+( ( this.disableKeyboardOnMobile ) ? 'CHECKED' : '' )+' > Disable keboard on mobiles</label>'+

                    '<label><input type="checkbox" name="sMondayFirstDay" id="sMondayFirstDay" '+( ( this.mondayFirstDay ) ? 'CHECKED' : '' )+' > Make Monday the first day of the week</label>'+

                    '<label><input type="checkbox" name="sAlwaysVisible" id="sAlwaysVisible" '+( ( this.alwaysVisible ) ? 'CHECKED' : '' )+' > Make calendar always visible</label>'+

                    '<label for="sDefaultDate">Default date [<a class="helpfbuilder" text="You can put one of the following type of values into this field:\n\nEmpty: Leave empty for current date.\n\nDate: A Fixed date with the same date format indicated in the &quot;Date Format&quot; drop-down field.\n\nNumber: A number of days from today. For example 2 represents two days from today and -1 represents yesterday.\n\nString: A smart text indicating a relative date. Relative dates must contain value (number) and period pairs; valid periods are &quot;y&quot; for years, &quot;m&quot; for months, &quot;w&quot; for weeks, and &quot;d&quot; for days. For example, &quot;+1m +7d&quot; represents one month and seven days from today.">help?</a>]</label>'+
					'<label style="padding-top:5px;padding-bottom:5px;"><input type="checkbox" name="sCurrentDate" id="sCurrentDate" '+( this.currentDate ? 'CHECKED' : '' )+'> Current date</label>'+
					'<input type="text" class="large" name="sDefaultDate" id="sDefaultDate" value="'+cff_esc_attr(this.defaultDate)+'" '+( this.currentDate ? 'readonly' : '' )+' /><i>(0, 0d or +0d represent the current date)</i>'+

					'<label for="sMinDate">Min date [<a class="helpfbuilder" text="You can put one of the following type of values into this field:\n\nEmpty: No min Date.\n\nDate: A Fixed date with the same date format indicated in the &quot;Date Format&quot; drop-down field.\n\nField Name: the name of another date field, Ex: fieldname1\n\nNumber: A number of days from today. For example 2 represents two days from today and -1 represents yesterday.\n\nString: A smart text indicating a relative date. Relative dates must contain value (number) and period pairs; valid periods are &quot;y&quot; for years, &quot;m&quot; for months, &quot;w&quot; for weeks, and &quot;d&quot; for days. For example, &quot;+1m +7d&quot; represents one month and seven days from today.">help?</a>]</label><input type="text" class="large" name="sMinDate" id="sMinDate" value="'+cff_esc_attr(this.minDate)+'" />'+

					'<label for="sMaxDate">Max date [<a class="helpfbuilder" text="You can put one of the following type of values into this field:\n\nEmpty: No max Date.\n\nDate: A Fixed date with the same date format indicated in the &quot;Date Format&quot; drop-down field.\n\nField Name: the name of another date field, Ex: fieldname1\n\nNumber: A number of days from today. For example 2 represents two days from today and -1 represents yesterday.\n\nString: A smart text indicating a relative date. Relative dates must contain value (number) and period pairs; valid periods are &quot;y&quot; for years, &quot;m&quot; for months, &quot;w&quot; for weeks, and &quot;d&quot; for days. For example, &quot;+1m +7d&quot; represents one month and seven days from today.">help?</a>]</label><input type="text" class="large" name="sMaxDate" id="sMaxDate" value="'+cff_esc_attr(this.maxDate)+'" />'+

                    '<label for="sValidDates">Valid Dates [<a class="helpfbuilder" text="To define some dates as valid, enter the dates with the format: mm/dd/yyyy separated by comma; for example: 12/31/2014,02/20/2014 or by hyphen for intervals; for example: 12/20/2014-12/28/2014 ">help?</a>]</label><input type="text" class="large" name="sValidDates" id="sValidDates" value="'+cff_esc_attr(this.validDates)+'" />'+

                    '<label for="sInvalidDates">Invalid Dates [<a class="helpfbuilder" text="To define some dates as invalid, enter the dates with the format: mm/dd/yyyy separated by comma; for example: 12/31/2014,02/20/2014 or by hyphen for intervals; for example: 12/20/2014-12/28/2014 ">help?</a>]</label><input type="text" class="large" name="sInvalidDates" id="sInvalidDates" value="'+cff_esc_attr(this.invalidDates)+'" />'+

                    '<label for="sErrorMssg">Invalid Dates Error Message</label><input type="text" class="large" name="sErrorMssg" id="sErrorMssg" value="'+cff_esc_attr(this.errorMssg)+'" />'+

                    '<label><input type="checkbox" name="sShowDropdown" id="sShowDropdown" '+((this.showDropdown)?"checked":"")+'/> Show Dropdown Year and Month</label><div id="divdropdownRange" style="display:'+((this.showDropdown)?"":"none")+'">Year Range [<a class="helpfbuilder" text="The range of years displayed in the year drop-down: either relative to today\'s year (&quot;-nn:+nn&quot;), absolute (&quot;nnnn:nnnn&quot;), or combinations of these formats (&quot;nnnn:-nn&quot;)">help?</a>]: <input aria-label="Dropdown range" type="text" name="sDropdownRange" id="sDropdownRange" value="'+cff_esc_attr(this.dropdownRange)+'"/></div>'+

					'<div class="working_dates"><label>Selectable dates </label><input aria-label="Sunday" name="sWD0" id="sWD0" value="0" type="checkbox" '+((this.working_dates[0])?"checked":"")+'/>Su<input aria-label="Monday" name="sWD1" id="sWD1" value="1" type="checkbox" '+((this.working_dates[1])?"checked":"")+' />Mo<input aria-label="Tuesday" name="sWD2" id="sWD2" value="2" type="checkbox" '+((this.working_dates[2])?"checked":"")+' />Tu<input aria-label="Wednesday" name="sWD3" id="sWD3" value="3" type="checkbox" '+((this.working_dates[3])?"checked":"")+' />We<input aria-label="Thursday" name="sWD4" id="sWD4" value="4" type="checkbox" '+((this.working_dates[4])?"checked":"")+' />Th<input aria-label="Friday" name="sWD5" id="sWD5" value="5" type="checkbox" '+((this.working_dates[5])?"checked":"")+' />Fr<input aria-label="Saturday" name="sWD6" id="sWD6" value="6" type="checkbox" '+((this.working_dates[6])?"checked":"")+' />Sa</div>'+

					// Fields for timeslots
					'<hr></hr>'+
					'<div class="width50 column"><label for="sMinHour">Min Hour</label><input type="number" class="large" name="sMinHour" id="sMinHour" value="'+cff_esc_attr(this.minHour)+'" /></div>'+
					'<div class="width50 columnr"><label for="sMinMinute">Min Minutes</label><input type="number" class="large" name="sMinMinute" id="sMinMinute" value="'+cff_esc_attr(this.minMinute)+'" /></div>'+
					'<div class="width50 column"><label for="sMaxHour">Max Hour</label><input type="number" class="large" name="sMaxHour" id="sMaxHour" value="'+cff_esc_attr(this.maxHour)+'" /></div>'+
					'<div class="width50 columnr"><label for="sMaxMinute">Max Minutes</label><input type="number" class="large" name="sMaxMinute" id="sMaxMinute" value="'+cff_esc_attr(this.maxMinute)+'" /></div><div class="clearer"></div>'+
					this.showSlotsInstance()+
					'<hr></hr>';
				}
	});