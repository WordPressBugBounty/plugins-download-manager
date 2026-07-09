<?php
/**
 * Activity Report Email Content Template
 *
 * Variables available:
 * - $data: Array with report data
 * - $sections: Array of enabled sections
 *
 * @package WPDM
 * @since 7.0.2
 */

if (!defined('ABSPATH')) die('!');

// Inline styles for email clients
$styles = [
    'section' => 'margin-bottom: 32px; padding: 24px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px;',
    'heading' => 'margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: #111827;',
    'subheading' => 'margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #374151;',
    'stat_grid' => 'width: 100%; border-collapse: separate; border-spacing: 12px;',
    'stat_card' => 'background: #f9fafb; padding: 16px; border-radius: 6px; text-align: center; vertical-align: top;',
    'stat_value' => 'display: block; font-size: 28px; font-weight: 700; color: #111827; margin-bottom: 4px;',
    'stat_label' => 'display: block; font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;',
    'change_positive' => 'display: inline-block; padding: 2px 8px; background: #d1fae5; color: #065f46; font-size: 12px; font-weight: 600; border-radius: 9999px; margin-top: 8px;',
    'change_negative' => 'display: inline-block; padding: 2px 8px; background: #fee2e2; color: #991b1b; font-size: 12px; font-weight: 600; border-radius: 9999px; margin-top: 8px;',
    'table' => 'width: 100%; border-collapse: collapse; font-size: 14px;',
    'th' => 'padding: 12px 16px; text-align: left; background: #f9fafb; color: #374151; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;',
    'td' => 'padding: 12px 16px; border-bottom: 1px solid #f3f4f6; color: #374151;',
    'link' => 'color: #4f46e5; text-decoration: none; font-weight: 500;',
    'badge' => 'display: inline-block; padding: 2px 8px; background: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600; border-radius: 4px;',
    'badge_gold' => 'display: inline-block; padding: 2px 8px; background: #fef3c7; color: #92400e; font-size: 11px; font-weight: 600; border-radius: 4px;',
    'bar' => 'height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;',
    'bar_fill' => 'height: 100%; background: linear-gradient(90deg, #6366f1, #4f46e5); border-radius: 4px;',
    'empty' => 'padding: 32px; text-align: center; color: #9ca3af; font-size: 14px;',
];
?>

<!-- Report Header -->
<div style="text-align: center; margin-bottom: 32px;">
    <h1 style="margin: 0 0 8px 0; font-size: 24px; font-weight: 700; color: #111827;">
        <?php echo esc_html($data['period_label']); ?> <?php _e('Activity Report', 'download-manager'); ?>
    </h1>
    <p style="margin: 0; font-size: 14px; color: #6b7280;">
        <?php echo esc_html($data['date_range']); ?>
    </p>
</div>

<?php if (!empty($data['download_summary'])): ?>
<!-- Download Summary -->
<div style="<?php echo $styles['section']; ?>">
    <h2 style="<?php echo $styles['heading']; ?>">
        <?php _e('Downloads Overview', 'download-manager'); ?>
    </h2>

    <table style="<?php echo $styles['stat_grid']; ?>">
        <tr>
            <td style="<?php echo $styles['stat_card']; ?> width: 33%;">
                <span style="<?php echo $styles['stat_value']; ?>"><?php echo number_format($data['download_summary']['total']); ?></span>
                <span style="<?php echo $styles['stat_label']; ?>"><?php _e('Total Downloads', 'download-manager'); ?></span>
                <?php
                $changeClass = $data['download_summary']['change_class'] === 'positive' ? $styles['change_positive'] : $styles['change_negative'];
                ?>
                <span style="<?php echo $changeClass; ?>"><?php echo esc_html($data['download_summary']['change']); ?></span>
            </td>
            <td style="<?php echo $styles['stat_card']; ?> width: 33%;">
                <span style="<?php echo $styles['stat_value']; ?>"><?php echo number_format($data['download_summary']['daily_average'], 1); ?></span>
                <span style="<?php echo $styles['stat_label']; ?>"><?php _e('Daily Average', 'download-manager'); ?></span>
            </td>
            <td style="<?php echo $styles['stat_card']; ?> width: 33%;">
                <span style="<?php echo $styles['stat_value']; ?>"><?php echo number_format($data['download_summary']['peak_day_count']); ?></span>
                <span style="<?php echo $styles['stat_label']; ?>"><?php _e('Peak Day', 'download-manager'); ?></span>
                <span style="display: block; font-size: 12px; color: #6b7280; margin-top: 4px;"><?php echo esc_html($data['download_summary']['peak_day']); ?></span>
            </td>
        </tr>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($data['top_packages'])): ?>
