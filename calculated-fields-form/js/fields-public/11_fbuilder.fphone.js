	$.fbuilder.controls['fPhone']=function(){};
	$.extend(
		$.fbuilder.controls['fPhone'].prototype,
		$.fbuilder.controls['ffields'].prototype,
		{
			title:"Phone",
			ftype:"fPhone",
			required:false,
			readonly:false,
			size:"medium",
			dformat:"### ### ####",
			predefined:"888 888 8888",

            countryComponent:false,
            toDisplay:'iso',
            countries:[],
            defaultCountry:'',
			dynamic: false,

			country_db: {"AF":{"prefix":"+93","min":9,"max":9},"AX":{"prefix":"+358","min":5,"max":12},"AL":{"prefix":"+355","min":3,"max":9},"DZ":{"prefix":"+213","min":8,"max":9},"AS":{"prefix":"+1684","min":10,"max":10},"AD":{"prefix":"+376","min":6,"max":9},"AO":{"prefix":"+244","min":9,"max":9},"AI":{"prefix":"+1264","min":10,"max":10},"AQ":{"prefix":"+672","min":10,"max":10},"AG":{"prefix":"+1268","min":10,"max":10},"AR":{"prefix":"+54","min":10,"max":10},"AM":{"prefix":"+374","min":8,"max":8},"AW":{"prefix":"+297","min":7,"max":7},"AU":{"prefix":"+61","min":5,"max":9},"AT":{"prefix":"+43","min":4,"max":13},"AZ":{"prefix":"+994","min":8,"max":9},"BS":{"prefix":"+1242","min":10,"max":10},"BH":{"prefix":"+973","min":8,"max":8},"BD":{"prefix":"+880","min":6,"max":10},"BB":{"prefix":"+1246","min":10,"max":10},"BY":{"prefix":"+375","min":9,"max":10},"BE":{"prefix":"+32","min":8,"max":9},"BZ":{"prefix":"+501","min":7,"max":7},"BJ":{"prefix":"+229","min":8,"max":8},"BM":{"prefix":"+1441","min":10,"max":10},"BT":{"prefix":"+975","min":7,"max":8},"BO":{"prefix":"+591","min":8,"max":8},"BA":{"prefix":"+387","min":8,"max":8},"BW":{"prefix":"+267","min":7,"max":8},"BV":{"prefix":"+47","min":5,"max":8},"BR":{"prefix":"+55","min":10,"max":10},"IO":{"prefix":"+246","min":7,"max":7},"BN":{"prefix":"+673","min":7,"max":7},"BG":{"prefix":"+359","min":7,"max":9},"BF":{"prefix":"+226","min":8,"max":8},"BI":{"prefix":"+257","min":8,"max":8},"KH":{"prefix":"+855","min":8,"max":8},"CM":{"prefix":"+237","min":8,"max":8},"CA":{"prefix":"+1","min":10,"max":10},"CV":{"prefix":"+238","min":7,"max":7},"KY":{"prefix":"+1345","min":10,"max":10},"CF":{"prefix":"+236","min":8,"max":8},"TD":{"prefix":"+235","min":8,"max":8},"CL":{"prefix":"+56","min":8,"max":9},"CN":{"prefix":"+86","min":5,"max":12},"CX":{"prefix":"+61","min":5,"max":9},"CC":{"prefix":"+61","min":5,"max":9},"CO":{"prefix":"+57","min":8,"max":10},"KM":{"prefix":"+269","min":7,"max":7},"CG":{"prefix":"+242","min":5,"max":9},"CD":{"prefix":"+243","min":5,"max":9},"CK":{"prefix":"+682","min":5,"max":5},"CR":{"prefix":"+506","min":8,"max":8},"CI":{"prefix":"+225","min":8,"max":8},"HR":{"prefix":"+385","min":8,"max":12},"CU":{"prefix":"+53","min":8,"max":8},"CY":{"prefix":"+357","min":8,"max":11},"CZ":{"prefix":"+420","min":4,"max":12},"DK":{"prefix":"+45","min":8,"max":8},"DJ":{"prefix":"+253","min":6,"max":6},"DM":{"prefix":"+1767","min":10,"max":10},"DO":{"prefix":"+1849","min":7,"max":7},"EC":{"prefix":"+593","min":8,"max":8},"EG":{"prefix":"+20","min":7,"max":9},"SV":{"prefix":"+503","min":7,"max":11},"GQ":{"prefix":"+240","min":9,"max":9},"ER":{"prefix":"+291","min":7,"max":7},"EE":{"prefix":"+372","min":7,"max":10},"ET":{"prefix":"+251","min":9,"max":9},"FK":{"prefix":"+500","min":5,"max":5},"FO":{"prefix":"+298","min":6,"max":6},"FJ":{"prefix":"+679","min":7,"max":7},"FI":{"prefix":"+358","min":5,"max":12},"FR":{"prefix":"+33","min":9,"max":9},"GF":{"prefix":"+594","min":9,"max":9},"PF":{"prefix":"+689","min":6,"max":6},"TF":{"prefix":"+262","min":9,"max":9},"GA":{"prefix":"+241","min":6,"max":7},"GM":{"prefix":"+220","min":7,"max":7},"GE":{"prefix":"+995","min":9,"max":9},"DE":{"prefix":"+49","min":6,"max":13},"GH":{"prefix":"+233","min":5,"max":9},"GI":{"prefix":"+350","min":8,"max":8},"GR":{"prefix":"+30","min":10,"max":10},"GL":{"prefix":"+299","min":6,"max":6},"GD":{"prefix":"+1473","min":10,"max":10},"GP":{"prefix":"+590","min":9,"max":9},"GU":{"prefix":"+1671","min":10,"max":10},"GT":{"prefix":"+502","min":8,"max":8},"GG":{"prefix":"+44","min":7,"max":10},"GN":{"prefix":"+224","min":8,"max":8},"GW":{"prefix":"+245","min":9,"max":9},"GY":{"prefix":"+592","min":7,"max":7},"HT":{"prefix":"+509","min":8,"max":8},"HM":{"prefix":"+672","min":8,"max":8},"VA":{"prefix":"+379","min":10,"max":10},"HN":{"prefix":"+504","min":8,"max":8},"HK":{"prefix":"+852","min":4,"max":9},"HU":{"prefix":"+36","min":8,"max":9},"IS":{"prefix":"+354","min":7,"max":9},"IN":{"prefix":"+91","min":7,"max":10},"ID":{"prefix":"+62","min":5,"max":10},"IR":{"prefix":"+98","min":6,"max":10},"IQ":{"prefix":"+964","min":8,"max":10},"IE":{"prefix":"+353","min":7,"max":11},"IM":{"prefix":"+44","min":7,"max":10},"IL":{"prefix":"+972","min":8,"max":9},"IT":{"prefix":"+39","min":11,"max":11},"JM":{"prefix":"+1876","min":10,"max":10},"JP":{"prefix":"+81","min":10,"max":10},"JE":{"prefix":"+44","min":7,"max":10},"JO":{"prefix":"+962","min":5,"max":9},"KZ":{"prefix":"+7","min":10,"max":10},"KE":{"prefix":"+254","min":6,"max":10},"KI":{"prefix":"+686","min":5,"max":5},"KP":{"prefix":"+850","min":6,"max":8},"KR":{"prefix":"+82","min":8,"max":11},"XK":{"prefix":"+383","min":9,"max":9},"KW":{"prefix":"+965","min":7,"max":8},"KG":{"prefix":"+996","min":9,"max":9},"LA":{"prefix":"+856","min":8,"max":10},"LV":{"prefix":"+371","min":7,"max":8},"LB":{"prefix":"+961","min":7,"max":8},"LS":{"prefix":"+266","min":8,"max":8},"LR":{"prefix":"+231","min":7,"max":8},"LY":{"prefix":"+218","min":8,"max":9},"LI":{"prefix":"+423","min":7,"max":9},"LT":{"prefix":"+370","min":8,"max":8},"LU":{"prefix":"+352","min":4,"max":11},"MO":{"prefix":"+853","min":7,"max":8},"MK":{"prefix":"+389","min":9,"max":9},"MG":{"prefix":"+261","min":9,"max":10},"MW":{"prefix":"+265","min":7,"max":8},"MY":{"prefix":"+60","min":7,"max":9},"MV":{"prefix":"+960","min":7,"max":7},"ML":{"prefix":"+223","min":8,"max":8},"MT":{"prefix":"+356","min":8,"max":8},"MH":{"prefix":"+692","min":7,"max":7},"MQ":{"prefix":"+596","min":9,"max":9},"MR":{"prefix":"+222","min":7,"max":7},"MU":{"prefix":"+230","min":7,"max":7},"YT":{"prefix":"+262","min":9,"max":9},"MX":{"prefix":"+52","min":10,"max":10},"FM":{"prefix":"+691","min":7,"max":7},"MD":{"prefix":"+373","min":8,"max":8},"MC":{"prefix":"+377","min":5,"max":9},"MN":{"prefix":"+976","min":7,"max":8},"ME":{"prefix":"+382","min":4,"max":12},"MS":{"prefix":"+1664","min":10,"max":10},"MA":{"prefix":"+212","min":9,"max":9},"MZ":{"prefix":"+258","min":8,"max":9},"MM":{"prefix":"+95","min":7,"max":9},"NA":{"prefix":"+264","min":6,"max":10},"NR":{"prefix":"+674","min":4,"max":7},"NP":{"prefix":"+977","min":8,"max":9},"NL":{"prefix":"+31","min":9,"max":9},"AN":{"prefix":"+599","min":7,"max":8},"NC":{"prefix":"+687","min":6,"max":6},"NZ":{"prefix":"+64","min":3,"max":10},"NI":{"prefix":"+505","min":8,"max":8},"NE":{"prefix":"+227","min":8,"max":8},"NG":{"prefix":"+234","min":7,"max":10},"NU":{"prefix":"+683","min":4,"max":4},"NF":{"prefix":"+672","min":6,"max":6},"MP":{"prefix":"+1670","min":7,"max":7},"NO":{"prefix":"+47","min":5,"max":6},"OM":{"prefix":"+968","min":7,"max":8},"PK":{"prefix":"+92","min":8,"max":11},"PW":{"prefix":"+680","min":7,"max":7},"PS":{"prefix":"+970","min":9,"max":10},"PA":{"prefix":"+507","min":7,"max":8},"PG":{"prefix":"+675","min":4,"max":11},"PY":{"prefix":"+595","min":5,"max":9},"PE":{"prefix":"+51","min":8,"max":11},"PH":{"prefix":"+63","min":8,"max":10},"PN":{"prefix":"+64","min":3,"max":10},"PL":{"prefix":"+48","min":6,"max":9},"PT":{"prefix":"+351","min":9,"max":11},"PR":{"prefix":"+1939","min":10,"max":10},"QA":{"prefix":"+974","min":3,"max":8},"RO":{"prefix":"+40","min":9,"max":9},"RU":{"prefix":"+7","min":10,"max":10},"RW":{"prefix":"+250","min":9,"max":9},"RE":{"prefix":"+262","min":9,"max":9},"BL":{"prefix":"+590","min":9,"max":9},"SH":{"prefix":"+290","min":5,"max":5},"KN":{"prefix":"+1869","min":10,"max":10},"LC":{"prefix":"+1758","min":10,"max":10},"MF":{"prefix":"+590","min":7,"max":7},"PM":{"prefix":"+508","min":6,"max":6},"VC":{"prefix":"+1784","min":10,"max":10},"WS":{"prefix":"+685","min":3,"max":7},"SM":{"prefix":"+378","min":6,"max":10},"ST":{"prefix":"+239","min":7,"max":7},"SA":{"prefix":"+966","min":8,"max":9},"SN":{"prefix":"+221","min":9,"max":9},"RS":{"prefix":"+381","min":4,"max":12},"SC":{"prefix":"+248","min":7,"max":7},"SL":{"prefix":"+232","min":8,"max":8},"SG":{"prefix":"+65","min":8,"max":12},"SK":{"prefix":"+421","min":4,"max":9},"SI":{"prefix":"+386","min":8,"max":8},"SB":{"prefix":"+677","min":5,"max":5},"SO":{"prefix":"+252","min":5,"max":8},"ZA":{"prefix":"+27","min":9,"max":9},"SS":{"prefix":"+211","min":9,"max":9},"GS":{"prefix":"+500","min":5,"max":5},"ES":{"prefix":"+34","min":9,"max":9},"LK":{"prefix":"+94","min":9,"max":9},"SD":{"prefix":"+249","min":9,"max":9},"SR":{"prefix":"+597","min":6,"max":7},"SJ":{"prefix":"+47","min":5,"max":8},"SZ":{"prefix":"+268","min":7,"max":8},"SE":{"prefix":"+46","min":7,"max":13},"CH":{"prefix":"+41","min":4,"max":12},"SY":{"prefix":"+963","min":8,"max":10},"TW":{"prefix":"+886","min":8,"max":9},"TJ":{"prefix":"+992","min":9,"max":9},"TZ":{"prefix":"+255","min":9,"max":9},"TH":{"prefix":"+66","min":8,"max":9},"TL":{"prefix":"+670","min":9,"max":9},"TG":{"prefix":"+228","min":8,"max":8},"TK":{"prefix":"+690","min":4,"max":4},"TO":{"prefix":"+676","min":5,"max":6},"TT":{"prefix":"+1868","min":10,"max":10},"TN":{"prefix":"+216","min":8,"max":8},"TR":{"prefix":"+90","min":10,"max":10},"TM":{"prefix":"+993","min":8,"max":8},"TC":{"prefix":"+1649","min":10,"max":10},"TV":{"prefix":"+688","min":5,"max":6},"UG":{"prefix":"+256","min":9,"max":9},"UA":{"prefix":"+380","min":9,"max":9},"AE":{"prefix":"+971","min":8,"max":9},"GB":{"prefix":"+44","min":7,"max":10},"US":{"prefix":"+1","min":10,"max":10},"UY":{"prefix":"+598","min":4,"max":11},"UZ":{"prefix":"+998","min":9,"max":9},"VU":{"prefix":"+678","min":5,"max":7},"VE":{"prefix":"+58","min":10,"max":10},"VN":{"prefix":"+84","min":7,"max":10},"VG":{"prefix":"+1284","min":10,"max":10},"VI":{"prefix":"+1340","min":10,"max":10},"WF":{"prefix":"+681","min":6,"max":6},"YE":{"prefix":"+967","min":6,"max":9},"ZM":{"prefix":"+260","min":9,"max":9},"ZW":{"prefix":"+263","min":5,"max":10}},

			_country_obj:function(prefix)
				{
					for( let i in this.countries ) {
						i = this.countries[i];
						if(this.country_db[i]['prefix'] == prefix )
							return this.country_db[i];
					}
					return false;
				},
			_on_change_events:function()
				{
					var me = this;
					$(':input[id*="'+me.name+'_"]').each(function(){
						el = $(this);
						el.on('change', function(){
							var v = '';
                            $('[id*="'+me.name+'_"]').each(function(){v+=$(this).val();});
							$('#'+me.name).val(v).trigger('change');
						})
						.on('keyup', function(evt){
							var e = $(this);
							if(e.val().length == e.attr('maxlength'))
							{
								e.trigger('change');
								let i = parseInt(e.attr('name').match(/\d+$/))+1;
								try{ $('#'+me.name+'_'+i).trigger('focus'); } catch(err){}
							}
						});
					});
				},
			_input_boxes:function( silent )
				{
					silent = silent || false;

					let me 		    = this,
						prefix      = $('#'+me.name+'_0').val(),
					    bk_number   = '',
						country_obj = me._country_obj(prefix),
						output      = '',
						placeholder = (typeof me.predefinedClick != 'undefined' && me.predefinedClick),
						cw			= me.toDisplay == 'iso' ? 60 : 90, // Country code width;
						predefined  = String( me.predefined ).replace(/\s/g, ''); // Used for placeholder.

					$('input[id*="'+me.name+'_"]').each(function(i,e){ bk_number += $(e).val(); });

					if ( country_obj ) {
						let symbol = ( me.dformat.length ) ? me.dformat[0] : '#', // Symbol to use for format.
							max    = country_obj['max'],
							min    = country_obj['min'],
							d      = /\s/.test(me.dformat) ? 3 : max,
							max_r  = max % d,
							min_r  = min % d,
							c	   = 1;

						if ( predefined.length && predefined.length < max ) predefined += predefined.substr(-1).repeat(max-predefined.length);

						for ( var i = 0, h = Math.floor( max/d ); i<h; i++ ) {
							let w = d + ( ( max_r && h - i <= max_r ) ? 1 : 0 ),
								n = Math.max( 0, Math.min( min, w ) ),
								v = ( i == h-1 ) ? bk_number : bk_number.substring(0, w);

							bk_number = bk_number.substring(v.length);
							min -= w;

							output += '<div class="uh_phone" style="width:calc( ( 100% - '+cw+'px ) / '+max+' * '+w+');">'+

							'<input aria-label="'+cff_esc_attr(me.title)+'" type="text" id="'+me.name+'_'+c+'" name="'+me.name+'_'+c+'" class="field '+((i==0 && !me.countryComponent) ? ' phone ' : ' digits ')+((me.required && n) ? ' required ' : '')+'" size="'+w+'" maxlength="'+w+'" minlength="'+n+'" '+(me.readonly?'readonly':'')+' style="'+cff_esc_attr(me.getCSSComponent('phone'))+'" value="'+cff_esc_attr(v)+'" '+
							(placeholder ? 'placeholder="'+cff_esc_attr( predefined.substring(0,w))+'" ' : '')
							+' />'+
							'<div class="l" style="'+cff_esc_attr(me.getCSSComponent('format'))+'">'+symbol.repeat(w)+'</div>'+
							'</div>';
							predefined = predefined.substring(w);
							c++;
						}

					}

					let e = $( '.'+me.name ).find('.components_container');
					e.find('.uh_phone:not(:first)').remove();
					e.append(output);
					$('[id*="'+me.name+'"].cpefb_error.message').remove();
					if ( ! silent ) $(':input[id*="'+me.name+'"]').valid();
					me._on_change_events();
				},
			show:function()
				{
                    var me  = this;

					me.predefined = String(me._getAttr('predefined', true)).trim().replace(/\s/g, '');
                    me.dformat = String(me.dformat).trim().replace(/\s+/g, ' ');

					var str  = "",
						tmpv = me.predefined,
						tmp  = me.dformat.length ? me.dformat.split(/\s+/) : ( tmpv.length ? tmpv.split(/\s+/) : [''] ),
						attr = (typeof me.predefinedClick != 'undefined' && me.predefinedClick) ? 'placeholder' : 'value',
						nc   = me.dformat.replace(/\s/g, '').length, // Number of characters.
                        c 	 = 0,
						cw	 = 0;

					str = '<div class="'+me.size+' components_container">';
                    if(me.countryComponent) {
						let db = {}, countries;

						if(!me.countries.length) me.countries = Object.keys(me.country_db);
						for( let i in me.countries ) {
							if ( me.countries[i] in me.country_db )
								db[me.countries[i]] = me.country_db[me.countries[i]];
						}
						countries = JSON.parse(JSON.stringify(me.countries));

						cw = me.toDisplay == 'iso' ? 60 : 90;
						str += '<div class="uh_phone" style="width:'+cw+'px;"><select id="'+me.name+'_'+c+'" name="'+me.name+'_'+c+'" class="field" style="'+cff_esc_attr(me.getCSSComponent('prefix'))+'">';

						if(me.toDisplay != 'iso') {
							db = Object.fromEntries(Object.entries(db).sort(
								function(a,b) {
									let n1 = a[1]['prefix'].replace(/[^\d]/g,'')*1,
										n2 = b[1]['prefix'].replace(/[^\d]/g,'')*1;
									return n1 < n2 ? -1 : ( n1 == n2 ? 0 : 1 );
								}));

							delete db[ me.defaultCountry == 'CA' ? 'US' : 'CA' ];
							delete db[ me.defaultCountry == 'RU' ? 'KZ' : 'RU' ];
							countries = Object.keys(db);
						} else {
							countries = countries.sort();
						}

                        for(let i in countries) {
							let prefix = db[countries[i]]['prefix'];
							str += '<option value="'+prefix+'" '+(me.defaultCountry == countries[i] ? 'SELECTED' : '')+'>'+(me.toDisplay == 'iso' ? countries[i] : prefix)+'</option>';
						}
                        str += '</select></div>';
                        c++;
                    }

					for (var i = 0, h = tmp.length;i<h;i++)
					{
						let l = tmp[i].length;

						str += '<div class="uh_phone" style="width:calc( ( 100% - '+cw+'px ) / '+Math.max(1, nc)+' * '+Math.max(1, l)+');"><input aria-label="'+cff_esc_attr(me.title)+'" type="text" id="'+me.name+'_'+c+'" name="'+me.name+'_'+c+'" class="field '+((i==0 && !me.countryComponent) ? ' phone ' : ' digits ')+((me.required) ? ' required ' : '')+'" size="'+l+'" '+attr+'="'+tmpv.substring(0,l)+'" maxlength="'+l+'" minlength="'+l+'" '+((me.readonly)?'readonly':'')+' style="'+cff_esc_attr(me.getCSSComponent('phone'))+'" /><div class="l" style="'+cff_esc_attr(me.getCSSComponent('format'))+'">'+tmp[i]+'</div></div>';

						tmpv = tmpv.substring(l);
						c++;
					}

					str += '</div>';

					return '<div class="fields '+cff_esc_attr(me.csslayout)+' '+me.name+' cff-phone-field" id="field'+me.form_identifier+'-'+me.index+'" style="'+cff_esc_attr(me.getCSSComponent('container'))+'"><label for="'+me.name+'" style="'+cff_esc_attr(me.getCSSComponent('label'))+'">'+me.title+''+((me.required)?"<span class='r'>*</span>":"")+'</label><div class="dfield"><input type="hidden" id="'+me.name+'" name="'+me.name+'" class="field" />'+str+'<div class="clearer"></div><span class="uh" style="'+cff_esc_attr(me.getCSSComponent('help'))+'">'+me.userhelp+'</span></div><div class="clearer"></div></div>';
				},
            after_show: function()
				{
					var me   = this;

					if(!('phone' in $.validator.methods))
						$.validator.addMethod("phone", function(value, element)
						{
							if(this.optional(element)) return true;
							else return /^\+{0,1}\d*$/.test(value);
						});

					me._on_change_events();
					$('#'+me.name+'_0').trigger('change');
					if (me.countryComponent && me.dynamic) {
						$('#'+me.name+'_0').on('change', function(){ me._input_boxes(); });
						me._input_boxes( true );
					}
				},
			val:function(raw, no_quotes)
				{
                    raw = raw || true;
                    no_quotes = no_quotes || false;
					var e = $('[id="'+this.name+'"]:not(.ignore)'),
						p = $.fbuilder.parseValStr(e.val(), raw, no_quotes);

					if(e.length) return ($.fbuilder.isNumeric(p) && !no_quotes) ? '"'+p+'"' : p;
					return 0;
				},
			setVal:function(v)
				{
					let me = this, max = 0, min = 0, prefix, country_obj;

					// Initialize min/max variables.
					$('input[id*="'+me.name+'_"]').each(function(i,e){
						e = $(e);
						max += e.attr( 'maxlength' )*1;
						min += e.attr( 'minlength' )*1;
					});

					function setPrefix( v ) {
						let l = v.length, o = '';

						for ( let i in me.countries ) {
							i = me.countries[i];
							let prefix = me.country_db[i]['prefix'],
								ln = l - prefix.length;
							if (
								v.indexOf( prefix ) == 0 &&
								me.country_db[i].min <= ln &&
								ln <= me.country_db[i].max
							) {
								if ( ! o || me.country_db[i].max < me.country_db[o].max ) o = i;
								if ( ln == me.country_db[o].max ) break;
							}
						}
						if( o ) $('select[id*="'+me.name+'_"]').val(me.country_db[o]['prefix']);
						return o;
					}; // End setPrefix.

					v = String(v).trim();
					$('[name="'+me.name+'"]').val(v);
					$('input[id*="'+me.name+'_"]').val('');
                    if(v.length) {
                        let f = v[0];

                        v = ( f != '+' ? '' : '+' ) + v.replace(/[^\d]/g, '');

                        if ( f == '+' && me.countryComponent ) {
							prefix = $('select[id*="'+me.name+'_"]').val();
							country_obj = me._country_obj(prefix);

							if( v.indexOf( prefix) != 0 || ( country_obj && country_obj.max+prefix.length <	v.length ) ) {
								prefix = setPrefix( v );
							}

							v = v.substring( prefix.length );
						}

						$('input[id*="'+me.name+'_"]').each(function(i,e) {
							e = $(e);
							let l = e.attr( 'maxlength' );
							e.val( v.substring( 0, l ) );
							v = v.substring( l );
						});
					}
				}
		}
	);