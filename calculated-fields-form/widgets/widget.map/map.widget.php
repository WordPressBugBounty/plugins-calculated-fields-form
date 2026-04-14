<?php
/*
....
*/
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('CPCFF_WidgetBase')) {
    class CPCFF_Map_Widget extends CPCFF_WidgetBase
    {
        public function __construct()
        {
            parent::__construct();
            if (is_admin() && current_user_can(apply_filters('cpcff_forms_edition_capability', 'manage_options'))) {
                if (
                    isset($_GET['_cpcff_map_widget']) &&
                    $_GET['_cpcff_map_widget'] === '1' &&
                    check_admin_referer('cff-map-widget', '_cpcff_nonce')
                ) {
                    // Serve the map iframe content for admin
                    $this->_getMap();
                    exit;
                }
            }
            // Handle static map display
            if (
                isset($_GET['_cpcff_static_map']) &&
                $_GET['_cpcff_static_map'] === '1' &&
                isset($_GET['_cpcff_lat']) &&
                isset($_GET['_cpcff_lng']) &&
                isset($_GET['_cpcff_radius']) &&
                isset($_GET['_cpcff_zoom']) &&
                check_admin_referer('cff-static-map', '_cpcff_nonce')
            ) {
                // Serve the static map iframe content
                $this->_getStaticMap(
                    floatval($_GET['_cpcff_lat']),
                    floatval($_GET['_cpcff_lng']),
                    floatval($_GET['_cpcff_radius']),
                    intval($_GET['_cpcff_zoom'])
                );
                exit;
            }
        } // End __construct

        private function _getMap()
        {
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                <style>
                    html,
                    body {
                        height: 100%;
                        margin: 0;
                    }

                    #map {
                        height: 100%;
                    }
                </style>
            </head>
            <body>
                <div id="map"></div>
                <script>
                    var map = L.map('map').setView([0, 0], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    }).addTo(map);
                    var marker;

                    function setCenter(lat, lng) {
                        map.setView([lat, lng], 13);
                        if (marker) map.removeLayer(marker);
                        marker = L.marker([lat, lng], {
                            draggable: true
                        }).addTo(map);
                        marker.on('dragend', function(e) {
                            var pos = e.target.getLatLng();
                            window.parent.postMessage({
                                type: 'updateLatLng',
                                lat: pos.lat,
                                lng: pos.lng
                            }, '*');
                        });
                    }
                    map.on('click', function(e) {
                        if (marker) map.removeLayer(marker);
                        marker = L.marker(e.latlng, {
                            draggable: true
                        }).addTo(map);
                        marker.on('dragend', function(e) {
                            var pos = e.target.getLatLng();
                            window.parent.postMessage({
                                type: 'updateLatLng',
                                lat: pos.lat,
                                lng: pos.lng
                            }, '*');
                        });
                        window.parent.postMessage({
                            type: 'updateLatLng',
                            lat: e.latlng.lat,
                            lng: e.latlng.lng
                        }, '*');
                    });
                    window.addEventListener('message', function(e) {
                        if (e.data.type === 'setCenter') {
                            setCenter(e.data.lat, e.data.lng);
                        }
                    });
                </script>
            </body>
            </html>
            <?php
        }

        private function _getStaticMap($lat, $lng, $radius, $zoom = 13)
        {
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                <style>
                    html,
                    body {
                        height: 100%;
                        margin: 0;
                    }

                    #map {
                        height: 100%;
                    }
                </style>
            </head>
            <body>
                <div id="map"></div>
                <script>
                    var map = L.map('map').setView([<?php echo $lat; ?>, <?php echo $lng; ?>], <?php echo $zoom; ?>);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    }).addTo(map);

                    // Disable all map interactions to make it static
                    map.dragging.disable();
                    map.touchZoom.disable();
                    map.doubleClickZoom.disable();
                    map.scrollWheelZoom.disable();
                    map.boxZoom.disable();
                    map.keyboard.disable();
                    if (map.tap) map.tap.disable();

                    // Display pin marker
                    L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>]).addTo(map);

                    // Display bounding circle if radius > 0
                    <?php if ($radius > 0) { ?>
                    var circle = L.circle([<?php echo $lat; ?>, <?php echo $lng; ?>], {
                        radius: <?php echo $radius; ?>,
                        color: '#0066ff',
                        weight: 2,
                        opacity: 0.6,
                        fillColor: '#0066ff',
                        fillOpacity: 0.1
                    }).addTo(map);

                    // Fit map to show the entire circle
                    map.fitBounds(circle.getBounds());
                    <?php } ?>
                </script>
            </body>
            </html>
            <?php
        }

        protected function admin_scripts()
        {
            // Inject the URL with the nonce to get the forms list
            $url = add_query_arg(['_cpcff_nonce'  => wp_create_nonce('cff-map-widget'), '_cpcff_map_widget' => '1'], admin_url());
            print 'var cpcff_map_widget_url = ' . wp_json_encode($url) . ';';
            // Inject the static map URL template
            $static_map_template = add_query_arg(['_cpcff_nonce'  => wp_create_nonce('cff-static-map'), '_cpcff_static_map' => '1', '_cpcff_lat' => '{lat}', '_cpcff_lng' => '{lng}', '_cpcff_radius' => '{radius}', '_cpcff_zoom' => '{zoom}'], admin_url());
            print 'var cpcff_static_map_url_template = ' . wp_json_encode($static_map_template) . ';';
            // Inject control scripts
            print file_get_contents(dirname(__FILE__) . '/assets/script/map.admin.js');
        }

        protected function public_scripts()
        {
            // Inject control scripts
            print file_get_contents(dirname(__FILE__) . '/assets/script/map.public.js');
        }

		protected function admin_styles()
        {
            wp_enqueue_style('cpcff_map_widget_css', plugins_url('/assets/style/map.admin.css', __FILE__));
        }
    } // End Class

    if (class_exists('CPCFF_WIDGETS_MANAGER')) {
        CPCFF_WIDGETS_MANAGER::add(new CPCFF_Map_Widget());
    }
}
