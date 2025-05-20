<?php
/**
 * Miscellaneous operations: CPCFF_AUXILIARY class
 *
 * Metaclass with miscellanous operations used through all plugin.
 *
 * @package CFF.
 * @since 1.0.167
 */

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeEnd
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
// phpcs:disable Squiz.Commenting.FunctionComment.Missing
// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
if ( ! class_exists( 'CPCFF_AUXILIARY' ) ) {
	/**
	 * Metaclass with miscellaneous operations.
	 *
	 * Publishes miscellanous operations to be used through all plugin's sections.
	 *
	 * @since  1.0.167
	 */
	class CPCFF_AUXILIARY {

		/**
		 * Public URL of the current blog.
		 *
		 * @since 1.0.167
		 * @var string $_site_url
		 */
		private static $_site_url;

		/**
		 * URL to the WordPress of the current blog.
		 *
		 * @since 1.0.167
		 * @var string $_wp_url
		 */
		private static $_wp_url;

		/**
		 * Current URL.
		 *
		 * @var string $_current_url
		 */
		private static $_current_url;

		/**
		 * ID of the current blog.
		 *
		 * @var string $_wp_id
		 */
		private static $_wp_id;

		/**
		 * Returns the id of current blog.
		 *
		 * If the ID was read previously, uses the value stored in class property.
		 *
		 * @return int.
		 */
		public static function blog_id() {
			if ( empty( self::$_wp_id ) ) {
				self::$_wp_id = get_current_blog_id();
			}
			return self::$_wp_id;
		} // End blog_id.

		/**
		 * Returns the public URL of the current blog.
		 *
		 * If the URL was read previously, uses the value stored in class property.
		 *
		 * @since 1.0.167
		 * @return string.
		 */
		public static function site_url( $no_protocol = false ) {
			if ( empty( self::$_site_url ) ) {
				$blog            = self::blog_id();
				self::$_site_url = get_home_url( $blog, '', is_ssl() ? 'https' : 'http' );
			}
			$_site_url = rtrim( self::$_site_url, '/' );
			if ( $no_protocol ) {
				$_site_url = preg_replace( '/^http(s?)\:/i', '', $_site_url );
			}
			return $_site_url;
		} // End site_url.

		/**
		 * Returns the URL to the WordPress of the current blog.
		 *
		 * If the URL was read previously, uses the value stored in class property.
		 *
		 * @since 1.0.167
		 * @return string.
		 */
		public static function wp_url() {
			if ( empty( self::$_wp_url ) ) {
				$blog          = self::blog_id();
				self::$_wp_url = get_admin_url( $blog );
			}
			return rtrim( self::$_wp_url, '/' );
		} // End wp_url.

		/**
		 * Returns the form editor.
		 */
		public static function editor_url() {
			return self::wp_url() . '/admin.php?page=cp_calculated_fields_form&_cpcff_nonce=' . wp_create_nonce( 'cff-form-settings' ) . '&cal=';
		} // End editor_url.

		/**
		 * Returns the URL to the current post url.
		 *
		 * @return string.
		 */
		public static function wp_current_url() {
			if ( is_admin() ) {
				return self::site_url();
			}
			if ( ! empty( self::$_current_url ) ) {
				return self::$_current_url;
			}

			$protocol = ( ( ! empty( $_SERVER['HTTPS'] ) && 'off' != $_SERVER['HTTPS'] ) || ( ! empty( $_SERVER['SERVER_PORT'] ) && 443 == $_SERVER['SERVER_PORT'] ) ) ? 'https://' : 'http://';

			self::$_current_url = $protocol . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
			return self::$_current_url;
		} // End wp_current_url.

		public static function paginate_links( $args = [] ) {
			$output = '';
			$aria_label   = __( 'Page', 'calculated-fields-form' ) . ' ';
			$aria_current = __( 'page', 'calculated-fields-form' ) . ' ';

			$base 		= $args['base'];
			$format		= ! empty( $args['format'] ) ? $args['format'] : '&p=%#%';
			$total 		= max( ! empty( $args['total'] ) && is_numeric( $args['total'] ) ? intval( $args['total'] ) : 1, 1 );
			$current 	= max( ! empty( $args['current'] ) && is_numeric( $args['current'] ) ? intval( $args['current'] ) : 1, 1 );
			$show_all	= ! empty( $args['show_all'] ) ? true : false;
			$end_size 	= max( ! empty( $args['end_size'] ) && is_numeric( $args['end_size'] ) ? intval( $args['end_size'] ) : 1, 1 );
			$mid_size 	= max( ! empty( $args['mid_size'] ) && is_numeric( $args['mid_size'] ) ? intval( $args['mid_size'] ) : 2, 2 );
			$add_args   = [];

			$current	= min( $current, $total );
			$dots 		= false;

			if ( ! empty( $args['add_args'] ) && is_array( $args['add_args'] ) ) {
				foreach( $args['add_args'] as $arg => $value ) {
					$add_args[] = urlencode( $arg ) . '=' . urlencode( $value );
				}
			}
			$add_args = '&' . implode( '&', $add_args );

			if ( 1 < $current && ! empty( $args['prev_text'] ) )	{
				$url = str_replace( '%_%', str_replace( '%#%', ( $current - 1 ), $format ), $base ) . $add_args;
				$output .= '<a aria-label="' . esc_attr( $aria_label . ( $current - 1 ) ) . '" class="prev page-numbers" href="' . esc_attr( $url ) . '">' . esc_html( $args['prev_text'] ) . '</a>';
			}

			for( $i = 1; $i <= $total; $i++ ) {
				if ( $i == $current ) {
					$output .= '<span aria-label="' . esc_attr( $aria_label . $i ) . '" aria-current="' . esc_attr( $aria_current ) . '" class="page-numbers current">' . $i . '</span>';
					$dots = true;
				} else {
					if (
						$show_all ||
						( $i <= $end_size || ( $current && $i >= $current - $mid_size && $i <= $current + $mid_size ) || $i > $total - $end_size )
					) {
						$url = str_replace( '%_%', str_replace( '%#%', $i, $format ), $base ) . $add_args;
						$output .= '<a aria-label="' . esc_attr( $aria_label . $i ) . '" class="page-numbers" href="' . esc_attr( $url ) . '">' . $i . '</a>';
						$dots = true;
					} elseif ( $dots && ! $show_all ) {
						$output .= '<span class="page-numbers dots">' . __( '&hellip;' ) . '</span>';
						$dots = false;
					}
				}
			}

			if ( $current < $total && ! empty( $args['next_text'] ) )	{
				$url = str_replace( '%_%', str_replace( '%#%', ( $current + 1 ), $format ), $base ) . $add_args;
				$output .= '<a aria-label="' . esc_attr( $aria_label . ( $current + 1 ) ) . '" class="next page-numbers" href="' . esc_attr( $url ) . '">' . esc_html( $args['next_text'] ) . '</a>';
			}

			return $output;
		} // End paginate_links.

		/** Change the uploaded files directory */
		public static function upload_dir( $dir ) {
			if ( empty( $dir ) ) {
				$dir = wp_upload_dir();
			}

			$dir['subdir'] = '/calculated-fields-form/uploads' . $dir['subdir'];
			$dir['path']   = $dir['basedir'] . $dir['subdir'];
			$dir['url']    = $dir['baseurl'] . $dir['subdir'];

			return $dir;
		} // End upload_dir.

		/**
		 * Sanitizes the value received as parameter, supporting the same posts tags
		 *
		 * @since Pro 5.0.235, Dev 5.0.279, Plat 10.0.318
		 *
		 * @params mixed $v.
		 * @return sanitized value.
		 */
		public static function sanitize( $v, $allow_cff_fields_tags = false, $no_trim = false ) {

			if ( is_array( $v ) ) {
				foreach ( $v as $k => $v2 ) {
					$v[ $k ] = self::sanitize( $v2, $allow_cff_fields_tags );
				}
			} else if( is_string( $v ) ) {
				$allowed_tags = wp_kses_allowed_html( 'post' );
				if ( is_array( $allowed_tags ) ) {
					unset( $allowed_tags['script'] );
					unset( $allowed_tags['button'] );
					unset( $allowed_tags['radio'] );
					unset( $allowed_tags['checkbox'] );
					unset( $allowed_tags['select'] );
					unset( $allowed_tags['textarea'] );
					unset( $allowed_tags['input'] );
					unset( $allowed_tags['form'] );
				}
				add_filter(
					'safecss_filter_attr_allow_css',
					function ( $allow_css, $css_test_string ) {
						if ( preg_match( '/rgb(a)?\(/i', $css_test_string ) ) {
							return true;
						}
						return $allow_css;
					},
					10,
					2
				);

				/* Replaces the <% and %> symbols to prevent field tags from being removed. */
				if ( $allow_cff_fields_tags ) {
					$v = str_replace( array( '<%', '%>' ), array( 'cff______%', '%______cff' ), $v );
				}

				if ( $no_trim ) {
					$v = wp_kses( wp_unslash( $v ), $allowed_tags );
				} else {
					$v = wp_kses( trim( wp_unslash( $v ) ), $allowed_tags );
				}

				// $v = str_ireplace( '&amp;', '&', $v );
				if ( function_exists( 'force_balance_tags' ) ) $v = force_balance_tags( $v );

				/* Recovers the <% and %> symbols. */
				if ( $allow_cff_fields_tags ) {
					$v = str_replace( array( 'cff______%', '%______cff' ), array( '<%', '%>' ), $v );
				}

				// the str_replace is a patch to solve an issue with the data: part in signature fields.
				// that are removed by wp_kse.
				$v = str_replace(
					array( '"image/svg+xml;base64', '"image/png;base64' ),
					array( '"data:image/svg+xml;base64', '"data:image/png;base64' ),
					$v
				);
			}
			return $v;
		} // End sanitize.

		/**
		 * Removes Bom characters.
		 *
		 * @since 1.0.179
		 *
		 * @param string $str text to clean.
		 * @return string.
		 */
		public static function clean_bom( $str ) {
			$bom = pack( 'H*', 'EFBBBF' );
			return preg_replace( "/$bom/", '', $str );
		} // End clean_bom.

		/**
		 * Converts some characters in a JSON string.
		 *
		 * @since 1.0.169
		 *
		 * @param string $str JSON string.
		 * @return string.
		 */
		public static function clean_json( $str ) {
			return str_replace(
				array( '	', "\n", "\r" ),
				array( ' ', '\n', '' ),
				$str
			);
		} // End clean_json.

		/**
		 * Set the hook for cleanning the expired transients
		 *
		 * @since 1.0.281
		 */
		public static function clean_transients_hook() {
			add_action( 'cpcff_clean_transients', array( 'CPCFF_AUXILIARY', 'clean_transients' ) );
			if ( ! wp_next_scheduled( 'cpcff_clean_transients' ) ) {
				wp_schedule_event( time() + 5, 'daily', 'cpcff_clean_transients' );
			}
		} // End clean_transients_hook.

		/**
		 * Clean the expired transients
		 *
		 * @since 1.0.281
		 */
		public static function clean_transients() {
			global $wpdb;
			$table = $wpdb->options;

			// get current PHP time, offset by a minute to avoid clashes with other tasks.
			$threshold = time() - MINUTE_IN_SECONDS;

			// delete expired transients, using the paired timeout record to find them.
			$sql = "
				delete from t1, t2
				using $table t1
				join $table t2 on t2.option_name = replace(t1.option_name, '_timeout', '')
				where t1.option_name like '\_transient\_timeout\_%'
				and t1.option_value < '$threshold'
			";
			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			// delete orphaned transient expirations.
			$sql = "
				delete from $table
				where option_name like '\_transient\_timeout\_%'
				and option_value < '$threshold'
			";

			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} // End clean_transients.

		/**
		 * Decodes a JSON string.
		 *
		 * Decode a JSON string, and receive a parameter to apply strip slashes first or not.
		 *
		 * @since 1.0.169
		 *
		 * @param string $str JSON string.
		 * @param string $stripcslashes Optional. To apply a stripcslashes to the text before json_decode. Default 'unescape'.
		 * @return mixed PHP Oject or False.
		 */
		public static function json_decode( $str, $stripcslashes = 'unescape' ) {
			try {
				$str = self::clean_json( $str );
				if ( 'unescape' == $stripcslashes ) {
					$str = stripcslashes( $str );
				}
				$obj = json_decode( $str );
			} catch ( Exception $err ) {
				self::write_log( $err ); }
			return ( ! empty( $obj ) ) ? $obj : false;
		} // End unserialize.

		/**
		 * Replaces recursively the elements in an array by the elements in another one.
		 *
		 * The method will use the PHP function: array_replace_recursive if exists.
		 *
		 * @since 1.0.169
		 *
		 * @param array $array1 list to replace.
		 * @param array $array2 replacement list.
		 * @return array.
		 */
		public static function array_replace_recursive( $array1, $array2 ) {
			// If the array_replace_recursive function exists, use it.
			if ( function_exists( 'array_replace_recursive' ) ) {
				return array_replace_recursive( $array1, $array2 );
			}
			foreach ( $array2 as $key1 => $val1 ) {
				if ( isset( $array1[ $key1 ] ) ) {
					if ( is_array( $val1 ) ) {
						foreach ( $val1 as $key2 => $val2 ) {
							$array1[ $key1 ][ $key2 ] = $val2;
						}
					} else {
						$array1[ $key1 ] = $val1;
					}
				} else {
					$array1[ $key1 ] = $val1;
				}
			}
			return $array1;
		} // End array_replace_recursive.

		public static function array_map_recursive( $array, $callback ) {
			return array_map(function($value) use ($callback) {
				if (is_array($value)) {
					return self::recursive_array_map($value, $callback);
				} else {
					return $callback($value);
				}
			}, $array);
		} // End array_map_recursive

		public static function replace_params_into_url( $url, $params = array() ) {
			try {
				if (
					preg_match( '/^<%from_page%>/i', $url, $match ) &&
					isset( $params['from_page'] )
				) {
					$url   = preg_replace( '/^<%from_page%>/i', $params['from_page'], $url );
					$parts = explode( '?', $url );
					$url   = array_shift( $parts );
					if ( count( $parts ) ) {
						$url .= '?' . implode( '&', $parts );
					}
				}

				$parts = parse_url( $url );
				if ( ! empty( $parts['query'] ) ) {
					parse_str( $parts['query'], $query_params );
					if ( ! empty( $query_params ) ) {
						foreach ( $query_params as $param_name => $param_value ) {
							if ( preg_match_all( '/<%([^%]+)%>/i', $param_value, $matches ) ) {
								foreach ( $matches[1] as $index => $fieldname ) {
									if ( isset( $params[ $fieldname . '_urls' ] ) ) {
										$replacement = $params[ $fieldname . '_url' ];
									} elseif ( isset( $params[ $fieldname ] ) ) {
										$replacement = $params[ $fieldname ];
									} else {
										$replacement = '';
									}

									$param_value = is_scalar($replacement) ? str_replace( $matches[0][ $index ], $replacement, $param_value ) : $replacement;

								}
								$query_params[ $param_name ] = $param_value;
							}
						}

						$parts['query'] = http_build_query( $query_params );
						$url            = ( isset( $parts['scheme'] ) ? "{$parts['scheme']}:" : '' ) .
								( ( isset( $parts['user'] ) || isset( $parts['host'] ) ) ? '//' : '' ) .
								( isset( $parts['user'] ) ? "{$parts['user']}" : '' ) .
								( isset( $parts['pass'] ) ? ":{$parts['pass']}" : '' ) .
								( isset( $parts['user'] ) ? '@' : '' ) .
								( isset( $parts['host'] ) ? "{$parts['host']}" : '' ) .
								( isset( $parts['port'] ) ? ":{$parts['port']}" : '' ) .
								( isset( $parts['path'] ) ? "{$parts['path']}" : '' ) .
								( isset( $parts['query'] ) ? "?{$parts['query']}" : '' ) .
								( isset( $parts['fragment'] ) ? "#{$parts['fragment']}" : '' );
					}
				}
			} catch ( Exception $err ) {
				error_log( $err->getMessage() );
			}

			return $url;
		} // End replace_params_into_url.

		/**
		 * Applies stripcslashes to the array elements recursively.
		 *
		 * The method checks if parameter is an array a text. If it is an array the method is called recursively.
		 *
		 * @since 1.0.176
		 *
		 * @param mixed $v array or single value.
		 * @return mixed the array or value with the slashes stripped
		 */
		public static function stripcslashes_recursive( $v ) {
			if ( is_array( $v ) ) {
				foreach ( $v as $k => $s ) {
					$v[ $k ] = self::stripcslashes_recursive( $s );
				}
				return $v;
			} else {
				return stripcslashes( $v );
			}
		} // End stripcslashes_recursive.

		public static function stripscript_recursive( $v ) {
			if ( is_array( $v ) ) {
				foreach ( $v as $k => $s ) {
					$v[ $k ] = self::stripscript_recursive( $s );
				}
				return $v;
			} else {
                return preg_replace( array( '/<\s*script\b.*\bscript\s*>/i', '/<\s*script[^>]*>/i', '/(\b)(on[a-z]+)\s*=/i' ), array( '', '', '$1_$2=' ), $v );
			}
		} // End stripscript_recursive.

		/**
		 * Checks if the website is being visited by a crawler.
		 *
		 * Returns true if the website is being visited by a search engine spider,
		 * and the plugin was configure for hidding the forms front them, else false.
		 *
		 * @since 1.0.169
		 *
		 * @return bool.
		 */
		public static function is_crawler() {
			return ( isset( $_SERVER['HTTP_USER_AGENT'] ) &&
					preg_match( '/bot|crawl|slurp|spider/i', sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) &&
					get_option( 'CP_CALCULATEDFIELDSF_EXCLUDE_CRAWLERS', false )
				);
		} // End is_crawler.

		/**
		 * Checks if the uploaded file is supported by WordPress and it is not a dangerous  file.
		 *
		 * Executables and scripts are considered as potencially dangerous files.
		 *
		 * @since 1.0.178
		 *
		 * @param array $file with the file's name and the temporal name after upload it.
		 * @return bool.
		 */
		public static function check_uploaded_file( $file ) {
			$filetmp  = $file['tmp_name'];
			$filename = $file['name'];

			// Get file info.
			$filetype = wp_check_filetype( basename( $filename ), null );

			// Excluding dangerous files.
			if (
				false == $filetype['ext'] ||
				in_array( $filetype['ext'], array( 'php', 'asp', 'aspx', 'cgi', 'pl', 'perl', 'exe' ) )
			) {
				return false;
			}

			return true;
		} // End check_uploaded_file.

		/**
		 * Adds the attribute: property="stylesheet" to the link tag to validate the link tags into the pages' bodies.
		 *
		 * Checks if it is an stylesheet and adds the property if has not been included previously.
		 *
		 * @since 1.0.178
		 *
		 * @param string $tag the link tag.
		 * @return string.
		 */
		public static function complete_link_tag( $tag ) {
			if (
				preg_match( '/stylesheet/i', $tag ) &&
				! preg_match( '/property\s*=/i', $tag )
			) {
				return str_replace( '/>', ' property="stylesheet" />', $tag );
			}
			return $tag;
		} // End complete_link_tag.

		/**
		 * Creates a new entry in the PHP Error Logs.
		 *
		 * @since 1.0.167
		 *
		 * @param mixed $log Log message, as text, array or plain object.
		 * @return void.
		 */
		public static function write_log( $log ) {
			try {
				if (
					defined( 'WP_DEBUG' ) &&
					true == WP_DEBUG
				) {
					if (
						is_array( $log ) ||
						is_object( $log )
					) {
						error_log( print_r( $log, true ) );
					} else {
						error_log( $log );
					}
				}
			} catch ( Exception $err ) {
				error_log( $err->getMessage() );
			}
		} // End write_log.

		/**
		 * Replaces all special tags in a text with the corresponding fields information and submitted data,
		 * returning an array with final text, and files submitted with the form.
		 *
		 * @param array  $fields list of form's fields.
		 * @param array  $params list of submitted fields and their values.
		 * @param string $text the text to process replacing the fields tags by their labels and values.
		 * @param string $summary the summary of submitted data stored in database.
		 * @param string $format to format the output as plain text or html. Values: html, text.
		 * @param string $postid database id of submitted data.
		 *
		 * @return array with the elements: "text" with the formatted text, "files" with the list of submitted files.
		 */
		public static function parsing_fields_on_text( $fields, $params, $text, $summary, $format, $postid ) {
			$text        = str_replace( array( '&lt;%', '&lt; %', '< %' ), '<%', $text );
			$text        = str_replace( array( '%&gt;', '% &lt;', '% >' ), '%>', $text );
			$attachments = array();

			// Remove empty blocks.
			while ( preg_match( "/<%\s*(fieldname\d+|final_price|payment_option|payment_status|coupon)_block\s*(?:(?!%>).)*%>/", $text, $matches ) ) {
				$tags   = self::_extract_tags( $matches[0] );
				$tags   = array_pop( $tags );
				$tags   = array_pop( $tags );
				$remove = false;
				if ( isset( $params[ $matches[1] ] ) ) {
					$tmp_param = $params[ $matches[1] ];
					$value     = is_array( $tmp_param ) ? implode( ',', $tmp_param ) : $tmp_param;
					$value     = trim( $value );

					if (
						'' == $value ||
						! ( is_null( $tags['if_value_is_greater_than'] ) || self::value( $tags['if_value_is_greater_than'] ) < self::value( $value ) ) ||
						! ( is_null( $tags['if_value_is_greater_than_or_equal_to'] ) || self::value( $tags['if_value_is_greater_than_or_equal_to'] ) <= self::value( $value ) ) ||
						! ( is_null( $tags['if_value_is_less_than'] ) || self::value( $value ) < self::value( $tags['if_value_is_less_than'] ) ) ||
						! ( is_null( $tags['if_value_is_less_than_or_equal_to'] ) || self::value( $value ) <= self::value( $tags['if_value_is_less_than_or_equal_to'] ) ) ||
						! ( is_null( $tags['if_value_is'] ) || $value == $tags['if_value_is'] ) ||
						! ( is_null( $tags['if_value_is_not'] ) || $value != $tags['if_value_is_not'] ) ||
						! ( is_null( $tags['if_value_like'] ) || preg_match( '/' . preg_quote( $tags['if_value_like'] ) . '/i', $value ) ) ||
						! ( is_null( $tags['if_value_unlike'] ) || ! preg_match( '/' . preg_quote( $tags['if_value_unlike'] ) . '/i', $value ) )
					) {
						$remove = true;
					}
				} else {
					$remove = true;
				}

				if ( $remove ) {
					$from = strpos( $text, $matches[0] );
					if ( preg_match( '/<%\s*' . $matches[1] . '_endblock\s*%>/', $text, $matches_end ) ) {
						$length = strpos( $text, $matches_end[0] ) + strlen( $matches_end[0] ) - $from;
					} else {
						$length = strlen( $matches[0] );
					}
					$text = substr_replace( $text, '', $from, $length );
				} else {
					$text = preg_replace( array( '/' . preg_quote( $matches[0] ) . '/', '/<%\s*' . $matches[1] . '_endblock\s*%>/' ), '', $text, 1 );
				}
			}

			// Remove empty nonblocks.
			while ( preg_match( "/<%\s*(fieldname\d+|final_price|payment_option|payment_status|coupon)_nonblock\s*%>/", $text, $matches ) ) {
				$remove = false;
				if ( isset( $params[ $matches[1] ] ) ) {
					$tmp_param = $params[ $matches[1] ];
					$value     = is_array( $tmp_param ) ? implode( ',', $tmp_param ) : $tmp_param;
					$value     = trim( $value );
					if ( '' != $value ) {
						$remove = true;
					}
				}

				if ( $remove ) {
					$from = strpos( $text, $matches[0] );
					if ( preg_match( '/<%\s*' . $matches[1] . '_endnonblock\s*%>/', $text, $matches_end ) ) {
						$length = strpos( $text, $matches_end[0] ) + strlen( $matches_end[0] ) - $from;
					} else {
						$length = strlen( $matches[0] );
					}
					$text = substr_replace( $text, '', $from, $length );
				} else {
					$text = preg_replace( array( '/' . preg_quote( $matches[0] ) . '/', '/<%\s*' . $matches[1] . '_endnonblock\s*%>/' ), '', $text, 1 );
				}
			}

			$tags = self::_extract_tags( $text );

			if ( 'html' == $format ) {
				$text    = str_replace( "\n", '', $text );
			}

			// Replace the INFO tags.
			if ( ! empty( $tags['info'] ) ) {
				$summary_copy = $summary;
				do {
					$tmp          = $summary_copy;
					$summary_copy = preg_replace(
						array(
							"/^[^\n]*:{1,2}\s*\n/",
							"/\n[^\n]*:{1,2}\s*\n/",
							"/\n[^\n]*:{1,2}\s*$/",
						),
						array(
							'',
							"\n",
							'',
						),
						$summary_copy
					);
				} while ( $summary_copy <> $tmp );

				foreach ( $tags['info'] as $tagData ) {
					self::_single_replacement( $tagData, ( ( $tagData['if_not_empty'] ) ? $summary_copy : $summary ), $text );
				}
				unset( $tags['info'] );
			}

			foreach ( $params as $item => $value ) {
				$value_bk = $value;

				if ( 'submissiondate_mmddyyyy' == $item || 'submissiondate_ddmmyyyy' == $item ) {
					if ( ! empty( $value ) && is_string( $value ) ) {
						$value = explode( ' ', $value );
						$value = $value[0];
					}
				}

				if ( $item == 'final_price' && ! empty( $params['final_price_formatted'] ) ) {
					$value = $params['final_price_formatted'];
				}

				if ( isset( $tags[ $item ] ) ) {
					$label      = ( isset( $fields[ $item ] ) && property_exists( $fields[ $item ], 'title' ) ) ? $fields[ $item ]->title : '';
					$shortlabel = ( isset( $fields[ $item ] ) && property_exists( $fields[ $item ], 'shortlabel' ) ) ? $fields[ $item ]->shortlabel : '';
					$value      = ( ! empty( $value ) || is_numeric( $value ) && 0 == $value ) ? ( ( is_array( $value ) ) ? implode( ( ! empty( $tags[ $item ][0] ) && ! empty( $tags[ $item ][0]['choices_separator'] ) ? $tags[ $item ][0]['choices_separator'] : ", " ), $value ) : $value ) : '';

					foreach ( $tags[ $item ] as $tagData ) {
						if (
							( is_null( $tagData['if_value_is_greater_than'] ) || self::value( $tagData['if_value_is_greater_than'] ) < self::value( $value ) ) &&
							( is_null( $tagData['if_value_is_greater_than_or_equal_to'] ) || self::value( $tagData['if_value_is_greater_than_or_equal_to'] ) <= self::value( $value ) ) &&
							( is_null( $tagData['if_value_is_less_than'] ) || self::value( $value ) < self::value( $tagData['if_value_is_less_than'] ) ) &&
							( is_null( $tagData['if_value_is_less_than_or_equal_to'] ) || self::value( $value ) <= self::value( $tagData['if_value_is_less_than_or_equal_to'] ) ) &&
							( is_null( $tagData['if_value_is'] ) || $value == $tagData['if_value_is'] ) &&
							( is_null( $tagData['if_value_is_not'] ) || $value != $tagData['if_value_is_not'] ) &&
							( is_null( $tagData['if_value_like'] ) || preg_match( '/' . preg_quote( $tagData['if_value_like'] ) . '/i', $value ) ) &&
							( is_null( $tagData['if_value_unlike'] ) || ! preg_match( '/' . preg_quote( $tagData['if_value_unlike'] ) . '/i', $value ) ) &&
							( 0 == $tagData['if_not_empty'] || '' !== $value )
						) {
							switch ( $tagData['tag'] ) {
								case $item:
									if ( strtolower( $item )  == 'itemnumber' ) {
										self::_single_replacement(
											$tagData,
											($postid ? ((isset($tagData['length']) && is_numeric($tagData['length'])) ? sprintf("%0{$tagData['length']}d", $postid) : $postid) : ''),
											$text
										);
										break;
									}
									if ( preg_match( '/_url(s?)$/i', $item ) && ! empty( $tagData['in_tag'] ) ) {
										$file_fieldname = explode( '_', $item)[0];
										$_names = [];
										if ( ! empty( $params[ $file_fieldname . '_name' ] ) ) {
											$_names = $params[ $file_fieldname . '_name' ];
										}

										$value  = preg_split( '/\n+/', $value );
										$in_tag = strtolower( $tagData['in_tag'] );
										switch ( $in_tag ) {
											case 'img':
											case '<img>':
												foreach ( $value as $_i => $_url ) {
													$_alt = '';
													if ( ! empty( $_names ) && ! empty( $_names[ $_i ] ) ) {
														$_alt = ' alt="' . esc_attr( $_names[ $_i ] ) . '"';
													}
													$value[ $_i ] = ( ! empty( $_url ) && @is_array( getimagesize( $_url ) ) ) ? '<img src="' . esc_attr( $_url ) . '"' . $_alt . '>' : $_url;
												}
												break;
											case 'a':
											case '<a>':
												foreach ( $value as $_i => $_url ) {
													$_text = ( ! empty( $tagData['text'] ) && strtolower($tagData['text']) == 'name' && ! empty( $_names ) && ! empty( $_names[ $_i ] ) ) ? $_names[ $_i ] : $_url;
													$value[ $_i ] = '<a href="' . esc_attr( $_url ) . '">' . esc_html( $_text ) . '</a>';
												}
												break;
										}
										$value = implode( "\n", $value );
									}
									self::_single_replacement( $tagData, $label . $tagData['separator'] . $value, $text );
									break;
								case $item . '_label':
									self::_single_replacement( $tagData, $label, $text );
									break;
								case $item . '_value':
									self::_single_replacement( $tagData, $value, $text );
									break;
								case $item . '_shortlabel':
									self::_single_replacement( $tagData, $shortlabel, $text );
									break;
							}
						} else {
							$text = str_replace( $tagData['node'], '', $text );
						}
					}
					unset( $tags[ $item ] );
				}

				if ( preg_match( "/_link\b/i", $item ) ) {
					$attachments = array_merge( $attachments, $value_bk );
				}
			}

			// To include the fCommentArea or fSectionBreak.
			foreach ( $tags as $tag ) {
				foreach ( $tag as $tagData ) {
					if ( preg_match( '/fieldname\d+/i', $tagData['tag'], $item ) ) {
						$item = $item[0];

						if ( isset( $fields[ $item ] ) && ( 'fCommentArea' == $fields[ $item ]->ftype || 'fSectionBreak' == $fields[ $item ]->ftype ) ) {
							$label      = ( property_exists( $fields[ $item ], 'title' ) ) ? $fields[ $item ]->title : '';
							$shortlabel = ( property_exists( $fields[ $item ], 'shortlabel' ) ) ? $fields[ $item ]->shortlabel : '';

							switch ( $tagData['tag'] ) {
								case $item:
								case $item . '_label':
								case $item . '_value':
									self::_single_replacement( $tagData, $label, $text );
									break;
								case $item . '_shortlabel':
									self::_single_replacement( $tagData, $shortlabel, $text );
									break;
							}
							unset( $tags[ $item ] );
						}
					} elseif ( preg_match( '/itemnumber/i', $tagData['tag'] ) ) {
						self::_single_replacement(
							$tagData,
							( $postid ? ( ( isset( $tagData['length'] ) && is_numeric( $tagData['length'] ) ) ? sprintf( "%0{$tagData['length']}d", $postid ) : $postid ) : '' ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.InterpolatedVariableNotSnakeCase
							$text
						);
					}
				}
			}

			self::_array_replacement( $tags, 'formid', ( ( ! empty( $params['formid'] ) ) ? $params['formid'] : '' ), $text );
			self::_array_replacement( $tags, 'from_page', ( ( ! empty( $params['from_page'] ) ) ? $params['from_page'] : '' ), $text );

			if ( ! empty( $params['formid'] ) ) {
				$form_obj    = new CPCFF_FORM( $params['formid'] );
				$thanks_page = $form_obj->get_option( 'fp_return_page', '/', $postid );
			}
			self::_array_replacement( $tags, 'thank_you_page', ( ! empty( $thanks_page ) ? $thanks_page : '' ), $text );

			self::_array_replacement( $tags, 'currentdate_mmddyyyy', gmdate( 'm/d/Y' ), $text );
			self::_array_replacement( $tags, 'currentdate_ddmmyyyy', gmdate( 'd/m/Y' ), $text );
			self::_array_replacement( $tags, 'currenttime', gmdate( 'H:i:s' ), $text );

			self::_array_replacement( $tags, 'submissiondate_mmddyyyy', ( ( ! empty( $fields['submission_datetime'] ) ) ? gmdate( 'm/d/Y', strtotime( $fields['submission_datetime'] ) ) : '' ), $text );
			self::_array_replacement( $tags, 'submissiondate_ddmmyyyy', ( ( ! empty( $fields['submission_datetime'] ) ) ? gmdate( 'd/m/Y', strtotime( $fields['submission_datetime'] ) ) : '' ), $text );
			self::_array_replacement( $tags, 'submissiontime', ( ( ! empty( $fields['submission_datetime'] ) ) ? gmdate( 'H:i:s', strtotime( $fields['submission_datetime'] ) ) : '' ), $text );

			self::_array_replacement( $tags, 'payment_status', ( ( isset( $fields['paid'] ) ) ? ( ( $fields['paid'] * 1 ) ? __( 'Paid', 'calculated-fields-form' ) : __( 'Not Paid', 'calculated-fields-form' ) ) : '' ), $text );
			self::_array_replacement( $tags, 'ipaddress', ( ( ! empty( $fields['ipaddr'] ) ) ? $fields['ipaddr'] : '' ), $text );
			self::_array_replacement( $tags, 'form_name', ( ( ! empty( $form_obj ) ) ? $form_obj->get_option( 'form_name', '' ) : '' ), $text );

			$form_title       = '';
			$form_description = '';
			if ( ! empty( $form_obj ) ) {
				$form_structure = $form_obj->get_option( 'form_structure', array() );
				if (
					! empty( $form_structure ) &&
					! empty( $form_structure[1] )
				) {
					if ( is_object( $form_structure[1] ) ) {
						$form_structure[1] = (array) $form_structure[1];
					}
					if (
						! empty( $form_structure[1][0] ) &&
						is_object( $form_structure[1][0] )
					) {
						if ( property_exists( $form_structure[1][0], 'title' ) ) {
							$form_title = $form_structure[1][0]->title;
						}

						if ( property_exists( $form_structure[1][0], 'description' ) ) {
							$form_description = $form_structure[1][0]->description;
						}
					}
				}
			}
			self::_array_replacement( $tags, 'form_title', $form_title, $text );
			self::_array_replacement( $tags, 'form_description', $form_description, $text );

			self::_array_replacement( $tags, 'subscription_id', ( ( ! empty( $params['subscr_id'] ) ) ? $params['subscr_id'] : '' ), $text );
			self::_array_replacement( $tags, 'transaction_id', ( ( ! empty( $params['txn_id'] ) ) ? $params['txn_id'] : '' ), $text );
			self::_array_replacement( $tags, 'couponcode', ( ( ! empty( $params['couponcode'] ) ) ? $params['couponcode'] : '' ), $text );
			self::_array_replacement( $tags, 'coupon', ( ( ! empty( $params['coupon'] ) ) ? $params['coupon'] : '' ), $text );

			foreach ( $tags as $tagArr ) {
				foreach ( $tagArr as $tagData ) {
					$text = str_replace( $tagData['node'], '', $text );
				}
			}

			if ( 'html' == $format ) {
				$base_code = [];
				while ( preg_match( '/<script\b[^>]*>(.*?)<\/script>/is', $text, $matches ) ) {
					$index = count( $base_code );
					$base_code[] = $matches[0];
					$text = str_replace( $matches[0], 'cff-javascript-block-placeholder-' . $index, $text );
				}
				$text = str_replace( "\n", '<br>', $text );
				foreach ( $base_code as $index => $base_code_block ) {
					$text = str_replace( 'cff-javascript-block-placeholder-' . $index, $base_code_block, $text );
				}
			}

			$text = apply_filters( 'cpcff_custom_tags', $text, $postid );
			if ( 'html' !== $format ) {
				$text = htmlspecialchars_decode( $text ); // 2024-12-30
			}
			return array(
				'text'  => $text,
				'files' => $attachments,
			);
		} // End parsing_fields_on_text.

		/*********************************** PRIVATE METHODS  ********************************************/

		/**
		 * Extracts all tags with the format <%...%> from the text.
		 *
		 * @since 1.0.181
		 * @param string $text text that includes the special tags.
		 * @return array multidimensional associative array, whose index are the tags name,
		 * and the internal arrays include the elements
		 *
		 *      - node, the literal tag.
		 *      - tag, the tag's name: fieldname#, fieldname#_label, fieldname#_value, info, ...
		 *      - if_not_empty, determines if the "if_not_empty" restriction is in the special tag or not.
		 *      - before, the value of "before" attribute in the tag.
		 *      - after, the value of "after" attribute in the tag.
		 *      - separator, the value of "separator" attribute in the tag, symbol to separate the field's label and value.
		 *      - in_tag, used with the fields tags: _url and _urls, the value would be the HTML tag with use the URLs.
		 */
		private static function _extract_tags( $text ) {
			$tags_arr = array();

			if (
				preg_match_all(
					"/<%(info|fieldname\d+|fieldname\d+_label|fieldname\d+_block|fieldname\d+_endblock|fieldname\d+_shortlabel|fieldname\d+_value|fieldname\d+_url|fieldname\d+_urls|fieldname\d+_path|fieldname\d+_paths|coupon|coupon_block|coupon_endblock|couponcode|itemnumber|formid|subscription_id|transaction_id|final_price|final_price_block|final_price_endblock|payment_option|payment_option_block|payment_option_endblock|ipaddress|from_page|form_name|form_title|form_description|thank_you_page|currentdate_mmddyyyy|currentdate_ddmmyyyy|currenttime|submissiondate_mmddyyyy|submissiondate_ddmmyyyy|submissiontime|payment_status|payment_status_block|payment_status_endblock)\b(?:(?!%>).)*%>/i",
					$text,
					$matches
				)
			) {
				$tag = array();
				foreach ( $matches[0] as $index => $value ) {
					$tag['node']         = $value;
					$tag['tag']          = strtolower( $matches[1][ $index ] );
					$tag['if_not_empty'] = preg_match( '/if_not_empty/i', $value );

					$tag['if_value_is_greater_than'] = ( preg_match( '/if_value_is_greater_than\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i', $value, $match ) ) ? $match[1] : null;

					$tag['if_value_is_greater_than_or_equal_to'] = ( preg_match( '/if_value_is_greater_than_or_equal_to\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i', $value, $match ) ) ? $match[1] : null;

					$tag['if_value_is_less_than'] = ( preg_match( '/if_value_is_less_than\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i', $value, $match ) ) ? $match[1] : null;

					$tag['if_value_is_less_than_or_equal_to'] = ( preg_match( '/if_value_is_less_than_or_equal_to\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i', $value, $match ) ) ? $match[1] : null;

					$tag['if_value_is'] = ( preg_match( '/if_value_is\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i', $value, $match ) ) ? $match[1] : null;

					$tag['if_value_is_not'] = ( preg_match( '/if_value_is_not\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i', $value, $match ) ) ? $match[1] : null;

					$tag['if_value_like'] = ( preg_match( '/if_value_like\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i', $value, $match ) ) ? $match[1] : null;

					$tag['if_value_unlike'] = ( preg_match( '/if_value_unlike\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i', $value, $match ) ) ? $match[1] : null;

					$tag['before'] = ( preg_match( '/before\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i', $value, $match ) ) ? $match[1] : '';

					$tag['after'] = ( preg_match( '/after\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i', $value, $match ) ) ? $match[1] : '';

					$tag['choices_separator'] = ( preg_match( "/choices_separator\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i",  $value, $match ) ) ? $match[1] : '';

					$tag['separator'] = ( preg_match( '/\bseparator\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i', $value, $match ) ) ? $match[1] : '';

					$tag['in_tag'] = ( preg_match( '/in_tag\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i', $value, $match ) ) ? trim( $match[1] ) : '';

					$tag[ 'text' ] = ( preg_match( "/text\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i",  $value, $match ) ) ? trim( $match[ 1 ] ) : '';

					$tag['callback'] = ( preg_match( '/callback\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i', $value, $match ) ) ? trim( $match[1] ) : '';

					$tag['length'] = ( preg_match( '/length\s*=\s*\{\{((?:(?!\}\}).)*)\}\}/i', $value, $match ) ) ? trim( $match[1] ) : '';

					// The base tag is the index of  $tags_arr, for the tags' names with the structure fieldname#_.
					// .., the index would be simply fieldname#.
					$baseTag = ( preg_match( '/(fieldname\d+)_(label|value|shortlabel)/i', $tag['tag'], $match ) ) ? $match[1] : $tag['tag'];

					if ( empty( $tags_arr[ $baseTag ] ) ) {
						$tags_arr[ $baseTag ] = array();
					}
					$tags_arr[ $baseTag ][] = $tag;
				}
			}
			return $tags_arr;
		} // End _extract_tags.

		/**
		 * Replaces the special tags in the text by the corresponding replacements
		 *
		 * @since 1.0.181
		 * @param array reference  &$tags multidimensional associative array, whose index are the tags name,
		 *  and the internal arrays include the elements
		 *
		 *       - node, the literal tag.
		 *       - tag, the tag's name: fieldname#, fieldname#_label, fieldname#_value, info, ...
		 *       - if_not_empty, determines if the "if_not_empty" restriction is in the special tag or not.
		 *       - before, the value of "before" attribute in the tag.
		 *       - after, the value of "after" attribute in the tag.
		 *       - separator, the value of "separator" attribute in the tag, symbol to separate the field's label and value.
		 * @param string           $tagName the tag's name: formi, itemnumber, currentdate_mmddyyyy, currentdate_ddmmyyyy,
		 *                ipaddress, couponcode, etc.
		 * @param string           $replacement the value uses to replace the special tag into the text.
		 * @param string reference &$text text to be processed.
		 * @return void.
		 */
		private static function _array_replacement( &$tags, $tagName, $replacement, &$text ) {
			if ( isset( $tags[ $tagName ] ) ) {
				foreach ( $tags[ $tagName ] as $tagData ) {
					self::_date_replacement( $tagData, $replacement );
					$text = str_replace( $tagData['node'], $tagData['before'] . $replacement . $tagData['after'], $text );
				}
				unset( $tags[ $tagName ] );
			}
		} // End _array_replacement.

		/**
		 * Replaces a special tags in the text by the corresponding replacement
		 *
		 * @since 1.0.181
		 * @param array            $tagData associative array with the indexes
		 *
		 *                 - node, the literal tag.
		 *                 - tag, the tag's name: fieldname#, fieldname#_label, fieldname#_value, info, ...
		 *                 - if_not_empty, determines if the "if_not_empty" restriction is in the special tag or not.
		 *                 - before, the value of "before" attribute in the tag.
		 *                 - after, the value of "after" attribute in the tag.
		 *                 - separator, the value of "separator" attribute in the tag, symbol to separate the field's label and value.
		 * @param string           $replacement the value uses to replace the special tag into the text.
		 * @param string reference &$text text to be processed.
		 * @return void.
		 */
		private static function _single_replacement( $tagData, $replacement, &$text ) {
			$allowed_callbacks = array(
				// Escape functions.
				'esc_html',
				'esc_url',
				'esc_url_raw',
				'esc_js',
				'esc_attr',
				'esc_textarea',

				// Sanitization functions.
				'sanitize_email',
				'sanitize_file_name',
				'sanitize_html_class',
				'sanitize_key',
				'sanitize_meta',
				'sanitize_mime_type',
				'sanitize_option',
				'sanitize_sql_orderby',
				'sanitize_text_field',
				'sanitize_title',
				'sanitize_title_for_query',
				'sanitize_title_with_dashes',
				'sanitize_user',
				'wp_filter_post_kses',
				'wp_filter_nohtml_kses',
			);

			if (
				! empty( $tagData['callback'] ) &&
				function_exists( $tagData['callback'] ) &&
				in_array( $tagData['callback'], $allowed_callbacks )
			) {
				$replacement = $tagData['callback']( $replacement );
			}

			self::_date_replacement( $tagData, $replacement );

			$text = str_replace( $tagData['node'], $tagData['before'] . $replacement . $tagData['after'], $text );
		} // End _single_replacement.

		private static function _date_replacement( $tagData, &$replacement ) {
			if (
				isset( $tagData['tag'] ) &&
				in_array(
					$tagData['tag'],
					array( 'currentdate_mmddyyyy', 'currentdate_ddmmyyyy', 'submissiondate_mmddyyyy', 'submissiondate_ddmmyyyy' )
				)
			) {
				if ( ! empty( $tagData['separator'] ) ) {
					$replacement = str_replace( '/', $tagData['separator'], trim( $replacement, $tagData['separator'] ) );
				}
			}
		} // End _date_replacement.
	} // End CPCFF_AUXILIARY.
}
