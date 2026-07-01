<?php
/**
 * Manage the forms' submissions with the database interaction, data, and methods.
 *
 * The class is static for sharing the submissions with multiple classes in the plugin and reduce the accesses to database.
 *
 * @package CFF.
 */

if(!class_exists('CPCFF_SUBMISSIONS'))
{
	/**
	 * Class to insert, update, and read the submissions data.
	 */
	class CPCFF_SUBMISSIONS
	{
		static private $_structure;

		/**
		 * Submissions cache for parsing template tags.
		 * Caches preprocessed submission data keyed by submission ID.
		 *
		 * @var array $_submissions_cache
		 */
		static private $_submissions_cache = array();

		/**
		 * Forms cache for parsing template tags.
		 * Caches preprocessed form data keyed by form ID.
		 *
		 * @var array $_forms_cache
		 */
		static private $_forms_cache = array();

		/**
		 * Submissions cache access order for LRU eviction.
		 *
		 * @var array $_submissions_cache_order
		 */
		static private $_submissions_cache_order = array();

		/**
		 * Forms cache access order for LRU eviction.
		 *
		 * @var array $_forms_cache_order
		 */
		static private $_forms_cache_order = array();

		/*********************************** PUBLIC METHODS  ********************************************/

		/**
		 * Populates the $_submissions_cache property with the submissions reading them from the database
		 *
		 * @param string $query, SQL query for reading the submissions.
		 * @return void.
		 */
		static public function populate($query)
		{
			global $wpdb;

			$rows = $wpdb->get_results($query);
			if(!empty($rows))
			{
				foreach($rows as $row)
				{
					if(isset($row->id))
					{
						self::$_submissions_cache[$row->id] = $row;
					}
				}
			}
			return $rows;
		} // End populate

		/**
		 * Returns a submission object or false.
		 *
		 * @param integer, the submission's id.
		 * @return mixed, return the submission object or false.
		 */
		static public function get($submission_id)
		{
			global $wpdb;

			// if the submission object has not been read, or has been read partially, reads it again.
			if(
				empty(self::$_submissions_cache[$submission_id]) ||
				empty(self::$_submissions_cache[$submission_id]->paypal_post)
			)
			{
				$query = $wpdb->prepare(
							'SELECT * FROM `'.CP_CALCULATEDFIELDSF_POSTS_TABLE_NAME.'` WHERE id=%d',
							$submission_id
						);
				self::populate($query);
				if(empty(self::$_submissions_cache[$submission_id])) return false;
			}

			// Unserialize the submitted fields if it is a text
			if(is_serialized(self::$_submissions_cache[$submission_id]->paypal_post))
			{
				self::$_submissions_cache[$submission_id]->paypal_post = (($tmp = unserialize(self::$_submissions_cache[$submission_id]->paypal_post, ['allowed_classes' => false])) !== false) ? $tmp : array();
			}
			return self::$_submissions_cache[$submission_id];
		} // end get

		/**
		 * Returns the form associated to a submission.
		 *
		 * If the submission exists it returns the corresponding form's object if there is the CPCFF_MAIN class,
		 * the form's id if the CPCFF_MAIN class does not exists, or false if the submisssion id is invalid.
		 *
		 * @param integer $submission_id, id of submission.
		 * @return mixed, the form's object, the form's id, or false.
		 */
		static public function get_form($submission_id)
		{
			$submission = self::get($submission_id);

			// The submission was read with a query that does not include the form's id.
			if(
				!empty($submission) &&
				empty($submission->formid)
			)
			{
				unset(self::$_submissions_cache[$submission_id]);
				$submission = self::get($submission_id);
			}

			if(!empty($submission))
			{
                $form_object = self::_get_form($submission->formid);
                return !empty($form_object) ? $form_object : $submission->formid;
            }
			return false;
		} // End get_form

		/**
		 * Inserts the submission in the database.
		 *
		 * @param array, associative array with the submission data.
		 * @return mixes, the submission' id or false.
		 */
		static public function insert($data)
		{
			global $wpdb;
			self::_init();

			$data = self::_clear($data);

			if(!empty($data))
			{
				if(empty($data['paypal_post'])) $data['paypal_post'] = array();
				if(!is_string($data['paypal_post'])) $data['paypal_post'] = serialize($data['paypal_post']);

				$format = self::_format($data);

				if(
					$wpdb->insert(
						CP_CALCULATEDFIELDSF_POSTS_TABLE_NAME,
						$data,
						$format
					)
				)
				{
					return $wpdb->insert_id;
				}
			}
			return false;
		} // End insert

		/**
		 * Updates the submission's data in the database.
		 *
		 * Calls the  cpcff_update_submission action.
		 *
		 * @param integer, the submission's id.
		 * @param mixed, associative array with the submission data or a stdClass.
		 * @return mixed, the submission' id or false.
		 */
		static public function update($submission_id, $data)
		{
			global $wpdb;
			self::_init();

			if(is_object($data)) $data = (array)$data;

			$data = self::_clear($data);

			if(!empty($data))
			{
				if(
					isset($data['paypal_post']) &&
					!is_string($data['paypal_post'])
				)
				$data['paypal_post'] = serialize($data['paypal_post']);

				$format = self::_format($data);

				unset(self::$_submissions_cache[$submission_id]);

				if(
					$wpdb->update(
						CP_CALCULATEDFIELDSF_POSTS_TABLE_NAME,
						$data,
						array('id'=>$submission_id),
						$format,
						array('%d')
					)
				)
				{
					/**
					 * Action called when a submission is updated, the submission ID is passed as parameter
					 */
					do_action( 'cpcff_update_submission', $submission_id );
					return $submission_id;
				}
			}
			return false;
		} // End update

		/**
		 * Deletes the submission from the submissions database.
		 *
		 * Deletes the database entry and calls the  cpcff_delete_submission action to allow the add-ons can update their databases.
		 *
		 * @param integer $submission_id, id of submission
		 * @return integer, the number of deleted rows.
		 */
		static public function delete($submission_id)
		{
			global $wpdb;
            $obj = self::get($submission_id);

            if($obj != false)
            {
                // Delete the uploaded files if they are not associated with other submissions
                // or they are not indexed by the WordPress media library
                try
                {
                    foreach($obj->paypal_post as $field => $files)
                    {
                        if(preg_match('/(fieldname\d+)_link/', $field, $matches))
                        {
                            if(!empty($obj->paypal_post[$matches[1].'_url']))
                            {
                                $urls = $obj->paypal_post[$matches[1].'_url'];
                                foreach($urls as $index => $url)
                                {
                                    $c = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.CP_CALCULATEDFIELDSF_POSTS_TABLE_NAME.' WHERE paypal_post LIKE %s', '%"'. $wpdb->esc_like($url).'"%'));

                                    $resolved = realpath($files[$index]);
                                    if ($resolved !== false && is_file($resolved))
                                    {
                                        if (intval($c) == 1)
                                        {
                                            @unlink($resolved);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }catch(Exception $err){}
            }

			$deleted_rows = $wpdb->delete(
				CP_CALCULATEDFIELDSF_POSTS_TABLE_NAME,
				array('id' => $submission_id),
				array('%d')
			);

            /**
			 * Action called when a submission is deleted, the submission ID is passed as parameter
			 */
			do_action( 'cpcff_delete_submission', $submission_id );

			// Removes the submission from the list
			unset(self::$_submissions_cache[$submission_id]);

			return ($deleted_rows) ? $deleted_rows : 0;
		} // End delete

		static public function generate_summary( $submission_id, $data = array(), $html = false ) {
			$summary = "";
			$fields_list = array();
			$chln = $html ? "<br />" : "\n";
			try {
				if ( ( $form_obj = self::get_form( $submission_id ) ) != false ) {
					$fields_list = $form_obj->get_fields();
				}

				if( empty( $data ) && ( $submission_obj = self::get( $submission_id ) ) != false ) {
					$data = $submission_obj->paypal_post;
				}

				foreach ( $data as $field_name => $field_value ) {
					$field_name = strtolower( $field_name );
					if ( preg_match( '/^fieldname\d+$/', $field_name ) ) {
						if (
							! empty( $fields_list[ $field_name ] ) &&
							property_exists( $fields_list[ $field_name ], 'title' ) &&
							! empty( $fields_list[ $field_name ]->title )
						) {
							$field_label = trim( $fields_list[ $field_name ]->title );
							$field_label = rtrim( $field_label, ':' );
							$summary .= $field_label . ": ";
						}

						$summary .= is_array( $field_value ) ? implode(', ', $field_value) : $field_value;
						$summary .= $chln;
					}
				}
			} catch ( Exception $err ) {
				$summary = "";
				error_log( $err->getMessage() );
			}

			return $summary;
		} // End generate_summary

		/**
		 * Parses template tags <%...%> in the text using cached submission and form data.
		 *
		 * This method wraps CPCFF_AUXILIARY::parsing_fields_on_text with internal caching
		 * to avoid repeated database reads for submission and form data.
		 *
		 * @param string $text The text containing template tags to parse.
		 * @param string $format Output format ('html', 'plain text', etc.).
		 * @param int $submission_id The submission ID.
		 * @return array Array with 'text' (parsed text) and 'files' (attachments).
		 */
		static public function parsing_fields_on_text($text, $format, $submission_id)
		{
			$submission_data = self::get($submission_id);

			if (empty($submission_data))
			{
				// Submission not found - call with empty params
				return CPCFF_AUXILIARY::parsing_fields_on_text(
					array(),
					array(),
					$text,
					'',
					$format,
					0
				);
			}

            $form_fields = array();
			$form_object = self::_get_form($submission_data->formid);
			if (! empty($form_object) && method_exists($form_object, 'get_fields'))
            {
                $form_fields = $form_object->get_fields();
            }

            // Completing form fields with submission data
            $form_fields['ipaddr'] = $submission_data->ipaddr;
            $form_fields['submission_datetime'] = $submission_data->time;
            $form_fields['paid'] = $submission_data->paid;

			return CPCFF_AUXILIARY::parsing_fields_on_text(
				$form_fields,
				$submission_data->paypal_post,
				$text,
				$submission_data->data,
				$format,
				$submission_id
			);
		} // End parsing_fields_on_text

		/*********************************** PRIVATE METHODS  ********************************************/

		/**
		 * Initializes the $_structure property with the names of columns and datatype in the submissions database's table.
		 *
		 * @return void.
		 */
		static private function _init()
		{
			self::$_structure = array(
				'id' 		=> '%d',
				'formid' 	=> '%d',
				'time' 		=> '%s',
				'ipaddr' 	=> '%s',
				'notifyto' 	=> '%s',
				'data' 		=> '%s',
				'paypal_post' => '%s',
				'paid'  	=> '%d'
			);
		} // End _init

		/**
		 * Checks if the there are elements not corresponding to columns in the submissions database, and removes them.
		 *
		 * @param array $data, the submissions data.
		 * @return array.
		 */
		static private function _clear($data)
		{
			self::_init();
			foreach($data as $column => $value)
			{
				if(!isset(self::$_structure[$column])) unset($data[$column]);
			}

			return $data;
		} // End _clear

		/**
		 * Returns an arrays with the corresponding format (%d, %s, %f) for the items of the array received as parameter,
		 * and the database structure.
		 *
		 * @param array, Array with items to be inserted in the database.
		 * @return array, Array of formats.
		 */
		static private function _format($data)
		{
			$format = array();
			foreach($data as $column => $value)
			{
				if(isset(self::$_structure[$column])) $format[] = self::$_structure[$column];
			}

			return $format;
		} // End _format

		/**
		 * Gets preprocessed form data from cache or CPCFF_FORM.
		 *
		 * @param int $form_id The form ID.
		 * @return object|null form object or null if form not found.
		 */
		static private function _get_form($form_id)
		{
			if (isset(self::$_forms_cache[$form_id]))
			{
				return self::$_forms_cache[$form_id];
			}

			if (!class_exists('CPCFF_FORM'))
			{
				return null;
			}

            self::$_forms_cache[$form_id] = new CPCFF_FORM($form_id);
            return self::$_forms_cache[$form_id];
		} // End _get_form

	} // End CPCFF_SUBMISSIONS
}
