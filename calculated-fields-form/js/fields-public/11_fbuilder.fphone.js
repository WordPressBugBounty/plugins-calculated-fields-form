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
			showFormatAsHint: -1,
			dseparator:"-", // none, space, -, .
			predefined:"888 888 8888",

            countryComponent:false,
            toDisplay:'iso',
			includeFlags:false,
			includeCountryNames:false,
            countries:[],
            defaultCountry:'',
			dynamic: false,

			FREE_MAX_DIGITS: 40,
			isFree: false,

			country_db: {"AD":{"prefix":"+376","min":6,"max":9,"country":"Andorra"},"AE":{"prefix":"+971","min":8,"max":9,"country":"United Arab Emirates"},"AF":{"prefix":"+93","min":9,"max":9,"country":"Afghanistan"},"AG":{"prefix":"+1268","min":7,"max":7,"country":"Antigua and Barbuda"},"AI":{"prefix":"+1264","min":7,"max":7,"country":"Anguilla"},"AL":{"prefix":"+355","min":8,"max":9,"country":"Albania"},"AM":{"prefix":"+374","min":8,"max":8,"country":"Armenia"},"AN":{"prefix":"+599","min":7,"max":8,"country":"Netherlands Antilles"},"AO":{"prefix":"+244","min":9,"max":9,"country":"Angola"},"AQ":{"prefix":"+672","min":10,"max":10,"country":"Antarctica"},"AR":{"prefix":"+54","min":10,"max":10,"country":"Argentina"},"AS":{"prefix":"+1684","min":7,"max":7,"country":"American Samoa"},"AT":{"prefix":"+43","min":5,"max":13,"country":"Austria"},"AU":{"prefix":"+61","min":9,"max":9,"country":"Australia"},"AW":{"prefix":"+297","min":7,"max":7,"country":"Aruba"},"AX":{"prefix":"+358","min":7,"max":10,"country":"Åland Islands"},"AZ":{"prefix":"+994","min":9,"max":9,"country":"Azerbaijan"},"BA":{"prefix":"+387","min":8,"max":9,"country":"Bosnia and Herzegovina"},"BB":{"prefix":"+1246","min":7,"max":7,"country":"Barbados"},"BD":{"prefix":"+880","min":10,"max":10,"country":"Bangladesh"},"BE":{"prefix":"+32","min":8,"max":9,"country":"Belgium"},"BF":{"prefix":"+226","min":8,"max":8,"country":"Burkina Faso"},"BG":{"prefix":"+359","min":8,"max":9,"country":"Bulgaria"},"BH":{"prefix":"+973","min":8,"max":8,"country":"Bahrain"},"BI":{"prefix":"+257","min":8,"max":8,"country":"Burundi"},"BJ":{"prefix":"+229","min":10,"max":10,"country":"Benin"},"BL":{"prefix":"+590","min":9,"max":9,"country":"Saint Barthélemy"},"BM":{"prefix":"+1441","min":7,"max":7,"country":"Bermuda"},"BN":{"prefix":"+673","min":7,"max":7,"country":"Brunei"},"BO":{"prefix":"+591","min":8,"max":8,"country":"Bolivia"},"BR":{"prefix":"+55","min":10,"max":11,"country":"Brazil"},"BS":{"prefix":"+1242","min":7,"max":7,"country":"Bahamas"},"BT":{"prefix":"+975","min":7,"max":8,"country":"Bhutan"},"BW":{"prefix":"+267","min":7,"max":10,"country":"Botswana"},"BY":{"prefix":"+375","min":9,"max":9,"country":"Belarus"},"BZ":{"prefix":"+501","min":7,"max":7,"country":"Belize"},"CA":{"prefix":"+1","min":10,"max":10,"country":"Canada"},"CC":{"prefix":"+61","min":9,"max":9,"country":"Coco (Keeling) Islands"},"CD":{"prefix":"+243","min":7,"max":9,"country":"DR Congo"},"CF":{"prefix":"+236","min":8,"max":8,"country":"Central African Republic"},"CG":{"prefix":"+242","min":9,"max":9,"country":"The Congo"},"CH":{"prefix":"+41","min":9,"max":9,"country":"Switzerland"},"CI":{"prefix":"+225","min":10,"max":10,"country":"Ivory Coast"},"CK":{"prefix":"+682","min":5,"max":5,"country":"Cook Islands"},"CL":{"prefix":"+56","min":9,"max":9,"country":"Chile"},"CM":{"prefix":"+237","min":9,"max":9,"country":"Cameroon"},"CN":{"prefix":"+86","min":10,"max":11,"country":"China"},"CO":{"prefix":"+57","min":8,"max":10,"country":"Colombia"},"CR":{"prefix":"+506","min":8,"max":8,"country":"Costa Rica"},"CU":{"prefix":"+53","min":8,"max":8,"country":"Cuba"},"CV":{"prefix":"+238","min":7,"max":7,"country":"Cape Verde"},"CX":{"prefix":"+61","min":9,"max":9,"country":"Christmas Island"},"CY":{"prefix":"+357","min":8,"max":8,"country":"Cyprus"},"CZ":{"prefix":"+420","min":9,"max":9,"country":"Czech Republic"},"DE":{"prefix":"+49","min":5,"max":11,"country":"Germany"},"DJ":{"prefix":"+253","min":8,"max":8,"country":"Djibouti"},"DK":{"prefix":"+45","min":8,"max":8,"country":"Denmark"},"DM":{"prefix":"+1767","min":7,"max":7,"country":"Dominica"},"DO":{"prefix":"+1849","min":7,"max":7,"country":"Dominican Republic"},"DZ":{"prefix":"+213","min":8,"max":9,"country":"Algeria"},"EC":{"prefix":"+593","min":9,"max":9,"country":"Ecuador"},"EE":{"prefix":"+372","min":7,"max":8,"country":"Estonia"},"EG":{"prefix":"+20","min":8,"max":10,"country":"Egypt"},"ER":{"prefix":"+291","min":7,"max":7,"country":"Eritrea"},"ES":{"prefix":"+34","min":9,"max":9,"country":"Spain"},"ET":{"prefix":"+251","min":9,"max":9,"country":"Ethiopia"},"FI":{"prefix":"+358","min":7,"max":10,"country":"Finland"},"FJ":{"prefix":"+679","min":7,"max":7,"country":"Fiji"},"FK":{"prefix":"+500","min":5,"max":5,"country":"Falkland Islands"},"FM":{"prefix":"+691","min":7,"max":7,"country":"Micronesia"},"FO":{"prefix":"+298","min":6,"max":6,"country":"Faroe Islands"},"FR":{"prefix":"+33","min":9,"max":9,"country":"France"},"GA":{"prefix":"+241","min":8,"max":8,"country":"Gabon"},"GB":{"prefix":"+44","min":9,"max":10,"country":"United Kingdom"},"GD":{"prefix":"+1473","min":7,"max":7,"country":"Grenada"},"GE":{"prefix":"+995","min":9,"max":9,"country":"Georgia"},"GF":{"prefix":"+594","min":9,"max":9,"country":"French Guiana"},"GG":{"prefix":"+44","min":10,"max":10,"country":"Guernsey"},"GH":{"prefix":"+233","min":9,"max":9,"country":"Ghana"},"GI":{"prefix":"+350","min":8,"max":8,"country":"Gibraltar"},"GL":{"prefix":"+299","min":6,"max":6,"country":"Greenland"},"GM":{"prefix":"+220","min":7,"max":9,"country":"Gambia"},"GN":{"prefix":"+224","min":9,"max":9,"country":"Guinea"},"GP":{"prefix":"+590","min":9,"max":9,"country":"Guadeloupe"},"GQ":{"prefix":"+240","min":9,"max":9,"country":"Equatorial Guinea"},"GR":{"prefix":"+30","min":10,"max":10,"country":"Greece"},"GS":{"prefix":"+500","min":5,"max":5,"country":"South Georgia and the South Sandwich Islands"},"GT":{"prefix":"+502","min":8,"max":8,"country":"Guatemala"},"GU":{"prefix":"+1671","min":7,"max":7,"country":"Guam"},"GW":{"prefix":"+245","min":9,"max":9,"country":"Guinea-Bissau"},"GY":{"prefix":"+592","min":7,"max":7,"country":"Guyana"},"HK":{"prefix":"+852","min":8,"max":8,"country":"Hong Kong"},"HM":{"prefix":"+672","min":8,"max":8,"country":"Heard Island and McDonald Islands"},"HN":{"prefix":"+504","min":8,"max":8,"country":"Honduras"},"HR":{"prefix":"+385","min":8,"max":9,"country":"Croatia"},"HT":{"prefix":"+509","min":8,"max":8,"country":"Haiti"},"HU":{"prefix":"+36","min":8,"max":9,"country":"Hungary"},"ID":{"prefix":"+62","min":7,"max":10,"country":"Indonesia"},"IE":{"prefix":"+353","min":6,"max":10,"country":"Ireland"},"IL":{"prefix":"+972","min":8,"max":9,"country":"Israel"},"IM":{"prefix":"+44","min":10,"max":10,"country":"Isle of Man"},"IN":{"prefix":"+91","min":10,"max":10,"country":"India"},"IO":{"prefix":"+246","min":7,"max":7,"country":"British Indian Ocean Territory"},"IQ":{"prefix":"+964","min":10,"max":10,"country":"Iraq"},"IR":{"prefix":"+98","min":5,"max":10,"country":"Iran"},"IS":{"prefix":"+354","min":7,"max":7,"country":"Iceland"},"IT":{"prefix":"+39","min":6,"max":11,"country":"Italy"},"JE":{"prefix":"+44","min":6,"max":6,"country":"Jersey"},"JM":{"prefix":"+1876","min":7,"max":7,"country":"Jamaica"},"JO":{"prefix":"+962","min":8,"max":9,"country":"Jordan"},"JP":{"prefix":"+81","min":9,"max":10,"country":"Japan"},"KE":{"prefix":"+254","min":7,"max":9,"country":"Kenya"},"KG":{"prefix":"+996","min":9,"max":9,"country":"Kyrgyzstan"},"KH":{"prefix":"+855","min":8,"max":8,"country":"Cambodia"},"KI":{"prefix":"+686","min":8,"max":8,"country":"Kiribati"},"KM":{"prefix":"+269","min":7,"max":7,"country":"Comoros"},"KN":{"prefix":"+1869","min":7,"max":7,"country":"Saint Kitts and Nevis"},"KP":{"prefix":"+850","min":6,"max":10,"country":"North Korea"},"KR":{"prefix":"+82","min":8,"max":11,"country":"South Korea"},"KW":{"prefix":"+965","min":8,"max":8,"country":"Kuwait"},"KY":{"prefix":"+1345","min":7,"max":7,"country":"Cayman Islands"},"KZ":{"prefix":"+7","min":10,"max":10,"country":"Kazakhstan"},"LA":{"prefix":"+856","min":8,"max":10,"country":"Laos"},"LB":{"prefix":"+961","min":7,"max":8,"country":"Lebanon"},"LC":{"prefix":"+1758","min":7,"max":7,"country":"Saint Lucia"},"LI":{"prefix":"+423","min":7,"max":9,"country":"Liechtenstein"},"LK":{"prefix":"+94","min":9,"max":9,"country":"Sri Lanka"},"LR":{"prefix":"+231","min":9,"max":9,"country":"Liberia"},"LS":{"prefix":"+266","min":8,"max":8,"country":"Lesotho"},"LT":{"prefix":"+370","min":8,"max":8,"country":"Lithuania"},"LU":{"prefix":"+352","min":8,"max":9,"country":"Luxembourg"},"LV":{"prefix":"+371","min":8,"max":8,"country":"Latvia"},"LY":{"prefix":"+218","min":8,"max":9,"country":"Libya"},"MA":{"prefix":"+212","min":9,"max":9,"country":"Morocco"},"MC":{"prefix":"+377","min":8,"max":8,"country":"Monaco"},"MD":{"prefix":"+373","min":8,"max":8,"country":"Moldova"},"ME":{"prefix":"+382","min":8,"max":8,"country":"Montenegro"},"MF":{"prefix":"+590","min":9,"max":9,"country":"Saint Martin"},"MG":{"prefix":"+261","min":9,"max":9,"country":"Madagascar"},"MH":{"prefix":"+692","min":7,"max":7,"country":"Marshall Islands"},"MK":{"prefix":"+389","min":8,"max":8,"country":"North Macedonia"},"ML":{"prefix":"+223","min":8,"max":8,"country":"Mali"},"MM":{"prefix":"+95","min":8,"max":10,"country":"Myanmar"},"MN":{"prefix":"+976","min":8,"max":8,"country":"Mongolia"},"MO":{"prefix":"+853","min":8,"max":8,"country":"Macau"},"MP":{"prefix":"+1670","min":7,"max":7,"country":"Northern Mariana Islands"},"MQ":{"prefix":"+596","min":9,"max":9,"country":"Martinique"},"MR":{"prefix":"+222","min":8,"max":8,"country":"Mauritania"},"MS":{"prefix":"+1664","min":7,"max":7,"country":"Montserrat"},"MT":{"prefix":"+356","min":8,"max":8,"country":"Malta"},"MU":{"prefix":"+230","min":7,"max":8,"country":"Mauritius"},"MV":{"prefix":"+960","min":7,"max":7,"country":"Maldives"},"MW":{"prefix":"+265","min":7,"max":9,"country":"Malawi"},"MX":{"prefix":"+52","min":10,"max":10,"country":"Mexico"},"MY":{"prefix":"+60","min":8,"max":11,"country":"Malaysia"},"MZ":{"prefix":"+258","min":8,"max":9,"country":"Mozambique"},"NA":{"prefix":"+264","min":9,"max":9,"country":"Namibia"},"NC":{"prefix":"+687","min":6,"max":6,"country":"New Caledonia"},"NE":{"prefix":"+227","min":8,"max":8,"country":"Niger"},"NF":{"prefix":"+672","min":6,"max":6,"country":"Norfolk Island"},"NG":{"prefix":"+234","min":10,"max":10,"country":"Nigeria"},"NI":{"prefix":"+505","min":8,"max":8,"country":"Nicaragua"},"NL":{"prefix":"+31","min":9,"max":9,"country":"Netherlands"},"NO":{"prefix":"+47","min":8,"max":8,"country":"Norway"},"NP":{"prefix":"+977","min":8,"max":10,"country":"Nepal"},"NR":{"prefix":"+674","min":7,"max":7,"country":"Nauru"},"NU":{"prefix":"+683","min":4,"max":7,"country":"Niue"},"NZ":{"prefix":"+64","min":8,"max":10,"country":"New Zealand"},"OM":{"prefix":"+968","min":8,"max":8,"country":"Oman"},"PA":{"prefix":"+507","min":7,"max":8,"country":"Panama"},"PE":{"prefix":"+51","min":8,"max":9,"country":"Peru"},"PF":{"prefix":"+689","min":8,"max":8,"country":"French Polynesia"},"PG":{"prefix":"+675","min":7,"max":8,"country":"Papua New Guinea"},"PH":{"prefix":"+63","min":9,"max":10,"country":"Philippines"},"PK":{"prefix":"+92","min":9,"max":10,"country":"Pakistan"},"PL":{"prefix":"+48","min":9,"max":9,"country":"Poland"},"PM":{"prefix":"+508","min":6,"max":9,"country":"Saint Pierre and Miquelon"},"PN":{"prefix":"+64","min":3,"max":10,"country":"Pitcairn"},"PR":{"prefix":"+1939","min":7,"max":7,"country":"Puerto Rico"},"PS":{"prefix":"+970","min":8,"max":9,"country":"State of Palestine"},"PT":{"prefix":"+351","min":9,"max":9,"country":"Portugal"},"PW":{"prefix":"+680","min":7,"max":7,"country":"Palau"},"PY":{"prefix":"+595","min":9,"max":9,"country":"Paraguay"},"QA":{"prefix":"+974","min":8,"max":8,"country":"Qatar"},"RE":{"prefix":"+262","min":9,"max":9,"country":"Réunion"},"RO":{"prefix":"+40","min":6,"max":9,"country":"Romania"},"RS":{"prefix":"+381","min":8,"max":12,"country":"Serbia"},"RU":{"prefix":"+7","min":10,"max":10,"country":"Russia"},"RW":{"prefix":"+250","min":9,"max":9,"country":"Rwanda"},"SA":{"prefix":"+966","min":9,"max":9,"country":"Saudi Arabia"},"SB":{"prefix":"+677","min":5,"max":7,"country":"Solomon Islands"},"SC":{"prefix":"+248","min":7,"max":7,"country":"Seychelles"},"SD":{"prefix":"+249","min":9,"max":9,"country":"Sudan"},"SE":{"prefix":"+46","min":7,"max":9,"country":"Sweden"},"SG":{"prefix":"+65","min":8,"max":8,"country":"Singapore"},"SH":{"prefix":"+290","min":4,"max":5,"country":"Saint Helena"},"SI":{"prefix":"+386","min":8,"max":8,"country":"Slovenia"},"SK":{"prefix":"+421","min":9,"max":9,"country":"Slovakia"},"SL":{"prefix":"+232","min":8,"max":8,"country":"Sierra Leone"},"SM":{"prefix":"+378","min":6,"max":10,"country":"San Marino"},"SN":{"prefix":"+221","min":9,"max":9,"country":"Senegal"},"SO":{"prefix":"+252","min":7,"max":9,"country":"Somalia"},"SR":{"prefix":"+597","min":6,"max":7,"country":"Suriname"},"SS":{"prefix":"+211","min":9,"max":9,"country":"South Sudan"},"ST":{"prefix":"+239","min":7,"max":7,"country":"Sao Tome and Principe"},"SV":{"prefix":"+503","min":8,"max":8,"country":"El Salvador"},"SY":{"prefix":"+963","min":8,"max":10,"country":"Syria"},"SZ":{"prefix":"+268","min":8,"max":8,"country":"Eswatini"},"TC":{"prefix":"+1649","min":7,"max":7,"country":"Turks and Caicos Islands"},"TD":{"prefix":"+235","min":8,"max":8,"country":"Chad"},"TF":{"prefix":"+262","min":9,"max":9,"country":"French Southern Territories"},"TG":{"prefix":"+228","min":8,"max":8,"country":"Togo"},"TH":{"prefix":"+66","min":8,"max":9,"country":"Thailand"},"TJ":{"prefix":"+992","min":9,"max":9,"country":"Tajikistan"},"TK":{"prefix":"+690","min":4,"max":7,"country":"Tokelau"},"TL":{"prefix":"+670","min":7,"max":8,"country":"Timor-Leste"},"TM":{"prefix":"+993","min":7,"max":8,"country":"Turkmenistan"},"TN":{"prefix":"+216","min":8,"max":8,"country":"Tunisia"},"TO":{"prefix":"+676","min":5,"max":7,"country":"Tonga"},"TR":{"prefix":"+90","min":10,"max":10,"country":"Turkey"},"TT":{"prefix":"+1868","min":7,"max":7,"country":"Trinidad and Tobago"},"TV":{"prefix":"+688","min":5,"max":7,"country":"Tuvalu"},"TW":{"prefix":"+886","min":8,"max":9,"country":"Taiwan"},"TZ":{"prefix":"+255","min":9,"max":9,"country":"Tanzania"},"UA":{"prefix":"+380","min":9,"max":9,"country":"Ukraine"},"UG":{"prefix":"+256","min":9,"max":9,"country":"Uganda"},"US":{"prefix":"+1","min":10,"max":10,"country":"United States"},"UY":{"prefix":"+598","min":8,"max":8,"country":"Uruguay"},"UZ":{"prefix":"+998","min":9,"max":9,"country":"Uzbekistan"},"VA":{"prefix":"+379","min":6,"max":11,"country":"Vatican City"},"VC":{"prefix":"+1784","min":7,"max":7,"country":"Saint Vincent and the Grenadines"},"VE":{"prefix":"+58","min":10,"max":10,"country":"Venezuela"},"VG":{"prefix":"+1284","min":7,"max":7,"country":"British Virgin Islands"},"VI":{"prefix":"+1340","min":7,"max":7,"country":"United States Virgin Islands"},"VN":{"prefix":"+84","min":9,"max":10,"country":"Vietnam"},"VU":{"prefix":"+678","min":5,"max":7,"country":"Vanuatu"},"WF":{"prefix":"+681","min":6,"max":6,"country":"Wallis and Futuna"},"WS":{"prefix":"+685","min":5,"max":7,"country":"Samoa"},"XK":{"prefix":"+383","min":8,"max":9,"country":"Kosovo"},"YE":{"prefix":"+967","min":7,"max":9,"country":"Yemen"},"YT":{"prefix":"+262","min":9,"max":9,"country":"Mayotte"},"ZA":{"prefix":"+27","min":9,"max":9,"country":"South Africa"},"ZM":{"prefix":"+260","min":9,"max":9,"country":"Zambia"},"ZW":{"prefix":"+263","min":9,"max":9,"country":"Zimbabwe"}},

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
                            var p = '';
                            $(':input[id*="'+me.name+'_"]').each(function(){
                                if(this.tagName == 'SELECT') p = $(this).val();
                                v+=$(this).val();
                            });
                            $('#'+me.name).val(v != p ? v : '').trigger('change');
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
						let symbol = me.isFree ? '#' : me.dformat[0], // Symbol to use for format.
							max    = country_obj['max'],
							min    = country_obj['min'],
							d      = /\s/.test(me.dformat) ? 3 : max,
							max_r  = max % d,
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
							(
								this.showFormatAsHint !== false ?
								'<div class="l" style="'+cff_esc_attr(me.getCSSComponent('format'))+'">'+cff_sanitize(symbol.repeat(w), true)+'</div>' :
								''
							)
							+
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

					me.isFree = !me.dformat.length;

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
						tmp = me.isFree ? [Array(me.FREE_MAX_DIGITS + 1).join('#')] : me.dformat.split(/\s+/),
						attr = (typeof me.predefinedClick != 'undefined' && me.predefinedClick) ? 'placeholder' : 'value',
						nc   = Math.max(1, me.dformat.replace(/\s/g, '').length),
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
									txt = '<span class="country-flag"><img src="https://cdn.jsdelivr.net/gh/cffdwboostercom/flags@main/'+db[countries[i]]['iso'].toLowerCase()+'.png"></span>'+txt;
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
						let l = tmp[i].length,
							w = me.isFree ? 'calc(100% - ' + cw + 'px)' : 'calc( ( 100% - ' + cw + 'px ) / ' + Math.max(1, nc) + ' * ' + Math.max(1, l) +')',
							mn = me.isFree ? 0 : l;

						str += ''+
						'<div class="uh_phone" style="width:'+ w +';">'+
							'<input aria-label="'+cff_esc_attr(me.title)+'" type="text" id="'+me.name+'_'+c+'" name="'+me.name+'_'+c+'" class="field '+((i==0 && !me.countryComponent) ? ' phone ' : ' digits ')+((me.required) ? ' required ' : '')+'" size="'+cff_esc_attr(l)+'" '+attr+'="'+cff_esc_attr(tmpv.substring(0,l))+'" maxlength="'+cff_esc_attr(l)+'" minlength="'+cff_esc_attr(mn)+'" '+((me.readonly)?'readonly':'')+' style="'+cff_esc_attr(me.getCSSComponent('phone'))+'" />'+
							( ! me.isFree && me.showFormatAsHint != false ? '<div class="l" style="'+cff_esc_attr(me.getCSSComponent('format'))+'">'+cff_sanitize(tmp[i], true)+'</div>' : '' )+
						'</div>';

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
									if ( ! state.id ) return state.text;
									if ('DOMPurify' in window) {
										let sanitized = DOMPurify.sanitize('<span class="prefix-option">'+state.text+'</span>');
										return $(sanitized);
									}
									return $('<span class="prefix-option">').text(state.text);
								},
                                'templateSelection': function(state){
									if ( ! state.id ) return state.text;
									let doc = new DOMParser().parseFromString(String(state.text), 'text/html');
									let code = doc.querySelector('.country-code');
									return code ? code.textContent : doc.body.textContent || '';
								},
								'dropdownAutoWidth' : true,
								'dropdownParent':prefix.next('.cff-select2-container'),
                                'matcher': function(params, data) {
                                    function stripHtml(str) {
                                        var doc = new DOMParser().parseFromString(str, 'text/html');
                                        return doc.body.textContent || '';
                                    };

                                    if ($.trim(params.term) === '') {
                                        return data;
                                    }

                                    if (typeof data.text === 'undefined') {
                                        return null;
                                    }

                                    var plainText = stripHtml(String(data.text)).trim();
                                    var term = String(params.term).toUpperCase().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

                                    if (new RegExp('(?:^|\\W)' + term, 'i').test(plainText)) {
                                        return data;
                                    }

                                    return null;
                                }
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