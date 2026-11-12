<?php
/** Server-side selection of the form builder presentation. */
if ( ! defined( 'ABSPATH' ) ) exit;

class CPCFF_BUILDER_DESIGN {
    const OPTION = 'CP_CALCULATEDFIELDSF_BUILDER_DESIGN';

    public static function init() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'resources' ), 2 );
        add_action( 'admin_head', array( __CLASS__, 'prepare' ), 1 );
        add_action( 'in_admin_header', array( __CLASS__, 'clean_notices' ), PHP_INT_MAX );
        add_action( 'admin_post_cpcff_builder_design', array( __CLASS__, 'save' ) );
    }

    public static function modern() {
        return ! self::classic_request() && 'classic' !== get_option( self::OPTION, 'modern' );
    }

    // Recovery affects this editor request, without changing the saved preference.
    public static function classic_request() {
        return isset( $_GET['cff_builder_design'] ) && 'classic' === $_GET['cff_builder_design'];
    }

    public static function classic_url( $form_id ) {
        return add_query_arg( array(
            'page' => 'cp_calculated_fields_form',
            'cal' => absint( $form_id ),
            '_cpcff_nonce' => wp_create_nonce( 'cff-form-settings' ),
            'cff_builder_design' => 'classic',
        ), admin_url( 'admin.php' ) );
    }

    private static function editor_request() {
        return isset( $_GET['page'], $_GET['cal'] ) &&
            'cp_calculated_fields_form' === $_GET['page'] && is_scalar( $_GET['cal'] ) && absint( $_GET['cal'] ) > 0 &&
            ( ! isset( $_GET['list'] ) || '1' !== $_GET['list'] ) &&
            current_user_can( apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ) ) &&
            isset( $_REQUEST['_cpcff_nonce'] ) && is_string( $_REQUEST['_cpcff_nonce'] ) &&
            wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_cpcff_nonce'] ) ), 'cff-form-settings' );
    }

    public static function resources() {
        if ( ! self::editor_request() ) return;
        wp_enqueue_style( 'cff-builder-design', plugins_url( '/css/builder-design.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array(), CP_CALCULATEDFIELDSF_VERSION );
        wp_enqueue_script( 'cff-builder-design', plugins_url( '/js/builder-design.js', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array( 'jquery' ), CP_CALCULATEDFIELDSF_VERSION, true );
        wp_localize_script( 'cff-builder-design', 'cffBuilderDesign', array(
            'unsaved' => __( 'There are unsaved changes. Switching editors reloads the page and discards these changes. Cancel to save them first. Switch anyway?', 'calculated-fields-form' ),
        ) );
        if ( self::modern() ) {
            // The stylesheet must be in the document head, before the original markup can paint.
            wp_enqueue_style( 'cff-builder-workspace', plugins_url( '/css/builder-workspace.css', CP_CALCULATEDFIELDSF_MAIN_FILE_PATH ), array( 'cp_calculatedfieldsf_builder_style', 'cff-builder-design' ), CP_CALCULATEDFIELDSF_VERSION );
        }
    }

    public static function clean_notices() {
        if ( ! self::editor_request() || ! self::modern() ) return;
        // Run after header callbacks, immediately before WordPress renders notices.
        foreach ( array( 'admin_notices', 'all_admin_notices', 'network_admin_notices', 'user_admin_notices' ) as $hook ) {
            remove_all_actions( $hook );
        }
    }

    public static function prepare() {
        if ( ! self::editor_request() || ! self::modern() ) return;
        // The shared CodePeople review banner prints from the admin bar, outside notice hooks.
        remove_action( 'admin_bar_menu', 'codepeople_add_promote_banner' );
        ?>
        <script>
        (function () {
            document.documentElement.classList.add('cff-builder-loading');
            window.cffBuilderReveal = function () {
                document.documentElement.classList.remove('cff-builder-loading');
                window.clearTimeout(window.cffBuilderBootTimeout);
            };
            window.cffBuilderFallback = function () {
                if (window.cffBuilderModernReady) { window.cffBuilderReveal(); return; }
                if (window.cffBuilderRecovering) return;
                window.cffBuilderRecovering = true;
                window.cffBuilderModernFailed = true;
                window.clearTimeout(window.cffBuilderBootTimeout);
                // Rebuild native controls on the server; a partial DOM is not a classic editor.
                window.location.replace(<?php echo wp_json_encode( self::classic_url( $_GET['cal'] ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>);
            };
            window.cffBuilderBootTimeout = window.setTimeout(window.cffBuilderFallback, 15000);
			window.addEventListener('DOMContentLoaded', function () {
                window.setTimeout(function () {
                    if ((!window.cffBuilderModernLoaded || window.cffBuilderModernFailed) && !window.cffBuilderModernReady) {
                        window.cffBuilderFallback();
                    }
                }, 0);
            });
        })();
        </script>
        <?php
    }

    public static function render_switch( $form_id ) {
        $modern = self::modern();
        ?>
        <form id="cff-builder-design-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" hidden>
            <input type="hidden" name="action" value="cpcff_builder_design" />
            <input type="hidden" name="form_id" value="<?php echo absint( $form_id ); ?>" />
            <?php wp_nonce_field( 'cff-builder-design', '_cff_design_nonce', false ); ?>
        </form>
        <div class="cff-builder-design-control">
			<?php
				if ( ! get_option('cff_switch_editor_described', false) ) {
					update_option('cff_switch_editor_described', true, false);
					print '<span class="cff-builder-design-description">';
					esc_html_e( 'You can switch between the classic and modern editors. The modern editor provides a more intuitive interface and better performance.', 'calculated-fields-form' );
					print '</span>';
				}
			?>
            <span class="cff-builder-design-classic"><?php esc_html_e( 'Classic', 'calculated-fields-form' ); ?></span>
            <button type="submit" form="cff-builder-design-form" name="design" value="<?php echo $modern ? 'classic' : 'modern'; ?>" class="cff-builder-design-switch" role="switch" aria-checked="<?php echo $modern ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e( 'Modern editor', 'calculated-fields-form' ); ?>" title="<?php echo esc_attr( $modern ? __( 'Switch to classic editor (reloads the page)', 'calculated-fields-form' ) : __( 'Switch to modern editor (reloads the page)', 'calculated-fields-form' ) ); ?>"><span aria-hidden="true"></span></button>
            <span class="cff-builder-design-modern"><?php esc_html_e( 'Modern', 'calculated-fields-form' ); ?></span>
        </div>
        <?php
    }

    public static function save() {
        if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || ! current_user_can( apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ) ) ) {
            wp_die( esc_html__( 'You are not allowed to change the editor design.', 'calculated-fields-form' ), '', array( 'response' => 403 ) );
        }
        check_admin_referer( 'cff-builder-design', '_cff_design_nonce' );
        $design = isset( $_POST['design'] ) && is_string( $_POST['design'] ) ? sanitize_key( wp_unslash( $_POST['design'] ) ) : '';
        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        if ( ! in_array( $design, array( 'modern', 'classic' ), true ) || ! $form_id ) {
            wp_die( esc_html__( 'Invalid editor design.', 'calculated-fields-form' ), '', array( 'response' => 400 ) );
        }
        update_option( self::OPTION, $design, false );
        wp_safe_redirect( add_query_arg( array( 'page' => 'cp_calculated_fields_form', 'cal' => $form_id, '_cpcff_nonce' => wp_create_nonce( 'cff-form-settings' ) ), admin_url( 'admin.php' ) ) );
        exit;
    }
}
