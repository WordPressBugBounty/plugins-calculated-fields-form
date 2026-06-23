<?php
if (! is_admin() || ! current_user_can(apply_filters('cpcff_forms_edition_capability', 'manage_options'))) {
    echo 'Direct access not allowed.';
    exit;
}

if (!defined('CP_CALCULATEDFIELDSF_ID')) {
    define('CP_CALCULATEDFIELDSF_ID', ((!empty($_GET["cal"]) && is_numeric($_GET["cal"])) ? intval($_GET["cal"]) : 0));
}

/**
 * Process the submissions list
 */

global $wpdb;

$message = "";
if (
    isset($_REQUEST['cff_toggle_details']) &&
    check_admin_referer('cff-toggle-details', '_cpcff_nonce')
) {
    update_option('cff-show-entry-details', ((! empty($_REQUEST['show_details'])) ? 1 : 0));
    exit;
} elseif (
    ! empty($_GET['ld']) &&
    check_admin_referer('cff-delete-submission', '_cpcff_nonce')
) {
    $user_id = get_current_user_id();
    $items_deleted = [];
    if (is_array($_GET['ld'])) {
        foreach ($_GET['ld'] as $entry_to_delete) {
            if (is_numeric($entry_to_delete) && ($entry_to_delete = intval($entry_to_delete)) !== 0) {
                CPCFF_SUBMISSIONS::delete($entry_to_delete);
                $items_deleted[] = $entry_to_delete;
            }
        }

        if ($items_deleted && count($items_deleted) > 0) {
            error_log('Calculated Fields Form: List of submissions deleted by user ' . $user_id . ' - IDs: ' . implode(', ', $items_deleted));
        }
    } elseif (is_numeric($_GET['ld']) && ($entry_to_delete = intval($_GET['ld'])) !== 0) {
        CPCFF_SUBMISSIONS::delete($entry_to_delete);
        $items_deleted[] = $entry_to_delete;
        error_log('Calculated Fields Form: Submission with ID ' . $entry_to_delete . ' deleted by user ' . $user_id);
    }

    if (count($items_deleted) > 0) {
        $message = __('Item(s) deleted', 'calculated-fields-form');
    }
} elseif (
    isset($_GET['da']) &&
    check_admin_referer('cff-delete-all-submissions', '_cpcff_nonce')
) {
    $events = $wpdb->get_results('SELECT id FROM ' . CP_CALCULATEDFIELDSF_POSTS_TABLE_NAME);
    foreach ($events as $event) {
        CPCFF_SUBMISSIONS::delete($event->id);
    }
    error_log('Calculated Fields Form: All submissions deleted by user ' . get_current_user_id());
    $message = __('Item(s) deleted', 'calculated-fields-form');
} else {
    check_admin_referer('cff-submissions-list', '_cpcff_nonce');
}

do_action('cpcff_additional_submissions_actions', $_GET);

$form_list_opts = '';
$form_list = CPCFF_FORM::forms_list(['no_form' => true]);

foreach ($form_list as $form) {
    $selected_opt = '';
    if ($form->id == CP_CALCULATEDFIELDSF_ID) {
        $current_form = $form;
        $selected_opt = 'SELECTED';
    }
    $form_list_opts .= '<option value="' . esc_attr($form->id) . '" ' . esc_attr($selected_opt) . '>' . esc_html($form->id . ' - ' . $form->form_name) . '</option>';
}

// For pagination.
$current_page       = max(1, ((isset($_GET["p"]) && is_numeric($_GET["p"])) ? intval($_GET["p"]) : 0));
$records_per_page   = 50;

// For filtering.
$cond = '';
if (
    isset($_GET["search"]) &&
    ($to_search = sanitize_text_field(wp_unslash($_GET["search"]))) !== ''
) {
    $escaped_to_search = '%' . $wpdb->esc_like($to_search) . '%';
    $cond .= $wpdb->prepare(" AND (data like %s OR paypal_post LIKE %s)", $escaped_to_search, $escaped_to_search);
}

if (
    isset($_GET["dfrom"]) &&
    ($from_date = sanitize_text_field(wp_unslash($_GET["dfrom"]))) !== '' &&
    strtotime($from_date)
) {
    $cond .= $wpdb->prepare(" AND (`time` >= %s)", $from_date . ' 00:00:00');
}

