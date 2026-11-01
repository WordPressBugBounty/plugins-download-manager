<?php
/**
 * Cron Jobs Settings Tab
 *
 * Admin UI for managing WPDM cron jobs.
 *
 * @package WPDM\Admin
 * @since 7.0.1
 */

if (!defined('ABSPATH')) exit;

use WPDM\__\CronJob;
use WPDM\__\CronJobs;

$counts = CronJob::getCounts();
$jobs = CronJob::getAll(['limit' => 50, 'orderby' => 'created_at', 'order' => 'DESC']);
$scheduledEvents = CronJobs::getScheduledEvents();
$cronUrl = CronJobs::getCronUrl();
$isWpCronDisabled = CronJobs::isWpCronDisabled();
?>

<style>
.wpdm-cron-stats {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}
.wpdm-cron-stat {
    flex: 1;
    text-align: center;
    padding: 15px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}
.wpdm-cron-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}
.wpdm-cron-stat-value.text-warning { color: #f59e0b; }
.wpdm-cron-stat-value.text-info { color: #3b82f6; }
.wpdm-cron-stat-value.text-success { color: #10b981; }
.wpdm-cron-stat-value.text-danger { color: #ef4444; }
.wpdm-cron-stat-label {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    margin-top: 4px;
}

/* Recent jobs */
.w3eden .wpdm-jobs-table { margin: 0; }
.w3eden .wpdm-jobs-table th {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #64748b;
    white-space: nowrap;
}
.w3eden .wpdm-jobs-table > tbody > tr > td { vertical-align: middle; }
.w3eden .wpdm-jobs-table tbody tr:hover { background: #f8fafc; }
.wpdm-job-id { color: #94a3b8; font-variant-numeric: tabular-nums; }
.wpdm-job-name { font-weight: 600; color: #1e293b; }
.wpdm-job-meta { margin-top: 2px; font-size: 12px; color: #64748b; }
.wpdm-job-meta--error {
    max-width: 340px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #dc2626;
}
.wpdm-job-queue {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    background: #f1f5f9;
    color: #475569;
    font-size: 12px;
    white-space: nowrap;
}
.wpdm-job-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
}
.wpdm-job-status::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}
.wpdm-job-status--pending { background: #fef3c7; color: #b45309; }
.wpdm-job-status--running { background: #dbeafe; color: #1d4ed8; }
.wpdm-job-status--running::before { animation: wpdm-job-pulse 1s ease-in-out infinite; }
.wpdm-job-status--completed { background: #d1fae5; color: #047857; }
.wpdm-job-status--failed { background: #fee2e2; color: #b91c1c; }
.wpdm-job-status--cancelled { background: #f1f5f9; color: #64748b; }
@keyframes wpdm-job-pulse { 50% { opacity: .3; } }
.wpdm-job-attempts { color: #64748b; white-space: nowrap; font-variant-numeric: tabular-nums; }
.wpdm-job-when { white-space: nowrap; }
.wpdm-job-overdue { display: block; font-size: 12px; color: #b45309; }
.wpdm-job-actions { display: flex; justify-content: flex-end; gap: 6px; }
.w3eden .wpdm-job-btn {
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    color: #64748b;
    font-size: 13px;
    line-height: 1;
    cursor: pointer;
    transition: all 150ms ease;
}
.w3eden .wpdm-job-btn:focus-visible { outline: 2px solid #6366f1; outline-offset: 1px; }
.w3eden .wpdm-job-btn:disabled { opacity: .6; cursor: wait; }
.w3eden .wpdm-job-btn--run:hover { background: #ecfdf5; border-color: #6ee7b7; color: #059669; }
.w3eden .wpdm-job-btn--cancel:hover { background: #fffbeb; border-color: #fcd34d; color: #d97706; }
.w3eden .wpdm-job-btn--retry:hover { background: #eff6ff; border-color: #93c5fd; color: #2563eb; }
.w3eden .wpdm-job-btn--delete:hover { background: #fef2f2; border-color: #fca5a5; color: #dc2626; }
</style>

<!-- Stats -->
<div class="wpdm-cron-stats">
    <div class="wpdm-cron-stat">
        <div class="wpdm-cron-stat-value"><?php echo esc_html($counts['total']); ?></div>
        <div class="wpdm-cron-stat-label"><?php esc_html_e('Total Jobs', 'download-manager'); ?></div>
    </div>
    <div class="wpdm-cron-stat">
        <div class="wpdm-cron-stat-value text-warning"><?php echo esc_html($counts[CronJob::STATUS_PENDING]); ?></div>
        <div class="wpdm-cron-stat-label"><?php esc_html_e('Pending', 'download-manager'); ?></div>
    </div>
    <div class="wpdm-cron-stat">
        <div class="wpdm-cron-stat-value text-info"><?php echo esc_html($counts[CronJob::STATUS_RUNNING]); ?></div>
        <div class="wpdm-cron-stat-label"><?php esc_html_e('Running', 'download-manager'); ?></div>
    </div>
    <div class="wpdm-cron-stat">
        <div class="wpdm-cron-stat-value text-success"><?php echo esc_html($counts[CronJob::STATUS_COMPLETED]); ?></div>
        <div class="wpdm-cron-stat-label"><?php esc_html_e('Completed', 'download-manager'); ?></div>
    </div>
    <div class="wpdm-cron-stat">
        <div class="wpdm-cron-stat-value text-danger"><?php echo esc_html($counts[CronJob::STATUS_FAILED]); ?></div>
        <div class="wpdm-cron-stat-label"><?php esc_html_e('Failed', 'download-manager'); ?></div>
    </div>
</div>

<?php if ($isWpCronDisabled): ?>
<!-- WP Cron Disabled Warning -->
<div class="alert alert-warning">
    <strong><?php esc_html_e('WordPress Cron is Disabled', 'download-manager'); ?></strong><br>
    <?php esc_html_e('DISABLE_WP_CRON is set to true. You need to set up a real cron job to process the job queue. Use the URL below with a cron service or server cron.', 'download-manager'); ?>
</div>
<?php endif; ?>

<!-- External Cron URL -->
<div class="panel panel-default">
    <div class="panel-heading"><?php esc_html_e('External Cron URL', 'download-manager'); ?></div>
    <div class="panel-body">
        <p class="text-muted" style="margin-bottom: 10px;">
            <?php esc_html_e('Runs everything WP cron runs for Download Manager: the job queue and the 6-hourly cleanup of cache and expired sessions. Add it to your server crontab or a service like EasyCron, every 5 to 15 minutes, if WP cron is disabled or your site gets little traffic.', 'download-manager'); ?>
        </p>
        <div class="form-group">
            <label for="wpdmckey"><?php esc_html_e('Cron Key', 'download-manager'); ?></label>
            <input type="text" class="form-control" name="__wpdm_cron_key" id="wpdmckey" value="<?php echo esc_attr(WPDM()->cronJob->cronKey()); ?>" />
            <em class="note"><?php esc_html_e('Changing the key changes the URL. Update your cron job after saving.', 'download-manager'); ?></em>
        </div>
        <div class="input-group">
            <input type="text" id="wpdm-cron-url" value="<?php echo esc_attr($cronUrl); ?>" class="form-control" readonly>
            <div class="input-group-btn">
                <button onclick="WPDM.copy('wpdm-cron-url')" type="button" class="btn btn-secondary ttip" title="Copy">
                    <i class="fa fa-copy"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scheduled Events -->
<div class="panel panel-default">
    <div class="panel-heading"><?php esc_html_e('Scheduled WordPress Cron Events', 'download-manager'); ?></div>
    <table class="table table-striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Hook', 'download-manager'); ?></th>
                <th><?php esc_html_e('Description', 'download-manager'); ?></th>
                <th><?php esc_html_e('Next Run', 'download-manager'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($scheduledEvents as $hook => $event): ?>
            <tr>
                <td><code><?php echo esc_html($hook); ?></code></td>
                <td><?php echo esc_html($event['description']); ?></td>
                <td>
                    <?php if ($event['next_run']): ?>
                        <?php echo esc_html(wp_date('M j, Y H:i:s', $event['next_run'])); ?>
                    <?php else: ?>
                        <span class="text-danger"><?php esc_html_e('Not scheduled', 'download-manager'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Actions -->
<p>
    <button type="button" class="btn btn-success" id="wpdm-run-cron">
        <i class="fa fa-play"></i> <?php esc_html_e('Run Job Queue Now', 'download-manager'); ?>
    </button>
</p>

<!-- Jobs Table -->
<div class="panel panel-default">
    <div class="panel-heading"><?php esc_html_e('Recent Jobs', 'download-manager'); ?></div>
    <?php if (empty($jobs)): ?>
    <div class="panel-body" style="text-align: center; padding: 40px;">
        <img src="<?php echo WPDM_ASSET_URL; ?>/images/no-job.png" style="width: 128px; padding: 20px;" /><br/>
        <p class="lead"><?php esc_html_e('No jobs in the queue yet.', 'download-manager'); ?></p>
    </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
    <table class="table wpdm-jobs-table">
        <thead>
            <tr>
                <th><?php esc_html_e('ID', 'download-manager'); ?></th>
                <th><?php esc_html_e('Job', 'download-manager'); ?></th>
                <th><?php esc_html_e('Queue', 'download-manager'); ?></th>
                <th><?php esc_html_e('Status', 'download-manager'); ?></th>
                <th><?php esc_html_e('Attempts', 'download-manager'); ?></th>
                <th><?php esc_html_e('Execute At', 'download-manager'); ?></th>
                <th><span class="sr-only"><?php esc_html_e('Actions', 'download-manager'); ?></span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jobs as $job):
                $status = $job->status ?? CronJob::STATUS_PENDING;
                $typeParts = explode('\\', $job->type);
                $jobName = end($typeParts);
                $jobDescription = '';
                $jobClass = $job->type;
                if (class_exists($jobClass) && is_subclass_of($jobClass, \WPDM\__\Jobs\Job::class)) {
                    $jobName = $jobClass::getName();
                    $jobDescription = $jobClass::getDescription();
                }
                $interval = (int) ($job->interval_seconds ?? 0);
                $overdue = $status === CronJob::STATUS_PENDING && $job->execute_at && $job->execute_at < time();
                $actions = [];
                if ($status === CronJob::STATUS_PENDING) {
                    $actions['run_now'] = ['run', 'fa-play', __('Run now', 'download-manager')];
                    $actions['cancel'] = ['cancel', 'fa-ban', __('Cancel', 'download-manager')];
                } elseif ($status === CronJob::STATUS_FAILED) {
                    $actions['retry'] = ['retry', 'fa-rotate-right', __('Retry', 'download-manager')];
                }
                $actions['delete'] = ['delete', 'fa-trash-can', __('Delete', 'download-manager')];
            ?>
            <tr id="job-<?php echo esc_attr($job->ID); ?>">
                <td class="wpdm-job-id">#<?php echo esc_html($job->ID); ?></td>
                <td>
                    <div class="wpdm-job-name" title="<?php echo esc_attr($job->type); ?>"><?php echo esc_html($jobName); ?></div>
                    <?php if ($status === CronJob::STATUS_FAILED && !empty($job->error_message)): ?>
                    <div class="wpdm-job-meta wpdm-job-meta--error" title="<?php echo esc_attr($job->error_message); ?>"><?php echo esc_html($job->error_message); ?></div>
                    <?php elseif ($interval > 0): ?>
                    <div class="wpdm-job-meta"><i class="fa-solid fa-repeat"></i> <?php echo esc_html(sprintf(__('Every %s', 'download-manager'), human_time_diff(0, $interval))); ?></div>
                    <?php elseif ($jobDescription): ?>
                    <div class="wpdm-job-meta"><?php echo esc_html($jobDescription); ?></div>
                    <?php endif; ?>
                </td>
                <td><span class="wpdm-job-queue"><?php echo esc_html($job->queue ?? 'default'); ?></span></td>
                <td><span class="wpdm-job-status wpdm-job-status--<?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></span></td>
                <td class="wpdm-job-attempts"><?php echo esc_html(($job->attempts ?? 0) . ' / ' . ($job->max_attempts ?? 3)); ?></td>
                <td class="wpdm-job-when">
                    <?php if ($job->execute_at): ?>
                    <span title="<?php echo esc_attr(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $job->execute_at)); ?>"><?php echo esc_html(wp_date('M j, H:i', $job->execute_at)); ?></span>
                    <?php if ($overdue): ?>
                    <span class="wpdm-job-overdue"><?php echo esc_html(sprintf(__('%s overdue', 'download-manager'), human_time_diff($job->execute_at))); ?></span>
                    <?php endif; ?>
                    <?php else: ?>
                    &mdash;
                    <?php endif; ?>
                </td>
                <td>
                    <div class="wpdm-job-actions">
                        <?php foreach ($actions as $action => [$variant, $icon, $label]): ?>
                        <button type="button" class="wpdm-job-btn wpdm-job-btn--<?php echo esc_attr($variant); ?> wpdm-job-action" data-action="<?php echo esc_attr($action); ?>" data-job="<?php echo esc_attr($job->ID); ?>" title="<?php echo esc_attr($label); ?>" aria-label="<?php echo esc_attr($label); ?>">
                            <i class="fa-solid <?php echo esc_attr($icon); ?>" aria-hidden="true"></i>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<script>
jQuery(function($) {
    var nonce = '<?php echo wp_create_nonce('wpdm_job_action'); ?>';
    var cronNonce = '<?php echo wp_create_nonce('wpdm_cron_nonce'); ?>';

    // Run cron button
    $('#wpdm-run-cron').on('click', function() {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;

        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Running...');

        $.post(ajaxurl, {
            action: 'wpdm_run_cron',
            nonce: cronNonce
        }, function(response) {
            $btn.prop('disabled', false).html('<i class="fa fa-play"></i> Run Job Queue Now');

            if (response.success) {
                var r = response.data.results;
                alert('Processed: ' + r.processed + ', Succeeded: ' + r.succeeded + ', Failed: ' + r.failed);
                location.reload();
            } else {
                alert(response.data.message || 'Failed to run cron');
            }
        }).fail(function() {
            $btn.prop('disabled', false).html('<i class="fa fa-play"></i> Run Job Queue Now');
            alert('Request failed');
        });
    });

    // Job actions
    $(document).on('click', '.wpdm-job-action', function() {
        var $btn = $(this);
        var action = $btn.data('action');
        var jobId = $btn.data('job');

        if (action === 'delete' && !confirm('<?php esc_html_e('Are you sure you want to delete this job?', 'download-manager'); ?>')) {
            return;
        }

        var icon = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>');

        $.post(ajaxurl, {
            action: 'wpdm_job_action',
            nonce: nonce,
            job_action: action,
            job_id: jobId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message || 'Action failed');
                $btn.prop('disabled', false).html(icon);
            }
        }).fail(function() {
            alert('Request failed');
            $btn.prop('disabled', false).html(icon);
        });
    });
});
</script>
