<?php
/**
 * Admin settings page display
 *
 * @link       https://wordpress.org/plugins/wp-post-sync-translate
 * @since      1.0.0
 *
 * @package    Wp_Post_Sync_Translate
 * @subpackage Wp_Post_Sync_Translate/admin/partials
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}

require_once plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'includes/class-wp-post-sync-translate-settings.php';

$mode    = Wp_Post_Sync_Translate_Settings::get_mode();
$targets = Wp_Post_Sync_Translate_Settings::get_targets();
?>

<div class="wrap">
	<h1><?php echo esc_html( 'WP Post Sync & Translate' ); ?></h1>

	<div class="wpst-container">
		<form id="wpst-settings-form" method="post" action="">
			<?php wp_nonce_field( 'wp-post-sync-translate', 'wpst_nonce' ); ?>

			<h2><?php echo esc_html( 'Configuration Mode' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row"><?php echo esc_html( 'Mode' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="mode" value="host" <?php checked( $mode, 'host' ); ?> />
								<?php echo esc_html( 'Host (Push posts to targets)' ); ?>
							</label>
							<br />
							<label>
								<input type="radio" name="mode" value="target" <?php checked( $mode, 'target' ); ?> />
								<?php echo esc_html( 'Target (Receive and translate posts)' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
			</table>

			<!-- Host Settings -->
			<div id="host-settings" style="<?php echo 'host' === $mode ? '' : 'display:none;'; ?>">
				<h2><?php echo esc_html( 'Target Sites' ); ?></h2>

				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th><?php echo esc_html( 'URL' ); ?></th>
							<th><?php echo esc_html( 'Key' ); ?></th>
							<th><?php echo esc_html( 'Action' ); ?></th>
						</tr>
					</thead>
					<tbody id="targets-list">
						<?php
						if ( ! empty( $targets ) ) {
							foreach ( $targets as $target ) {
								?>
								<tr data-url="<?php echo esc_attr( $target['url'] ); ?>">
									<td><?php echo esc_html( $target['url'] ); ?></td>
									<td>
										<code><?php echo esc_html( substr( $target['key'], 0, 8 ) . '...' ); ?></code>
										<button type="button" class="button copy-key" data-key="<?php echo esc_attr( $target['key'] ); ?>" title="Copy key">
											<?php echo esc_html( 'Copy' ); ?>
										</button>
									</td>
									<td>
										<button type="button" class="button button-link-delete remove-target" data-url="<?php echo esc_attr( $target['url'] ); ?>">
											<?php echo esc_html( 'Remove' ); ?>
										</button>
									</td>
								</tr>
								<?php
							}
						}
						?>
					</tbody>
				</table>

				<div style="margin-top: 20px;">
					<h3><?php echo esc_html( 'Add New Target' ); ?></h3>
					<input type="url" id="new-target-url" placeholder="<?php echo esc_attr( 'https://target-site.com' ); ?>" style="width: 300px; padding: 5px;" />
					<button type="button" class="button button-primary" id="add-target-btn">
						<?php echo esc_html( 'Add Target' ); ?>
					</button>
					<span id="add-target-message" style="margin-left: 10px;"></span>
				</div>
			</div>

			<!-- Target Settings -->
			<div id="target-settings" style="<?php echo 'target' === $mode ? '' : 'display:none;'; ?>">
				<h2><?php echo esc_html( 'Target Configuration' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="target_key"><?php echo esc_html( 'Host Key' ); ?></label>
						</th>
						<td>
							<input type="text" id="target_key" name="target_key" value="<?php echo esc_attr( Wp_Post_Sync_Translate_Settings::get_target_key() ); ?>" class="regular-text" placeholder="<?php echo esc_attr( 'Paste the key from the Host site' ); ?>" />
							<p class="description"><?php echo esc_html( 'Paste the key generated on the Host site.' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="language"><?php echo esc_html( 'Translation Language' ); ?></label>
						</th>
						<td>
							<select id="language" name="language">
								<option value="fr" <?php selected( Wp_Post_Sync_Translate_Settings::get_target_language(), 'fr' ); ?>>
									<?php echo esc_html( 'French' ); ?>
								</option>
								<option value="es" <?php selected( Wp_Post_Sync_Translate_Settings::get_target_language(), 'es' ); ?>>
									<?php echo esc_html( 'Spanish' ); ?>
								</option>
								<option value="hi" <?php selected( Wp_Post_Sync_Translate_Settings::get_target_language(), 'hi' ); ?>>
									<?php echo esc_html( 'Hindi' ); ?>
								</option>
							</select>
							<p class="description"><?php echo esc_html( 'Select the language to translate posts into.' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="chatgpt_key"><?php echo esc_html( 'ChatGPT API Key' ); ?></label>
						</th>
						<td>
							<input type="password" id="chatgpt_key" name="chatgpt_key" value="<?php echo esc_attr( Wp_Post_Sync_Translate_Settings::get_chatgpt_key() ); ?>" class="regular-text" placeholder="<?php echo esc_attr( 'sk-...' ); ?>" />
							<p class="description"><?php echo esc_html( 'Your OpenAI API key for translations. Keep this secret!' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<p class="submit">
				<button type="button" class="button button-primary" id="save-settings-btn">
					<?php echo esc_html( 'Save Settings' ); ?>
				</button>
				<span id="save-message" style="margin-left: 10px;"></span>
			</p>
		</form>
	</div>

	<!-- Logs Section -->
	<div style="margin-top: 40px;">
		<h2><?php echo esc_html( 'Sync Logs' ); ?></h2>

		<?php
		require_once plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'includes/class-wp-post-sync-translate-database.php';

		$logs = Wp_Post_Sync_Translate_Database::get_logs(
			array(
				'limit'  => 50,
				'offset' => 0,
			)
		);
		?>

		<?php if ( ! empty( $logs ) ) : ?>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php echo esc_html( 'Time' ); ?></th>
						<th><?php echo esc_html( 'Action' ); ?></th>
						<th><?php echo esc_html( 'Status' ); ?></th>
						<th><?php echo esc_html( 'Host Post ID' ); ?></th>
						<th><?php echo esc_html( 'Target Post ID' ); ?></th>
						<th><?php echo esc_html( 'Target URL' ); ?></th>
						<th><?php echo esc_html( 'Duration' ); ?></th>
						<th><?php echo esc_html( 'Message' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $logs as $log ) {
						?>
						<tr>
							<td><?php echo esc_html( $log->created_at ); ?></td>
							<td><?php echo esc_html( $log->action ); ?></td>
							<td>
								<span class="status-<?php echo esc_attr( $log->status ); ?>">
									<?php echo esc_html( ucfirst( $log->status ) ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $log->host_post_id ); ?></td>
							<td><?php echo esc_html( $log->target_post_id ?? '-' ); ?></td>
							<td><?php echo esc_html( $log->target_site_url ); ?></td>
							<td><?php echo esc_html( $log->duration_ms . 'ms' ); ?></td>
							<td><?php echo esc_html( substr( $log->message, 0, 50 ) ); ?></td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php echo esc_html( 'No logs yet.' ); ?></p>
		<?php endif; ?>
	</div>
</div>