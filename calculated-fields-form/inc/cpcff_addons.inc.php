<?php
/**
 * Load the add-ons
 */

if(!class_exists('CPCFF_ADDONS'))
{
	class CPCFF_ADDONS
	{
		private static $_active_list;
		private static $_addons_list;

		public static function load()
		{
			// Initialize properties
			self::$_active_list = array();
			self::$_addons_list = array();

			// Get the list of active addons
			self::$_active_list = get_option( 'cpcff_addons_active_list', array() );
			if(
				!empty( self::$_active_list ) ||
				(
					isset( $_GET["page"] ) &&
					strpos($_GET["page"], CP_CALCULATED_FIELDS_SETTINGS_PAGE)  !==  false
				)
			)
			{
				$path = CP_CALCULATEDFIELDSF_BASE_PATH.'/addons';
				if( file_exists( $path ) )
				{
					$addons = dir( $path );
					while( false !== ( $entry = $addons->read() ) )
					{
						if( strlen( $entry ) > 3 && strtolower( pathinfo( $entry, PATHINFO_EXTENSION) ) == 'php' )
						{
							require_once $addons->path.'/'.$entry;
						}
					}
				}
			}

		} // End load

		/**
		 * Prints the add-ons list
		 */
		public static function print_list()
		{
			$default_category = 'uncategorized';
			$categories = array();
            $counters = array();
			foreach( self::$_addons_list as $key => $obj )
			{
				$category = $default_category;
				if(property_exists($obj, 'category'))
				{
					$category = strtolower($obj::$category);
					if(!isset($categories[$category]))
                    {
                        $counters[$category] = 0;
                        $categories[$category] = '';
                    }
				}
                if($counters[$category]%2 == 0) $categories[$category] .= '<div style="clear:both;"></div>';
                $counters[$category] += 1;

                $help = '';
                if(($documentation_url = $obj->get_help()) !== '')
                    $help = '<a target="_blank" href="'.esc_attr($documentation_url).'">[?]</a>';
				$categories[$category] .= '<div class="cff-addon-item"><label for="'.$key.'" style="font-weight:bold;"><input type="checkbox" id="'.$key.'" name="cpcff_addons" value="'.$key.'" '.( ( $obj->addon_is_active() || $obj->is_default() ) ? 'CHECKED' : '' ) . ( $obj->is_default() ? ' onchange="this.checked=\'true\';"': '') .' >'.$obj->get_addon_name().' '.$help.'</label> <div style="font-style:italic;padding-left:20px;">'.$obj->get_addon_description().'</div></div>';
			}
			ksort($categories);
			foreach($categories as $category => $list)
			{
				print '<div class="cff-addons-category">'.__($category).'</div>';
                print $list;
                print '<div style="clear:both;"></div>';
			}
		} // End print_list

		/**
		 * Adds a new addon to the list of existents add-ons
		 */
		public static function add( $obj )
		{
			self::$_addons_list[$obj->get_addon_id()] = $obj;
		} // End add

		/**
		 * Return a boolean that indicates if the add-on is active or not
		 */
		public static function is_active( $addon_id )
		{
			return in_array($addon_id, self::$_active_list);
		} // End is_active

		/**
		 * Replace the list of active add-ons, and activates them
		 */
		public static function refresh_actives( $list )
		{
			delete_option( 'cpcff_addons_active_list' );
			if(!empty($list))
			{
				update_option( 'cpcff_addons_active_list', $list );
				self::$_active_list = $list;
				foreach( $list as $addon )
				{
					if( isset( self::$_addons_list[ $addon ] ) )
					{
						self::$_addons_list[ $addon ]->activate();
					}
				}
			}
			do_action( 'cff_admin_state_changed', 'addons_active_change' );
		} // End refresh_actives

		/**
		 * Activate add-on
		 */
		public static function activate_addon( $addon )
		{
			if (
				isset( self::$_addons_list[ $addon ] ) &&
				! in_array( $addon, self::$_active_list )
			) {
				self::$_active_list[] = $addon;
				update_option( 'cpcff_addons_active_list', self::$_active_list );
				self::$_addons_list[ $addon ]->activate();
			}
		} // End refresh_actives

		/**
		 * Prints the general add ons settings
		 */
		public static function print_settings()
		{
			if( !empty( self::$_active_list ) )
			{
				foreach( self::$_active_list as $addon_id )
					if( isset( self::$_addons_list[ $addon_id ] ) ) print self::$_addons_list[ $addon_id ]->get_addon_settings();
			}
		} // End print_settings

		/**
		 * Prints the add ons settings related with the form's id
		 */
		public static function print_form_settings($form_id)
		{
			if( !empty( self::$_active_list ) )
			{
				foreach( self::$_active_list as $addon_id )
					if( isset( self::$_addons_list[$addon_id] ) )
						print self::$_addons_list[$addon_id]->get_addon_form_settings($form_id);
			}
		} // End print_form_settings

	} // End CPCFF_ADDONS
}