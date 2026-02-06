<?php
if (!defined('ABSPATH')) die();
/**
 * User: shahnuralam
 * Date: 1/16/18
 * Time: 12:33 AM
 */

error_reporting(0);
//global $post;
$ID = wpdm_query_var('__wpdmlo');

$post_type = get_post_type($ID);
$post_status = get_post_status($ID);
if($post_type !== 'wpdmpro' && $post_status !== 'publish'){
    \WPDM\__\Messages::fullPage("Error: Invalid request", \WPDM\__\UI::card("Error: Invalid request", ["Your request could not be processed."]));
    die();
}

//$post = get_post(wpdm_query_var('__wpdmlo'));
//setup_postdata($post);
//$pack = new \WPDM\Package();
//$pack->Prepare(get_the_ID());
$form_lock = (int)get_post_meta($ID, '__wpdm_form_lock', true);
$terms_lock = (int)get_post_meta($ID, '__wpdm_terms_lock', true);
$base_price = (double)get_post_meta($ID, '__wpdm_base_price', true);
$color_scheme = get_option('__wpdm_color_scheme', 'light');

?><!DOCTYPE html>
<html style="background: transparent">
<head>
    <title>Download <?php get_the_title($ID); ?></title>


    <?php if($form_lock === 1  || $base_price > 0) wp_head(); else { ?>
        <script type="text/javascript">
            const wpdm_url = <?php echo json_encode(WPDM()->wpdm_urls);?>;
        </script>
        <link rel="stylesheet" href="<?php echo WPDM_ASSET_URL; ?>css/front.min.css" />
        <?php if ($color_scheme !== 'light') { ?>
        <link rel="stylesheet" href="<?php echo WPDM_ASSET_URL; ?>css/front-dark.min.css" />
        <?php } ?>
        <script src="<?php echo includes_url(); ?>/js/jquery/jquery.js"></script>
        <script src="<?php echo includes_url(); ?>/js/jquery/jquery.form.min.js"></script>
        <script src="<?php echo WPDM_ASSET_URL; ?>js/wpdm.js"></script>
        <script src="<?php echo WPDM_ASSET_URL; ?>js/front.min.js"></script>
        <?php
        $_font = get_option('__wpdm_google_font', 'Sen');
        $font = explode(":", $_font);
        $font = $font[0];
        $font = $font ? $font . ',' : '';
        if($_font) {
        ?>
        <link href="https://fonts.googleapis.com/css2?family=<?php echo str_replace("regular", 400, $_font); ?>" rel="stylesheet">
        <style>
            .w3eden .fetfont,
            .w3eden .btn,
            .w3eden .btn.wpdm-front h3.title,
            .w3eden .wpdm-social-lock-box .IN-widget a span:last-child,
            .w3eden .card-header,
            .w3eden .card-footer,
            .w3eden .badge,
            .w3eden .label,
            .w3eden .table,
            .w3eden .card-body,
            .w3eden .wpdm-frontend-tabs a,
            .w3eden .alert:before,
            .w3eden .discount-msg,
            .w3eden .panel.dashboard-panel h3,
            .w3eden #wdmds .list-group-item,
            .w3eden #package-description .wp-switch-editor,
            .w3eden .w3eden.author-dashbboard .nav.nav-tabs li a,
            .w3eden .wpdm_cart thead th,
            .w3eden #csp .list-group-item,
            .w3eden .modal-title {
                font-family: <?php echo $font; ?> -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            }
            .w3eden .btn
            {
                font-weight: 800 !important;
            }
            .w3eden .btn {
                letter-spacing: 1px;
                text-transform: uppercase;
            }
            .w3eden #csp .list-group-item {
                text-transform: unset;
            }


        </style>
    <?php
    }
    $wpdmss = maybe_unserialize(get_option('__wpdm_disable_scripts', array()));
    $uicolors = maybe_unserialize(get_option('__wpdm_ui_colors', array()));
    $primary = isset($uicolors['primary']) ? $uicolors['primary'] : '#4a8eff';
    $secondary = isset($uicolors['secondary']) ? $uicolors['secondary'] : '#4a8eff';
    $success = isset($uicolors['success']) ? $uicolors['success'] : '#18ce0f';
    $info = isset($uicolors['info']) ? $uicolors['info'] : '#2CA8FF';
    $warning = isset($uicolors['warning']) ? $uicolors['warning'] : '#f29e0f';
    $danger = isset($uicolors['danger']) ? $uicolors['danger'] : '#ff5062';
    $font = get_option('__wpdm_google_font', 'Sen');
    $font = explode(":", $font);
    $font = $font[0];
    $font = $font ? "\"{$font}\"," : '';
    if (is_singular('wpdmpro'))
	    $ui_button = get_option('__wpdm_ui_download_button');
    else
	    $ui_button = get_option('__wpdm_ui_download_button_sc');
    $class = ".btn." . (isset($ui_button['color']) ? $ui_button['color'] : 'btn-primary') . (isset($ui_button['size']) && $ui_button['size'] != '' ? "." . $ui_button['size'] : '');

    ?>
        <style>

            :root {
                --color-primary: <?php echo $primary; ?>;
                --color-primary-rgb: <?php echo wpdm_hex2rgb($primary); ?>;
                --color-primary-hover: <?php echo isset($uicolors['primary'])?$uicolors['primary_hover']:'#4a8eff'; ?>;
                --color-primary-active: <?php echo isset($uicolors['primary'])?$uicolors['primary_active']:'#4a8eff'; ?>;
                --clr-sec: <?php echo $secondary; ?>;
                --clr-sec-rgb: <?php echo wpdm_hex2rgb($secondary); ?>;
                --clr-sec-hover: <?php echo isset($uicolors['secondary'])?$uicolors['secondary_hover']:'#4a8eff'; ?>;
                --clr-sec-active: <?php echo isset($uicolors['secondary'])?$uicolors['secondary_active']:'#4a8eff'; ?>;
                --color-success: <?php echo $success; ?>;
                --color-success-rgb: <?php echo wpdm_hex2rgb($success); ?>;
                --color-success-hover: <?php echo isset($uicolors['success_hover'])?$uicolors['success_hover']:'#4a8eff'; ?>;
                --color-success-active: <?php echo isset($uicolors['success_active'])?$uicolors['success_active']:'#4a8eff'; ?>;
                --color-info: <?php echo $info; ?>;
                --color-info-rgb: <?php echo wpdm_hex2rgb($info); ?>;
                --color-info-hover: <?php echo isset($uicolors['info_hover'])?$uicolors['info_hover']:'#2CA8FF'; ?>;
                --color-info-active: <?php echo isset($uicolors['info_active'])?$uicolors['info_active']:'#2CA8FF'; ?>;
                --color-warning: <?php echo $warning; ?>;
                --color-warning-rgb: <?php echo wpdm_hex2rgb($warning); ?>;
                --color-warning-hover: <?php echo isset($uicolors['warning_hover'])?$uicolors['warning_hover']:'orange'; ?>;
                --color-warning-active: <?php echo isset($uicolors['warning_active'])?$uicolors['warning_active']:'orange'; ?>;
                --color-danger: <?php echo $danger; ?>;
                --color-danger-rgb: <?php echo wpdm_hex2rgb($danger); ?>;
                --color-danger-hover: <?php echo isset($uicolors['danger_hover'])?$uicolors['danger_hover']:'#ff5062'; ?>;
                --color-danger-active: <?php echo isset($uicolors['danger_active'])?$uicolors['danger_active']:'#ff5062'; ?>;
                --color-green: <?php echo isset($uicolors['green'])?$uicolors['green']:'#30b570'; ?>;
                --color-blue: <?php echo isset($uicolors['blue'])?$uicolors['blue']:'#0073ff'; ?>;
                --color-purple: <?php echo isset($uicolors['purple'])?$uicolors['purple']:'#8557D3'; ?>;
                --color-red: <?php echo isset($uicolors['red'])?$uicolors['red']:'#ff5062'; ?>;
                --color-muted: rgba(69, 89, 122, 0.6);
                --wpdm-font: <?php echo $font; ?> -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            }

            .wpdm-download-link<?php echo $class; ?> {
                border-radius: <?php echo (isset($ui_button['borderradius'])?$ui_button['borderradius']:4); ?>px;
            }


        </style>
	    <?php
    }
    ?>
    <style>
        /* ============================================
           Enterprise Modal - Modern Design System
           ============================================ */

        :root {
            --modal-bg: #ffffff;
            --modal-border: rgba(0, 0, 0, 0.08);
            --modal-shadow:
                0 0 0 1px rgba(0, 0, 0, 0.03),
                0 2px 4px rgba(0, 0, 0, 0.02),
                0 8px 16px rgba(0, 0, 0, 0.04),
                0 16px 32px rgba(0, 0, 0, 0.06),
                0 32px 64px rgba(0, 0, 0, 0.08);
            --modal-radius: 20px;
            --icon-size: 80px;
            --backdrop-blur: 12px;
            --transition-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
            --transition-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --transition-exit: cubic-bezier(0.4, 0, 1, 1);
        }

        html, body {
            overflow: visible;
            height: 100%;
            width: 100%;
            padding: 0;
            margin: 0;
            font-weight: 400;
            font-size: 10pt;
            font-family: var(--wpdm-font);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Backdrop */
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.2) !important;
            opacity: 0 !important;
            backdrop-filter: blur(1px);
            -webkit-backdrop-filter: blur(1px);
            transition: opacity 0.4s var(--transition-smooth) !important;
        }

        .modal-backdrop.show {
            opacity: 1 !important;
        }

        /* Modal Dialog Container */
        .modal.fade .modal-dialog {
            transform: scale(0.9) translateY(30px);
            opacity: 0;
            transition:
                transform 0.5s var(--transition-spring),
                opacity 0.4s var(--transition-smooth);
        }

        .modal.show .modal-dialog {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        /* Exit animation */
        .modal.fade:not(.show) .modal-dialog {
            transform: scale(0.95) translateY(20px);
            opacity: 0;
            transition:
                transform 0.25s var(--transition-exit),
                opacity 0.2s var(--transition-exit);
        }

        /* Modal Content - Enterprise Card */
        .w3eden .modal-content {
            background: var(--modal-bg);
            border: none;
            border-radius: var(--modal-radius);
            box-shadow: var(--modal-shadow);
            max-width: 100%;
            padding-top: calc(var(--icon-size) / 2 + 8px) !important;
            overflow: visible;
            position: relative;
        }

        /* Subtle gradient overlay on content */
        .w3eden .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 120px;
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.03) 0%, transparent 100%);
            pointer-events: none;
            border-radius: var(--modal-radius) var(--modal-radius) 0 0;
        }

        /* Modal Header */
        .w3eden .modal-header {
            border: 0;
            padding: 0;
        }

        /* Modal Title */
        h4.modal-title,
        .modal-content h4 {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #1e293b;
            font-size: 12pt;
            display: inline-block;
            font-family: var(--wpdm-font);
            margin: 0 0 8px;
            opacity: 0;
            transform: translateY(10px);
            animation: modalContentFadeIn 0.5s var(--transition-spring) 0.2s forwards;
        }

        /* Product title animation */
        .modal-content .color-purple {
            opacity: 0;
            transform: translateY(10px);
            animation: modalContentFadeIn 0.5s var(--transition-spring) 0.3s forwards;
        }

        @keyframes modalContentFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Modal Icon - Floating Design */
        .modal-icon {
            width: var(--icon-size);
            height: var(--icon-size);
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            top: 0;
            border-radius: 50%;
            margin-top: calc(var(--icon-size) / -2);
            left: calc(50% - var(--icon-size) / 2);
            position: absolute;
            z-index: 999999;
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.08),
                0 8px 24px rgba(0, 0, 0, 0.12),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
            opacity: 0;
            transform: scale(0.5) translateY(20px);
            animation: modalIconPop 0.6s var(--transition-spring) 0.1s forwards;
        }

        @keyframes modalIconPop {
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Icon ring effect */
        .modal-icon::before {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary, #6366f1) 0%, var(--color-purple, #8557D3) 100%);
            opacity: 0.15;
            z-index: -1;
        }

        .modal-icon img,
        .modal-icon .wp-post-image {
            border-radius: 50%;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
        }

        /* Close Button - Minimal Design */
        .close {
            position: absolute;
            z-index: 999999;
            top: 16px;
            right: 16px;
            width: 28px;
            height: 28px;
            padding: 0;
            margin: 0;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            opacity: 0.4 !important;
            cursor: pointer;
        }

        .close svg {
            color: #64748b;
            transition: all 0.2s var(--transition-smooth);
        }

        .close:hover {
            opacity: 1 !important;
        }

        .close:hover svg {
            color: #ef4444;
        }

        .close:active {
            transform: scale(0.9);
        }

        /* Modal Body */
        .w3eden .modal-body {
            max-height: calc(100vh - 240px);
            overflow-y: auto;
            padding: 0 20px 20px !important;
            opacity: 0;
            transform: translateY(15px);
            animation: modalBodyFadeIn 0.5s var(--transition-spring) 0.35s forwards;
        }

        @keyframes modalBodyFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom Scrollbar */
        .w3eden .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .w3eden .modal-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .w3eden .modal-body::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 3px;
        }

        .w3eden .modal-body::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        /* Cards inside modal */
        .w3eden .card {
            margin-bottom: 0;
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: all 0.25s var(--transition-smooth);
        }

        .w3eden .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-color: rgba(0, 0, 0, 0.08);
        }

        .w3eden .card:last-child {
            margin-bottom: 10px !important;
        }

        .w3eden .card-default {
            margin-top: 10px !important;
        }

        .card-body {
            line-height: 1.6;
            letter-spacing: 0.3px;
            font-size: 11pt;
            color: #334155;
        }

        /* Form Elements */
        .w3eden label {
            font-weight: 500;
            color: #475569;
            margin-bottom: 6px;
        }

        .w3eden .form-control {
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 10px 14px;
            transition: all 0.25s var(--transition-smooth);
        }

        .w3eden .form-control:focus {
            border-color: var(--color-primary, #6366f1);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .w3eden .input-group-lg .form-control {
            font-size: 14pt !important;
        }

        .w3eden .input-group-lg .input-group-btn .btn {
            border-top-right-radius: 10px !important;
            border-bottom-right-radius: 10px !important;
        }

        .w3eden .input-group.input-group-lg .input-group-btn .btn {
            font-size: 11pt !important;
        }

        .w3eden .wpforms-field-medium {
            max-width: 100% !important;
            width: 100% !important;
        }

        /* Buttons */
        .btn {
            outline: none !important;
            text-decoration: none !important;
        }

        .btn-viewcart,
        #cart_submit {
            line-height: 30px !important;
            width: 100%;
        }

        /* Social Lock Buttons */
        .wpdm-social-lock.btn {
            display: block;
            width: 100%;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 8px;
        }

        /* Price Display */
        .w3eden h3.wpdmpp-product-price {
            text-align: center;
            margin-bottom: 24px !important;
            font-size: 24pt;
            color: #1e293b;
        }

        /* Spin Animation */
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .spin {
            animation: spin 1.5s linear infinite;
            display: inline-block;
        }

        /* Images */
        img {
            max-width: 100%;
        }

        .wp-post-image {
            width: 100%;
            height: auto;
            border-radius: 50%;
        }

        form * {
            max-width: 100% !important;
        }

        /* Reduced Motion Support */
        @media (prefers-reduced-motion: reduce) {
            .modal.fade .modal-dialog,
            .modal-backdrop,
            .modal-icon,
            h4.modal-title,
            .modal-content .color-purple,
            .w3eden .modal-body {
                animation: none !important;
                transition-duration: 0.01ms !important;
                opacity: 1 !important;
                transform: none !important;
            }
            .close {
                opacity: 0.4 !important;
            }
        }

        /* Manual dark mode - always apply when .dark-mode class is present */
        .w3eden.dark-mode .modal-content::before {
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.08) 0%, transparent 100%);
        }
        .w3eden.dark-mode h4.modal-title,
        .w3eden.dark-mode .modal-content h4 {
            color: var(--dm-text);
        }
        .w3eden.dark-mode .modal-content .color-purple {
            color: #c4b5fd !important;
        }
        .w3eden.dark-mode .modal-icon {
            background: linear-gradient(145deg, var(--dm-bg-tertiary) 0%, var(--dm-bg-secondary) 100%);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3), 0 8px 24px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        .w3eden.dark-mode .close svg {
            color: var(--dm-text-muted);
        }
        .w3eden.dark-mode .close:hover svg {
            color: #f87171;
        }

        /* System preference dark mode - only when OS prefers dark AND no light-mode class */
        @media (prefers-color-scheme: dark) {
            .w3eden:not(.light-mode) .modal-content::before {
                background: linear-gradient(180deg, rgba(99, 102, 241, 0.08) 0%, transparent 100%);
            }
            .w3eden:not(.light-mode) h4.modal-title,
            .w3eden:not(.light-mode) .modal-content h4 {
                color: var(--dm-text);
            }
            .w3eden:not(.light-mode) .modal-content .color-purple {
                color: #c4b5fd !important;
            }
            .w3eden:not(.light-mode) .modal-icon {
                background: linear-gradient(145deg, var(--dm-bg-tertiary) 0%, var(--dm-bg-secondary) 100%);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3), 0 8px 24px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            }
            .w3eden:not(.light-mode) .close svg {
                color: var(--dm-text-muted);
            }
            .w3eden:not(.light-mode) .close:hover svg {
                color: #f87171;
            }
        }

    </style>




    <?php do_action("wpdm_modal_iframe_head"); ?>
