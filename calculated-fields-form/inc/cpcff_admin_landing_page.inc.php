<?php
if ( !is_admin() )
{
	print 'Direct access not allowed.';
    exit;
}
?>
<style>
.notice{display:none;}
.cff-landing-page table td{width:50%;}
.cff-landing-page a{box-shadow:none !important;outline:none !important;}
.cff-main-button,
.cff-secondary-button{text-transform: capitalize;width: 260px;display: inline-block;text-decoration: none !important;color: white !important;height: 46px;line-height: 46px;font-size: 18px;border-radius: 5px;background: #2271b1;box-shadow:none !important;outline:none !important;}
.cff-secondary-button{background: white;border: 1px solid #b2b5ba;color: #454647 !important}
.cff-main-button:hover{background: #135e96;}
.cff-secondary-button:hover{background: #f0f2f5;}
.cff-why-upgrade{color:white;}
@media screen AND (min-width:710px) {
	.cff-lading-page-advanced-columns{display:flex;flex-direction:row;flex-wrap:wrap;gap:20px;}
	.cff-lading-page-advanced-col1{max-width:35%;}
	.cff-lading-page-advanced-col2{max-width:35%;}
	.cff-lading-page-advanced-col3{padding-top:30px;flex-grow:1;}
	.cff-lading-page-feature-col1{max-width:50%;}
	.cff-lading-page-feature-col2{max-width:50%;}
}

.cff-lading-page-advanced-columns ul {
  list-style-type: none;
}

.cff-lading-page-advanced-columns ul li {
  position: relative;
  margin-bottom:15px;
}

.cff-lading-page-advanced-columns ul li::before {
  content: "\2713";
  display: inline-block;
  color: yellow;
  margin-right:10px;
}
</style>
<div class="wrap" style="margin: 0 auto;max-width: 720px;padding-top:100px;">
	<div class="postbox cff-landing-page">
		<div class="inside" style="padding:0;">
			<div style="display:inline-block;position:absolute;left:calc(50% - 50px);top:-50px;border-radius:50%;overflow:hidden;width:100px;height:100px;background-size:contain !important;border:3px solid white;box-shadow: 0px 0px 3px rgba(0, 0, 0, .5);background:url('data:image/png;base64,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');"></div>

			<div style="padding:70px 20px 20px 20px;">
				<h2 style="text-align:center;"><?php
					esc_html_e( 'Welcome to the Calculated Fields Form', 'calculated-fields-form' );
				?></h2>
				<p style="margin-top:0;margin-bottom:20px;text-align:center;font-size:16px;"><?php esc_html_e( 'An easy and powerful form builder, perfect for novice developers and experts alike.', 'calculated-fields-form' ); ?></p>
			</div>
			<div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; background: white;">
			  <iframe
				style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
				src="https://www.youtube.com/embed/R8hEbD8w2RM"
				title="Build a Contact Form with the Calculated Fields Form"
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
				referrerpolicy="strict-origin-when-cross-origin"
				allowfullscreen>
			  </iframe>
			</div>
			<div style="padding:20px;">
				<div class="cff-video-tutorial-other-videos">
					<div class="cff-video-tutorial-thumbnail"><a href="https://youtu.be/R8hEbD8w2RM" target="_blank"><img src="<?php print esc_attr( plugins_url('../images/th-video-0.png', __FILE__) ); ?>" alt="<?php esc_attr_e('Basic contact form', 'calculated-fields-form'); ?>"></a><div class="cff-video-tutorial-thumbnail-title"><?php esc_html_e('Basic Form', 'calculated-fields-form'); ?></div></div>
					<div class="cff-video-tutorial-thumbnail"><a href="https://youtu.be/NSac2cAN8RE" target="_blank"><img src="<?php print esc_attr( plugins_url('../images/th-video-1.png', __FILE__) ); ?>" alt="<?php esc_attr_e('Fields in columns', 'calculated-fields-form'); ?>"></a><div class="cff-video-tutorial-thumbnail-title"><?php esc_html_e('Columns', 'calculated-fields-form'); ?></div></div>
					<div class="cff-video-tutorial-thumbnail"><a href="https://youtu.be/acZC-fVh3y8" target="_blank"><img src="<?php print esc_attr( plugins_url('../images/th-video-2.png', __FILE__) ); ?>" alt="<?php esc_attr_e('Personalize form design', 'calculated-fields-form'); ?>"></a><div class="cff-video-tutorial-thumbnail-title"><?php esc_html_e('Design', 'calculated-fields-form'); ?></div></div>
					<div class="cff-video-tutorial-thumbnail"><a href="https://youtu.be/X3nByJtaXzA" target="_blank"><img src="<?php print esc_attr( plugins_url('../images/th-video-5.png', __FILE__) ); ?>" alt="<?php esc_attr_e('WooCommerce Integration', 'calculated-fields-form'); ?>"></a><div class="cff-video-tutorial-thumbnail-title"><a href="https://cff.dwbooster.com/download" target="_blank"><?php esc_html_e('WooCommerce', 'calculated-fields-form'); ?></a></div></div>
					<div class="cff-video-tutorial-thumbnail"><a href="https://youtu.be/TXLMB3_w-Xg" target="_blank"><img src="<?php print esc_attr( plugins_url('../images/th-video-6.png', __FILE__) ); ?>" alt="<?php esc_attr_e('PDF Generator', 'calculated-fields-form'); ?>"></a><div class="cff-video-tutorial-thumbnail-title"><?php esc_html_e('PDF/Invoice', 'calculated-fields-form'); ?></div></div>
					<div class="cff-video-tutorial-thumbnail"><a href="https://youtu.be/FzltD0AFU6Y" target="_blank"><img src="<?php print esc_attr( plugins_url('../images/th-video-3.png', __FILE__) ); ?>" alt="<?php esc_attr_e('Advanced calculator', 'calculated-fields-form'); ?>"></a><div class="cff-video-tutorial-thumbnail-title"><?php esc_html_e('Financial', 'calculated-fields-form'); ?></div></div>
					<div class="cff-video-tutorial-thumbnail"><a href="https://youtu.be/s4FM59LC-H4" target="_blank"><img src="<?php print esc_attr( plugins_url('../images/th-video-4.png', __FILE__) ); ?>" alt="<?php esc_attr_e('Fields dependency', 'calculated-fields-form'); ?>"></a><div class="cff-video-tutorial-thumbnail-title"><?php esc_html_e('Dependencies', 'calculated-fields-form'); ?></div></div>
				</div>
				<p style="text-align:center;font-size:16px;"><?php esc_html_e( 'The "Calculated Fields Form" plugin creates dynamic, interactive forms with built-in calculations and logic, enabling advanced functionality.', 'calculated-fields-form' ); ?></p>
				<table border="0" style="width:100%;">
					<tr style="border:0;">
						<td style="border:0;text-align:center;">
							<a href="admin.php?page=cp_calculated_fields_form_sub_new&from_landing_page=1" class="cff-main-button"><?php esc_html_e( 'Create your first form', 'calculated-fields-form' ); ?></a>
						</td>
						<td style="border:0;text-align:center;">
							<a href="https://youtube.com/playlist?list=PLY-AOoHciOKgZQsqWfkQlHJ21sm3qPF9X&si=kUytVX7aF69GhKD" target="_blank" class="cff-secondary-button"><?php esc_html_e( 'More video tutorials', 'calculated-fields-form' ); ?></a>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>

	<div class="postbox cff-landing-page">
		<div class="inside" style="padding:0;">
			<div style="padding:20px;margin-bottom:20px;">
				<h2 style="text-align:center;"><?php
					esc_html_e( 'Calculated Fields Form Features', 'calculated-fields-form' );
				?></h2>
				<table border="0" style="width:100%;margin-top:20px;">
					<tr style="border:0;">
						<td style="border:0;">
							<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-forms"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a3 3 0 0 0 -3 3v12a3 3 0 0 0 3 3" /><path d="M6 3a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3" /><path d="M13 7h7a1 1 0 0 1 1 1v8a1 1 0 0 1 -1 1h-7" /><path d="M5 7h-1a1 1 0 0 0 -1 1v8a1 1 0 0 0 1 1h1" /><path d="M17 12h.01" /><path d="M13 12h.01" /></svg>
							<b>Drag & Drop Form Builder</b><br>
							<span>Build stunning forms effortlessly with drag-drop.</span>
						</td>
						<td style="border:0;">
							<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-file-type-css"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" /><path d="M8 16.5a1.5 1.5 0 0 0 -3 0v3a1.5 1.5 0 0 0 3 0" /><path d="M11 20.25c0 .414 .336 .75 .75 .75h1.25a1 1 0 0 0 1 -1v-1a1 1 0 0 0 -1 -1h-1a1 1 0 0 1 -1 -1v-1a1 1 0 0 1 1 -1h1.25a.75 .75 0 0 1 .75 .75" /><path d="M17 20.25c0 .414 .336 .75 .75 .75h1.25a1 1 0 0 0 1 -1v-1a1 1 0 0 0 -1 -1h-1a1 1 0 0 1 -1 -1v-1a1 1 0 0 1 1 -1h1.25a.75 .75 0 0 1 .75 .75" /></svg>
							<b>Predefined Designs</b><br>
							<span>Transform your project instantly using curated, professional designs.</span>
						</td>
					</tr>
					<tr style="border:0;">
						<td style="border:0;">
							<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-template"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4m0 1a1 1 0 0 1 1 -1h14a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-14a1 1 0 0 1 -1 -1z" /><path d="M4 12m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M14 12l6 0" /><path d="M14 16l6 0" /><path d="M14 20l6 0" /></svg>
							<b>Form Templates</b><br>
							<span>Instant projects with versatile form templates for every industry.</span>
						</td>
						<td style="border:0;">
							<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-stack-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20.894 15.553a1 1 0 0 1 -.447 1.341l-8 4a1 1 0 0 1 -.894 0l-8 -4a1 1 0 0 1 .894 -1.788l7.553 3.774l7.554 -3.775a1 1 0 0 1 1.341 .447m0 -4a1 1 0 0 1 -.447 1.341l-8 4a1 1 0 0 1 -.894 0l-8 -4a1 1 0 0 1 .894 -1.788l7.552 3.775l7.554 -3.775a1 1 0 0 1 1.341 .447m-8.887 -8.552q .056 0 .111 .007l.111 .02l.086 .024l.012 .006l.012 .002l.029 .014l.05 .019l.016 .009l.012 .005l8 4a1 1 0 0 1 0 1.788l-8 4a1 1 0 0 1 -.894 0l-8 -4a1 1 0 0 1 0 -1.788l8 -4l.011 -.005l.018 -.01l.078 -.032l.011 -.002l.013 -.006l.086 -.024l.11 -.02l.056 -.005z" /></svg>
							<b>Conditional Fields</b><br>
							<span>Dynamically display relevant fields based on input.</span>
						</td>
					</tr>
					<tr style="border:0;">
						<td style="border:0;">
							<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-math-function"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 19a2 2 0 0 0 2 2c2 0 2 -4 3 -9s1 -9 3 -9a2 2 0 0 1 2 2" /><path d="M5 12h6" /><path d="M15 12l6 6" /><path d="M15 18l6 -6" /></svg>
							<b>Advanced Formula Editor</b><br>
							<span>Build and edit complex formulas visually with error checking and syntax highlighting.</span>
						</td>
						<td style="border:0;">
							<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-mail-forward"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 18h-7a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v7.5" /><path d="M3 6l9 6l9 -6" /><path d="M15 18h6" /><path d="M18 15l3 3l-3 3" /></svg>
							<b>Emails Notification</b><br>
							<span>Email notifications deliver form submission data promptly.</span>
						</td>
					</tr>
				</table>
				<div style="text-align:center;margin-top:30px;">
					<a href="https://cff.dwbooster.com/download#comparison" target="_blank" class="cff-secondary-button"><?php esc_html_e( 'Features List', 'calculated-fields-form' ); ?></a>
				</div>
			</div>
			<div style="padding:20px;background:#000000;" class="cff-why-upgrade">
				<h2 style="text-align:left;color:white;">
				<?php
					esc_html_e( 'Why Upgrade?', 'calculated-fields-form' );
				?>
				<span style="font-size:90%;font-weight:300;">
				<?php
					esc_html_e( '(One-time purchase, lifetime access plugin updates)', 'calculated-fields-form' );
				?>
				</span>
				</h2>
				<div class="cff-lading-page-advanced-columns">
					<ul class="cff-lading-page-advanced-col1">
						<li><b><?php esc_html_e( 'Advanced Controls', 'calculated-fields-form' ); ?></b>: <?php esc_html_e( 'Populate form fields with external data sources (e.g., databases, Google Sheets, etc.)', 'calculated-fields-form' ); ?></li>
						<li><b><?php esc_html_e( 'Advanced Operations', 'calculated-fields-form' ); ?></b>: <?php esc_html_e( 'Distance calculation, financial operations, chart drawing, and others.', 'calculated-fields-form' ); ?></li>
						<li><b><?php esc_html_e( 'Payment Forms', 'calculated-fields-form' ); ?></b>: <?php esc_html_e( 'Multiple payment gateways integration.', 'calculated-fields-form' ); ?></li>
						<li><b><?php esc_html_e( 'Entry Management', 'calculated-fields-form' ); ?></b>: <?php esc_html_e( 'Analysis, editing, and CSV export.', 'calculated-fields-form' ); ?></li>
					</ul>
					<ul class="cff-lading-page-advanced-col2">
						<li><b><?php esc_html_e( 'Invoice Generation', 'calculated-fields-form' ); ?></b>: <?php esc_html_e( 'PDF files.', 'calculated-fields-form' ); ?></li>
						<li><b><?php esc_html_e( 'Ecommerce Integration', 'calculated-fields-form' ); ?></b>: <?php esc_html_e( 'Calculate WooCommerce and Easy Digital Downloads products prices.', 'calculated-fields-form' ); ?></li>
						<li><b><?php esc_html_e( 'Third-Party Plugins Integration', 'calculated-fields-form' ); ?></b>: <?php esc_html_e( 'Supports AffiliateWP, Emma, The Events Calendar, MailPoet, and more.', 'calculated-fields-form' ); ?></li>
						<li><b><?php esc_html_e( 'TThird-Party Services Integration', 'calculated-fields-form' ); ?></b>: <?php esc_html_e( 'Zapier, Dropbox, Google Places, Google Analytics, MailChimp, and many others.', 'calculated-fields-form' ); ?></li>
					</ul>
					<div class="cff-lading-page-advanced-col3">
						<div style="text-align:center;"><span style="font-size:22px;display:inline-block;border-bottom:1px solid rgba(255,255,255,0.3);padding:0 10px; 10px; 10px;">PRO</span></div>
						<div style="font-size:32px;text-align:center;margin-top:20px;">€49.99</div>
						<div style="font-size:14px;text-align:center;color:rgba(255,255,255,.5);"><?php esc_html_e( 'LIFETIME', 'calculated-fields-form' ); ?></div>
						<div style="margin-top:30px;text-align:center;"><a href="https://cff.dwbooster.com/download" target="_blank" class="cff-secondary-button" style="width:auto;padding-left:20px;padding-right:20px;"><?php esc_html_e( 'UPGRADE!', 'calculated-fields-form' ); ?></a></div>
					</div>
				</div>
			</div>
			<table border="0" style="width:100%;margin-top:20px;">
				<tr style="border:0;">
					<td style="border:0;text-align:center;">
						<a href="admin.php?page=cp_calculated_fields_form_sub_new&from_landing_page=1" class="cff-main-button"><?php esc_html_e( 'Create your first form', 'calculated-fields-form' ); ?></a>
					</td>
					<td style="border:0;text-align:center;">
						<a href="https://cff.dwbooster.com/download" target="_blank" style="font-size:16px;"><?php esc_html_e( 'Upgrade your plugin copy', 'calculated-fields-form' ); ?></a>
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>
<?php
delete_transient( 'cff-video-tutorial' );