<!-- Top Packages -->
<div style="<?php echo $styles['section']; ?>">
    <h2 style="<?php echo $styles['heading']; ?>">
        <?php _e('Top Downloads', 'download-manager'); ?>
    </h2>

    <?php if (count($data['top_packages']) > 0): ?>
    <table style="<?php echo $styles['table']; ?>">
        <thead>
            <tr>
                <th style="<?php echo $styles['th']; ?> width: 40px;">#</th>
                <th style="<?php echo $styles['th']; ?>"><?php _e('Package', 'download-manager'); ?></th>
                <th style="<?php echo $styles['th']; ?> width: 120px; text-align: right;"><?php _e('Downloads', 'download-manager'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['top_packages'] as $package): ?>
            <tr>
                <td style="<?php echo $styles['td']; ?>">
                    <?php if ($package['rank'] <= 3): ?>
                    <span style="<?php echo $styles['badge_gold']; ?>">#<?php echo $package['rank']; ?></span>
                    <?php else: ?>
                    <span style="color: #9ca3af;">#<?php echo $package['rank']; ?></span>
                    <?php endif; ?>
                </td>
                <td style="<?php echo $styles['td']; ?>">
                    <a href="<?php echo esc_url($package['url']); ?>" style="<?php echo $styles['link']; ?>">
                        <?php echo esc_html($package['title']); ?>
                    </a>
                    <div style="margin-top: 8px;">
                        <div style="<?php echo $styles['bar']; ?>">
                            <div style="<?php echo $styles['bar_fill']; ?> width: <?php echo $package['bar_width']; ?>%;"></div>
                        </div>
                    </div>
                </td>
                <td style="<?php echo $styles['td']; ?> text-align: right; font-weight: 600;">
                    <?php echo number_format($package['downloads']); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="<?php echo $styles['empty']; ?>">
        <?php _e('No downloads recorded during this period.', 'download-manager'); ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($data['trending_packages'])): ?>
<!-- Trending Packages -->
<div style="<?php echo $styles['section']; ?>">
    <h2 style="<?php echo $styles['heading']; ?>">
        <?php _e('Trending Packages', 'download-manager'); ?>
    </h2>

    <?php if (count($data['trending_packages']) > 0): ?>
    <table style="<?php echo $styles['table']; ?>">
        <thead>
            <tr>
                <th style="<?php echo $styles['th']; ?>"><?php _e('Package', 'download-manager'); ?></th>
                <th style="<?php echo $styles['th']; ?> width: 100px; text-align: center;"><?php _e('Previous', 'download-manager'); ?></th>
                <th style="<?php echo $styles['th']; ?> width: 100px; text-align: center;"><?php _e('Current', 'download-manager'); ?></th>
                <th style="<?php echo $styles['th']; ?> width: 100px; text-align: right;"><?php _e('Growth', 'download-manager'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['trending_packages'] as $package): ?>
            <tr>
                <td style="<?php echo $styles['td']; ?>">
                    <a href="<?php echo esc_url($package['url']); ?>" style="<?php echo $styles['link']; ?>">
                        <?php echo esc_html($package['title']); ?>
                    </a>
                </td>
                <td style="<?php echo $styles['td']; ?> text-align: center; color: #6b7280;">
                    <?php echo number_format($package['previous_downloads']); ?>
                </td>
                <td style="<?php echo $styles['td']; ?> text-align: center; font-weight: 600;">
                    <?php echo number_format($package['current_downloads']); ?>
                </td>
                <td style="<?php echo $styles['td']; ?> text-align: right;">
                    <span style="<?php echo $styles['change_positive']; ?>"><?php echo esc_html($package['growth_text']); ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="<?php echo $styles['empty']; ?>">
        <?php _e('No trending packages found for this period.', 'download-manager'); ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($data['user_activity'])): ?>
