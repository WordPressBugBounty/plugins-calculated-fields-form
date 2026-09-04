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
    const PROTOCOL_VERSION = '2024-11-05';

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
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
					// We cannot read the actual password (stored hashed),
					// so we just confirm one exists and show its name.
					$app_password_hint = $passwords[0]['name'];
				}
			}
		}

		return array(
			'username'          => $username,
			'has_app_passwords' => $has_app_passwords,
			'app_password_hint' => $app_password_hint, // Name of the first app password
			'app_passwords_url' => $app_passwords_url, // Empty if passwords exist
		);
	}

	static public function get_setup_instructions_html() {
        if (! current_user_can(apply_filters('cpcff_forms_edition_capability', 'manage_options'))) {
            return '';
        }
		$auth_info = self::get_auth_info();
		$username = ! empty( $auth_info['username']) ? $auth_info['username'] : 'WP_USERNAME';
		$route_url = self::get_route();

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

		[mcp_servers.wordpress-cff-mcp.headers]
		X-WP-Username = "{$username}"
		X-WP-App-Password = "WP_PASSWORD"
		EOT;

		$output = '<div id="cff-mcp-instructions" class="cff-settings-dialog-modal" style="display:none;text-align:left;"><div class="cff-settings-dialog"  style="padding-bottom:10px;gap:10px;">' .

        '<div class="cff-settings-dialog-header" style="display:flex;gap:10px;padding-bottom:0;align-items:flex-start;">
			<span style="flex-grow:1;text-align:left;">
				<b style="font-size:16px;">' .
        		esc_html__( 'Connecting AI Agent to the WordPress site via MCP', 'calculated-fields-form' ) .
				'</b>
				<div style="margin-top:5px;display:flex;align-items:flex-start;gap:10px;">
					<span>
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
  						<circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.5"/>
  						<polygon points="6,5.5 10.5,8 6,10.5" fill="currentColor"/>
						</svg>
					</span>
					<a href="https://youtu.be/ZF5SoaKVmOg?si=cOH_OTksABk3WJyW" target="_blank" style="text-decoration:none;" title="'. esc_html__('Video tutorial', 'calculated-fields-form') . '">' . esc_html__('Analizing forms submissions', 'calculated-fields-form') . '</a>
				</div>
			</span>
			<a href="javascript:void(0);" onclick="fbuilderjQuery(\'.cff-settings-dialog-modal\').animate({\'opacity\':0}, \'fast\', function(){this.style.display=\'none\';});" title="' . esc_html__('Close', 'calculated-fields-form') . '" class="cff-settings-dialog-close">&times;</a>' .
        '</div>' .

		'<div class="cff-settings-dialog-container" style="overflow-y:auto;padding-top:0;padding-bottom:30px;">'.
		'<p style="padding:10px;border:1px solid #00A32A;">' . __('The instructions below use <strong>OpenCode</strong> as an example, but the same endpoint and credentials work with <strong>Claude Desktop</strong>, <strong>Cursor</strong>, <strong>Windsurf</strong>, <strong>Cline</strong>, and any other MCP-compatible tool, only the configuration file format may differ slightly.', 'calculated-fields-form') . '</p>' .

		'<!-- STEP 1: Application Password -->' .
        '<div style="margin-bottom:20px;"><b>' . esc_html__( 'Step 1 - Create an Application Password', 'calculated-fields-form' ) . '</b></div>';

		if ( $auth_info['has_app_passwords'] ) :

            $output .= wp_kses_post(
				'<p style="margin: 0 0 20px; padding: 8px 12px; background: #edfaef; border-left: 4px solid #00a32a; border-radius: 3px;">' .
				sprintf(
					__( '✅ User <b>%s</b> already has an application password named <b>%s</b>.<br>Use its value for <code>WP_PASSWORD</code> below. If you no longer have it, delete it and create a new one in <a href="%s" target="_blank">your profile → Application Passwords</a>.', 'calculated-fields-form' ),
					esc_html( $auth_info['username'] ),
					esc_html( $auth_info['app_password_hint'] ),
					esc_url( $auth_info['app_passwords_url'] )
				) .
				'</p>'
			);

        else :

			$output .= wp_kses_post(
				'<p style="margin: 0 0 20px; padding: 8px 12px; background: #fcf0f1; border-left: 4px solid #d63638; border-radius: 3px;">' .
				sprintf(
					__( '⚠️ User <b>%s</b> has no application password yet. <a href="%s" target="_blank">Go to your profile → Application Passwords</a>, enter a name (e.g. <i>AI Agent</i>), click <strong>Add New Application Password</strong>, and copy the generated value - it will only be shown once.', 'calculated-fields-form' ),
					esc_html( $auth_info['username'] ),
					esc_url( $auth_info['app_passwords_url'] )
				) .
				'</p>'
			);

        endif;

		$output .= '<div style="display:flex;gap:10px;margin-bottom:20px;"><input type="text" id="cff-application-password" style="flex-grow:1;height:initial;" placeholder="' . esc_html__( 'Enter the application password', 'calculated-fields-form') . '"><button id="cff-apply-application-password" type="button" class="button-secondary">' . esc_html__('Apply', 'calculated-fields-form') . '</button></div>';

		// Radio buttons to select the AI agent
		$output .= '<div class="cff-radio-group-ctrl">
			<label tabindex="0"><input type="radio" name="cpcff_ai_agent" value="opencode" checked><span>OpenCode</span></label>
			<label tabindex="0"><input type="radio" name="cpcff_ai_agent" value="claudecode"><span>Claude Code</span></label>
			<label tabindex="0"><input type="radio" name="cpcff_ai_agent" value="codex"><span>Codex</span></label>
		</div>';

		//  OpenCode
        $output .=
		'<div class="cpcff_ai_agent_opencode">
			<!-- STEP 2: opencode.json -->
        	<div style="margin-top:20px;margin-bottom:20px;"><b>' . __( 'Step 2 - Create the <code>opencode.json</code> file', 'calculated-fields-form' ) . '</b></div>
        	<p style="margin: 0 0 6px;">'.
        		__( 'Create a file named <code>opencode.json</code> inside your project root folder (affects only that project):', 'calculated-fields-form' ) .
			'</p>
			<code style="background:#f0f0f1; padding:2px 6px; border-radius:3px;">your-project/.opencode/opencode.json</code>' .

        	'<div style="margin-bottom:10px;display:flex;gap:10px;align-items:end;"><span style="flex-grow:1;">' . esc_html__( 'Copy the following content into the file:', 'calculated-fields-form' ) . '</span><button type="button" onclick="navigator.clipboard.writeText(document.getElementById(\'cff-opencode-json\').textContent);alert(\'' . esc_html__( 'Copied !!!', 'calculated-fields-form' ) . '\');" class="button-secondary">' . esc_html__('Copy to clipboard', 'calculated-fields-form') . '</button></div>' .

        	'<pre id="cff-opencode-json" style="background:#1e1e1e; color:#d4d4d4; padding:12px 16px; border-radius:5px; overflow-x:auto; font-size:13px; margin:0 0 6px;">' . esc_html( $opencode_json ) . '</pre>' .
        	'<p style="margin: 0 0 12px; font-size: 13px; color: #646970;">' .
        	__( 'Replace the <code>WP_PASSWORD</code> placeholders with the Application Password copied previously.', 'calculated-fields-form' ) .
        	'</p>';

			$output .= '<!-- STEP 3: Verify -->' .
        	'<div style="margin-bottom:20px;"><b>'. esc_html__( 'Step 3 - Verify the connection', 'calculated-fields-form' ) . '</b></div>' .
        	'<p style="margin: 0 0 6px;">' .
        	esc_html__( 'Launch OpenCode inside your project folder. You can verify the MCP server is reachable by running "List tools" command', 'calculated-fields-form' ) .
        	'</p>' .
        	'<p style="margin: 0 0 0; font-size: 13px; color: #646970;">' .
        	esc_html__( 'A successful response lists all available tools in JSON format. If you see an error, double-check your application password.', 'calculated-fields-form' ) .
        	'</p>' .
		'</div>';

		// Claude Code
		$output .=
				'<div class="cpcff_ai_agent_claudecode" style="display:none;">
			<!-- STEP 2: .mcp.json -->
        	<div style="margin-top:20px;margin-bottom:20px;"><b>' . __('Step 2 - Create the <code>.mcp.json</code> file', 'calculated-fields-form') . '</b></div>
        	<p style="margin: 0 0 6px;">' .
				__('Create a file named <code>.mcp.json</code> inside your project root folder (affects only that project):', 'calculated-fields-form') .
			'</p>
			<code style="background:#f0f0f1; padding:2px 6px; border-radius:3px;">your-project/.mcp.json</code>' .

			'<div style="margin-bottom:10px;display:flex;gap:10px;align-items:end;"><span style="flex-grow:1;">' . esc_html__('Copy the following content into the file:', 'calculated-fields-form') . '</span><button type="button" onclick="navigator.clipboard.writeText(document.getElementById(\'cff-mcp-json\').textContent);alert(\'' . esc_html__('Copied !!!', 'calculated-fields-form') . '\');" class="button-secondary">' . esc_html__('Copy to clipboard', 'calculated-fields-form') . '</button></div>' .

			'<pre id="cff-mcp-json" style="background:#1e1e1e; color:#d4d4d4; padding:12px 16px; border-radius:5px; overflow-x:auto; font-size:13px; margin:0 0 6px;">' . esc_html($mcp_json) . '</pre>' .
			'<p style="margin: 0 0 12px; font-size: 13px; color: #646970;">' .
			__('Replace the <code>WP_PASSWORD</code> placeholders with the Application Password copied previously.', 'calculated-fields-form') .
			'</p>';

			$output .= '<!-- STEP 3: Verify -->' .
			'<div style="margin-bottom:20px;"><b>' . esc_html__('Step 3 - Verify the connection', 'calculated-fields-form') . '</b></div>' .
			'<p style="margin: 0 0 6px;">' .
			esc_html__('Launch Claude Code inside your project folder. You can verify the MCP server is reachable by running "/mcp" command', 'calculated-fields-form') .
			'</p>' .
			'<p style="margin: 0 0 0; font-size: 13px; color: #646970;">' .
			esc_html__('There, you’ll see the status (Connected, Pending approval, Failed to connect) and the number of exposed tools. If you see an error, double-check your application password.', 'calculated-fields-form') .
			'</p>' .
		'</div>';

		// Codex
		$output .=
		'<div class="cpcff_ai_agent_codex" style="display:none;">
			<!-- STEP 2: config.toml -->
        	<div style="margin-top:20px;margin-bottom:20px;"><b>' . __('Step 2 - Create the <code>config.toml</code> file', 'calculated-fields-form') . '</b></div>
        	<p style="margin: 0 0 6px;">' .
				__('Create a file named <code>config.toml</code> inside your project root folder (affects only that project):', 'calculated-fields-form') .
			'</p>
			<code style="background:#f0f0f1; padding:2px 6px; border-radius:3px;">your-project/.codex/config.toml</code>' .

			'<div style="margin-bottom:10px;display:flex;gap:10px;align-items:end;"><span style="flex-grow:1;">' . esc_html__('Copy the following content into the file:', 'calculated-fields-form') . '</span><button type="button" onclick="navigator.clipboard.writeText(document.getElementById(\'cff-config-toml\').textContent);alert(\'' . esc_html__('Copied !!!', 'calculated-fields-form') . '\');" class="button-secondary">' . esc_html__('Copy to clipboard', 'calculated-fields-form') . '</button></div>' .

			'<pre id="cff-config-toml" style="background:#1e1e1e; color:#d4d4d4; padding:12px 16px; border-radius:5px; overflow-x:auto; font-size:13px; margin:0 0 6px;">' . esc_html($config_toml) . '</pre>' .
			'<p style="margin: 0 0 12px; font-size: 13px; color: #646970;">' .
			__('Replace the <code>WP_PASSWORD</code> placeholders with the Application Password copied previously.', 'calculated-fields-form') .
			'</p>';

			$output .= '<!-- STEP 3: Verify -->' .
			'<div style="margin-bottom:20px;"><b>' . esc_html__('Step 3 - Verify the connection', 'calculated-fields-form') . '</b></div>' .
			'<p style="margin: 0 0 6px;">' .
			esc_html__('Execute the following batch into the project folder:', 'calculated-fields-form') .
			'<code>codex mcp list</code></p>' .
		'</div>';

		$output .='</div> <!-- End Container -->' .
		'</div></div>';

		$output .= '<script>
		document.getElementById("cff-apply-application-password").addEventListener("click", function () {
			const password = String(document.getElementById("cff-application-password").value).replace(/[^0-9a-z]/ig, "");
			if(password.length) {
				["cff-opencode-json", "cff-mcp-json", "cff-config-toml"].forEach(function(id) {
					let pre = document.getElementById(id);
					console.log(pre.textContent);
					pre.textContent = pre.textContent.replace(
						/"X-WP-App-Password"\s*:\s*"[^\"]*"/,
						`"X-WP-App-Password": "${password}"`
					).replace(
						/X-WP-App-Password\s*=\s*"[^"]*"/,
						`X-WP-App-Password = "${password}"`
					);
				});
			}
		});
		document.querySelectorAll(\'input[name="cpcff_ai_agent"]\').forEach(function(radio) {
			radio.addEventListener("change", function() {
				document.querySelectorAll(\'.cpcff_ai_agent_opencode, .cpcff_ai_agent_claudecode, .cpcff_ai_agent_codex\').forEach(function(div) {
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
		</script>';

		return $output;
	}

	static public function get_route() {
		return function_exists('rest_url') ? rest_url( self::NAMESPACE . self::ROUTE ) : '';
	}

    public function register_routes() {
        // GET: SSE connection endpoint
		if ( function_exists('register_rest_route') ) {
			register_rest_route( self::NAMESPACE, self::ROUTE, array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_sse' ),
				'permission_callback' => array( $this, 'check_permission' ),
			) );

			// POST: JSON-RPC 2.0 handler
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
        return $this->authenticate( $request );
    }

    /**
     * Authenticate using X-WP-Username / X-WP-App-Password headers.
     * Includes a transient-based rate limit (5 failed attempts per IP per 5 minutes).
     */
    private function authenticate( WP_REST_Request $request ) {
		// Rate limit by client IP: max 5 failed auth attempts per 5 minutes.
		$ip     = preg_replace( '/[^0-9a-fA-F:.]/', '', $_SERVER['REMOTE_ADDR'] ?? '' );
        $rate_key = 'cff_mcp_auth_fail_' . md5( $ip );
        $failures  = (int) get_transient( $rate_key );
        if ( $failures >= 5 ) {
            return new WP_Error( 'rest_forbidden', 'Unauthorized', array( 'status' => 429 ) );
        }

        $username = $request->get_header( 'X-WP-Username' );
        $password = $request->get_header( 'X-WP-App-Password' );

        if ( $username && $password ) {
            $user = wp_authenticate_application_password( null, $username, $password );
            if ( is_wp_error( $user ) ) {
                set_transient( $rate_key, $failures + 1, 5 * MINUTE_IN_SECONDS );
                return new WP_Error( 'rest_forbidden', 'Unauthorized', array( 'status' => 401 ) );
            }
            wp_set_current_user( $user->ID );
        }

        if ( ! current_user_can( CPCFF_ABILITY_API::get_capability() ) ) {
            return new WP_Error( 'rest_forbidden', 'Unauthorized', array( 'status' => 401 ) );
        }

        return true;
    }

    /**
     * GET: opens SSE stream and sends the POST endpoint URL.
     */
    public function handle_sse( WP_REST_Request $request ) {
        $auth = $this->authenticate( $request );
        if ( is_wp_error( $auth ) ) {
            return $auth;
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

        $endpoint_url = rest_url( self::NAMESPACE . self::ROUTE );
        echo 'event: endpoint' . "\n";
        echo 'data: ' . $endpoint_url . "\n\n";
        flush();

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
        $auth = $this->authenticate( $request );
        if ( is_wp_error( $auth ) ) {
            return $auth;
        }

        $body   = $request->get_json_params();
        $id     = $body['id'] ?? null;
        $method = $body['method'] ?? '';
        $params = $body['params'] ?? array();

        if ( empty( $method ) ) {
            return $this->rpc_error( $id, -32600, 'Invalid Request: missing method' );
        }

        switch ( $method ) {
            case 'initialize':
                return $this->rpc_result( $id, $this->get_server_info() );

            case 'notifications/initialized':
                // Notification: no response body needed.
                return new WP_REST_Response( null, 204 );

            case 'ping':
                return $this->rpc_result( $id, new stdClass() );

            case 'tools/list':
                return $this->rpc_result( $id, $this->list_tools() );

            case 'tools/call':
                return $this->rpc_result( $id, $this->call_tool( $params ) );

            default:
                return $this->rpc_error( $id, -32601, 'Method not found: ' . $method );
        }
    }

    /**
     * Returns the server capabilities sent in response to 'initialize'.
     */
    private function get_server_info() {
        return array(
            'protocolVersion' => self::PROTOCOL_VERSION,
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

    private function call_tool( $params ) {
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
			return $this->rpc_error( null, -32602, 'Tool not available' );
		}

		// MCP exposure is opt-in via meta.show_in_mcp on the ability registration.
		$ability = $abilities[ $ability_id ];
		$meta    = method_exists( $ability, 'get_meta' ) ? (array) $ability->get_meta() : array();
		if ( empty( $meta['show_in_mcp'] ) ) {
			return $this->rpc_error( null, -32602, 'Tool not available' );
		}

		// wp_execute_ability() no existe aún en WordPress core.
		// Obtenemos el objeto ability y lo ejecutamos directamente.
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
			// Log the actual error server-side; return a generic message to the caller.
			error_log( 'CFF MCP call_tool error: ' . $result->get_error_code() . ' - ' . $result->get_error_message() );
			return array(
				'content' => array(
					array(
						'type' => 'text',
						'text' => 'Internal error',
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