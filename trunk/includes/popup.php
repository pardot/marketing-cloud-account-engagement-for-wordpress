<?php
/**
 * HTML for the Pardot shortcode builder form.
 *
 * @since 1.4.4
 *
 * @var string $pardot_settings_url The wp-admin URL to the Pardot settings page.
 * @var string $spinner_url The URL to "spinner" gif image.
 */
?>
<div id="pardot-forms-shortcode-insert-dialog">

	<form method="post" action="#">

		<section class="pardot-forms-modal-fields">

			<!-- "Forms" Section -->
			<h2><?php esc_html_e( 'Forms', 'pardot' ); ?></h2>

			<table>
				<tbody>

					<!-- Selection for "Forms" -->
					<tr><td><label for="shortcode"><?php esc_html_e( 'Select a form to insert:', 'pardot' ); ?></label></td></tr>

					<tr>
						<td>
							<span id="pardot-forms-shortcode-select">
								<input type="hidden" id="shortcode">
								<img class="spinner" src="<?php echo esc_url( $spinner_url ); ?>" height="16" weight="16" alt="<?php esc_attr_e( 'Time waits for no man.', 'pardot' ); ?>">
							</span>
						</td>
					</tr>

					<!-- Optional iframe Parameters -->
					<tr><td><h4><?php esc_html_e( 'Optional iframe Parameters', 'pardot' ); ?></h4></td></tr>
					<tr><td><small><?php esc_html_e( 'Height and width should be in digits only (i.e. 250).', 'pardot' ); ?></small></td></tr>

					<tr>
						<td>
							<label for="formh"><?php esc_html_e( 'Height:', 'pardot' )?></label> <input type="text" size="6" id="formh" name="formh">
							<label for="formw"><?php esc_html_e( 'Width:', 'pardot' )?></label> <input type="text" size="6" id="formw" name="formw">
						</td>
					</tr>

					<tr>
						<td colspan="2">
							<label for="formc"><?php esc_html_e( 'Class:', 'pardot' ); ?></label> <input type="text" id="formc" name="formc" class="medium-text">
						</td>
					</tr>

				</tbody>
			</table>

			<!-- "Dynamic Content" Section -->
			<h2><?php esc_html_e( 'Dynamic Content', 'pardot' ); ?></h2>

			<table>
				<tbody>

					<!-- Selection for "Dynamic Content" -->
					<tr><td><label for="shortcode-dc"><?php esc_html_e( 'Select dynamic content to insert:', 'pardot' ); ?></label></td></tr>

					<tr>
						<td>
							<span id="pardot-dc-shortcode-select">
								<input type="hidden" id="shortcodedc">
								<img class="spinner" src="<?php echo esc_url( $spinner_url ); ?>" height="16" weight="16" alt="<?php esc_attr_e( 'Time waits for no man.', 'pardot' ); ?>">
							</span>
						</td>
					</tr>

					<!-- Optional iframe Parameters -->
					<tr><td><h4><?php esc_html_e( 'Optional iframe Parameters', 'pardot' ); ?></h4></td></tr>
					<tr><td><small><?php esc_html_e( 'Height and width should be in px or % (i.e. 250px or 90%).', 'pardot' ); ?></small></td></tr>

					<tr>
						<td>
							<label for="dch"><?php esc_html_e( 'Height:', 'pardot' )?></label> <input type="text" size="6" id="dch" name="dch">
							<label for="dcw"><?php esc_html_e( 'Width:', 'pardot' )?></label> <input type="text" size="6" id="dcw" name="dcw">
						</td>
					</tr>

					<tr>
						<td colspan="2">
							<label for="dcc"><?php esc_html_e( 'Class:', 'pardot' ); ?></label> <input type="text" id="dcc" name="dcc" class="medium-text">
						</td>
					</tr>
				
				</tbody>
			</table>

		</section><!-- .pardot-forms-modal-fields -->

		<section class="pardot-forms-modal-actions">
			<input type="submit" id="pardot-forms-modal-insert" name="insert" value="<?php esc_attr_e( 'Insert', 'pardot' ); ?>" class="button-primary">

			<!-- If you're reading this, you're getting a sneak peek of a future relase. Well done! -->
			<!--<span class="reload-button"><input type="submit" id="reload" name="reload" value="Reload" class="updateButton" onclick="return refresh_cache();" /></span>-->

			<input type="submit" id="pardot-forms-modal-cancel" name="cancel" value="<?php esc_attr_e( 'Cancel', 'pardot' ); ?>" class="button-secondary">
		</section><!-- .pardot-forms-modal-actions -->

		<section id="pardot-forms-cache-notice">
			<small><?php printf( __( '<strong>Not seeing something you added recently in Pardot?</strong> Please click the Clear Cache button on the <a href="%s" target="_parent">Pardot Settings Page</a>.', 'pardot' ), esc_url( $pardot_settings_url ) ); ?></small>
		</section>
	</form>
</div>