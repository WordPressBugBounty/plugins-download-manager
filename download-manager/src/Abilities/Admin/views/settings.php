<?php
/**
 * AI Abilities settings tab.
 */

use WPDM\Abilities\Abilities;
use WPDM\Abilities\Admin\SettingsTab;

if ( ! defined( 'ABSPATH' ) ) {
	die( '!' );
}

$wpdm_abl_supported = Abilities::supported();
$wpdm_abl_adapter   = SettingsTab::mcpAdapterVersion();
$wpdm_abl_enabled   = Abilities::enabledGroups();
$wpdm_abl_endpoint  = rest_url( 'mcp/mcp-adapter-default-server' );
$wpdm_abl_config    = wp_json_encode(
	[
		'mcpServers' => [
			'wpdm' => [
				'command' => 'npx',
				'args'    => [ '-y', '@automattic/mcp-wordpress-remote@latest' ],
				'env'     => [
					'WP_API_URL'      => $wpdm_abl_endpoint,
					'WP_API_USERNAME' => 'agent-user',
					'WP_API_PASSWORD' => 'xxxx xxxx xxxx xxxx xxxx xxxx',
				],
			],
		],
	],
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);
$wpdm_abl_claude = sprintf(
	'claude mcp add wpdm -e WP_API_URL=%s -e WP_API_USERNAME=agent-user -e WP_API_PASSWORD="xxxx xxxx xxxx xxxx xxxx xxxx" -- npx -y @automattic/mcp-wordpress-remote@latest',
	$wpdm_abl_endpoint
);
?>
<style>
	.wpdm-abl-badge, .wpdm-abl-status {
		display: inline-block;
		padding: 2px 8px;
		border-radius: 999px;
		font-size: 11px;
		font-weight: 600;
		line-height: 18px;
		white-space: nowrap;
	}
	.wpdm-abl-badge--read, .wpdm-abl-status--ok { background: #ecfdf5; color: #047857; }
	.wpdm-abl-badge--write { background: #eef2ff; color: #4338ca; }
	.wpdm-abl-badge--destructive, .wpdm-abl-status--denied { background: #fef2f2; color: #b91c1c; }
	.wpdm-abl-status--error { background: #fffbeb; color: #b45309; }
	.wpdm-abl-group { padding: 16px 0; border-top: 1px solid #e2e8f0; }
	.wpdm-abl-group:first-child { border-top: 0; padding-top: 0; }
	.wpdm-abl-group label.wpdm-abl-toggle { display: flex; gap: 10px; align-items: flex-start; font-weight: 600; margin: 0; cursor: pointer; }
	.wpdm-abl-group label.wpdm-abl-toggle input { margin-top: 3px; }
	.wpdm-abl-group p { margin: 4px 0 8px 24px; color: #64748b; }
	.wpdm-abl-abilities { margin: 0 0 0 24px; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 6px; }
	.wpdm-abl-abilities li { display: flex; gap: 8px; align-items: center; }
	.wpdm-abl-abilities code { font-size: 12px; }
	.wpdm-abl-pre { position: relative; }
	.wpdm-abl-pre pre { background: #0f172a; color: #e2e8f0; padding: 14px 76px 14px 16px; border-radius: 8px; font-size: 12px; white-space: pre-wrap; word-break: break-all; margin: 0; }
	.wpdm-abl-pre .btn { position: absolute; top: 8px; right: 8px; }
	.wpdm-abl-log-filters { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
	.wpdm-abl-log-filters select { width: auto; min-width: 160px; }
	#wpdm-abl-log td { vertical-align: middle; font-size: 12px; }
</style>

<?php if ( ! $wpdm_abl_supported ) : ?>
	<div class="panel panel-default">
		<div class="panel-body">
			<div class="alert alert-info" style="margin: 0">
				<?php
				printf(
					/* translators: %s: current WordPress version */
					esc_html__( 'AI abilities need WordPress 6.9 or later. This site runs WordPress %s.', 'download-manager' ),
					esc_html( get_bloginfo( 'version' ) )
				);
				?>
			</div>
		</div>
	</div>
	<?php return; ?>
<?php endif; ?>

<div class="panel panel-default">
	<div class="panel-heading"><?php esc_html_e( 'Status', 'download-manager' ); ?></div>
	<div class="panel-body">
		<table class="table" style="margin: 0">
			<tr>
				<td style="width: 220px"><?php esc_html_e( 'Abilities API', 'download-manager' ); ?></td>
				<td>
					<span class="wpdm-abl-status wpdm-abl-status--ok"><?php esc_html_e( 'Available', 'download-manager' ); ?></span>
					<?php
					/* translators: %s: WordPress version */
					printf( esc_html__( 'WordPress %s', 'download-manager' ), esc_html( get_bloginfo( 'version' ) ) );
					?>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'MCP Adapter', 'download-manager' ); ?></td>
				<td>
					<?php if ( $wpdm_abl_adapter ) : ?>
						<span class="wpdm-abl-status wpdm-abl-status--ok"><?php esc_html_e( 'Active', 'download-manager' ); ?></span>
						<?php
						/* translators: %s: MCP Adapter version */
						printf( esc_html__( 'Version %s', 'download-manager' ), esc_html( $wpdm_abl_adapter ) );
						?>
					<?php else : ?>
						<span class="wpdm-abl-status wpdm-abl-status--error"><?php esc_html_e( 'Not active', 'download-manager' ); ?></span>
						<?php esc_html_e( 'Agents connect through the MCP Adapter plugin.', 'download-manager' ); ?>
						<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=mcp-adapter&tab=search&type=term' ) ); ?>"><?php esc_html_e( 'Install it', 'download-manager' ); ?></a>
					<?php endif; ?>
				</td>
			</tr>
		</table>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading"><?php esc_html_e( 'Ability groups', 'download-manager' ); ?></div>
	<div class="panel-body">
		<p class="text-muted">
			<?php esc_html_e( 'An enabled group can be called by AI agents and through the REST API. Every call still runs as the signed-in WordPress user, with that user\'s permissions.', 'download-manager' ); ?>
		</p>
		<?php foreach ( Abilities::groups() as $wpdm_abl_slug => $wpdm_abl_group ) : ?>
			<div class="wpdm-abl-group">
				<label class="wpdm-abl-toggle">
					<input type="checkbox" name="__wpdm_abilities_groups[]" value="<?php echo esc_attr( $wpdm_abl_slug ); ?>" <?php checked( in_array( $wpdm_abl_slug, $wpdm_abl_enabled, true ) ); ?> />
					<?php echo esc_html( $wpdm_abl_group['label'] ); ?>
				</label>
				<p><?php echo esc_html( $wpdm_abl_group['description'] ); ?></p>
				<ul class="wpdm-abl-abilities">
					<?php foreach ( SettingsTab::abilitiesIn( $wpdm_abl_slug ) as $wpdm_abl_ability ) : ?>
						<?php list( $wpdm_abl_kind, $wpdm_abl_kind_label ) = SettingsTab::badge( $wpdm_abl_ability ); ?>
						<li>
							<span class="wpdm-abl-badge wpdm-abl-badge--<?php echo esc_attr( $wpdm_abl_kind ); ?>"><?php echo esc_html( $wpdm_abl_kind_label ); ?></span>
							<code><?php echo esc_html( $wpdm_abl_ability->get_name() ); ?></code>
							<span class="text-muted"><?php echo esc_html( $wpdm_abl_ability->get_label() ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading"><?php esc_html_e( 'Limits', 'download-manager' ); ?></div>
	<div class="panel-body">
		<div class="row">
			<?php foreach ( SettingsTab::limitLabels() as $wpdm_abl_key => $wpdm_abl_label ) : ?>
				<div class="col-md-6 form-group">
					<label for="wpdm-abl-limit-<?php echo esc_attr( $wpdm_abl_key ); ?>"><?php echo esc_html( $wpdm_abl_label ); ?></label>
					<input type="number" class="form-control" min="1" max="<?php echo (int) SettingsTab::LIMIT_RANGES[ $wpdm_abl_key ]; ?>"
						id="wpdm-abl-limit-<?php echo esc_attr( $wpdm_abl_key ); ?>"
						name="__wpdm_abilities_limits[<?php echo esc_attr( $wpdm_abl_key ); ?>]"
						value="<?php echo (int) Abilities::limit( $wpdm_abl_key ); ?>" />
				</div>
			<?php endforeach; ?>
		</div>
		<p class="text-muted" style="margin: 0"><?php esc_html_e( 'A call over a limit is refused with a rate-limit error, and the agent can retry later.', 'download-manager' ); ?></p>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading"><?php esc_html_e( 'Connect an agent', 'download-manager' ); ?></div>
	<div class="panel-body">
		<ol style="padding-left: 18px; margin-top: 0">
			<li><?php esc_html_e( 'Create a separate WordPress user for the agent, with only the role it needs. Don\'t connect agents as an administrator.', 'download-manager' ); ?></li>
			<li>
				<?php esc_html_e( 'Signed in as that user, create an application password.', 'download-manager' ); ?>
				<a href="<?php echo esc_url( admin_url( 'profile.php#application-passwords-section' ) ); ?>"><?php esc_html_e( 'Application passwords', 'download-manager' ); ?></a>
			</li>
			<li><?php esc_html_e( 'Add the server to your AI client, replacing the username and password placeholders.', 'download-manager' ); ?></li>
		</ol>

		<div class="form-group">
			<label><?php esc_html_e( 'MCP endpoint', 'download-manager' ); ?></label>
			<input type="text" class="form-control" readonly value="<?php echo esc_attr( $wpdm_abl_endpoint ); ?>" />
		</div>

		<div class="form-group">
			<label><?php esc_html_e( 'Claude Desktop, Cursor or VS Code config', 'download-manager' ); ?></label>
			<div class="wpdm-abl-pre">
				<pre id="wpdm-abl-config"><?php echo esc_html( $wpdm_abl_config ); ?></pre>
				<button type="button" class="btn btn-secondary btn-xs wpdm-abl-copy" data-target="wpdm-abl-config"><?php esc_html_e( 'Copy', 'download-manager' ); ?></button>
			</div>
		</div>

		<div class="form-group" style="margin-bottom: 0">
			<label><?php esc_html_e( 'Claude Code command', 'download-manager' ); ?></label>
			<div class="wpdm-abl-pre">
				<pre id="wpdm-abl-claude"><?php echo esc_html( $wpdm_abl_claude ); ?></pre>
				<button type="button" class="btn btn-secondary btn-xs wpdm-abl-copy" data-target="wpdm-abl-claude"><?php esc_html_e( 'Copy', 'download-manager' ); ?></button>
			</div>
		</div>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading">
		<?php esc_html_e( 'Recent activity', 'download-manager' ); ?>
	</div>
	<div class="panel-body">
		<div class="wpdm-abl-log-filters" style="margin-bottom: 12px">
			<select class="form-control input-sm" id="wpdm-abl-filter-status">
				<option value=""><?php esc_html_e( 'All results', 'download-manager' ); ?></option>
				<option value="ok"><?php esc_html_e( 'OK', 'download-manager' ); ?></option>
				<option value="error"><?php esc_html_e( 'Error', 'download-manager' ); ?></option>
				<option value="denied"><?php esc_html_e( 'Denied', 'download-manager' ); ?></option>
			</select>
			<select class="form-control input-sm" id="wpdm-abl-filter-ability">
				<option value=""><?php esc_html_e( 'All abilities', 'download-manager' ); ?></option>
				<?php foreach ( Abilities::groups() as $wpdm_abl_slug => $wpdm_abl_group ) : ?>
					<?php foreach ( SettingsTab::abilitiesIn( $wpdm_abl_slug ) as $wpdm_abl_ability ) : ?>
						<option value="<?php echo esc_attr( $wpdm_abl_ability->get_name() ); ?>"><?php echo esc_html( $wpdm_abl_ability->get_name() ); ?></option>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</select>
			<button type="button" class="btn btn-danger btn-sm" id="wpdm-abl-clear" style="margin-left: auto"><?php esc_html_e( 'Clear log', 'download-manager' ); ?></button>
		</div>
		<div class="table-responsive">
			<table class="table table-striped" style="margin: 0">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'download-manager' ); ?></th>
						<th><?php esc_html_e( 'User', 'download-manager' ); ?></th>
						<th><?php esc_html_e( 'Ability', 'download-manager' ); ?></th>
						<th><?php esc_html_e( 'Object', 'download-manager' ); ?></th>
						<th><?php esc_html_e( 'Result', 'download-manager' ); ?></th>
						<th><?php esc_html_e( 'Source', 'download-manager' ); ?></th>
					</tr>
				</thead>
				<tbody id="wpdm-abl-log"><?php echo SettingsTab::logRows(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in logRows(). ?></tbody>
			</table>
		</div>
		<p class="text-muted" style="margin: 10px 0 0"><?php esc_html_e( 'Shows the latest 50 calls. Entries older than 90 days are deleted automatically.', 'download-manager' ); ?></p>
	</div>
</div>

<script>
	(function ($) {
		var nonce = '<?php echo esc_js( wp_create_nonce( 'wpdm_ability_log' ) ); ?>';

		function reload() {
			$.get(ajaxurl, {
				action: 'wpdm_ability_log',
				_nonce: nonce,
				status: $('#wpdm-abl-filter-status').val(),
				ability: $('#wpdm-abl-filter-ability').val()
			}, function (res) {
				if (res && res.success) {
					$('#wpdm-abl-log').html(res.data.html);
				}
			});
		}

		$('#wpdm-abl-filter-status, #wpdm-abl-filter-ability').off('change.wpdmabl').on('change.wpdmabl', reload);

		$('#wpdm-abl-clear').off('click.wpdmabl').on('click.wpdmabl', function () {
			var $btn = $(this).prop('disabled', true);
			$.post(ajaxurl, { action: 'wpdm_ability_log_clear', _nonce: nonce }, function (res) {
				if (res && res.success) {
					$('#wpdm-abl-log').html(res.data.html);
				}
				$btn.prop('disabled', false);
			});
		});

		$('.wpdm-abl-copy').off('click.wpdmabl').on('click.wpdmabl', function () {
			var $btn = $(this), text = $('#' + $btn.data('target')).text(), label = $btn.text();
			var done = function () {
				$btn.text('<?php echo esc_js( __( 'Copied', 'download-manager' ) ); ?>');
				setTimeout(function () { $btn.text(label); }, 1500);
			};
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(text).then(done);
			} else {
				var $tmp = $('<textarea>').val(text).appendTo('body').select();
				document.execCommand('copy');
				$tmp.remove();
				done();
			}
		});
	})(jQuery);
</script>
