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
			dseparator:"-", // none, space, -, .
			predefined:"888 888 8888",

            countryComponent:false,
            toDisplay:'iso',
			includeFlags:false,
			includeCountryNames:false,
            countries:[],
            defaultCountry:'',
			dynamic: false,

			country_db: {"AF":{"prefix":"+93","min":9,"max":9,"country":"Afghanistan"},"AX":{"country":"Åland Islands","prefix":"+358","min":5,"max":12},"AL":{"prefix":"+355","min":3,"max":9,"country":"Albania"},"DZ":{"prefix":"+213","min":8,"max":9,"country":"Algeria"},"AS":{"prefix":"+1684","min":10,"max":10,"country":"American Samoa"},"AD":{"prefix":"+376","min":6,"max":9,"country":"Andorra"},"AO":{"prefix":"+244","min":9,"max":9,"country":"Angola"},"AI":{"prefix":"+1264","min":10,"max":10,"country":"Anguilla"},"AQ":{"country":"Antarctica","prefix":"+672","min":10,"max":10},"AG":{"prefix":"+1268","min":10,"max":10,"country":"Antigua and Barbuda"},"AR":{"prefix":"+54","min":10,"max":10,"country":"Argentina"},"AM":{"prefix":"+374","min":8,"max":8,"country":"Armenia"},"AW":{"prefix":"+297","min":7,"max":7,"country":"Aruba"},"AU":{"prefix":"+61","min":5,"max":9,"country":"Australia"},"AT":{"prefix":"+43","min":4,"max":13,"country":"Austria"},"AZ":{"prefix":"+994","min":8,"max":9,"country":"Azerbaijan"},"BS":{"prefix":"+1242","min":10,"max":10,"country":"Bahamas"},"BH":{"prefix":"+973","min":8,"max":8,"country":"Bahrain"},"BD":{"prefix":"+880","min":6,"max":10,"country":"Bangladesh"},"BB":{"prefix":"+1246","min":10,"max":10,"country":"Barbados"},"BY":{"prefix":"+375","min":9,"max":10,"country":"Belarus"},"BE":{"prefix":"+32","min":8,"max":9,"country":"Belgium"},"BZ":{"prefix":"+501","min":7,"max":7,"country":"Belize"},"BJ":{"prefix":"+229","min":8,"max":8,"country":"Benin"},"BM":{"prefix":"+1441","min":10,"max":10,"country":"Bermuda"},"BT":{"prefix":"+975","min":7,"max":8,"country":"Bhutan"},"BO":{"prefix":"+591","min":8,"max":8,"country":"Bolivia"},"BA":{"prefix":"+387","min":8,"max":8,"country":"Bosnia and Herzegovina"},"BW":{"prefix":"+267","min":7,"max":8,"country":"Botswana"},"BR":{"prefix":"+55","min":10,"max":10,"country":"Brazil"},"IO":{"country":"British Indian Ocean Territory","prefix":"+246","min":7,"max":7},"BN":{"prefix":"+673","min":7,"max":7,"country":"Brunei"},"BG":{"prefix":"+359","min":7,"max":9,"country":"Bulgaria"},"BF":{"prefix":"+226","min":8,"max":8,"country":"Burkina Faso"},"BI":{"prefix":"+257","min":8,"max":8,"country":"Burundi"},"KH":{"prefix":"+855","min":8,"max":8,"country":"Cambodia"},"CM":{"prefix":"+237","min":8,"max":8,"country":"Cameroon"},"CA":{"prefix":"+1","min":10,"max":10,"country":"Canada"},"CV":{"prefix":"+238","min":7,"max":7,"country":"Cape Verde"},"KY":{"prefix":"+1345","min":10,"max":10,"country":"Cayman Islands"},"CF":{"prefix":"+236","min":8,"max":8,"country":"Central African Republic"},"TD":{"prefix":"+235","min":8,"max":8,"country":"Chad"},"CL":{"prefix":"+56","min":8,"max":9,"country":"Chile"},"CN":{"prefix":"+86","min":5,"max":12,"country":"China"},"CX":{"country":"Christmas Island","prefix":"+61","min":5,"max":9},"CC":{"country":"Coco (Keeling) Islands","prefix":"+61","min":5,"max":9},"CO":{"prefix":"+57","min":8,"max":10,"country":"Colombia"},"KM":{"prefix":"+269","min":7,"max":7,"country":"Comoros"},"CG":{"country":"The Congo","prefix":"+242","min":5,"max":9},"CD":{"prefix":"+243","min":5,"max":9,"country":"DR Congo"},"CK":{"prefix":"+682","min":5,"max":5,"country":"Cook Islands"},"CR":{"prefix":"+506","min":8,"max":8,"country":"Costa Rica"},"CI":{"prefix":"+225","min":8,"max":8,"country":"Ivory Coast"},"HR":{"prefix":"+385","min":8,"max":12,"country":"Croatia"},"CU":{"prefix":"+53","min":8,"max":8,"country":"Cuba"},"CY":{"prefix":"+357","min":8,"max":11,"country":"Cyprus"},"CZ":{"prefix":"+420","min":4,"max":12,"country":"Czech Republic"},"DK":{"prefix":"+45","min":8,"max":8,"country":"Denmark"},"DJ":{"prefix":"+253","min":6,"max":6,"country":"Djibouti"},"DM":{"prefix":"+1767","min":10,"max":10,"country":"Dominica"},"DO":{"prefix":"+1849","min":7,"max":7,"country":"Dominican Republic"},"EC":{"prefix":"+593","min":8,"max":8,"country":"Ecuador"},"EG":{"prefix":"+20","min":7,"max":9,"country":"Egypt"},"SV":{"prefix":"+503","min":7,"max":11,"country":"El Salvador"},"GQ":{"prefix":"+240","min":9,"max":9,"country":"Equatorial Guinea"},"ER":{"prefix":"+291","min":7,"max":7,"country":"Eritrea"},"EE":{"prefix":"+372","min":7,"max":10,"country":"Estonia"},"ET":{"prefix":"+251","min":9,"max":9,"country":"Ethiopia"},"FK":{"prefix":"+500","min":5,"max":5,"country":"Falkland Islands"},"FO":{"prefix":"+298","min":6,"max":6,"country":"Faroe Islands"},"FJ":{"prefix":"+679","min":7,"max":7,"country":"Fiji"},"FI":{"prefix":"+358","min":5,"max":12,"country":"Finland"},"FR":{"prefix":"+33","min":9,"max":9,"country":"France"},"GF":{"prefix":"+594","min":9,"max":9,"country":"French Guiana"},"PF":{"prefix":"+689","min":6,"max":6,"country":"French Polynesia"},"TF":{"country":"French Southern Territories","prefix":"+262","min":9,"max":9},"GA":{"prefix":"+241","min":6,"max":7,"country":"Gabon"},"GM":{"prefix":"+220","min":7,"max":7,"country":"Gambia"},"GE":{"prefix":"+995","min":9,"max":9,"country":"Georgia"},"DE":{"prefix":"+49","min":6,"max":13,"country":"Germany"},"GH":{"prefix":"+233","min":5,"max":9,"country":"Ghana"},"GI":{"prefix":"+350","min":8,"max":8,"country":"Gibraltar"},"GR":{"prefix":"+30","min":10,"max":10,"country":"Greece"},"GL":{"prefix":"+299","min":6,"max":6,"country":"Greenland"},"GD":{"prefix":"+1473","min":10,"max":10,"country":"Grenada"},"GP":{"prefix":"+590","min":9,"max":9,"country":"Guadeloupe"},"GU":{"prefix":"+1671","min":10,"max":10,"country":"Guam"},"GT":{"prefix":"+502","min":8,"max":8,"country":"Guatemala"},"GG":{"country":"Guernsey","prefix":"+44","min":7,"max":10},"GN":{"prefix":"+224","min":8,"max":8,"country":"Guinea"},"GW":{"country":"Guinea-Bissau","prefix":"+245","min":9,"max":9},"GY":{"prefix":"+592","min":7,"max":7,"country":"Guyana"},"HT":{"prefix":"+509","min":8,"max":8,"country":"Haiti"},"HM":{"country":"Heard Island and McDonald Islands","prefix":"+672","min":8,"max":8},"VA":{"prefix":"+379","min":10,"max":10,"country":"Vatican City"},"HN":{"prefix":"+504","min":8,"max":8,"country":"Honduras"},"HK":{"prefix":"+852","min":4,"max":9,"country":"Hong Kong"},"HU":{"prefix":"+36","min":8,"max":9,"country":"Hungary"},"IS":{"prefix":"+354","min":7,"max":9,"country":"Iceland"},"IN":{"prefix":"+91","min":7,"max":10,"country":"India"},"ID":{"prefix":"+62","min":5,"max":10,"country":"Indonesia"},"IR":{"prefix":"+98","min":6,"max":10,"country":"Iran"},"IQ":{"prefix":"+964","min":8,"max":10,"country":"Iraq"},"IE":{"prefix":"+353","min":7,"max":11,"country":"Ireland"},"IM":{"country":"Isle of Man","prefix":"+44","min":7,"max":10},"IL":{"prefix":"+972","min":8,"max":9,"country":"Israel"},"IT":{"prefix":"+39","min":11,"max":11,"country":"Italy"},"JM":{"prefix":"+1876","min":10,"max":10,"country":"Jamaica"},"JP":{"prefix":"+81","min":10,"max":10,"country":"Japan"},"JE":{"country":"Jersey","prefix":"+44","min":7,"max":10},"JO":{"prefix":"+962","min":5,"max":9,"country":"Jordan"},"KZ":{"prefix":"+7","min":10,"max":10,"country":"Kazakhstan"},"KE":{"prefix":"+254","min":6,"max":10,"country":"Kenya"},"KI":{"prefix":"+686","min":5,"max":5,"country":"Kiribati"},"KP":{"prefix":"+850","min":6,"max":8,"country":"North Korea"},"KR":{"prefix":"+82","min":8,"max":11,"country":"South Korea"},"XK":{"country":"Kosovo","prefix":"+383","min":9,"max":9},"KW":{"prefix":"+965","min":7,"max":8,"country":"Kuwait"},"KG":{"prefix":"+996","min":9,"max":9,"country":"Kyrgyzstan"},"LA":{"prefix":"+856","min":8,"max":10,"country":"Laos"},"LV":{"prefix":"+371","min":7,"max":8,"country":"Latvia"},"LB":{"prefix":"+961","min":7,"max":8,"country":"Lebanon"},"LS":{"prefix":"+266","min":8,"max":8,"country":"Lesotho"},"LR":{"prefix":"+231","min":7,"max":8,"country":"Liberia"},"LY":{"prefix":"+218","min":8,"max":9,"country":"Libya"},"LI":{"prefix":"+423","min":7,"max":9,"country":"Liechtenstein"},"LT":{"prefix":"+370","min":8,"max":8,"country":"Lithuania"},"LU":{"prefix":"+352","min":4,"max":11,"country":"Luxembourg"},"MO":{"prefix":"+853","min":7,"max":8,"country":"Macau"},"MK":{"country":"North Macedonia","prefix":"+389","min":9,"max":9},"MG":{"prefix":"+261","min":9,"max":10,"country":"Madagascar"},"MW":{"prefix":"+265","min":7,"max":8,"country":"Malawi"},"MY":{"prefix":"+60","min":7,"max":9,"country":"Malaysia"},"MV":{"prefix":"+960","min":7,"max":7,"country":"Maldives"},"ML":{"prefix":"+223","min":8,"max":8,"country":"Mali"},"MT":{"prefix":"+356","min":8,"max":8,"country":"Malta"},"MH":{"prefix":"+692","min":7,"max":7,"country":"Marshall Islands"},"MQ":{"prefix":"+596","min":9,"max":9,"country":"Martinique"},"MR":{"prefix":"+222","min":7,"max":7,"country":"Mauritania"},"MU":{"prefix":"+230","min":7,"max":7,"country":"Mauritius"},"YT":{"country":"Mayotte","prefix":"+262","min":9,"max":9},"MX":{"prefix":"+52","min":10,"max":10,"country":"Mexico"},"FM":{"prefix":"+691","min":7,"max":7,"country":"Micronesia"},"MD":{"prefix":"+373","min":8,"max":8,"country":"Moldova"},"MC":{"prefix":"+377","min":5,"max":9,"country":"Monaco"},"MN":{"prefix":"+976","min":7,"max":8,"country":"Mongolia"},"ME":{"prefix":"+382","min":4,"max":12,"country":"Montenegro"},"MS":{"prefix":"+1664","min":10,"max":10,"country":"Montserrat"},"MA":{"prefix":"+212","min":9,"max":9,"country":"Morocco"},"MZ":{"prefix":"+258","min":8,"max":9,"country":"Mozambique"},"MM":{"prefix":"+95","min":7,"max":9,"country":"Myanmar"},"NA":{"prefix":"+264","min":6,"max":10,"country":"Namibia"},"NR":{"prefix":"+674","min":4,"max":7,"country":"Nauru"},"NP":{"prefix":"+977","min":8,"max":9,"country":"Nepal"},"NL":{"prefix":"+31","min":9,"max":9,"country":"Netherlands"},"AN":{"country":"Netherlands Antilles","prefix":"+599","min":7,"max":8},"NC":{"prefix":"+687","min":6,"max":6,"country":"New Caledonia"},"NZ":{"prefix":"+64","min":3,"max":10,"country":"New Zealand"},"NI":{"prefix":"+505","min":8,"max":8,"country":"Nicaragua"},"NE":{"prefix":"+227","min":8,"max":8,"country":"Niger"},"NG":{"prefix":"+234","min":7,"max":10,"country":"Nigeria"},"NU":{"prefix":"+683","min":4,"max":4,"country":"Niue"},"NF":{"country":"Norfolk Island","prefix":"+672","min":6,"max":6},"MP":{"country":"Northern Mariana Islands","prefix":"+1670","min":7,"max":7},"NO":{"prefix":"+47","min":5,"max":6,"country":"Norway"},"OM":{"prefix":"+968","min":7,"max":8,"country":"Oman"},"PK":{"prefix":"+92","min":8,"max":11,"country":"Pakistan"},"PW":{"prefix":"+680","min":7,"max":7,"country":"Palau"},"PS":{"country":"State of Palestine","prefix":"+970","min":9,"max":10},"PA":{"prefix":"+507","min":7,"max":8,"country":"Panama"},"PG":{"prefix":"+675","min":4,"max":11,"country":"Papua New Guinea"},"PY":{"prefix":"+595","min":5,"max":9,"country":"Paraguay"},"PE":{"prefix":"+51","min":8,"max":11,"country":"Peru"},"PH":{"prefix":"+63","min":8,"max":10,"country":"Philippines"},"PN":{"country":"Pitcairn","prefix":"+64","min":3,"max":10},"PL":{"prefix":"+48","min":6,"max":9,"country":"Poland"},"PT":{"prefix":"+351","min":9,"max":11,"country":"Portugal"},"PR":{"prefix":"+1939","min":10,"max":10,"country":"Puerto Rico"},"QA":{"prefix":"+974","min":3,"max":8,"country":"Qatar"},"RO":{"prefix":"+40","min":9,"max":9,"country":"Romania"},"RU":{"prefix":"+7","min":10,"max":10,"country":"Russia"},"RW":{"prefix":"+250","min":9,"max":9,"country":"Rwanda"},"RE":{"country":"Réunion","prefix":"+262","min":9,"max":9},"BL":{"country":"Saint Barthélemy","prefix":"+590","min":9,"max":9},"SH":{"country":"Saint Helena","prefix":"+290","min":5,"max":5},"KN":{"prefix":"+1869","min":10,"max":10,"country":"Saint Kitts and Nevis"},"LC":{"prefix":"+1758","min":10,"max":10,"country":"Saint Lucia"},"MF":{"country":"Saint Martin","prefix":"+590","min":7,"max":7},"PM":{"prefix":"+508","min":6,"max":6,"country":"Saint Pierre and Miquelon"},"VC":{"prefix":"+1784","min":10,"max":10,"country":"Saint Vincent and the Grenadines"},"WS":{"prefix":"+685","min":3,"max":7,"country":"Samoa"},"SM":{"prefix":"+378","min":6,"max":10,"country":"San Marino"},"ST":{"prefix":"+239","min":7,"max":7,"country":"Sao Tome and Principe"},"SA":{"prefix":"+966","min":8,"max":9,"country":"Saudi Arabia"},"SN":{"prefix":"+221","min":9,"max":9,"country":"Senegal"},"RS":{"prefix":"+381","min":4,"max":12,"country":"Serbia"},"SC":{"prefix":"+248","min":7,"max":7,"country":"Seychelles"},"SL":{"prefix":"+232","min":8,"max":8,"country":"Sierra Leone"},"SG":{"prefix":"+65","min":8,"max":12,"country":"Singapore"},"SK":{"prefix":"+421","min":4,"max":9,"country":"Slovakia"},"SI":{"prefix":"+386","min":8,"max":8,"country":"Slovenia"},"SB":{"prefix":"+677","min":5,"max":5,"country":"Solomon Islands"},"SO":{"prefix":"+252","min":5,"max":8,"country":"Somalia"},"ZA":{"prefix":"+27","min":9,"max":9,"country":"South Africa"},"SS":{"country":"South Sudan","prefix":"+211","min":9,"max":9},"GS":{"country":"South Georgia and the South Sandwich Islands","prefix":"+500","min":5,"max":5},"ES":{"prefix":"+34","min":9,"max":9,"country":"Spain"},"LK":{"prefix":"+94","min":9,"max":9,"country":"Sri Lanka"},"SD":{"prefix":"+249","min":9,"max":9,"country":"Sudan"},"SR":{"prefix":"+597","min":6,"max":7,"country":"Suriname"},"SZ":{"prefix":"+268","min":7,"max":8,"country":"Eswatini"},"SE":{"prefix":"+46","min":7,"max":13,"country":"Sweden"},"CH":{"prefix":"+41","min":4,"max":12,"country":"Switzerland"},"SY":{"prefix":"+963","min":8,"max":10,"country":"Syria"},"TW":{"prefix":"+886","min":8,"max":9,"country":"Taiwan"},"TJ":{"prefix":"+992","min":9,"max":9,"country":"Tajikistan"},"TZ":{"prefix":"+255","min":9,"max":9,"country":"Tanzania"},"TH":{"prefix":"+66","min":8,"max":9,"country":"Thailand"},"TL":{"country":"Timor-Leste","prefix":"+670","min":9,"max":9},"TG":{"prefix":"+228","min":8,"max":8,"country":"Togo"},"TK":{"prefix":"+690","min":4,"max":4,"country":"Tokelau"},"TO":{"prefix":"+676","min":5,"max":6,"country":"Tonga"},"TT":{"prefix":"+1868","min":10,"max":10,"country":"Trinidad and Tobago"},"TN":{"prefix":"+216","min":8,"max":8,"country":"Tunisia"},"TR":{"prefix":"+90","min":10,"max":10,"country":"Turkey"},"TM":{"prefix":"+993","min":8,"max":8,"country":"Turkmenistan"},"TC":{"prefix":"+1649","min":10,"max":10,"country":"Turks and Caicos Islands"},"TV":{"prefix":"+688","min":5,"max":6,"country":"Tuvalu"},"UG":{"prefix":"+256","min":9,"max":9,"country":"Uganda"},"UA":{"prefix":"+380","min":9,"max":9,"country":"Ukraine"},"AE":{"prefix":"+971","min":8,"max":9,"country":"United Arab Emirates"},"GB":{"prefix":"+44","min":7,"max":10,"country":"United Kingdom"},"US":{"prefix":"+1","min":10,"max":10,"country":"United States"},"UY":{"prefix":"+598","min":4,"max":11,"country":"Uruguay"},"UZ":{"prefix":"+998","min":9,"max":9,"country":"Uzbekistan"},"VU":{"prefix":"+678","min":5,"max":7,"country":"Vanuatu"},"VE":{"prefix":"+58","min":10,"max":10,"country":"Venezuela"},"VN":{"prefix":"+84","min":7,"max":10,"country":"Vietnam"},"VG":{"prefix":"+1284","min":10,"max":10,"country":"British Virgin Islands"},"VI":{"prefix":"+1340","min":10,"max":10,"country":"United States Virgin Islands"},"WF":{"prefix":"+681","min":6,"max":6,"country":"Wallis and Futuna"},"YE":{"prefix":"+967","min":6,"max":9,"country":"Yemen"},"ZM":{"prefix":"+260","min":9,"max":9,"country":"Zambia"},"ZW":{"prefix":"+263","min":5,"max":10,"country":"Zimbabwe"}},

			_country_obj:function(prefix)
				{
					for( let i in this.countries ) {
						i = this.countries[i];
						if( i in this.country_db && this.country_db[i]['prefix'] == prefix )
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
                            $(':input[id*="'+me.name+'_"]').each(function(){v+=$(this).val();});
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
							+' inputmode="tel" />'+
							'<div class="l" style="'+cff_esc_attr(me.getCSSComponent('format'))+'">'+cff_sanitize(symbol.repeat(w), true)+'</div>'+
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
			init:function()
				{
					var me  = this;
					me.predefined = String(me._getAttr('predefined', true)).trim().replace(/\s/g, '');
                    me.dformat = cff_esc_attr(String(me.dformat).trim().replace(/\s+/g, ' '));
					if(!me.countries.length) me.countries = Object.keys(me.country_db);
					else {
						let filtered = [];
						for( let i in me.countries ) {
							if( me.countries[i] in me.country_db ) {
								filtered.push(me.countries[i]);
							}
						}
						me.countries = filtered;
					}
				},
			show:function()
				{
                    var me  = this;

					var str  = "",
						tmpv = me.predefined,
						tmp  = me.dformat.length ? me.dformat.split(/\s+/) : ( tmpv.length ? tmpv.split(/\s+/) : [''] ),
						attr = (typeof me.predefinedClick != 'undefined' && me.predefinedClick) ? 'placeholder' : 'value',
						nc   = me.dformat.replace(/\s/g, '').length, // Number of characters.
                        c 	 = 0,
						cw	 = 0;

					str = '<div class="'+me.size+' components_container">';
                    if(me.countryComponent) {
						let db = {}, countries = [];

						for( let i in me.countries ) {
							if ( me.countries[i] in me.country_db ) {
								if ( ! ( me.countries[i] in db ) ) {
									db[me.countries[i]] = me.country_db[me.countries[i]];
									db[me.countries[i]]['iso'] = me.countries[i];
								}

								countries.push(me.countries[i]);
							}
						}

						cw = me.toDisplay == 'iso' ? 60 : 90;
						str += '<div class="uh_phone" style="width:'+cw+'px;"><select id="'+me.name+'_'+c+'" name="'+me.name+'_'+c+'" class="field" style="'+cff_esc_attr(me.getCSSComponent('prefix'))+'">';

						if( me.includeCountryNames ) { // Sort by country names.
							countries = countries.sort(function(a, b){
								let n1 = me.country_db[a]['country'].toLowerCase(),
									n2 = me.country_db[b]['country'].toLowerCase();
								if( n1 < n2) return -1;
								if( n2 < n1) return 1;
								return 0;
							});
						} else if ( me.toDisplay != 'iso' ) { // Sort by prefix.
							countries = countries.sort(function(a, b){
								let n1 = me.country_db[a]['prefix'].replace(/[^\d]/g,'')*1,
									n2 = me.country_db[b]['prefix'].replace(/[^\d]/g,'')*1;
								if( n1 < n2) return -1;
								if( n2 < n1) return 1;
								return 0;
							});
						} else { // Sort by ISO.
							countries = countries.sort();
						}

						let addedPrefix = {};
						for ( let i in countries ) {
							let prefix  = db[countries[i]]['prefix'],
								iso	    = db[countries[i]]['iso'],
								txt 	= cff_sanitize( me.toDisplay == 'iso' ? iso : prefix );

							if( me.includeFlags || me.includeCountryNames ) {
								txt = '<span class="country-code">'+txt+'</span>';
								if(me.includeCountryNames) {
									txt = '<span class="country-name">'+cff_sanitize(db[countries[i]]['country'])+'</span>'+txt;
								}

								if(me.includeFlags) {
									txt = '<span class="country-flag"><img src="https://cdn.statically.io/gh/cffdwboostercom/flags/main/'+db[countries[i]]['iso'].toLowerCase()+'.png"></span>'+txt;
								}
							} else if ( me.toDisplay != 'iso' ) {
								if (
									prefix in addedPrefix ||
									(
										me.defaultCountry &&
										me.defaultCountry != iso &&
										db[ me.defaultCountry ]['prefix'] == prefix
									)
								) continue;
							}
							addedPrefix[prefix] = 1;
							str += '<option value="'+cff_esc_attr(prefix)+'" '+(me.defaultCountry == countries[i] ? 'SELECTED' : '')+' data-iso="'+cff_esc_attr(iso)+'">'+cff_esc_attr(txt)+'</option>';
						}
                        str += '</select></div>';
                        c++;
                    }

					for (var i = 0, h = tmp.length;i<h;i++)
					{
						let l = tmp[i].length;

						str += '<div class="uh_phone" style="width:calc( ( 100% - '+cw+'px ) / '+Math.max(1, nc)+' * '+Math.max(1, l)+');"><input aria-label="'+cff_esc_attr(me.title)+'" type="text" id="'+me.name+'_'+c+'" name="'+me.name+'_'+c+'" class="field '+((i==0 && !me.countryComponent) ? ' phone ' : ' digits ')+((me.required) ? ' required ' : '')+'" size="'+cff_esc_attr(l)+'" '+attr+'="'+cff_esc_attr(tmpv.substring(0,l))+'" maxlength="'+cff_esc_attr(l)+'" minlength="'+cff_esc_attr(l)+'" '+((me.readonly)?'readonly':'')+' style="'+cff_esc_attr(me.getCSSComponent('phone'))+'" /><div class="l" style="'+cff_esc_attr(me.getCSSComponent('format'))+'">'+cff_sanitize(tmp[i], true)+'</div></div>';

						tmpv = tmpv.substring(l);
						c++;
					}

					str += '</div>';

                    return '<div class="fields ' + cff_esc_attr(me.csslayout) + ' ' + me.name + ' cff-phone-field" id="field' + me.form_identifier + '-' + me.index + '" style="' + cff_esc_attr(me.getCSSComponent('container')) + '"><label for="' + me.name + '" style="' + cff_esc_attr(me.getCSSComponent('label')) + '">' + cff_sanitize(me.title, true) + '' + ((me.required) ? "<span class='r'>*</span>" : "") + '</label><div class="dfield"><input type="hidden" id="' + me.name + '" name="' + me.name + '" class="field' + ((me.required) ? ' required ' : '') +'" data-msg="" ' + (me.predefined && !me.predefinedClick ? 'value="'+cff_esc_attr(me.predefined)+'"' : '') + ' style="' + cff_esc_attr(me.getCSSComponent('phone')) + '" />'+str+'<div class="clearer"></div><span class="uh" style="'+cff_esc_attr(me.getCSSComponent('help'))+'">'+cff_sanitize(me.userhelp, true)+'</span></div><div class="clearer"></div></div>';
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

					if (me.countryComponent) {
						let prefix = $('select#'+me.name+'_0');
						if ( 'select2' in $.fn && ( me.includeFlags || me.includeCountryNames ) ) {
							prefix.after('<span class="cff-select2-container"></span>');
							prefix.select2({
                                'templateResult': function(state){
									return (state.id) ? $('<span class="prefix-option">'+state.text+'</span>') : state.text;
								},
                                'templateSelection': function(state){
									return (state.id) ? $('<span class="prefix-selected-option">'+state.text+'</span>').find('.country-code').text() : state.text;
								},
								'dropdownAutoWidth' : true,
								'dropdownParent':prefix.next('.cff-select2-container')
                           });
						}
						prefix.trigger('change');
						if (me.dynamic) {
							prefix.on('change', function(){ me._input_boxes(); });
							me._input_boxes( true );
						}
					}
				},
			get_separator:function(){
				let separators = { 'none': '', 'space': ' ', '-': '-', '.' : '.' };
				return ( this.dseparator in separators) ? separators[this.dseparator] : '-';
			},
			val:function(raw, no_quotes, disable_ignore_check)
				{
                    raw = raw || false;
                    no_quotes = no_quotes || false;
					var me = this,
						e = (disable_ignore_check) ? $('[id="'+this.name+'"]') : $('[id="'+this.name+'"]:not(.ignore)'),
						pr = $('[id^="'+this.name+'_"]')
							.map(function(){return String($(this).val()).trim();})
							.get()
							.filter(function(value){return value.length>0;}).join(me.get_separator()),
						p  = $.fbuilder.parseValStr(
							raw ? pr : e.val(),
							true,
							no_quotes
						);

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
						if( o ) { o = me.country_db[o]['prefix']; $('select[id*="'+me.name+'_"]').val(o).trigger('change'); }
						return o;
					}; // End setPrefix.

					v = cff_esc_attr(String(v).trim());
					$('[name="'+me.name+'"]').val(v);
					$('input[id*="'+me.name+'_"]').val('');
                    if(v.length) {
                        let f = v[0];

                        v = ( f != '+' ? '' : '+' ) + v.replace(/[^\d]/g, '');

                        if ( f == '+' && me.countryComponent ) {
							prefix = $('select[id*="'+me.name+'_"]').val();
							country_obj = me._country_obj(prefix);

							if( v.indexOf( prefix ) != 0 || ( country_obj && country_obj.max+prefix.length < v.length ) ) {
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