<?php
if (!is_admin()) {
	print 'Direct access not allowed.';
	exit;
}
if (CPCFF_ABILITY_API::are_abilities_available()) :
?>
	<a onclick="fbuilderjQuery('#cff-mcp-instructions').css('opacity', 0).show().animate({'opacity':1}, 'fast');" class="button-secondary"><?php esc_html_e('Using AI Agents', 'calculated-fields-form'); ?></a>
<?php
	print CFF_MCP_SERVER::get_setup_instructions_html();
endif;
?>