</head>
<?php
// Build body classes based on color scheme setting
$body_classes = 'w3eden';
if ($color_scheme === 'light') {
    $body_classes .= ' light-mode';
} elseif ($color_scheme === 'dark') {
    $body_classes .= ' dark-mode';
}
// 'system' = no extra class, follows OS preference via @media (prefers-color-scheme: dark)
?>
<body class="<?php echo esc_attr($body_classes); ?>" style="background: transparent">

<div class="modal fade" id="wpdm-locks" tabindex="-1" role="dialog" aria-labelledby="wpdm-optinmagicLabel">
    <div class="modal-dialog modal-dialog-centered" role="document" style="width: <?php echo $terms_lock === 1?395:365; ?>px;max-width: calc(100% - 20px);margin: 0 auto;">
        <div class="modal-content">
            <div class="modal-icon">
                <?php if(has_post_thumbnail($ID)) echo get_the_post_thumbnail($ID, 'thumbnail'); else echo WPDM()->package::icon($ID, true, 'p-2'); ?>
            </div>
            <div class="text-center mt-3 mb-3">
                <button type="button" class="close btn btn-link p-0" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">
                        <svg style="width: 24px" id="Outlined" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><title/><g id="Fill"><path d="M16,2A14,14,0,1,0,30,16,14,14,0,0,0,16,2Zm0,26A12,12,0,1,1,28,16,12,12,0,0,1,16,28Z"/><polygon points="19.54 11.05 16 14.59 12.46 11.05 11.05 12.46 14.59 16 11.05 19.54 12.46 20.95 16 17.41 19.54 20.95 20.95 19.54 17.41 16 20.95 12.46 19.54 11.05"/></g></svg>
                    </span></button>
                <h4 class="d-block"><?php echo ($base_price > 0)? __('Buy','download-manager'): __('Download','download-manager'); ?></h4>
                <div style="letter-spacing: 1px;font-weight: 400;margin-top: 5px" class="color-purple d-block"><?php echo get_the_title($ID); ?></div>
            </div>
            <div class="modal-body" id="wpdm-lock-options">
                <?php
                $extras = isset($_REQUEST['__wpdmfl']) ? ['ind' => wpdm_query_var('__wpdmfl', 'txt')] : [];
                echo WPDM()->package->downloadLink(wpdm_query_var('__wpdmlo', 'int'), 1, $extras);
                ?>
            </div>

        </div>

    </div>
    <?php

    ?>
</div>

<script>
    jQuery(function ($) {

        $('a').each(function () {
            if($(this).attr('href') !== '#')
                $(this).attr('target', '_blank');
        });

        /*$('body').on('click','a', function () {
            if($(this).attr('href') !== '#')
                $(this).attr('target', '_parent');
        });*/

        /*$('body').on('click','a[data-downloadurl]', function () {
            window.parent.location.href = $(this).data('downloadurl');
        });*/

        $('#wpdm-locks').on('hidden.bs.modal', function (e) {
            var parentWindow = document.createElement("a");
            parentWindow.href = document.referrer.toString();
            if(parentWindow.hostname === window.location.hostname)
                window.parent.hideLockFrame();
            else
                window.parent.postMessage({'task': 'hideiframe'}, "*");
        });

        showModal();
    });

    function showModal() {
        jQuery('#wpdm-locks').modal('show');
    }

</script>
<div style="display: none">
    <?php  if($form_lock === 1 || $base_price > 0) wp_footer(); ?>
    <?php do_action("wpdm_modal_iframe_footer"); ?>
</div>
</body>
</html>
