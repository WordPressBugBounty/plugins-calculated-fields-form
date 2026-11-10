<?php
/**
 * MCP server for Calculated Fields Form
 *
 * Exposes registered abilities through a REST endpoint
 * implementing the MCP protocol over HTTP+SSE transport
 * with full JSON-RPC 2.0 compliance.
 *
 * @package Calculated_Fields_Form
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CFF_MCP_SERVER' ) ) :

class CFF_MCP_SERVER {

    const NAMESPACE       = 'cff-mcp/v1';
    const ROUTE           = '/process';

    // Name given to the Application Password that this plugin creates from the
    // setup popup. The name tells the user which password the site created,
    // when he looks for it in his profile.
    const APP_PASSWORD_NAME = 'CFF AI Assistant';

    // Protocol revisions this server can speak. The MCP specification
    // deprecated the HTTP+SSE transport (2024-11-05) in favour of Streamable
    // HTTP (2025-03-26 and later); the endpoint implements Streamable HTTP and
    // negotiates the revision with the client on every initialize request.
    const PROTOCOL_VERSION = '2025-11-25';
    const SUPPORTED_PROTOCOL_VERSIONS = array( '2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25' );

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        add_action( 'wp_ajax_cpcff_mcp_create_app_password', array( $this, 'ajax_create_app_password' ) );
    }

	static public function get_auth_info() {
		$username 			= '';
		$has_app_passwords  = false;
		$app_password_hint 	= '';
		$app_passwords_url 	= admin_url( 'profile.php#application-passwords-section' );

		$user = wp_get_current_user();
		if ( $user ) {
			$username = $user->user_login;

			// Check if Application Passwords are available (WP 5.6+)
			// and if the current user has any created.

			if ( class_exists( 'WP_Application_Passwords' ) ) {
				$passwords = WP_Application_Passwords::get_user_application_passwords( $user->ID );

				if ( ! empty( $passwords ) ) {
					$has_app_passwords = true;
					$app_password_hint = $passwords[0]['name'];
				}
			}
		}

		return array(
			'username'          => $username,
			'has_app_passwords' => $has_app_passwords,
			'app_password_hint' => $app_password_hint, // Name of the first app password
			'app_passwords_url' => $app_passwords_url, // Where the user manages them
		);
	}

	/**
	 * Creates an Application Password for the current user and returns its value.
	 *
	 * WordPress shows the value of an application password only once, at
	 * creation time, so the popup cannot offer it unless the site creates it.
	 * WP_Application_Passwords::create_new_application_password() applies no
	 * check on its own, so the guards that the core applies in
	 * wp-admin/authorize-application.php are replicated here.
	 */
	public function ajax_create_app_password() {
		check_ajax_referer( 'cpcff_mcp_app_password', 'nonce' );

		// admin-ajax.php only dispatches this action for a user with a
		// session, so the user always exists here and the gate that matters is
		// the capability one.
		$user = wp_get_current_user();

		if ( ! current_user_can( apply_filters( 'cpcff_forms_edition_capability', 'manage_options' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'calculated-fields-form' ) ), 403 );
		}

		if ( ! class_exists( 'WP_Application_Passwords' ) || ! wp_is_application_passwords_available_for_user( $user ) ) {
			wp_send_json_error( array( 'message' => __( 'Application passwords are not available on this site. They need a secure (https) address.', 'calculated-fields-form' ) ), 400 );
		}

		if ( wp_is_site_protected_by_basic_auth( 'front' ) ) {
			wp_send_json_error( array( 'message' => __( 'This site is protected by a basic authentication password, so application passwords cannot be used.', 'calculated-fields-form' ) ), 400 );
		}

		$created = WP_Application_Passwords::create_new_application_password(
			$user->ID,
			array( 'name' => self::APP_PASSWORD_NAME )
		);

		if ( is_wp_error( $created ) ) {
			wp_send_json_error( array( 'message' => $created->get_error_message() ), 400 );
		}

		list( $password, $item ) = $created;

		wp_send_json_success( array( 'password' => $password ) );
	}

	/**
	 * Escapes a value for a double quoted shell argument.
	 */
	private static function shell_escape( $value ) {
		return str_replace(
			array( '\\', '"', '$', '`' ),
			array( '\\\\', '\\"', '\\$', '\\`' ),
			(string) $value
		);
	}

	/**
	 * Builds the block with the command that registers the server.
	 */
	private static function command_step( $command, $element_id, $note ) {
		return '<div style="margin-top:20px;margin-bottom:20px;"><b>' . esc_html__( 'Step 2 - Run this command', 'calculated-fields-form' ) . '</b></div>' .
			'<p style="margin: 0 0 6px;">' .
				esc_html__( 'Copy the command below and paste it in the terminal of your computer (the window where you type commands: on Windows press Win + R, type cmd and press Enter; on macOS open the Terminal application). Then press Enter.', 'calculated-fields-form' ) .
			'</p>' .
			'<div style="margin-bottom:10px;display:flex;gap:10px;align-items:end;"><span style="flex-grow:1;">' . esc_html__( 'Copy the following command:', 'calculated-fields-form' ) . '</span><button type="button" onclick="navigator.clipboard.writeText(document.getElementById(\'' . $element_id . '\').textContent);alert(\'' . esc_js( __( 'Copied !!!', 'calculated-fields-form' ) ) . '\');" class="button-secondary">' . esc_html__( 'Copy to clipboard', 'calculated-fields-form' ) . '</button></div>' .
			'<pre id="' . esc_attr( $element_id ) . '" style="background:#1e1e1e; color:#d4d4d4; padding:12px 16px; border-radius:5px; overflow-x:auto; font-size:13px; margin:0 0 6px;">' . esc_html( $command ) . '</pre>' .
			'<p style="margin: 0 0 12px; font-size: 13px; color: #646970;">' . $note . '</p>';
	}
	static public function get_setup_instructions_html() {
        if (! current_user_can(apply_filters('cpcff_forms_edition_capability', 'manage_options'))) {
            return '';
        }
		$auth_info = self::get_auth_info();
		$username = ! empty( $auth_info['username']) ? $auth_info['username'] : 'WP_USERNAME';
		$route_url = self::get_route();

		$button_label = esc_html__( 'Create a password for me', 'calculated-fields-form' );

		$opencode_json = json_encode(
			array(
				'$schema' => 'https://opencode.ai/config.json',
				'mcp'     => array(
					'wordpress-cff-mcp' => array(
						'type'    => 'remote',
						'url'     => $route_url,
						'headers' => array(
							'X-WP-Username'     => $username,
							'X-WP-App-Password' => 'WP_PASSWORD',
						),
						'oauth'   => false,
						'enabled' => true,
					),
				),
			), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);

		$mcp_json = json_encode(
			array(
				'mcpServers' => array(
					'wordpress-cff-mcp' => array(
						'type' => 'http',
						'url' => $route_url,
						'headers' => array(
							'X-WP-Username' => $username,
							'X-WP-App-Password' => 'WP_PASSWORD',
						),
					),
				),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);

		$config_toml = <<<EOT
		[mcp_servers.wordpress-cff-mcp]
		url = "{$route_url}"

		[mcp_servers.wordpress-cff-mcp.http_headers]
		X-WP-Username = "{$username}"
		X-WP-App-Password = "WP_PASSWORD"
		EOT;

		// The values of the commands are escaped for the shell (double-quoted
		// context) so a username or a site URL can never inject extra arguments
		// into the generated command.

		// The commands register the server in the USER account of the agent, not in
		// a project: the user does not need to know which folder to run them in and
		// the password does not end up in a file inside a repository.
		$opencode_cmd = 'opencode mcp add wordpress-cff-mcp --global --url "' . self::shell_escape( $route_url ) . '"' .
			' --header "X-WP-Username=' . self::shell_escape( $username ) . '"' .
			' --header "X-WP-App-Password=WP_PASSWORD"';

		$claude_cmd = 'claude mcp add --transport http --scope user wordpress-cff-mcp "' . self::shell_escape( $route_url ) . '"' .
			' --header "X-WP-Username: ' . self::shell_escape( $username ) . '"' .
			' --header "X-WP-App-Password: WP_PASSWORD"';


		$opencode_block = self::command_step(
			$opencode_cmd,
			'cff-opencode-cmd',
			esc_html__( 'You can run the command from any folder: the connection is saved in your user account, so the assistant finds it wherever you work.', 'calculated-fields-form' )
		);

		$claude_block = self::command_step(
			$claude_cmd,
			'cff-claude-cmd',
			esc_html__( 'You can run the command from any folder: the connection is saved in your user account, so the assistant finds it wherever you work.', 'calculated-fields-form' )
		);

		$output = '<div id="cff-mcp-instructions" class="cff-settings-dialog-modal" style="display:none;text-align:left;"><div class="cff-settings-dialog"  style="padding-bottom:10px;gap:10px;">' .

        '<div class="cff-settings-dialog-header" style="display:flex;gap:10px;padding-bottom:0;align-items:flex-start;">
			<span style="flex-grow:1;text-align:left;">
				<b style="font-size:16px;">' .
        		esc_html__( 'Connect your AI assistant to this site', 'calculated-fields-form' ) .
				'</b>
				<div style="margin-top:5px;display:flex;align-items:flex-start;gap:10px;">
					<span>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
  						<circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.5"/>
  						<polygon points="6,5.5 10.5,8 6,10.5" fill="currentColor"/>
						</svg>
					</span>
					<a href="https://youtu.be/ZF5SoaKVmOg?si=cOH_OTksABk3WJyW" target="_blank" rel="noopener" style="text-decoration:none;" title="'. esc_html__('Video tutorial', 'calculated-fields-form') . '">' . esc_html__('Analyzing form submissions', 'calculated-fields-form') . '</a>
				</div>
			</span>
			<a href="javascript:void(0);" onclick="fbuilderjQuery(\'.cff-settings-dialog-modal\').animate({\'opacity\':0}, \'fast\', function(){this.style.display=\'none\';});" title="' . esc_html__('Close', 'calculated-fields-form') . '" class="cff-settings-dialog-close">&times;</a>' .
        '</div>' .

		'<div class="cff-settings-dialog-container" style="overflow-y:auto;padding-top:0;padding-bottom:30px;">'.
		'<p style="padding:10px;border:1px solid #00A32A;">' . __('Three steps and about two minutes: <strong>1)</strong> create a password for the assistant, <strong>2)</strong> run one command in the terminal, <strong>3)</strong> check that the assistant can read the forms and the submissions of this site. Choose below the tool you use to talk with the AI. <strong>Claude Desktop</strong>, <strong>Cursor</strong>, <strong>Windsurf</strong> and <strong>Cline</strong> also work with this site: they use the same configuration format shown below, but each one keeps it in its own settings file.', 'calculated-fields-form') . '</p>' .

		'<!-- STEP 1: Application Password -->' .
        '<div style="margin-bottom:20px;"><b>' . esc_html__( 'Step 1 - Create an Application Password', 'calculated-fields-form' ) . '</b></div>' .
				'<p style="margin: 0 0 12px;">' . esc_html__( 'The assistant needs a password to connect to this site.', 'calculated-fields-form' ) . '</p>';

		if ( $auth_info['has_app_passwords'] ) :

			$output .= wp_kses_post(
				'<p id="cff-mcp-has-app-password" style="margin: 0 0 20px; padding: 8px 12px; background: #edfaef; border-left: 4px solid #00a32a; border-radius: 3px;">' .
				sprintf(
					wp_kses_post( __( '✅ You already have an application password.<br>If you still have its value, type it in the field and press Apply. If you do not have it, press the "%1$s" button and the site will create a new one for you.', 'calculated-fields-form' ) ),
					$button_label
				) .
				'</p>'
			);

        else :

			$output .= wp_kses_post(
				'<p id="cff-mcp-no-app-password" style="margin: 0 0 20px; padding: 8px 12px; background: #fcf0f1; border-left: 4px solid #d63638; border-radius: 3px;">' .
				sprintf(
					wp_kses_post( __( '⚠️ You do not have an application password yet.<br>The site can create one for you by pressing the "%1$s" button, or you can create one yourself in <a href="%2$s" target="_blank" rel="noopener">your profile → Application Passwords</a>.', 'calculated-fields-form' ) ),
					$button_label,
					esc_url( $auth_info['app_passwords_url'] )
				) .
				'</p>'
			);

        endif;

		// The site can create the application password for the user, so the popup
		// does not need to send them to the profile screen and back.
		$can_create  = false;
		$create_note = '';
		$popup_user  = wp_get_current_user();
		if ( ! class_exists( 'WP_Application_Passwords' ) ) {
			$create_note = __( 'Application passwords are not available on this site.', 'calculated-fields-form' );
		} elseif ( ! wp_is_application_passwords_available_for_user( $popup_user ) ) {
			$create_note = __( 'Application passwords are not available on this site. They need a secure (https) address.', 'calculated-fields-form' );
		} elseif ( wp_is_site_protected_by_basic_auth( 'front' ) ) {
			$create_note = __( 'This site is protected by a basic authentication password, so application passwords cannot be used.', 'calculated-fields-form' );
		} else {
			$can_create = true;
		}

		// The two ways of getting a password are kept apart: they are two
		// different actions, and the user has to know which one he is doing.
		$output .= '<div style="margin-bottom:20px;">' .
			'<b>' . esc_html__( 'Create a new password', 'calculated-fields-form' ) . '</b>' .
			'<p style="margin: 4px 0 8px; font-size: 13px; color: #646970;">' . esc_html__( 'The site creates it for you and fills it in automatically.', 'calculated-fields-form' ) . '</p>' .
			'<button type="button" id="cff-mcp-create-app-password" class="button-secondary"' . ( $can_create ? '' : ' disabled' ) . '>' . $button_label . '</button>' .
			'<span id="cff-mcp-create-app-password-message" style="margin-left:10px;color:#b32d2e;font-size:13px;">' . esc_html( $create_note ) . '</span>' .
			'</div>';

		$output .= '<hr style="border:0;border-top:1px solid #dcdcde;margin:0 0 20px;">';

		$output .= '<div style="margin-bottom:20px;">' .
			'<b>' . esc_html__( 'Use a password you already have', 'calculated-fields-form' ) . '</b>' .
			'<div style="display:flex;gap:10px;margin-top:8px;margin-bottom:8px;"><input type="password" id="cff-application-password" autocomplete="off" spellcheck="false" style="flex-grow:1;height:initial;" placeholder="' . esc_html__( 'Paste the application password', 'calculated-fields-form') . '"><button id="cff-apply-application-password" type="button" class="button-secondary">' . esc_html__('Apply', 'calculated-fields-form') . '</button></div>' .
			'<p id="cff-mcp-password-hint" style="margin: 0 0 0; font-size: 13px; color: #646970;">' . esc_html__( 'Paste your password and press Apply. Steps 2 and 3 appear after that.', 'calculated-fields-form' ) . '</p>' .
			'</div>';


		// Revoking a password is left to the user: it may already be in use by
		// an agent, and invalidating it would break that agent. The note is
		// always rendered, because the site can create a password from this
		// window, and then the note has to appear without reloading the page.
		$output .= wp_kses_post(
			'<p id="cff-mcp-revoke-note" style="margin: 0 0 20px; font-size: 13px; color: #646970;' . ( $auth_info['has_app_passwords'] ? '' : ' display:none;' ) . '">' .
			sprintf(
				wp_kses_post( __( 'Do you want to revoke a password? You can remove your application passwords in <a href="%s" target="_blank" rel="noopener">your profile → Application Passwords</a>.', 'calculated-fields-form' ) ),
				esc_url( $auth_info['app_passwords_url'] )
			) .
			'</p>'
		);

		$output .= '<div id="cff-mcp-agent-steps" style="display:none;">' .
			'<p style="margin: 0 0 10px; font-size: 13px; color: #646970;">' . esc_html__( 'Which tool do you use to talk with the AI?', 'calculated-fields-form' ) . '</p>' .
			'<div class="cff-radio-group-ctrl">
			<label tabindex="0"><input type="radio" name="cpcff_ai_agent" value="opencode" checked><span>OpenCode</span></label>
			<label tabindex="0"><input type="radio" name="cpcff_ai_agent" value="claudecode"><span>Claude Code</span></label>
			<label tabindex="0"><input type="radio" name="cpcff_ai_agent" value="codex"><span>Codex</span></label>
		</div>';

        $output .=
		'<div class="cpcff_ai_agent_opencode">
			<!-- STEP 2: one command -->' . $opencode_block . '
			<!-- ALTERNATIVE: opencode.json -->
        	<div style="margin-top:20px;margin-bottom:20px;"><b>' . wp_kses_post( __( 'Only if you prefer to do it by hand - the <code>opencode.json</code> file', 'calculated-fields-form' ) ) . '</b></div>
        	<p style="margin: 0 0 6px;">'.
        		wp_kses_post( __( 'Open the file <code>opencode.json</code> in your user settings folder with any text editor and paste the content below. If the file already exists, add the block at the end and keep the rest of the content:', 'calculated-fields-form' ) ) .
			'</p>
			<code style="background:#f0f0f1; padding:2px 6px; border-radius:3px;">~/.config/opencode/opencode.json</code>' .

        	'<div style="margin-bottom:10px;display:flex;gap:10px;align-items:end;"><span style="flex-grow:1;">' . esc_html__( 'Copy the following content into the file:', 'calculated-fields-form' ) . '</span><button type="button" onclick="navigator.clipboard.writeText(document.getElementById(\'cff-opencode-json\').textContent);alert(\'' . esc_js( __( 'Copied !!!', 'calculated-fields-form' ) ) . '\');" class="button-secondary">' . esc_html__('Copy to clipboard', 'calculated-fields-form') . '</button></div>' .

        	'<pre id="cff-opencode-json" style="background:#1e1e1e; color:#d4d4d4; padding:12px 16px; border-radius:5px; overflow-x:auto; font-size:13px; margin:0 0 6px;">' . esc_html( $opencode_json ) . '</pre>' .
        	'<p style="margin: 0 0 12px; font-size: 13px; color: #646970;">' .
        	esc_html__( 'The password has already been filled in for you. Save the file and you are done.', 'calculated-fields-form' ) .
        	'</p>';

			$output .= '<!-- STEP 3: Verify -->' .
        	'<div style="margin-bottom:20px;"><b>'. esc_html__( 'Step 3 - Check the connection', 'calculated-fields-form' ) . '</b></div>' .
        	'<p style="margin: 0 0 6px;">' .
        	esc_html__( 'Now start your assistant as usual: type opencode in the terminal. You must see three tools named get-entries, ai-form-bootstrap and issue-preview-url.', 'calculated-fields-form' ) .
        	'</p>' .
        	'<p style="margin: 0 0 0; font-size: 13px; color: #646970;">' .
        	esc_html__( 'If you do not see them, repeat the command with the correct application password.', 'calculated-fields-form' ) .
        	'</p>' .
		'</div>';

		$output .=
				'<div class="cpcff_ai_agent_claudecode" style="display:none;">
			<!-- STEP 2: one command -->' . $claude_block . '
			<!-- ALTERNATIVE: .mcp.json -->
        	<div style="margin-top:20px;margin-bottom:20px;"><b>' . __('Only if you prefer to do it by hand - the <code>.claude.json</code> file', 'calculated-fields-form') . '</b></div>
        	<p style="margin: 0 0 6px;">' .
				__('Open the file <code>.claude.json</code> in your user settings folder with any text editor and paste the content below. If the file already exists, add the block at the end and keep the rest of the content. Claude Desktop, Cursor, Windsurf and Cline use this same format from their own settings file:', 'calculated-fields-form') .
			'</p>
			<code style="background:#f0f0f1; padding:2px 6px; border-radius:3px;">~/.claude.json</code>' .

			'<div style="margin-bottom:10px;display:flex;gap:10px;align-items:end;"><span style="flex-grow:1;">' . esc_html__('Copy the following content into the file:', 'calculated-fields-form') . '</span><button type="button" onclick="navigator.clipboard.writeText(document.getElementById(\'cff-mcp-json\').textContent);alert(\'' . esc_js( __( 'Copied !!!', 'calculated-fields-form' ) ) . '\');" class="button-secondary">' . esc_html__('Copy to clipboard', 'calculated-fields-form') . '</button></div>' .

			'<pre id="cff-mcp-json" style="background:#1e1e1e; color:#d4d4d4; padding:12px 16px; border-radius:5px; overflow-x:auto; font-size:13px; margin:0 0 6px;">' . esc_html($mcp_json) . '</pre>' .
			'<p style="margin: 0 0 12px; font-size: 13px; color: #646970;">' .
			__('The password has already been filled in for you. Save the file and you are done.', 'calculated-fields-form') .
			'</p>';

			$output .= '<!-- STEP 3: Verify -->' .
			'<div style="margin-bottom:20px;"><b>' . esc_html__('Step 3 - Check the connection', 'calculated-fields-form') . '</b></div>' .
			'<p style="margin: 0 0 6px;">' .
			esc_html__('Now start Claude Code as usual: type claude in the terminal and then type /mcp. The status of this site must be Connected.', 'calculated-fields-form') .
			'</p>' .
			'<p style="margin: 0 0 0; font-size: 13px; color: #646970;">' .
			esc_html__('If the status is not connected, repeat the command with the correct application password.', 'calculated-fields-form') .
			'</p>' .
		'</div>';

		$output .=
		'<div class="cpcff_ai_agent_codex" style="display:none;">
			<!-- STEP 2: config.toml -->
        	<div style="margin-top:20px;margin-bottom:20px;"><b>' . __('Step 2 - Add the server to the Codex settings file', 'calculated-fields-form') . '</b></div>
        	<p style="margin: 0 0 6px;">' .
				__('Codex cannot register a server with a password from the command line, so it needs a settings file instead. Open the file <code>config.toml</code> in your user settings folder with any text editor, add the block below at the end of the file and save it. If the file does not exist, create it:', 'calculated-fields-form') .
			'</p>
			<code style="background:#f0f0f1; padding:2px 6px; border-radius:3px;">~/.codex/config.toml</code>' .

			'<div style="margin-bottom:10px;display:flex;gap:10px;align-items:end;"><span style="flex-grow:1;">' . esc_html__('Copy the following content into the file:', 'calculated-fields-form') . '</span><button type="button" onclick="navigator.clipboard.writeText(document.getElementById(\'cff-config-toml\').textContent);alert(\'' . esc_js( __( 'Copied !!!', 'calculated-fields-form' ) ) . '\');" class="button-secondary">' . esc_html__('Copy to clipboard', 'calculated-fields-form') . '</button></div>' .

			'<pre id="cff-config-toml" style="background:#1e1e1e; color:#d4d4d4; padding:12px 16px; border-radius:5px; overflow-x:auto; font-size:13px; margin:0 0 6px;">' . esc_html($config_toml) . '</pre>' .
			'<p style="margin: 0 0 12px; font-size: 13px; color: #646970;">' .
			__('The password has already been filled in for you. Save the file and you are done.', 'calculated-fields-form') .
			'</p>';

			$output .= '<!-- STEP 3: Verify -->' .
			'<div style="margin-bottom:20px;"><b>' . esc_html__('Step 3 - Check the connection', 'calculated-fields-form') . '</b></div>' .
			'<p style="margin: 0 0 6px;">' . esc_html__('Now type this command in the terminal:', 'calculated-fields-form') .
			'<code>codex mcp list</code></p>' .
			'<p style="margin: 0 0 0; font-size: 13px; color: #646970;">' .
			esc_html__('You must see wordpress-cff-mcp in the list. If it does not appear, repeat the command with the correct application password.', 'calculated-fields-form') .
			'</p>' .
		'</div>';

		$output .='</div><!-- End Agent Steps -->' .
		'</div> <!-- End Container -->' .
		'</div></div>';

		$output .= '<script>
		(function () {
			var field = document.getElementById("cff-application-password");
			var createBtn = document.getElementById("cff-mcp-create-app-password");
			var message = document.getElementById("cff-mcp-create-app-password-message");
			var generatedNote = ' . wp_json_encode( __( 'The password was created and filled in for you. You can copy the command below.', 'calculated-fields-form' ), JSON_HEX_TAG | JSON_HEX_APOS ) . ';
			var genericError = ' . wp_json_encode( __( 'The site could not create the password. Reload the page and try again.', 'calculated-fields-form' ), JSON_HEX_TAG | JSON_HEX_APOS ) . ';
			var timeoutError = ' . wp_json_encode( __( 'The site did not answer in time. Try again.', 'calculated-fields-form' ), JSON_HEX_TAG | JSON_HEX_APOS ) . ';
			var timeout = ' . (int) apply_filters( 'cpcff_mcp_create_password_timeout', 25000 ) . ';
			var blocks = ["cff-opencode-cmd", "cff-opencode-json", "cff-claude-cmd", "cff-mcp-json", "cff-config-toml"];
			var templates = blocks.map(function (id) {
				var pre = document.getElementById(id);
				return pre ? pre.textContent : "";
			});

			if (field) {
				field.addEventListener("focus", function () {
					field.type = "text";
					field.select();
				});
				field.addEventListener("blur", function () {
					field.type = "password";
				});
			}

			function showMessage(text) {
				if (message) { message.textContent = text || ""; }
			}

			function markPasswordExists() {
				var noPassword = document.getElementById("cff-mcp-no-app-password");
				if (noPassword) { noPassword.style.display = "none"; }
				var revoke = document.getElementById("cff-mcp-revoke-note");
				if (revoke) { revoke.style.display = ""; }
			}

			function applyPassword(value) {
				var password = String(value || "").replace(/[^0-9a-z]/ig, "");
				if (!password.length) {
					if (field) { field.focus(); }
					return false;
				}
				blocks.forEach(function (id, index) {
					var pre = document.getElementById(id);
					if (pre) { pre.textContent = templates[index].split("WP_PASSWORD").join(password); }
				});
				if (field) { field.value = password; }
				var steps = document.getElementById("cff-mcp-agent-steps");
				if (steps) {
					steps.style.display = "block";
					steps.scrollIntoView({behavior: "smooth", block: "nearest"});
				}
				return true;
			}

			var applyBtn = document.getElementById("cff-apply-application-password");
			if (applyBtn) {
				applyBtn.addEventListener("click", function () {
					if (applyPassword(field ? field.value : "")) { markPasswordExists(); }
				});
			}

			if (createBtn) {
				createBtn.addEventListener("click", function () {
					createBtn.disabled = true;
					showMessage("");
					var body = new FormData();
					body.append("action", ' . wp_json_encode( 'cpcff_mcp_create_app_password', JSON_HEX_TAG | JSON_HEX_APOS ) . ');
					body.append("nonce", ' . wp_json_encode( wp_create_nonce( 'cpcff_mcp_app_password' ), JSON_HEX_TAG | JSON_HEX_APOS ) . ');
					var controller = new AbortController();
					var timer = setTimeout(function () { controller.abort(); }, timeout);
					fetch(' . wp_json_encode( admin_url( 'admin-ajax.php' ), JSON_HEX_TAG | JSON_HEX_APOS ) . ', {method: "POST", body: body, credentials: "same-origin", signal: controller.signal})
						.then(function (response) { return response.json(); })
						.then(function (json) {
							clearTimeout(timer);
							if (json && json.success) {
								applyPassword(json.data.password);
								var hint = document.getElementById("cff-mcp-password-hint");
								if (hint) { hint.textContent = generatedNote; }
								markPasswordExists();
							} else {
								showMessage((json && json.data && json.data.message) ? json.data.message : genericError);
							}
							createBtn.disabled = false;
						})
						.catch(function (error) {
							clearTimeout(timer);
							showMessage((error && error.name === "AbortError") ? timeoutError : genericError);
							createBtn.disabled = false;
						});
				});
			}

			document.querySelectorAll(\'input[name="cpcff_ai_agent"]\').forEach(function (radio) {
				radio.addEventListener("change", function () {
					document.querySelectorAll(\'.cpcff_ai_agent_opencode, .cpcff_ai_agent_claudecode, .cpcff_ai_agent_codex\').forEach(function (div) {
						div.style.display = "none";
					});
					if(this.value === "opencode") {
						document.querySelector(\'.cpcff_ai_agent_opencode\').style.display = "block";
					} else if(this.value === "claudecode") {
						document.querySelector(\'.cpcff_ai_agent_claudecode\').style.display = "block";
					} else if(this.value === "codex") {
						document.querySelector(\'.cpcff_ai_agent_codex\').style.display = "block";
					}
				});
			});
		})();
		</script>';
		return $output;
	}

	static public function get_route() {
		return function_exists('rest_url') ? rest_url( self::NAMESPACE . self::ROUTE ) : '';
	}

    public function register_routes() {
		if ( function_exists('register_rest_route') ) {
			register_rest_route( self::NAMESPACE, self::ROUTE, array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_sse' ),
				'permission_callback' => array( $this, 'check_permission' ),
			) );

			register_rest_route( self::NAMESPACE, self::ROUTE, array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_request' ),
				'permission_callback' => array( $this, 'check_permission' ),
			) );
		}
    }

    /**
     * Public permission gate for the REST routes. Delegates to authenticate()
     * so the WP REST API returns the WP_Error before the handler runs.
     */
    public function check_permission( WP_REST_Request $request ) {
        $origin = $this->check_origin( $request );
        if ( is_wp_error( $origin ) ) {
            return $origin;
        }
        return $this->authenticate( $request );
    }

    /**
     * Origin validation (DNS rebinding protection).
     *
     * The MCP specification requires servers to validate the Origin header on
     * every incoming connection and to answer 403 when it does not belong to
     * this site. Command line agents do not send Origin at all, so an absent
     * header is always accepted.
     */
    private function check_origin( WP_REST_Request $request ) {
        $origin = $request->get_header( 'Origin' );

        if ( empty( $origin ) ) {
            return true;
        }

        $origin_host = wp_parse_url( $origin, PHP_URL_HOST );
        $allowed     = array();

        // Only the hosts of this site are allowed. The Host header of the
        // request is deliberately NOT part of this list: in a DNS rebinding
        // attack the attacker controls both the page origin and the host the
        // browser resolves to, so accepting the request host would defeat the
        // check (Origin would always equal Host).
        foreach ( array( home_url(), site_url() ) as $site_address ) {
            $host = wp_parse_url( $site_address, PHP_URL_HOST );
            if ( ! empty( $host ) ) {
                $allowed[] = strtolower( $host );
            }
        }

        /**
         * Filters the host names allowed to reach the MCP endpoint.
         *
         * @param string[] $allowed Lowercase host names.
         */
        $allowed = apply_filters( 'cpcff_mcp_allowed_origins', array_filter( array_unique( $allowed ) ) );
        $allowed = array_map( 'strtolower', array_map( 'strval', (array) $allowed ) );

        if ( empty( $origin_host ) || ! in_array( strtolower( $origin_host ), $allowed, true ) ) {
            return new WP_Error( 'rest_forbidden', 'Forbidden origin.', array( 'status' => 403 ) );
        }

        return true;
    }

    /**
     * Authenticate using X-WP-Username / X-WP-App-Password headers.
     * Includes a transient-based rate limit (5 failed attempts per IP per 5 minutes).
     */
    /**
     * Counts one failed authentication per request.
     *
     * The route registers a permission callback and the handlers authenticate
     * again, so the same request can reach authenticate() more than once.
     * Without this guard every failed attempt burned several units of the rate
     * limit and two typos were enough to lock the address out.
     */
    private static $auth_failure_counted = false;

    private function authenticate( WP_REST_Request $request ) {
		// Rate limit by client IP and user name: max 5 failed attempts per
		// combination, per 5 minutes. The user name is part of the key
		// because on a site behind a proxy or a CDN every request arrives
		// with the address of the proxy: keyed by the address alone, five
		// wrong passwords from anyone would block the agents of every user of
		// the site for five minutes.
		$ip       = preg_replace( '/[^0-9a-fA-F:.]/', '', $_SERVER['REMOTE_ADDR'] ?? '' );
		$username = $request->get_header( 'X-WP-Username' );
		$password = $request->get_header( 'X-WP-App-Password' );
		$rate_key = 'cff_mcp_auth_fail_' . md5( $ip . '|' . strtolower( (string) $username ) );
		$failures = (int) get_transient( $rate_key );
		if ( $failures >= 5 ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Too many failed attempts. Wait a few minutes and try again.', 'calculated-fields-form' ),
				array( 'status' => 429 )
			);
		}
        if ( $username && $password ) {
            $user = wp_authenticate_application_password( null, $username, $password );
            if ( is_wp_error( $user ) ) {
                if ( ! self::$auth_failure_counted ) {
                    self::$auth_failure_counted = true;
                    set_transient( $rate_key, $failures + 1, 5 * MINUTE_IN_SECONDS );
                }
                return new WP_Error( 'rest_forbidden', __( 'Unauthorized', 'calculated-fields-form' ), array( 'status' => 401 ) );
            }
            if ( ! $user ) {
                // WordPress returns the input user (null here) when application
                // passwords are not in use on this site, so no credential can be
                // accepted. This is a site configuration state, not a wrong
                // password, so it does not count as a failed attempt.
                return new WP_Error( 'rest_forbidden', __( 'Unauthorized', 'calculated-fields-form' ), array( 'status' => 401 ) );
            }
            wp_set_current_user( $user->ID );
        }

        if ( ! current_user_can( CPCFF_ABILITY_API::get_capability() ) ) {
            return new WP_Error( 'rest_forbidden', __( 'Unauthorized', 'calculated-fields-form' ), array( 'status' => 401 ) );
        }

        // A successful authentication clears the counter: a user who mistypes
        // the password once is not locked out afterwards.
        delete_transient( $rate_key );
        self::$auth_failure_counted = false;

        return true;
    }

    /**
     * Confirms that the permission callback already authenticated the request.
     * The handlers do not authenticate again: that would repeat the credential
     * check and count the same failed attempt several times.
     */
    private function is_authenticated() {
        return current_user_can( CPCFF_ABILITY_API::get_capability() );
    }

    /**
     * GET: opens SSE stream and sends the POST endpoint URL.
     */
    public function handle_sse( WP_REST_Request $request ) {
        if ( ! $this->is_authenticated() ) {
            return new WP_Error( 'rest_forbidden', __( 'Unauthorized', 'calculated-fields-form' ), array( 'status' => 401 ) );
        }

        if ( ob_get_level() ) {
            ob_end_clean();
        }

		if ( ! headers_sent() ) {
			header( 'Content-Type: text/event-stream' );
			header( 'Cache-Control: no-cache' );
			header( 'Connection: keep-alive' );
			header( 'X-Accel-Buffering: no' );
		}

        // The HTTP+SSE transport (protocol 2024-11-05, deprecated since
        // 2025-03-26) expects the endpoint event as the first event of the
        // stream. Streamable HTTP clients do not: they only expect server to
        // client messages, so the event is sent to legacy clients only.
        $header_version = $request->get_header( 'MCP-Protocol-Version' );
        if ( empty( $header_version ) || '2024-11-05' === $header_version ) {
            $endpoint_url = rest_url( self::NAMESPACE . self::ROUTE );
            echo 'event: endpoint' . "\n";
            echo 'data: ' . $endpoint_url . "\n\n";
            flush();
        }

        $timeout = time() + 60;
        while ( time() < $timeout ) {
            if ( connection_aborted() ) {
                break;
            }
            echo ': keep-alive' . "\n\n";
            flush();
            sleep( 15 );
        }

        exit;
    }

    /**
     * POST: handles JSON-RPC 2.0 requests following the MCP protocol.
     */
    public function handle_request( WP_REST_Request $request ) {
        if ( ! $this->is_authenticated() ) {
            return new WP_Error( 'rest_forbidden', __( 'Unauthorized', 'calculated-fields-form' ), array( 'status' => 401 ) );
        }

        $body   = $request->get_json_params();
        $id     = $body['id'] ?? null;
        $method = $body['method'] ?? '';
        $params = $body['params'] ?? array();

        if ( empty( $method ) ) {
            return $this->rpc_error( $id, -32600, 'Invalid Request: missing method' );
        }

        // Clients state the protocol revision in the MCP-Protocol-Version header
        // (MCP 2025-06-18 and later) and, for initialize, in the request body.
        // An unsupported revision is rejected with the list of supported ones.
        $header_version = $request->get_header( 'MCP-Protocol-Version' );
        if ( ! empty( $header_version ) && ! in_array( $header_version, self::SUPPORTED_PROTOCOL_VERSIONS, true ) ) {
            return $this->unsupported_version_response( $id, $header_version );
        }

        switch ( $method ) {
            case 'initialize':
                // Negotiation: answer with the revision the client asked for when
                // it is supported, otherwise with the latest one supported here.
                $requested  = ( is_array( $params ) && ! empty( $params['protocolVersion'] ) ) ? (string) $params['protocolVersion'] : '';
                $negotiated = in_array( $requested, self::SUPPORTED_PROTOCOL_VERSIONS, true ) ? $requested : self::PROTOCOL_VERSION;
                return $this->rpc_result( $id, $this->get_server_info( $negotiated ) );

            case 'notifications/initialized':
                // The MCP specification requires 202 Accepted with no body for
                // JSON-RPC notifications.
                return new WP_REST_Response( null, 202 );

            case 'ping':
                return $this->rpc_result( $id, new stdClass() );

            case 'tools/list':
                return $this->rpc_result( $id, $this->list_tools() );

            case 'tools/call':
                $outcome = $this->call_tool( $params, $id );
                if ( $outcome instanceof WP_REST_Response ) {
                    // A JSON-RPC error must be the top level member of the
                    // response. Nested inside "result" a compliant client
                    // reads it as a successful call.
                    return new WP_REST_Response( $outcome->get_data(), 200 );
                }
                return $this->rpc_result( $id, $outcome );

            default:
                return $this->rpc_error( $id, -32601, 'Method not found: ' . $method );
        }
    }

    /**
     * Returns the server capabilities sent in response to 'initialize'.
     */
    private function get_server_info( $protocol_version = null ) {
        return array(
            'protocolVersion' => $protocol_version ? $protocol_version : self::PROTOCOL_VERSION,
            'capabilities'    => array(
                'tools' => array( 'listChanged' => false ),
            ),
            'serverInfo'      => array(
                'name'    => 'cff-mcp-server',
                'version' => '1.0.0',
            ),
        );
    }

    /**
     * Error response for a protocol revision this server does not implement.
     * The MCP specification requires HTTP 400 with an
     * UnsupportedProtocolVersionError (-32022) listing the supported revisions.
     */
    private function unsupported_version_response( $id, $requested ) {
        return new WP_REST_Response(
            array(
                'jsonrpc' => '2.0',
                'id'      => $id,
                'error'   => array(
                    'code'    => -32022,
                    'message' => 'Unsupported protocol version',
                    'data'    => array(
                        'supported' => self::SUPPORTED_PROTOCOL_VERSIONS,
                        'requested' => $requested,
                    ),
                ),
            ),
            400
        );
    }

    /**
     * Builds a JSON-RPC 2.0 success response.
     */
    private function rpc_result( $id, $result ) {
        return new WP_REST_Response(
            array(
                'jsonrpc' => '2.0',
                'id'      => $id,
                'result'  => $result,
            ),
            200
        );
    }

    /**
     * Builds a JSON-RPC 2.0 error response.
     * Note: JSON-RPC errors always use HTTP 200.
     */
    private function rpc_error( $id, $code, $message ) {
        return new WP_REST_Response(
            array(
                'jsonrpc' => '2.0',
                'id'      => $id,
                'error'   => array(
                    'code'    => $code,
                    'message' => $message,
                ),
            ),
            200
        );
    }

    private function list_tools() {
		$abilities = wp_get_abilities( array( 'category' => CPCFF_ABILITY_API::get_category() ) );
		$tools     = array();

		foreach ( $abilities as $ability_id => $ability ) {
			// Only surface abilities that opt-in to MCP exposure.
			// Source of truth: meta.show_in_mcp on the ability registration.
			$meta = method_exists( $ability, 'get_meta' ) ? (array) $ability->get_meta() : array();
			if ( empty( $meta['show_in_mcp'] ) ) {
				continue;
			}
			$tools[] = array(
				'name'        => $ability_id,
				'description' => (string) $ability->get_description(),
				'inputSchema' => $this->normalize_schema( $ability->get_input_schema() ),
			);
		}

		return array( 'tools' => $tools );
	}

	private function normalize_schema( $schema ) {
		if ( ! is_array( $schema ) ) {
			return array( 'type' => 'object', 'properties' => new stdClass() );
		}

		if ( empty( $schema['type'] ) ) {
			$schema['type'] = 'object';
		}

		// JSON Schema requires 'properties' to be an object {}, never an array [].
		// PHP encodes empty arrays as [], so we force stdClass for empty properties.
		if ( ! isset( $schema['properties'] ) || $schema['properties'] === array() ) {
			$schema['properties'] = new stdClass();
		}

		return $schema;
	}

    private function call_tool( $params, $id = null ) {
		$ability_id = $params['name'] ?? '';
		$input      = $params['arguments'] ?? array();

		if ( is_string( $input ) ) {
			$decoded = json_decode( $input, true );
			if ( is_array( $decoded ) ) {
				$input = $decoded;
			}
		}

		if ( ! is_array( $input ) ) {
			$input = array();
		}

		// Reject unknown abilities up-front with a generic message (no leak of
		// ability IDs that the caller doesn't already know).
		$abilities = function_exists( 'wp_get_abilities' ) ? wp_get_abilities() : array();
		if ( ! isset( $abilities[ $ability_id ] ) ) {
			return $this->rpc_error( $id, -32602, 'Tool not available' );
		}

		// MCP exposure is opt-in via meta.show_in_mcp on the ability registration.
		$ability = $abilities[ $ability_id ];
		$meta    = method_exists( $ability, 'get_meta' ) ? (array) $ability->get_meta() : array();
		if ( empty( $meta['show_in_mcp'] ) ) {
			return $this->rpc_error( $id, -32602, 'Tool not available' );
		}

		$result = null;

		if ( function_exists( 'wp_execute_ability' ) ) {
			// Disponible en futuras versiones de WordPress.
			$result = wp_execute_ability( $ability_id, $input );
		} else if( method_exists( $ability, 'execute' ) ) {
			$result = $ability->execute( $input );
		} elseif ( method_exists( $ability, 'run' ) ) {
			$result = $ability->run( $input );
		} elseif ( method_exists( $ability, 'call' ) ) {
			$result = $ability->call( $input );
		} else {
			return $this->rpc_error( null, -32603, 'Tool not available' );
		}

		if ( is_wp_error( $result ) ) {
			// Log the actual error server-side; surface the code+message to the caller so the agent can iterate.
			error_log( 'CFF MCP call_tool error: ' . $result->get_error_code() . ' - ' . $result->get_error_message() );

			$error_data = $result->get_error_data();
			$body       = array(
				'code'    => $result->get_error_code(),
				'message' => $result->get_error_message(),
				'data'    => is_array( $error_data ) ? $error_data : array(),
			);

			return array(
				'content' => array(
					array(
						'type' => 'text',
						'text' => wp_json_encode( $body ),
					),
				),
				'isError' => true,
			);
		}

		return array(
			'content' => array(
				array(
					'type' => 'text',
					'text' => is_string( $result ) ? $result : json_encode( $result ),
				),
			),
		);
	}
}

new CFF_MCP_SERVER();

endif;