<!-- User Activity -->
<div style="<?php echo $styles['section']; ?>">
    <h2 style="<?php echo $styles['heading']; ?>">
        <?php _e('User Activity', 'download-manager'); ?>
    </h2>

    <table style="<?php echo $styles['stat_grid']; ?>">
        <tr>
            <td style="<?php echo $styles['stat_card']; ?> width: 25%;">
                <span style="<?php echo $styles['stat_value']; ?>"><?php echo number_format($data['user_activity']['new_users']); ?></span>
                <span style="<?php echo $styles['stat_label']; ?>"><?php _e('New Users', 'download-manager'); ?></span>
            </td>
            <td style="<?php echo $styles['stat_card']; ?> width: 25%;">
                <span style="<?php echo $styles['stat_value']; ?>"><?php echo number_format($data['user_activity']['unique_downloaders']); ?></span>
                <span style="<?php echo $styles['stat_label']; ?>"><?php _e('Unique Downloaders', 'download-manager'); ?></span>
            </td>
            <td style="<?php echo $styles['stat_card']; ?> width: 25%;">
                <span style="<?php echo $styles['stat_value']; ?>"><?php echo number_format($data['user_activity']['registered_downloaders']); ?></span>
                <span style="<?php echo $styles['stat_label']; ?>"><?php _e('Registered', 'download-manager'); ?></span>
            </td>
            <td style="<?php echo $styles['stat_card']; ?> width: 25%;">
                <span style="<?php echo $styles['stat_value']; ?>"><?php echo number_format($data['user_activity']['guest_downloaders']); ?></span>
                <span style="<?php echo $styles['stat_label']; ?>"><?php _e('Guests', 'download-manager'); ?></span>
            </td>
        </tr>
    </table>

    <?php if (!empty($data['user_activity']['top_downloaders'])): ?>
    <h3 style="<?php echo $styles['subheading']; ?> margin-top: 24px;">
        <?php _e('Top Downloaders', 'download-manager'); ?>
    </h3>
    <table style="<?php echo $styles['table']; ?>">
        <thead>
            <tr>
                <th style="<?php echo $styles['th']; ?>"><?php _e('User', 'download-manager'); ?></th>
                <th style="<?php echo $styles['th']; ?> width: 100px; text-align: right;"><?php _e('Downloads', 'download-manager'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['user_activity']['top_downloaders'] as $user): ?>
            <tr>
                <td style="<?php echo $styles['td']; ?>">
                    <strong><?php echo esc_html($user['name']); ?></strong>
                    <span style="display: block; font-size: 12px; color: #6b7280;"><?php echo esc_html($user['email']); ?></span>
                </td>
                <td style="<?php echo $styles['td']; ?> text-align: right; font-weight: 600;">
                    <?php echo number_format($user['downloads']); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($data['category_breakdown'])): ?>
