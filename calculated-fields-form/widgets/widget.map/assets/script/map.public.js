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
			user_pin_color: '#ff0000',
			associated_address_field: '',

            init: function() {
                let me = this;
                me.map_width = String(me.map_width).trim();
                me.map_height = String(me.map_height).trim();

                if (me.map_width === '') me.map_width = '100%';
                else if (!isNaN(me.map_width*1)) me.map_width += 'px';

                if (me.map_height === '') me.map_height = '350px';
                else if (!isNaN(me.map_height*1)) me.map_height += 'px';

                me.map_provider = String(me.map_provider || 'public').trim().toLowerCase();
                me.map_provider_url = String(me.map_provider_url || '').trim();
                if (me.map_provider !== 'public') {
                    let validUrl = false;
                    try {
                        if (me.map_provider_url !== '') {
                            let parsed = new URL(me.map_provider_url);
                            let hasValidProtocol = parsed.protocol === 'https:' || parsed.protocol === 'http:';
                            validUrl = hasValidProtocol;
                        }
                    } catch (err) {
                        validUrl = false;
                    }

                    if (!validUrl) {
                        me.map_provider = 'public';
                        me.map_provider_url = '';
                    }
                }

                me.latitude = parseFloat(me.latitude);
                if (isNaN(me.latitude) || me.latitude < -90 || me.latitude > 90) {
                    me.latitude = 0;
                }
                me.longitude = parseFloat(me.longitude);
                if (isNaN(me.longitude) || me.longitude < -180 || me.longitude > 180) {
                    me.longitude = 0;
                }

                me.zoom = parseInt(me.zoom, 10);
                if (isNaN(me.zoom)) {
                    me.zoom = 13;
                }

                me.radius = parseFloat(me.radius);
                if (isNaN(me.radius) || me.radius < 0) {
                    me.radius = 0;
                }

                me.allow_user_pin = (me.allow_user_pin === true || me.allow_user_pin === 1 || me.allow_user_pin === '1' || me.allow_user_pin === 'true');
                me.user_pin_color = me.user_pin_color || '#ff0000';
                me.associated_address_field = me.associated_address_field || '';
            },

            show: function() {
                let me = this;

                return '<div class="fields ' + cff_esc_attr(me.csslayout) + ' ' + me.name + ' cff-map-widget" id="field' + me.form_identifier + '-' + me.index + '" style="' + cff_esc_attr(me.getCSSComponent('container')) + '"><label for="' + me.name + '" style="' + cff_esc_attr(me.getCSSComponent('label')) + '">' + cff_sanitize(me.title, true) + '</label><div class="dfield">' +

                '<div id="' + me.name + '" class="cff-map-container" style="width:' + cff_esc_attr(me.map_width) + ';height:' + cff_esc_attr(me.map_height) + ';' + cff_esc_attr(this.getCSSComponent('map_container')) + '"></div>'+

                '<span class="uh" style="' + cff_esc_attr(this.getCSSComponent('help')) + '">' + cff_sanitize(this.userhelp, true) + '</span></div><div class="clearer"></div></div>';
            },

            after_show: function() {
                let me = this;
                if (typeof L === 'undefined') {
                    var link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(link);

                    var script = document.createElement('script');
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    script.onload = function() { me._initMap(); };
                    document.head.appendChild(script);
                } else {
                    me._initMap();
                }
            },

            _initMap: function() {
                let me = this;
                var mapContainer = document.getElementById(me.name);
                if (!mapContainer) return;

                if (me.latitude === '' || isNaN(me.latitude)) me.latitude = 0;
                if (me.longitude === '' || isNaN(me.longitude)) me.longitude = 0;
                if (me.zoom === '' || isNaN(me.zoom)) me.zoom = 13;

                me._map = L.map(me.name).setView([me.latitude, me.longitude], me.zoom);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(me._map);

                var userMarker = null;

                if (me.latitude != 0 || me.longitude != 0) {
                    var titleAttr = me.pin_title ? { title: me.pin_title } : {};
                    var preMarker = L.marker([me.latitude, me.longitude], titleAttr).addTo(me._map);
                    if (me.pin_title) preMarker.bindPopup(me.pin_title).openPopup();
                }

                if (me.radius > 0) {
                    L.circle([me.latitude, me.longitude], {
                        radius: me.radius,
                        color: '#0066ff',
                        weight: 2,
                        opacity: 0.6,
                        fillColor: '#0066ff',
                        fillOpacity: 0.1
                    }).addTo(me._map);
                }

                if (me.allow_user_pin) {
                    var userPinIcon = L.divIcon({
                        className: 'cff-user-pin-icon',
                        html: '<div style="background-color:' + me.user_pin_color + ';width:24px;height:24px;border-radius:50%;border:2px solid white;box-shadow:0 0 5px rgba(0,0,0,0.5);"></div>',
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
                    });

                    me._map.on('click', function(e) {
                        if (me.radius > 0 && me._map.distance(e.latlng, [me.latitude, me.longitude]) > me.radius) return;
                        if (userMarker) me._map.removeLayer(userMarker);
                        userMarker = L.marker(e.latlng, { icon: userPinIcon, draggable: true }).addTo(me._map);
                        userMarker.on('dragend', function(ev) { me._handleMarkerDrag(ev.target.getLatLng()); });
                        me._handleMarkerClick(e.latlng);
                    });

                    if (me.associated_address_field) {
                        var addrField = getField(me.associated_address_field, me.form_identifier);
                        if (addrField) {
                            addrField.jQueryRef().find(':input').on('change', function() {
                                if (me.triggeredByMap) { me.triggeredByMap = false; return; }
                                var addr = addrField.val(true, true);
                                if (addr && addr.trim()) {
                                    me._geocodeAddress(addr, function(lat, lng) {
                                        if (lat && lng) {
                                            if (userMarker) me._map.removeLayer(userMarker);
                                            userMarker = L.marker([lat, lng], { icon: userPinIcon, draggable: true }).addTo(me._map);
                                            userMarker.on('dragend', function(ev) { me._handleMarkerDrag(ev.target.getLatLng()); });
                                            me._map.setView([lat, lng], me.zoom);
                                        }
                                    });
                                }
                            });
                        }
                    }
                }

                var checkAndInvalidate = function() {
                    var rect = mapContainer.getBoundingClientRect();
                    if (rect.width > 0 && rect.height > 0) {
                        me._map.invalidateSize();
                        observer.disconnect();
                    }
                };

                if (!mapContainer.offsetParent) {
                    var observer = new MutationObserver(checkAndInvalidate);
                    observer.observe(document.body, { childList: true, subtree: true, attributes: true });
                }
            },

            _handleMarkerClick: function(latlng) {
                let me = this;
                me._reverseGeocode(latlng.lat, latlng.lng, function(address) {
                    if (address && me.associated_address_field) {
                        var addrField = getField(me.associated_address_field, me.form_identifier);
                        if (addrField) {
                            me.triggeredByMap = true;
                            addrField.setVal(address);
                        }
                    }
                });
            },

            _handleMarkerDrag: function(latlng) {
                this._handleMarkerClick(latlng);
            },

            _getGeocodingUrl: function() {
                let me = this;
                if (me.map_provider !== 'public' && me.map_provider_url) {
                    return me.map_provider_url.replace(/\/$/, '');
                }
                return '';
            },

            _reverseGeocode: function(lat, lng, callback) {
                let me = this;
                var customUrl = me._getGeocodingUrl();

                var tryReverseGeocode = function(baseUrl, onSuccess, onFail) {
                    var url = baseUrl + '/reverse?format=json&lat=' + lat + '&lon=' + lng;
                    fetch(url)
                        .then(function(response) { return response.json(); })
                        .then(function(data) {
                            if (data && data.display_name) {
                                onSuccess(data.display_name);
                            } else {
                                onFail();
                            }
                        })
                        .catch(function() { onFail(); });
                };

                var nominatimFallback = function() {
                    tryReverseGeocode('https://nominatim.openstreetmap.org', function(result) {
                        callback(result);
                    }, function() {
                        tryReverseGeocode('https://photon.komoot.io', function(result) {
                            callback(result);
                        }, function() {
                            callback('');
                        });
                    });
                };

                if (customUrl) {
                    tryReverseGeocode(customUrl, function(result) {
                        callback(result);
                    }, function() {
                        nominatimFallback();
                    });
                } else {
                    nominatimFallback();
                }
            },

            _geocodeAddress: function(address, callback) {
                let me = this;
                var customUrl = me._getGeocodingUrl();

                var tryGeocode = function(baseUrl, onSuccess, onFail) {
                    var url = baseUrl + '/search?format=json&q=' + encodeURIComponent(address);
                    fetch(url)
                        .then(function(response) { return response.json(); })
                        .then(function(data) {
                            if (data && data.length > 0) {
                                onSuccess(parseFloat(data[0].lat), parseFloat(data[0].lon));
                            } else {
                                onFail();
                            }
                        })
                        .catch(function() { onFail(); });
                };

                var nominatimFallback = function() {
                    tryGeocode('https://nominatim.openstreetmap.org', function(lat, lng) {
                        callback(lat, lng);
                    }, function() {
                        tryGeocode('https://photon.komoot.io', function(lat, lng) {
                            callback(lat, lng);
                        }, function() {
                            callback(null, null);
                        });
                    });
                };

                if (customUrl) {
                    tryGeocode(customUrl, function(lat, lng) {
                        callback(lat, lng);
                    }, function() {
                        nominatimFallback();
                    });
                } else {
                    nominatimFallback();
                }
            },

	});