if (
    isset($_GET["dto"]) &&
    ($to_date = sanitize_text_field(wp_unslash($_GET["dto"]))) !== '' &&
    strtotime($to_date)
) {
    $cond .= $wpdb->prepare(" AND (`time` <= %s)", $to_date . ' 23:59:59');
}

// If there is not selected a form, get only the entries corresponding to existing forms
$_from_where_clauses = $wpdb->prepare(
    "FROM " . CP_CALCULATEDFIELDSF_POSTS_TABLE_NAME .
        " LEFT JOIN " . $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE .
        " ON (" . CP_CALCULATEDFIELDSF_POSTS_TABLE_NAME . ".formid=" . $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE . ".id) WHERE formid" . (CP_CALCULATEDFIELDSF_ID == 0 ? "<>" : "=") . "%d",
    CP_CALCULATEDFIELDSF_ID
) . $cond;

$counter_events_query = "SELECT COUNT(" . CP_CALCULATEDFIELDSF_POSTS_TABLE_NAME . ".id) " . $_from_where_clauses;
$counter_events_query = apply_filters('cpcff_count_messages_query', $counter_events_query);
$total_events = $wpdb->get_var($counter_events_query);
$total_pages  = ceil($total_events / $records_per_page);

$events_query = "SELECT DISTINCT " . CP_CALCULATEDFIELDSF_POSTS_TABLE_NAME . ".*, IFNULL(" . $wpdb->prefix . CP_CALCULATEDFIELDSF_FORMS_TABLE . ".id, 0) as form_exists " . $_from_where_clauses . " ORDER BY " . CP_CALCULATEDFIELDSF_POSTS_TABLE_NAME . ".id DESC LIMIT " . (($current_page - 1) * $records_per_page) . "," . $records_per_page;

/**
 * Allows modify the query of messages, passing the query as parameter
 * returns the new query
 */
$events_query = apply_filters('cpcff_messages_query', $events_query);
$events = CPCFF_SUBMISSIONS::populate($events_query);