<!-- Category Breakdown -->
<div style="<?php echo $styles['section']; ?>">
    <h2 style="<?php echo $styles['heading']; ?>">
        <?php _e('Category Breakdown', 'download-manager'); ?>
    </h2>

    <?php if (count($data['category_breakdown']) > 0): ?>
    <table style="<?php echo $styles['table']; ?>">
        <thead>
            <tr>
                <th style="<?php echo $styles['th']; ?>"><?php _e('Category', 'download-manager'); ?></th>
                <th style="<?php echo $styles['th']; ?> width: 100px; text-align: center;"><?php _e('Downloads', 'download-manager'); ?></th>
                <th style="<?php echo $styles['th']; ?> width: 80px; text-align: center;"><?php _e('Share', 'download-manager'); ?></th>
                <th style="<?php echo $styles['th']; ?> width: 80px; text-align: right;"><?php _e('Change', 'download-manager'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($data['category_breakdown'], 0, 10) as $category): ?>
            <tr>
                <td style="<?php echo $styles['td']; ?>">
                    <?php if (!is_wp_error($category['url'])): ?>
                    <a href="<?php echo esc_url($category['url']); ?>" style="<?php echo $styles['link']; ?>">
                        <?php echo esc_html($category['name']); ?>
                    </a>
                    <?php else: ?>
                    <?php echo esc_html($category['name']); ?>
                    <?php endif; ?>
                </td>
                <td style="<?php echo $styles['td']; ?> text-align: center; font-weight: 600;">
                    <?php echo number_format($category['downloads']); ?>
                </td>
                <td style="<?php echo $styles['td']; ?> text-align: center;">
                    <span style="<?php echo $styles['badge']; ?>"><?php echo $category['percentage']; ?>%</span>
                </td>
                <td style="<?php echo $styles['td']; ?> text-align: right;">
                    <?php
                    $isPositive = strpos($category['change'], '+') === 0;
                    $changeStyle = $isPositive ? $styles['change_positive'] : $styles['change_negative'];
                    ?>
                    <span style="<?php echo $changeStyle; ?>"><?php echo esc_html($category['change']); ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="<?php echo $styles['empty']; ?>">
        <?php _e('No category data available.', 'download-manager'); ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($data['revenue_summary'])): ?>
<!-- Revenue Summary -->
<div style="<?php echo $styles['section']; ?> border-color: #10b981;">
    <h2 style="<?php echo $styles['heading']; ?> color: #065f46;">
        <?php _e('Revenue Summary', 'download-manager'); ?>
    </h2>

    <table style="<?php echo $styles['stat_grid']; ?>">
        <tr>
            <td style="<?php echo $styles['stat_card']; ?> background: #ecfdf5; width: 33%;">
                <span style="<?php echo $styles['stat_value']; ?> color: #065f46;"><?php echo esc_html($data['revenue_summary']['revenue_formatted']); ?></span>
                <span style="<?php echo $styles['stat_label']; ?>"><?php _e('Total Revenue', 'download-manager'); ?></span>
                <?php
                $changeClass = $data['revenue_summary']['change_class'] === 'positive' ? $styles['change_positive'] : $styles['change_negative'];
                ?>
                <span style="<?php echo $changeClass; ?>"><?php echo esc_html($data['revenue_summary']['change']); ?></span>
            </td>
            <td style="<?php echo $styles['stat_card']; ?> width: 33%;">
                <span style="<?php echo $styles['stat_value']; ?>"><?php echo number_format($data['revenue_summary']['orders']); ?></span>
                <span style="<?php echo $styles['stat_label']; ?>"><?php _e('Orders', 'download-manager'); ?></span>
            </td>
            <td style="<?php echo $styles['stat_card']; ?> width: 33%;">
                <span style="<?php echo $styles['stat_value']; ?>"><?php echo esc_html($data['revenue_summary']['average_order']); ?></span>
                <span style="<?php echo $styles['stat_label']; ?>"><?php _e('Avg. Order Value', 'download-manager'); ?></span>
            </td>
        </tr>
    </table>

    <?php if (!empty($data['revenue_summary']['top_products'])): ?>
    <h3 style="<?php echo $styles['subheading']; ?> margin-top: 24px;">
        <?php _e('Top Selling Products', 'download-manager'); ?>
    </h3>
    <table style="<?php echo $styles['table']; ?>">
        <thead>
            <tr>
                <th style="<?php echo $styles['th']; ?>"><?php _e('Product', 'download-manager'); ?></th>
                <th style="<?php echo $styles['th']; ?> width: 80px; text-align: center;"><?php _e('Sold', 'download-manager'); ?></th>
                <th style="<?php echo $styles['th']; ?> width: 100px; text-align: right;"><?php _e('Revenue', 'download-manager'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['revenue_summary']['top_products'] as $product): ?>
            <tr>
                <td style="<?php echo $styles['td']; ?>"><?php echo esc_html($product['title']); ?></td>
                <td style="<?php echo $styles['td']; ?> text-align: center;"><?php echo number_format($product['quantity']); ?></td>
                <td style="<?php echo $styles['td']; ?> text-align: right; font-weight: 600; color: #065f46;">
                    <?php echo esc_html($data['revenue_summary']['currency'] . number_format($product['revenue'], 2)); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($data['storage_usage'])): ?>
