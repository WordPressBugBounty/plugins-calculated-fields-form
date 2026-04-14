	$.fbuilder.typeList.push(
		{
			id:"fmap_widget",
			name:"Map",
			control_category:30
		}
	);
	$.fbuilder.controls[ 'fmap_widget' ]=function(){};
	$.extend(
		true,
		$.fbuilder.controls[ 'fmap_widget' ].prototype,
		$.fbuilder.controls[ 'ffields' ].prototype,
		{
			title:"Map",
			ftype:"fmap_widget",

			map_width: '100%',
			map_height: '350px',
			map_provider: 'public',
			map_provider_url: '',

			// Center and bounding.
            pin_title: '',
			latitude: '',
			longitude: '',
			zoom: '',
			radius: '',

			allow_user_pin: true,
			associated_address_field: '',

            initAdv: function () {
                delete this.advanced.css['input'];
                if (!('map_container' in this.advanced.css)) this.advanced.css.map_container = { label: 'Map container', rules: {} };
            },

			display:function( css_class )
				{
					css_class = css_class || '';
					let id = 'field'+this.form_identifier+'-'+this.index;
					let lat = this.latitude || 0;
					let lng = this.longitude || 0;
					let radius = this.radius || 0;
					let zoom = this.zoom || 13;
					let staticMapUrl = cpcff_static_map_url_template.replace('{lat}', lat).replace('{lng}', lng).replace('{radius}', radius).replace('{zoom}', zoom);
					return '<div class="fields '+this.name+' '+this.ftype+' '+css_class+'" id="'+id+'" title="'+this.controlLabel('Single Line Text')+'"><div class="arrow ui-icon ui-icon-grip-dotted-vertical "></div>'+this.iconsContainer()+'<label for="'+id+'-box">'+cff_sanitize(this.title, true)+''+((this.required)?"*":"")+'</label><div class="dfield">'+this.showColumnIcon()+'<div style="width:100%;height:250px;"><iframe id="staticMapIframe_'+id+'" style="width:100%; height:100%; border:none; pointer-events:none;" src="'+cff_esc_attr(staticMapUrl)+'"></iframe></div><span class="uh">'+cff_sanitize(this.userhelp, true)+'</span></div><div class="clearer"></div></div>';
				},
			editItemEvents:function()
				{
                    function sanitizeWidthHeight(value) {
                        if (value === null || value === undefined) {
                            return '';
                        }
                        if (typeof value === 'number') {
                            return String(value) + 'px';
                        } else if (typeof value !== 'string') {
                            return '';
                        }
                        value = value.replace(/\s/g, '');
                        if (value === '') {
                            return '';
                        }
                        const numericOnly = /^[-+]?(?:\d*\.\d+|\d+)$/;
                        const validDimension = /^0$|^[-+]?(?:\d*\.\d+|\d+)(?:px|em|rem|%|cm|mm|in|pt|pc|ex|ch|vw|vh|vmin|vmax|q)$/i;
                        if (numericOnly.test(value)) {return value + 'px';}
                        if (!validDimension.test(value)) {
                            value = value.replace(/[^0-9]+/g, '');
                            if (value === '') return '';
                            return value + 'px';
                        }
                        return value;
                    };

					let me  = this;
					let evt = [
                            {s:"#sMapProvider",e:"input", l:"map_provider", x:1, f:function(el){
								let v = el.val();
								$('.sMapProviderUrl')[v=='private' ? 'show' : 'hide']();
								return v;
							}, silent:1},
                            {s:"#sMapProviderUrl",e:"input change",l:"map_provider_url",x:1,silent:1},
                            {s:"#sMapWidth",e:"change", l:"map_width", x:1, f:function(el){
                                let v = sanitizeWidthHeight(el.val());
                                el.val(v);
                                return v;
                            }, silent:1},
                            {s:"#sMapHeight",e:"change", l:"map_height", x:1, f:function(el){
                                let v = sanitizeWidthHeight(el.val());
                                el.val(v);
                                return v;
                            }, silent:1},
                            {s:"#sPinTitle",e:"input change",l:"pin_title",x:1,silent:1},
                            {s:"#sLatitude",e:"input change", l:"latitude", x:1, f:function(el, updateMap){
                                updateMap = typeof updateMap === 'undefined' ? true : updateMap;
								let v = el.val();
								let iframe = $('#mapIframe')[0];
								if (updateMap && iframe && iframe.contentWindow) {
									let lng = $('#sLongitude').val() || 0;
									iframe.contentWindow.postMessage({type: 'setCenter', lat: v || 0, lng: lng}, '*');
								}
								return v;
                            }},
                            {s:"#sLongitude",e:"input change", l:"longitude", x:1, f:function(el, updateMap){
                                updateMap = typeof updateMap === 'undefined' ? true : updateMap;
								let v = el.val();
								let iframe = $('#mapIframe')[0];
								if (updateMap && iframe && iframe.contentWindow) {
									let lat = $('#sLatitude').val() || 0;
									iframe.contentWindow.postMessage({type: 'setCenter', lat: lat, lng: v || 0}, '*');
								}
								return v;
							}},
                            {s:"#sZoom",e:"input change", l:"zoom", f:function(el){
								let v = el.val();
								return v;
							}},
                            {s:"#sRadius",e:"input change", l:"radius", f:function(el){
								let v = el.val();
								return v;
							}},
                            {s:"#sAllowUserPin",e:"input change", l:"allow_user_pin", f:function(el){
								let is_checked = $(this).is(':checked');
								$('.sAssociatedAddressField')[is_checked ? 'show' : 'hide']();
								return is_checked;
                            }, silent:1},
                            {s:"#sAssociatedAddressField", e:"change", l:"associated_address_field", x:1, silent:1}
                        ],
                        items           = this.fBuild.getItems(),
                        allowedTypes    = { ftext: 1, ftextds: 1 },
                        options			= '<option value=""></option>';

                    for (let i = 0; i < items.length; i++) {
                        if (allowedTypes[items[i].ftype]) {
							let item = items[i];
							options += '<option value="'+cff_esc_attr(item.name)+'" '+((item.name == me.associated_address_field)?"selected":"")+'>'+cff_esc_attr(item.title+' ('+item.name+')')+'</option>';
						}
                    }

                    $('#sAssociatedAddressField').html(options);

                    $('#mapIframe').attr('src', cpcff_map_widget_url);
					$('#mapIframe').on('load', function() {
						var iframe = this;
						var lat = $('#sLatitude').val() || 0;
						var lng = $('#sLongitude').val() || 0;
						if (!lat && !lng) {
							if (navigator.geolocation) {
								navigator.geolocation.getCurrentPosition(function(pos) {
									lat = pos.coords.latitude;
									lng = pos.coords.longitude;
                                    $('#sLatitude').val(lat).trigger('change');
                                    $('#sLongitude').val(lng).trigger('change');
								});
							}
						} else {
							iframe.contentWindow.postMessage({type: 'setCenter', lat: lat, lng: lng}, '*');
						}
					});
					window.addEventListener('message', function(e) {
						if (e.data.type === 'updateLatLng') {
							$('#sLatitude').val(e.data.lat).trigger('change', false);
							$('#sLongitude').val(e.data.lng).trigger('change', false);
						}
					});
					$.fbuilder.controls[ 'ffields' ].prototype.editItemEvents.call(this,evt);
				},
			showSpecialDataInstance: function()
				{
					let output = '<label for="sMapProvider">Map Provider</label><select name="sMapProvider" id="sMapProvider" class="large">'+
					'<option value="public" '+(this.map_provider == 'public' ? 'selected' : '')+'>Public Map Provider</option>'+
					'<option value="private" '+(this.map_provider == 'private' ? 'selected' : '')+'>Own Map Provider</option>'+
					'</select>'+

					'<div class="sMapProviderUrl" style="display:'+(this.map_provider == 'private' ? 'block' : 'none')+';"><label for="sMapProviderUrl">Map Provider URL</label><input type="text" name="sMapProviderUrl" id="sMapProviderUrl" value="'+cff_esc_attr(this.map_provider_url)+'" class="large"></div>'+

					'<div class="column width50"><label for="sMapWidth">Map Width</label><input type="text" name="sMapWidth" id="sMapWidth" value="'+cff_esc_attr(this.map_width)+'" class="large"></div>'+
					'<div class="column width50"><label for="sMapHeight">Map Height</label><input type="text" name="sMapHeight" id="sMapHeight" value="'+cff_esc_attr(this.map_height)+'" class="large"></div>'+
					'<div class="clearer"></div>'+

					'<div class="sMapContainer" style="margin-top:10px;margin-bottom:10px;"><iframe id="mapIframe" style="width:100%; height:250px; border:none;"></iframe></div>'+

                    '<label for="sPinTitle">Pin Title</label><input type="text" name="sPinTitle" id="sPinTitle" value="' + cff_esc_attr(this.pin_title) +'" class="large">'+

                    '<div class="column width50"><label for="sLatitude">Center Pin Latitude</label><input type="number" name="sLatitude" id="sLatitude" value="' + cff_esc_attr(this.latitude) +'" class="large" step="any"></div>'+

					'<div class="column width50"><label for="sLongitude">Center Pin Longitude</label><input type="number" name="sLongitude" id="sLongitude" value="'+cff_esc_attr(this.longitude)+'" class="large" step="any"></div>'+
					'<div class="clearer"></div>'+

					'<div class="column width50"><label for="sRadius">Bounding Radius</label><input type="number" name="sRadius" id="sRadius" value="'+cff_esc_attr(this.radius)+'" class="large"><i>(in meters)</i></div>'+
					'<div class="column width50"><label for="sZoom">Default Map Zoom</label><input type="number" name="sZoom" id="sZoom" value="'+cff_esc_attr(this.zoom)+'" class="large"><i>(0-19)</i></div>'+
					'<div class="clearer"></div>'+

					'<div><label><input type="checkbox" name="sAllowUserPin" id="sAllowUserPin" '+(this.allow_user_pin ? 'checked' : '')+'> Allow users to insert map pin</label></div>'+

					'<div class="sAssociatedAddressField" style="display:'+(this.allow_user_pin ? 'block' : 'none' )+';"><label for="sAssociatedAddressField">Associated Address Field</label><select name="sAssociatedAddressField" id="sAssociatedAddressField" class="large"></select><i>(Select an address field to associate with the map pin)</i></div>';

					return output;
				}
	});