if ($message) {
    echo "<div id='setting-error-settings_updated' class='updated settings-error'><p><strong>" . esc_html($message) . "</strong></p></div>";
}
?>
<div class="wrap cff-entries-backend">
    <div class="cff-navigation-main-menu"><a onclick="document.location='admin.php?page=cp_calculated_fields_form';" class="button-secondary"><?php esc_html_e('Back to forms list...', 'calculated-fields-form'); ?></a></div>
    <div style="clear:both;"></div>

    <h1 style="display:block;"><?php esc_html_e('Calculated Fields Form - Message List', 'calculated-fields-form'); ?></h1>

    <div id="normal-sortables" class="meta-box-sortables">
        <hr />
        <h3><?php esc_html_e('This message list is from', 'calculated-fields-form'); ?>: <?php echo ((!empty($current_form)) ? esc_html($current_form->form_name) : esc_html__('every form', 'calculated-fields-form')); ?></h3>
    </div>


    <form action="admin.php" method="get" class="cff-filter-entries">
        <input type="hidden" name="page" value="cp_calculated_fields_form" />
        <input type="hidden" name="list" value="1" />
        <div style="display:inline-block; white-space:nowrap; margin-right:20px;">
            <?php esc_html_e('Search for', 'calculated-fields-form'); ?>: <input type="text" name="search" value="<?php echo esc_attr((! empty($to_search)) ? $to_search : ''); ?>" />
        </div>
        <div style="display:inline-block; white-space:nowrap; margin-right:20px;" class="cff-filter-left-column">
            <?php esc_html_e('From', 'calculated-fields-form'); ?>: <input type="text" id="dfrom" name="dfrom" value="<?php echo esc_attr((! empty($from_date)) ? $from_date : ''); ?>" />
        </div>
        <div style="display:inline-block; white-space:nowrap; margin-right:20px;" class="cff-filter-right-column">
            <?php esc_html_e('To', 'calculated-fields-form'); ?>: <input type="text" id="dto" name="dto" value="<?php echo esc_attr(! empty($to_date) ? $to_date : ''); ?>" />
        </div>
        <div style="display:inline-block; white-space:nowrap; margin-right:20px;">
            <?php esc_html_e('In', 'calculated-fields-form'); ?>: <select id="cal" name="cal">
                <option value="0"><?php esc_html_e('All forms', 'calculated-fields-form'); ?></option><?php echo $form_list_opts; ?>
            </select>
        </div>
        <?php
        /**
         * Additional filtering options, allows to add new fields for filtering the results
         */
        do_action('cpcff_messages_filters');
        ?>
        <nobr><span class="submit"><input type="submit" name="ds" value="<?php esc_attr_e('Filter', 'calculated-fields-form'); ?>" class="button-primary" style="min-width:100px;margin-right:10px;" /><input type="button" value="<?php esc_attr_e('Export to CSV', 'calculated-fields-form'); ?>" class="button-secondary cff-upgrade-button" onclick="cff_open_upgrade_confirm();" /></span></nobr>
        <input type="hidden" name="_cpcff_nonce" value="<?php echo wp_create_nonce('cff-submissions-list'); ?>" />
    </form>

    <br />

    <?php
    $page_links = CPCFF_AUXILIARY::paginate_links(
        [
            'base'         => 'admin.php?page=cp_calculated_fields_form&%_%',
            'format'       => 'p=%#%',
            'total'        => $total_pages,
            'current'      => $current_page,
            'show_all'     => false,
            'end_size'     => 1,
            'mid_size'     => 2,
            'prev_text'    => __('&laquo; Previous', 'calculated-fields-form'),
            'next_text'    => __('Next &raquo;', 'calculated-fields-form'),
            'add_args'     => [
                'cal'           => CP_CALCULATEDFIELDSF_ID,
                'list'          => 1,
                'dfrom'         => ((! empty($from_date)) ? $from_date : ''),
                'dto'           => ((! empty($to_date)) ? $to_date : ''),
                'search'        => ((! empty($to_search)) ? $to_search : ''),
                '_cpcff_nonce'  => wp_create_nonce('cff-submissions-list'),
            ]
        ]
    );

    $page_links_top = '<div style="text-align:right;">' .
        '<div style="float:left;">
        <label><input type="checkbox" name="show_details" onchange="cp_toggle_details(this);" ' . ((get_option('cff-show-entry-details', 0)) ? 'CHECKED' : '') . '> ' . esc_html__('Show all entry details', 'calculated-fields-form') . '</label>' .
        '</div>' .
        $page_links .
        '<div style="clear:both;"></div></div>';

    $page_links_bottom = '<div style="text-align:right;">' .
        $page_links .
        '</div>';

    echo $page_links_top;
    ?>
    <div id="dex_printable_contents" style="padding:10px 0;">
        <table class="wp-list-table widefat pages cff-custom-table cff-events-list <?php echo ((get_option('cff-show-entry-details', 0)) ? 'cff-event-show-details' : ''); ?>" cellspacing="0">
            <thead>
                <tr>
                    <th style="font-weight:bold;"><input type="checkbox" onclick="cp_checkAllItems( this )" style="margin-left:8px;"></th>
                    <th style="padding-left:7px;font-weight:bold;"><?php esc_html_e('ID', 'calculated-fields-form'); ?></th>
                    <th style="padding-left:7px;font-weight:bold;"><?php esc_html_e('Form', 'calculated-fields-form'); ?></th>
                    <th style="padding-left:7px;font-weight:bold;"><?php esc_html_e('Date', 'calculated-fields-form'); ?></th>
                    <th style="padding-left:7px;font-weight:bold;"><?php esc_html_e('IP Address', 'calculated-fields-form'); ?></th>
                    <th style="padding-left:7px;font-weight:bold;"><?php esc_html_e('Email', 'calculated-fields-form'); ?><a href="jasvascript:void(0);" onclick="cff_open_upgrade_confirm();" style="text-decoration:none;margin-left:5px;">&#128712;</a></th>
                    <th style="padding-left:7px;font-weight:bold;"><?php esc_html_e('Payment info', 'calculated-fields-form'); ?><a href="jasvascript:void(0);" onclick="cff_open_upgrade_confirm();" style="text-decoration:none;margin-left:5px;">&#128712;</a></th>
                    <?php
                    /**
                     * Action called to include new headers in the table of messages
                     */
                    do_action('cpcff_messages_list_header');
                    ?>
                    <th style="padding-left:7px;font-weight:bold;" class="cff-events-actions"><?php esc_html_e('Options', 'calculated-fields-form'); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php
                if (empty($events)) echo '<tr><td colspan="100%" style="text-align:center;">' . esc_html__('No events found.', 'calculated-fields-form') . '</td></tr>';
                $year_month = '';
                foreach ($events as $event) if (isset($event)) {
                    $time = strtotime($event->time);
                    $current_year_month = date('Y-m', $time);
                    if ($year_month !== $current_year_month) {
                        $year_month = $current_year_month;
                        echo '<tr><td colspan="100%" style="font-weight:bold;text-align:center;background-color:#ffeebc;text-transform:uppercase;border-bottom: 1px solid #c3c4c7;">' . esc_html(date_i18n('F Y', $time)) . '</td></tr>';
                    }
                ?>
                    <tr class='alternate author-self status-draft format-default iedit' valign="top">
                        <td><input type="checkbox" value="<?php echo esc_attr($event->id); ?>" class="cp_item" style="margin-left:8px;"></td>
                        <td><?php echo esc_html($event->id); ?></td>
                        <td><?php echo esc_html($event->formid); ?></td>
                        <td><?php echo esc_html(date('Y-m-d H:i', $time)); ?></td>
                        <td><?php echo esc_html($event->ipaddr); ?></td>
                        <td>-</td>
                        <td>-</td>
                        <?php
                        /**
                         * Action called to add related data to the message
                         * The row is passed as parameter
                         */
                        do_action('cpcff_message_row_data', $event);
                        ?>
                        <td class="cff-events-actions">
                            <?php
                            $buttons = '';
                            $buttons .= '<input type="button" name="caldelete_' . esc_attr($event->id) . '" value="' . esc_attr__('Delete', 'calculated-fields-form') . '" onclick="cp_deleteMessageItem(' . esc_attr($event->id) . ', ' . esc_attr($event->formid) . ');" class="button-secondary" />' .
                                '<input type="button" value="' . esc_attr__('Change Payment Status', 'calculated-fields-form') . '" onclick="cff_open_upgrade_confirm();" class="button-secondary cff-upgrade-button" />' .
                                '<input type="button" value="' . esc_attr__('Edit (Raw)', 'calculated-fields-form') . '" onclick="cff_open_upgrade_confirm();" class="button-secondary cff-upgrade-button" />' .
                                '<input type="button" value="' . esc_attr__('Send Mails', 'calculated-fields-form') . '" onclick="cff_open_upgrade_confirm();" class="button-secondary cff-upgrade-button" />';

                            echo apply_filters('cpcff_message_row_buttons', $buttons, $event);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="100" style="border-top:1px solid #c3c4c7;border-bottom:1px solid #c3c4c7;">
                            <div class="cff-event-data">
                                <div class="cff-event-data-handle" onclick="this.closest('.cff-event-data').classList.toggle('cff-event-show-details');" title="<?php esc_attr_e('Show/hide details', 'calculated-fields-form'); ?>">
                                    <hr />
                                </div>
                                <div class="cff-event-data-details">
                                    <?php
                                    echo str_replace("\n", "<br />", wp_kses_post(wp_unslash($event->data)));

                                    // Add links
                                    $serialized_entry_details = $event->paypal_post;
                                    $entry_details = unserialize($serialized_entry_details, ['allowed_classes' => false]);
                                    if ($entry_details !== false) {
                                        if (preg_match('/fieldname\d+_url/', $serialized_entry_details)) {
                                            foreach ($entry_details as $_key => $_value) {
                                                if (is_array($_value) && strpos($_key, '_url')) {
                                                    foreach ($_value as $_url) {
                                                        echo '<p><a href="' . esc_url($_url) . '" target="_blank">' . esc_html($_url) . '</a></p>';
                                                    }
                                                }
                                            }
                                        }

                                        echo '</div>';
                                        echo '<div class="cff-event-data-footer">';
                                        echo '<hr />';
                                        if (!empty($entry_details['from_page'])) {
                                            echo '<p>' . esc_html__('Form Page', 'calculated-fields-form') . ': <a href="' . esc_url($entry_details['from_page']) . '" target="_blank">' . esc_html($entry_details['from_page']) . '</a></p>';
                                        }
                                    }
                                    do_action('cpcff_message_additional_details', $event);
                                    ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php
    echo $page_links_bottom;
    ?>
    <p class="submit"><input type="button" name="pbutton" value="<?php esc_attr_e('Delete all checked', 'calculated-fields-form'); ?>" onclick="cp_deleteAllTicked();" class="button-secondary" /> <input type="button" name="pbutton" value="<?php esc_attr_e('Print', 'calculated-fields-form'); ?>" onclick="cp_do_dexapp_print();" class="button-secondary" /> <span style="display:inline-block;float:right;text-align:right;"><input type="button" name="pbutton" value="<?php esc_attr_e('Delete all entries from all forms', 'calculated-fields-form'); ?>" onclick="cp_deleteAll();" class="button-secondary" style="background-color:#951717;border-color:#951717;color:white;" /><br><i><?php esc_html_e('Caution: "Delete all" erases all submissions in the database.', 'calculated-fields-form'); ?></i></span></p>

    <div style="border:1px solid #F0AD4E;background:#FBE6CA;padding:10px;color:#3c434a;margin-bottom:20px;box-sizing:border-box;">
        <p><?php
        esc_html_e('The commercial version of the "Calculated Fields Form" plugin allows you to add a summary of the information collected by the form on the "Thank You Page" content, manage payments, send user confirmation emails, analyze data by using AI agents, and much more.', 'calculated-fields-form');
        ?></p>
        <p style="text-transform: uppercase; font-weight:700; font-size:24px;margin-top:15px;margin-bottom:15px;line-height:28px;"><a href="https://cff.dwbooster.com/download" target="_blank" style="text-decoration:none;color:#3c434a;text-shadow:1px 1px 2px white;"><?php esc_html_e('Pay only ONCE, use it FOREVER', 'calculated-fields-form'); ?></a></p>
        <p style="font-size:18px; font-weight:400;line-height:28px;"><?php esc_html_e('No additional charges, ', 'calculated-fields-form'); ?> <span style="background:white;display:inline-block;padding:0 5px;"><a href="https://cff.dwbooster.com/terms" target="_blank" style="text-decoration:none;"><?php esc_html_e('lifetime updates', 'calculated-fields-form'); ?></a></span>, <?php esc_html_e('one copy for all your websites', 'calculated-fields-form'); ?> <a href="https://cff.dwbooster.com/download" target="_blank" style="text-decoration:none;" class="button-primary"><?php esc_html_e('Get it now', 'calculated-fields-form'); ?></a></p>
    </div>
</div>

<script data-category="functional" type="text/javascript">
    (function() {
        let $ = fbuilderjQuery;
        $(function() {
            $("#dfrom").datepicker({
                dateFormat: 'yy-mm-dd'
            });
            $("#dto").datepicker({
                dateFormat: 'yy-mm-dd'
            });
        });
    })();

    function cp_checkAllItems(e) {
        try {
            let $ = fbuilderjQuery;
            $(e).closest('table').find('input[type="checkbox"]').prop('checked', $(e).prop('checked'));
        } catch (err) {}
    }

    function cp_deleteMessageItem(id, form_id) {
        let title = "<?php echo esc_js(__('Delete entry', 'calculated-fields-form')); ?>";
        let yes_button = "<?php echo esc_js(__('Yes, delete it', 'calculated-fields-form')); ?>";
        let no_button = "<?php echo esc_js(__('No, keep it', 'calculated-fields-form')); ?>";
        let message = "<?php echo esc_js(__('You are about to delete the item with id: ', 'calculated-fields-form')); ?>" + id + " <?php echo esc_js(__('in the form: ', 'calculated-fields-form')); ?>" + form_id + "<br><b><?php echo esc_js(__('Are you sure that you want to delete this item?', 'calculated-fields-form')); ?></b>";

        fbuilderjQuery.fbuilder.confirmationDialog(title, message, yes_button, no_button, function() {
            document.location = 'admin.php?page=cp_calculated_fields_form&cal=<?php echo CP_CALCULATEDFIELDSF_ID; ?>&list=1&ld=' + id + '&r=' + Math.random() + '&_cpcff_nonce=<?php echo wp_create_nonce('cff-delete-submission'); ?>';
            return true;
        });
    }

    function cp_deleteAllTicked() {
        try {
            let $ = fbuilderjQuery,
                ld = [],
                ids = [];

            $('.cp_item:checked').each(function() {
                ids.push(this.value);
                ld.push('ld[]=' + this.value);
            });
            if (ld.length) {
                let title = "<?php echo esc_js(__('Delete entries', 'calculated-fields-form')); ?>";
                let yes_button = "<?php echo esc_js(__('Yes, delete them', 'calculated-fields-form')); ?>";
                let no_button = "<?php echo esc_js(__('No, keep them', 'calculated-fields-form')); ?>";
                let message = "<?php echo esc_js(__('You are about to delete the checked item(s) with id(s): ', 'calculated-fields-form')); ?>" + ids.join(', ') + "<br><b><?php echo esc_js(__('Are you sure that you want to delete the item(s)?', 'calculated-fields-form')); ?></b>";

                fbuilderjQuery.fbuilder.confirmationDialog(title, message, yes_button, no_button, function() {
                    document.location = 'admin.php?page=cp_calculated_fields_form&cal=<?php echo CP_CALCULATEDFIELDSF_ID; ?>&list=1&' + ld.join('&') + '&r=' + Math.random() + '&_cpcff_nonce=<?php echo wp_create_nonce('cff-delete-submission'); ?>';
                    return true;
                });
            } else {
                alert('<?php echo esc_js(__('Please select at least one item to delete.', 'calculated-fields-form')); ?>');
            }
        } catch (err) {}
    }

    function cp_deleteAll() {
        try {
            let title = "<?php echo esc_js(__('Delete all entries from all forms', 'calculated-fields-form')); ?>";
            let yes_button = "<?php echo esc_js(__('Yes, delete them', 'calculated-fields-form')); ?>";
            let no_button = "<?php echo esc_js(__('No, keep them', 'calculated-fields-form')); ?>";
            let message = "<b><?php echo esc_js(__('Are you sure that you want to delete all items from all the forms? Please note that you will not be able to recover them.', 'calculated-fields-form')); ?></b>" + "<label for='cp_confirm_delete_all' style='display:block;margin-top:20px;'>" + <?php echo wp_json_encode(__('Enter the <b>"delete"</b> word to confirm:', 'calculated-fields-form')); ?> + "</label><input type='text' id='cp_confirm_delete_all' style='width:100%;margin-top:10px;' />";

            fbuilderjQuery.fbuilder.confirmationDialog(title, message, yes_button, no_button, function() {
                let confirmation = jQuery('#cp_confirm_delete_all').val();
                if (String(confirmation).toLowerCase().trim() != 'delete') {
                    alert('<?php echo esc_js(__('Please enter the "delete" word to confirm.', 'calculated-fields-form')); ?>');
                    return false;
                }
                document.location = 'admin.php?page=cp_calculated_fields_form&cal=<?php echo CP_CALCULATEDFIELDSF_ID; ?>&list=1&r=' + Math.random() + '&da=1&_cpcff_nonce=<?php echo wp_create_nonce('cff-delete-all-submissions'); ?>';
                return true;
            });
        } catch (err) {}
    }

    function cp_do_dexapp_print() {
        w = window.open('', '_blank', 'noopener,noreferrer');
        w.document.write("<style>table{border:2px solid black;width:100%;}th{border-bottom:2px solid black;text-align:left}td{padding-left:10px;border-bottom:1px solid black;} img{max-width:100%;}</style>" + document.getElementById('dex_printable_contents').innerHTML);
        w.document.close();
        w.focus();
        w.print();
        w.close();
    }

    function cp_toggle_details(e) {
        fbuilderjQuery.ajax({
            url: '<?php echo esc_url(admin_url('admin.php?page=cp_calculated_fields_form')); ?>',
            type: 'GET',
            data: {
                _cpcff_nonce: '<?php echo esc_js(wp_create_nonce('cff-toggle-details')); ?>',
                list: 1,
                cal: 0,
                cff_toggle_details: 1,
                show_details: e.checked ? 1 : 0
            }
        });
        document.getElementsByClassName('cff-events-list')[0].classList[e.checked ? 'add' : 'remove']('cff-event-show-details');
    }

    function cff_open_upgrade_confirm() {
        if (confirm("<?php print esc_js(__('These features aren\'t available in this version. Do you want to open the plugin\'s page to check other versions?', 'calculated-fields-form')); ?>"))
            window.open('https://cff.dwbooster.com/download', '_blank');
    }
</script>