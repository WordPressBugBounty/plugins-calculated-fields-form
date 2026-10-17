<?php
/*
....
*/

if( !class_exists( 'CPCFF_BaseAddon' ) )
{
    class CPCFF_BaseAddon
    {
        /************* ADDON SYSTEM - ATTRIBUTES AND METHODS *************/
		protected $addonID;
		protected $name = '';
		protected $description = '';
        protected $help = '';
        protected $optimizer_file;
        protected $optimizer_inline;
		protected $active_by_default = false; // Active by default, it cannot be deactivated.

		private $table_columns;

        protected function __construct()
        {
            if(get_option('CP_CALCULATEDFIELDSF_OPTIMIZATION_PLUGIN', CP_CALCULATEDFIELDSF_OPTIMIZATION_PLUGIN)*1)
            {
                // Solves a conflict caused by the "WP Rocket" plugin
                add_filter('rocket_exclude_js', array($this,'exclude_js'));
                add_filter('rocket_exclude_defer_js', array($this,'exclude_js'));
                add_filter('rocket_delay_js_exclusions', array($this,'exclude_js'));

                // Some "WP Rocket" functions can be use with "WP-Optimize"
                add_filter('wp-optimize-minify-blacklist', array($this,'exclude_js'));
                add_filter('wp-optimize-minify-default-exclusions', array($this,'exclude_js'));
            }

            add_filter('rocket_excluded_inline_js_content', array($this,'excluded_inline_js'));
            add_filter('rocket_defer_inline_exclusions', array($this,'excluded_inline_js'));
            add_filter('rocket_delay_js_exclusions', array($this,'excluded_inline_js'));
        } // End __construct

		protected function _run_update_database($db_queries)
		{
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($db_queries); // Running the queries
		}

		protected function update_database()
		{

		}

		protected function add_field_verify ($table, $field, $type = "varchar(255) DEFAULT '' NOT NULL")
        {
            global $wpdb;
            if(empty($this->table_columns))
			{
				// $table originates from the class property $this->form_table, not user input.
				// SHOW columns requires an identifier; placeholders cannot parameterize it.
				$columns = $wpdb->get_results("SHOW columns FROM `".$table."`");
				foreach( $columns as $column ) $this->table_columns[$column->Field] = $column->Field;
			}
            if (empty($this->table_columns[$field]))
            {
                // ALTER TABLE identifiers cannot be parameterized via wpdb->prepare().
                // $table, $field, $type originate from class properties — not user input.
                $sql = "ALTER TABLE  `".$table."` ADD `".$field."` ".$type;
                $wpdb->query($sql);
            }
        }

		public function activate()
		{
			$this->update_database();
		}

		public function get_addon_id()
		{
			return $this->addonID;
		}

		public function get_addon_name()
		{
			return $this->name . ( $this->is_default() ? ' (' . esc_html__( 'Default Active', 'calculated-fields-form' ). ')': '' );
		}

		public function get_addon_description()
		{
			return $this->description;
		}

        public function get_help()
		{
			return $this->help;
		}

		public function get_addon_form_settings( $form_id )
		{
			return '';
		}

		public function get_addon_settings()
		{
			return '';
		}

		public function addon_is_active()
		{
			return CPCFF_ADDONS::is_active($this->get_addon_id());
		}

		public function is_default()
		{
			return $this->active_by_default;
		}

		// Redefined by the add-ons to delete the different options and drop the corresponding database tables
		public function complete_uninstall()
		{
			return false;
		}

        public function exclude_js($excluded_js = [])
        {
            if(!empty($this->optimizer_file)) $excluded_js = array_merge($excluded_js, $this->optimizer_file);
            return $excluded_js;
        }

        public function excluded_inline_js($excluded_js = [])
        {
            if(!empty($this->optimizer_inline)) $excluded_js = array_merge($excluded_js, $this->optimizer_inline);
            return $excluded_js;
        }
	} // End Class
}