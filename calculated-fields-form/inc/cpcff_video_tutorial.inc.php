<?php
if ( !is_admin() )
{
	print 'Direct access not allowed.';
    exit;
}
?>
<a href="javascript:void(0);" onclick="<?php print esc_attr( wp_is_mobile() ? "window.open('https://youtu.be/R8hEbD8w2RM', '_blank');" : "fbuilderjQuery('#cff-video-tutorial-modal').css({'opacity':0,'display':'block'}).animate({'opacity':1}, 'fast');"); ?>" class="button-secondary"><?php esc_html_e( 'Video Tutorial', 'calculated-fields-form' ); ?></a>
<div id="cff-video-tutorial-modal" style="display:<?php print esc_attr( get_transient( 'cff-video-tutorial' ) ? 'block' : 'none' ); ?>;">
	<div id="cff-video-tutorial">
		<div class="cff-video-tutorial-header"><a href="javascript:void(0);" onclick="fbuilderjQuery('#cff-video-tutorial-modal').animate({'opacity':0}, 'fast', function(){this.style.display='none';});" title="<?php esc_attr_e( 'Close video popup', 'calculated-fields-form'); ?>" class="cff-video-tutorial-close">X</a></div>
		<div class="cff-video-tutorial-container">
			<iframe src="https://www.youtube.com/embed/R8hEbD8w2RM" title="Calculated Fields Form Plugin - How it works?" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen=""></iframe>
		</div>
		<div class="cff-video-tutorial-other-videos">
			<div class="cff-video-tutorial-thumbnail"><a href="https://youtu.be/R8hEbD8w2RM" target="_blank"><img src="<?php print esc_attr( plugins_url('../images/th-video-0.png', __FILE__) ); ?>" alt="<?php esc_attr_e('Basic contact form', 'calculated-fields-form'); ?>"></a><div class="cff-video-tutorial-thumbnail-title"><?php esc_html_e('Basic Form', 'calculated-fields-form'); ?></div></div>
			<div class="cff-video-tutorial-thumbnail"><a href="https://youtu.be/NSac2cAN8RE" target="_blank"><img src="<?php print esc_attr( plugins_url('../images/th-video-1.png', __FILE__) ); ?>" alt="<?php esc_attr_e('Fields in columns', 'calculated-fields-form'); ?>"></a><div class="cff-video-tutorial-thumbnail-title"><?php esc_html_e('Columns', 'calculated-fields-form'); ?></div></div>
			<div class="cff-video-tutorial-thumbnail"><a href="https://youtu.be/acZC-fVh3y8" target="_blank"><img src="<?php print esc_attr( plugins_url('../images/th-video-2.png', __FILE__) ); ?>" alt="<?php esc_attr_e('Personalize form design', 'calculated-fields-form'); ?>"></a><div class="cff-video-tutorial-thumbnail-title"><?php esc_html_e('Design', 'calculated-fields-form'); ?></div></div>
			<div class="cff-video-tutorial-thumbnail" style="border: 2px dashed purple;"><a href="https://youtu.be/X3nByJtaXzA" target="_blank"><img src="<?php print esc_attr( plugins_url('../images/th-video-5.png', __FILE__) ); ?>" alt="<?php esc_attr_e('WooCommerce Integration', 'calculated-fields-form'); ?>"></a><div class="cff-video-tutorial-thumbnail-title"><a href="https://cff.dwbooster.com/download" target="_blank"><?php esc_html_e('WooCommerce', 'calculated-fields-form'); ?></a></div></div>
			<div class="cff-video-tutorial-thumbnail" style="border: 2px dashed purple;"><a href="https://youtu.be/TXLMB3_w-Xg" target="_blank"><img src="<?php print esc_attr( plugins_url('../images/th-video-6.png', __FILE__) ); ?>" alt="<?php esc_attr_e('PDF Generator', 'calculated-fields-form'); ?>"></a><div class="cff-video-tutorial-thumbnail-title"><?php esc_html_e('PDF/Invoice', 'calculated-fields-form'); ?></div></div>
			<div class="cff-video-tutorial-thumbnail"><a href="https://youtu.be/FzltD0AFU6Y" target="_blank"><img src="<?php print esc_attr( plugins_url('../images/th-video-3.png', __FILE__) ); ?>" alt="<?php esc_attr_e('Advanced calculator', 'calculated-fields-form'); ?>"></a><div class="cff-video-tutorial-thumbnail-title"><?php esc_html_e('Financial', 'calculated-fields-form'); ?></div></div>
			<div class="cff-video-tutorial-thumbnail"><a href="https://youtu.be/s4FM59LC-H4" target="_blank"><img src="<?php print esc_attr( plugins_url('../images/th-video-4.png', __FILE__) ); ?>" alt="<?php esc_attr_e('Fields dependency', 'calculated-fields-form'); ?>"></a><div class="cff-video-tutorial-thumbnail-title"><?php esc_html_e('Dependencies', 'calculated-fields-form'); ?></div></div>
		</div>
	</div>
</div>
<?php
delete_transient( 'cff-video-tutorial' );
?>