<!-- Storage Usage -->
<div style="<?php echo $styles['section']; ?>">
    <h2 style="<?php echo $styles['heading']; ?>">
        <?php _e('Storage Usage', 'download-manager'); ?>
    </h2>

    <table style="<?php echo $styles['stat_grid']; ?>">
        <tr>
            <td style="<?php echo $styles['stat_card']; ?> width: 25%;">
                <span style="<?php echo $styles['stat_value']; ?>"><?php echo esc_html($data['storage_usage']['total_size']); ?></span>
                <span style="<?php echo $styles['stat_label']; ?>"><?php _e('Total Size', 'download-manager'); ?></span>
            </td>
            <td style="<?php echo $styles['stat_card']; ?> width: 25%;">
                <span style="<?php echo $styles['stat_value']; ?>"><?php echo number_format($data['storage_usage']['file_count']); ?></span>
                <span style="<?php echo $styles['stat_label']; ?>"><?php _e('Total Files', 'download-manager'); ?></span>
            </td>
            <td style="<?php echo $styles['stat_card']; ?> width: 25%;">
                <span style="<?php echo $styles['stat_value']; ?>"><?php echo number_format($data['storage_usage']['package_count']); ?></span>
                <span style="<?php echo $styles['stat_label']; ?>"><?php _e('Packages', 'download-manager'); ?></span>
            </td>
            <td style="<?php echo $styles['stat_card']; ?> width: 25%;">
                <span style="<?php echo $styles['stat_value']; ?>"><?php echo number_format($data['storage_usage']['new_packages']); ?></span>
                <span style="<?php echo $styles['stat_label']; ?>"><?php _e('New This Period', 'download-manager'); ?></span>
            </td>
        </tr>
    </table>

    <?php if (!empty($data['storage_usage']['largest_packages'])): ?>
    <h3 style="<?php echo $styles['subheading']; ?> margin-top: 24px;">
        <?php _e('Largest Packages', 'download-manager'); ?>
    </h3>
    <table style="<?php echo $styles['table']; ?>">
        <thead>
            <tr>
                <th style="<?php echo $styles['th']; ?>"><?php _e('Package', 'download-manager'); ?></th>
                <th style="<?php echo $styles['th']; ?> width: 100px; text-align: right;"><?php _e('Size', 'download-manager'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['storage_usage']['largest_packages'] as $package): ?>
            <tr>
                <td style="<?php echo $styles['td']; ?>"><?php echo esc_html($package['title']); ?></td>
                <td style="<?php echo $styles['td']; ?> text-align: right; font-weight: 600;">
                    <?php echo esc_html($package['size']); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Footer -->
<div style="text-align: center; padding: 24px 0; border-top: 1px solid #e5e7eb; margin-top: 32px;">
    <p style="margin: 0 0 8px 0; font-size: 13px; color: #6b7280;">
        <?php _e('This report was automatically generated by WordPress Download Manager.', 'download-manager'); ?>
    </p>
    <p style="margin: 0;">
        <a href="<?php echo admin_url('edit.php?post_type=wpdmpro&page=settings&tab=activity-reports'); ?>" style="<?php echo $styles['link']; ?> font-size: 13px;">
            <?php _e('Manage Report Settings', 'download-manager'); ?>
        </a>
    </p>
</div>
