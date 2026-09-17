<?php

use WPDM\AssetManager\AssetManager;

if(!defined('ABSPATH')) die('Error!');
global $current_user;
$root = AssetManager::root();
//$items = file_exists($root)?glob($root.'*', GLOB_ONLYDIR):array();
if(is_admin()){
    ?>

    <div>

    <?php
}
?>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Overpass+Mono&subset=latin");
        #wpcontent{
            padding-left: 0 !important;
        }
        #mainfmarea{
            width: calc(100% - 160px);
            position: fixed;
            z-index: 99;
        }
        #wpdm-dashboard-content{
            overflow: visible;
        }
        #rename{
            z-index: 999999999999;
            padding-top: 150px;
            overflow: hidden;
        }

        /* New folder / new file panel. Lives inside #mainfmarea so it always stacks above the file manager, including in browser full-screen. */
        .wpdmam-create{
            position: absolute;
            z-index: 1000;
            width: 300px;
            max-width: calc(100% - 32px);
            padding: 16px;
            text-align: left;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 12px 32px rgba(15, 23, 42, 0.14);
        }
        .wpdmam-create[hidden]{
            display: none;
        }
        .wpdmam-create__head{
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }
        .wpdmam-create__icon{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 32px;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            font-size: 14px;
            color: var(--color-primary, #4f46e5);
            background: rgba(var(--color-primary-rgb, 79, 70, 229), 0.1);
        }
        .wpdmam-create__title{
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.3;
            color: #0f172a;
        }
        .wpdmam-create__label{
            display: block;
            margin: 0 0 6px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
        }
        .w3eden .wpdmam-create__input.form-control{
            height: 36px;
            margin: 0;
            padding: 0 10px;
            font-size: 13px;
        }
        .w3eden .wpdmam-create__input.form-control[aria-invalid="true"],
        .w3eden .wpdmam-create__input.form-control[aria-invalid="true"]:focus{
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
        }
        .wpdmam-create__error{
            margin: 8px 0 0;
            font-size: 12px;
            line-height: 1.4;
            color: #b91c1c;
        }
        .wpdmam-create__error:empty{
            margin: 0;
        }
        .wpdmam-create__actions{
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 16px;
        }

        /* Upload tray */
        /* Scoped to #upfile so these rules outrank the base .w3eden p and .w3eden button styles. */
        #upfile.wpdmam-upload{
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 100001;
            width: 360px;
            max-width: calc(100vw - 32px);
            overflow: hidden;
            text-align: left;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 16px 40px rgba(15, 23, 42, 0.16);
        }
        #upfile .wpdmam-upload__head{
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 12px 10px 12px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        #upfile .wpdmam-upload__heading{
            flex: 1 1 auto;
            min-width: 0;
        }
        #upfile .wpdmam-upload__title{
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.3;
            color: #0f172a;
        }
        #upfile .wpdmam-upload__summary{
            margin: 2px 0 0;
            font-size: 12px;
            line-height: 1.4;
            color: #64748b;
        }
        #upfile .wpdmam-upload__text-btn,
        #upfile .wpdmam-upload__browse{
            padding: 0;
            border: 0;
            background: none;
            font: inherit;
            font-weight: 600;
            color: var(--color-primary, #4f46e5);
            cursor: pointer;
        }
        #upfile .wpdmam-upload__text-btn{
            flex: 0 0 auto;
            padding: 6px 8px;
            font-size: 12px;
            border-radius: 6px;
        }
        #upfile .wpdmam-upload__text-btn:hover{
            background: rgba(var(--color-primary-rgb, 79, 70, 229), 0.08);
        }
        #upfile .wpdmam-upload__text-btn[hidden]{
            display: none;
        }
        #upfile .wpdmam-upload__browse:hover{
            text-decoration: underline;
        }
        #upfile .wpdmam-upload__close{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 28px;
            width: 28px;
            height: 28px;
            padding: 0;
            border: 0;
            border-radius: 6px;
            background: none;
            font-size: 14px;
            color: #64748b;
            cursor: pointer;
        }
        #upfile .wpdmam-upload__close:hover{
            background: #f1f5f9;
            color: #0f172a;
        }
        #upfile .wpdmam-upload__close:focus-visible,
        #upfile .wpdmam-upload__text-btn:focus-visible,
        #upfile .wpdmam-upload__browse:focus-visible{
            outline: 2px solid var(--color-primary, #4f46e5);
            outline-offset: 2px;
        }
        #upfile .wpdmam-upload__body{
            padding: 12px 16px 14px;
        }
        /* The border needs !important: admin-styles.css gives every #drag-drop-area an !important green border. */
        #upfile #drag-drop-area{
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: auto;
            min-height: 132px;
            margin: 0;
            padding: 20px 16px;
            text-align: center;
            background: #f8fafc;
            border: 1.5px dashed #cbd5e1 !important;
            border-radius: 10px;
            transition: border-color 150ms ease, background-color 150ms ease;
        }
        #upfile .drag-over #drag-drop-area{
            background: rgba(var(--color-primary-rgb, 79, 70, 229), 0.06);
            border-color: var(--color-primary, #4f46e5) !important;
        }
        #upfile .wpdmam-upload__drop-icon{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            margin-bottom: 8px;
            border-radius: 10px;
            font-size: 16px;
            color: var(--color-primary, #4f46e5);
            background: rgba(var(--color-primary-rgb, 79, 70, 229), 0.1);
        }
        #upfile .wpdmam-upload__drop-title{
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
        }
        #upfile .wpdmam-upload__drop-hint{
            margin: 2px 0 0;
            font-size: 12px;
            color: #64748b;
        }
        #upfile .wpdmam-upload__limit{
            margin: 8px 0 0;
            font-size: 11px;
            color: #64748b;
        }
        #upfile .wpdmam-upload__queue{
            max-height: 264px;
            overflow-y: auto;
        }
        #upfile .wpdmam-upload__queue:empty{
            display: none;
        }
        #upfile .wpdmam-upload__item{
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 16px;
            border-top: 1px solid #f1f5f9;
        }
        #upfile .wpdmam-upload__item-icon{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 28px;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            font-size: 12px;
            color: #64748b;
            background: #f1f5f9;
        }
        #upfile .wpdmam-upload__item[data-state="done"] .wpdmam-upload__item-icon{
            color: #047857;
            background: #ecfdf5;
        }
        #upfile .wpdmam-upload__item[data-state="failed"] .wpdmam-upload__item-icon{
            color: #b91c1c;
            background: #fef2f2;
        }
        #upfile .wpdmam-upload__item-main{
            flex: 1 1 auto;
            min-width: 0;
        }
        #upfile .wpdmam-upload__item-row{
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 8px;
        }
        #upfile .wpdmam-upload__item-name{
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            font-size: 13px;
            font-weight: 500;
            color: #0f172a;
        }
        #upfile .wpdmam-upload__item-meta{
            flex: 0 0 auto;
            font-size: 11px;
            color: #64748b;
            font-variant-numeric: tabular-nums;
        }
        #upfile .wpdmam-upload__bar{
            height: 4px;
            margin-top: 7px;
            overflow: hidden;
            border-radius: 4px;
            background: #f1f5f9;
        }
        #upfile .wpdmam-upload__bar-fill{
            display: block;
            width: 0;
            height: 100%;
            border-radius: inherit;
            background: var(--color-primary, #4f46e5);
            transition: width 200ms ease;
        }
        #upfile .wpdmam-upload__item[data-state="done"] .wpdmam-upload__bar-fill{
            background: #10b981;
        }
        #upfile .wpdmam-upload__item[data-state="failed"] .wpdmam-upload__bar-fill{
            background: #ef4444;
        }
        #upfile .wpdmam-upload__item-status{
            margin: 4px 0 0;
            font-size: 11px;
            line-height: 1.4;
            color: #64748b;
        }
        #upfile .wpdmam-upload__item-status:empty{
            display: none;
        }
        #upfile .wpdmam-upload__item[data-state="done"] .wpdmam-upload__item-status{
            color: #047857;
        }
        #upfile .wpdmam-upload__item[data-state="failed"] .wpdmam-upload__item-status{
            color: #b91c1c;
        }
        @media (prefers-reduced-motion: reduce){
            #upfile #drag-drop-area,
            #upfile .wpdmam-upload__bar-fill{
                transition: none;
            }
        }
        #breadcrumb,
        .wpdm-file-locator,
        .wpdm-dir-locator a.explore-dir{
            font-family: 'Overpass Mono', sans-serif !important;
        }
        .well-file{
            font-family: Montserrat,sans-serif;
            font-size: 12px;
            line-height: 40px;
        }
        .progress:after{
            position: absolute;
            color: rgba(0,0,0,0.3);
            width: 100%;
            text-align: center;
            left: 0;
            font-family: Montserrat,sans-serif;
            font-size: 12px;
            text-transform: uppercase;
        }
        .panel-file{
            font-size: 12px;
        }
        .panel-file .media-body {
            line-height: normal;
            display: inline-block;
        }
        .item_label{
            margin-top: 5px;
            font-size: 9pt;
            line-height: 1;
            display: block;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: #5e6a6d;
        }

        .w3eden .well .btn-sm{
            padding: 8px 16px;
        }
        .panel-file .panel-footer{
            text-align: center;
        }
        .modal *{
            font-size: 10pt;
        }
        .w3eden .modal-header{
            font-size: 10pt;
            line-height: normal;
            font-family: var(--wpdm-font);
            background: #f5f5f5;
            border-radius: 10px 10px 0 0 !important;
        }
        .w3eden .modal-footer{
            border: 0 !important;
            border-radius: 0 0 10px 10px !important;
            padding-top: 0;
        }
        .w3eden .modal-content{
            border-radius: 10px !important;
            box-shadow: 0 0 25px rgba(0,0, 0, 0.2);
        }
        #drag-drop-area{
            border: 0.2rem dashed #28c83599 !important;
            border-radius: 5px;
            font-family: "Overpass Mono", monospace;
            color: rgba(0,0,0,0.2);
            font-size: 14pt;
        }
        #drag-drop-area .btn{
            font-family: "Overpass Mono", monospace;
            border-radius: 500px;
        }

        #breadcrumb{
            margin: 10px 0 10px;
            font-size: 9pt;
            color: #888888;
        }

        .w3eden .panel.wpdm-file-manager-panel{
            border: 0 !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            margin-bottom: 0 !important;
        }
        .w3eden .panel.wpdm-file-manager-panel .panel-heading{
            /*background: linear-gradient(to bottom, rgb(253, 255, 255) 0%,rgb(240, 243, 243) 100%);*/
            background: rgba(240, 243, 243, 0.3);
            border-top: 1px solid #e7eaea !important;
            border-bottom: 1px solid #e7eaea !important;
            /*box-shadow: 2px 0 5px rgba(18, 150, 201, 0.09);*/

        }
        .panel-heading,
        #breadcrumb a{
            color: #aaaaaa;
            text-transform: unset !important;
            font-weight: 400;
        }
        #breadcrumb .fa{
            color: #8896aa;
            display: inline-block;
            margin-top: -2px !important;
            vertical-align: middle;
            margin-right: 5px;
            margin-left: 5px;
        }
        .panel-file .panel-body{
            height: 130px;
            text-align: center;
        }
        #scandir .file-row,
        #scandir .dir-row{
            padding: 10px 10px 10px 0;
            border-bottom: 1px solid #eeeeee;
            -webkit-transition: all ease-in-out 300ms;
            -moz-transition: all ease-in-out 300ms;
            -ms-transition: all ease-in-out 300ms;
            -o-transition: all ease-in-out 300ms;
            transition: all ease-in-out 300ms;
        }
        #scandir .file-row:hover, #scandir .dir-row:hover {
            background: rgba(0,0,0,0.02);
            -webkit-transition: all ease-in-out 300ms;
            -moz-transition: all ease-in-out 300ms;
            -ms-transition: all ease-in-out 300ms;
            -o-transition: all ease-in-out 300ms;
            transition: all ease-in-out 300ms;

        }
        img.icon{
            width: 38px;
            margin-right: 5px;
            padding: 0 !important;
        }
        #scandir img{
            box-shadow: none;
            margin-bottom: 0;
        }
        .action-btns-ctrl{
            line-height: 40px;
            width: 32px;
            text-align: center;
            outline: none !important;
            margin-right: 5px;
        }
        .action-btns {
            opacity: 0 !important;
            position: absolute !important;
            z-index: -99;
            right: 50px;
            white-space: nowrap;
            -webkit-transition: all ease-in-out 400ms;
            -moz-transition: all ease-in-out 400ms;
            -ms-transition: all ease-in-out 400ms;
            -o-transition: all ease-in-out 400ms;
            transition: all ease-in-out 400ms;
            line-height: 40px;
        }
        .action-btns.action-btns-show{
            opacity: 1 !important;
            z-index: 999;
        }

        .action-btns .btn.btn-xs {
            width: 32px;
            height: 28px;
            line-height: 26px;
            padding: 0;
            font-size: 8pt;
            text-align: center;
            border-radius: 5px;
        }

        .c-pointer{
            color: #1272d2;
            transition: all ease-in-out 300ms;
        }
        .c-pointer:hover{
            color: #2c92fa;
        }
        .d-inline-block{
            display: inline-block !important;
        }

        img.fm-folder{
            box-shadow: none;
            width: 16px;
            display: inline-block;
            vertical-align: middle;
        }
        .wpdmfm-folder-tree{
            padding: 0;
            margin: 0;
        }

        .wpdmfm-folder-tree li{
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 12px;
            color: #757f8d;
            white-space: nowrap;
            line-height: 20px;
        }
        .wpdmfm-folder-tree li > .handle{
            background: url("<?php echo WPDM_BASE_URL.'assets/images/folder.svg' ?>") left center no-repeat;
            background-size: 16px;
            display: inline-block;
            width: 20px;
            height: 20px;
            float: left;
            cursor: pointer;
        }
        .wpdmfm-folder-tree ul{
            margin-left: 18px !important;
            padding-left: 0;
        }
        .wpdmfm-folder-tree li a:visited,
        .wpdmfm-folder-tree li a:hover,
        .wpdmfm-folder-tree li a{
            color: #657989;
            text-decoration:  none;
            font-family: var(--wpdm-font);
            cursor: pointer;
        }
        .wpdmfm-folder-tree li a:hover{
            color: #6075c8;
        }
        .wpdmfm-folder-tree li.expanded > .handle{
            background: url("<?php echo WPDM_BASE_URL.'assets/images/folder-o.svg' ?>") left 2px no-repeat;
            background-size: 16px;
        }
        .wpdmfm-folder-tree li.busy > .handle{
            background: url("<?php echo WPDM_BASE_URL.'assets/images/loader.svg' ?>") left no-repeat;
            background-size: 16px;
        }
        .wpdm-dir-locator,
        .wpdm-file-locator{
            padding: 15px !important;
        }
        .wpdm-dir-locator{
            background: rgba(240, 243, 243, 0.2);
            border-right: 1px solid #e7eaea;
        }

        [data-simplebar]{
            height: 500px;
            overflow: auto;
            min-width: 100%;
        }
        .wpdm-dir-locator .simplebar-content{
            overflow-x: auto !important;
        }
        .wpdm-file-locator [data-simplebar]{
            overflow-x: hidden !important;
        }

        #wpbody-content{
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        #mainfmc{
            overflow: hidden;
        }
        .w3eden #mainfmc .btn{
            text-transform: capitalize !important;
            font-family: var(--wpdm-font) !important;
            font-weight: 400 !important;
        }

        .w3eden .btn.btn-simple:not(:hover){
            color: #7886a2;
            border-color: #c9cfdb;
        }

        .w3eden  .btn.btn-simple:hover { background-color: rgba(201, 207, 219, 0.2) !important; }
        .w3eden  .btn.btn-simple:hover:not(.btn-danger):not(.btn-info):not(.btn-primary):not(.btn-success){ color: #3c5382; }
        .w3eden  .btn.btn-simple.btn-success:hover { background-color: rgba(var(--color-success-rgb), 0.1) !important; }
        .w3eden  .btn.btn-simple.btn-primary:hover { background-color: rgba(var(--color-primary-rgb), 0.1) !important; }
        .w3eden  .btn.btn-simple.btn-danger:hover { background-color: rgba(var(--color-danger-rgb), 0.1) !important; }
        .w3eden  .btn.btn-simple.btn-info:hover { background-color: rgba(var(--color-info-rgb), 0.1) !important; }
        .w3eden  .btn.btn-simple.btn-warning:hover { background-color: rgba(var(--color-warning-rgb), 0.1) !important; color: var(--color-warning) !important; }

        .w3eden  .btn.btn-simple:active {
            box-shadow: inset 0 0 4px rgba(0, 0, 0, 0.13);
        }

        #wpdmeditor{
            position: absolute;
            left: 0;
            top: 0;
            border: 0;
            width: 100%;
            height: calc(100vh - 150px);
            z-index: 9;
            box-shadow: none;
            border-radius: 0;
        }
        #wpdmeditor .panel-heading{
            border-top: 0 !important;
        }
        #wpdmeditor .panel-heading,
        #wpdmeditor .panel-footer{
            border-radius: 0;
        }
        #wpdmeditor #filecontent_alt,
        #wpdmeditor #filecontent{
            height: calc(100% - 40px);
            width: 100%;
            padding: 30px;
            overflow: auto;
            font-family: "Overpass Mono", monospace;
            border: 0;
            background: transparent;
        }
        .CodeMirror.cm-s-default.CodeMirror-wrap{
            height: calc(100% - 94px);
        }
        #wpdmeditor #filecontent_alt{
            text-align: center;
        }
        #wpdmeditor #filecontent_alt img{
            max-width: 100%;
        }
        #filewin{
            -webkit-transition: all ease-in-out 400ms;
            -moz-transition: all ease-in-out 400ms;
            -ms-transition: all ease-in-out 400ms;
            -o-transition: all ease-in-out 400ms;
            transition: all ease-in-out 400ms;
        }

        .w3eden #__file_settings_tabs.nav.nav-tabs > li > a{
            box-shadow: none !important;
            border: 1px solid #e8e8e8;
            padding: 8px 16px;
            font-size: 10px;
            font-weight: 400 !important;
        }
        .w3eden #__file_settings_tabs.nav.nav-tabs > li.active > a{
            border-bottom: 1px solid #ffffff;
        }
        .w3eden #__file_settings_tabs.nav.nav-tabs > li:not(.active) > a{
            background: #fafafa;
        }

        .w3eden #__asset_settings .form-control.form-control-lg{
            border: 0;
            background: #ffffff; text-align: center; font-family: "Overpass Mono", monospace;font-size: 11pt !important;box-shadow: none !important;
        }
        .w3eden #__asset_settings .panel-default{
            border: 1px solid #e6e6e6;
        }
        .w3eden #__asset_settings .panel-default .panel-heading {
            border-bottom: 1px solid #e6e6e6;
            background: #fafafa;
        }
        .w3eden #__asset_settings .panel-default .panel-footer {
            border-top: 1px solid #e6e6e6;
            background: #fafafa;
        }

        .allow-roles label,
        .w3eden #__asset_settings .tab-content *,
        .w3eden #__asset_settings .tab-content{
            font-size: 11px;
        }
        .allow-roles label{
            font-weight: 400;
            line-height: 16px;
        }
        .allow-roles label input{
            margin: 0 5px !important;
        }


        .w3eden .modal-header .close.pull-right {
            height: 16px;
            line-height: 16px;
        }

        #__asset_links .asset-link{
            margin: 5px 0;
            border: 1px solid #e8e8e8;
            border-radius: 3px !important;
        }
        #__asset_links .asset-link .form-control{
            background: #ffffff;
            border: 0 !important;
            box-shadow: none !important;
        }
        #__asset_links .asset-link .input-group-addon{
            border: 0 !important;
            background: #ffffff;
            padding-right: 0;
            color: var(--color-info);
        }
        #__asset_links .asset-link .btn{
            border: 0 !important;
            background: #ffffff;
            color: var(--color-success);
            border-left: 1px solid #e8e8e8 !important;
            z-index: 2;
        }
        #__asset_links .asset-link .btn .fa-trash.color-danger{
            color: var(--color-danger) !important;
        }

        .w3eden #__asset_links .asset-link.input-group-lg > .form-control, .w3eden #__asset_links .asset-link.input-group-lg > .input-group-addon, .w3eden #__asset_links .asset-link.input-group-lg > .input-group-btn > .btn {
            height: 36px;
            padding: 8px 12px;
        }

        .wp-video{ margin: 0 auto !important; width: 100% !important; }
        .wp-video-shortcode,
        .wp-audio-shortcode {
            margin: 15px 15px 10px 15px;
            width: calc(100% - 30px) !important;
        }
        .wp-video-shortcode{
            height: auto !important;
        }
        button.btn-unzip{
            display: none !important;
        }
        button.btn-unzip.application_zip{
            display: inline-block !important;
        }
        .modal-backdrop.in{
            display: none;
        }
        .w3eden .modal.fade.in{
            background: rgba(0,0,0,0.3);
        }
        #__asset_settings {
            height: calc(100vh - 190px);
            overflow: auto;
        }
        [data-simplebar]{
            height: calc(100vh - 175px);
        }
        #cogwin > .panel{
            height: calc(100vh - 146px);
            box-shadow: none;
        }
        .ss-wrapper {
            overflow: hidden;
            width: 100%;
            height: 100%;
            position: relative;
            z-index: 1;
            float: left;
        }

        .ss-content {
            height: 100%;
            width: calc(100% + 18px);
            padding: 0 0 0 0;
            position: relative;
            overflow-x: hidden !important;
            overflow-y: scroll;
            box-sizing: border-box;
        }

        .ss-content.rtl {
            width: calc(100% + 18px);
            right: auto;
        }

        .ss-scroll {
            position: relative;
            background: rgba(0, 0, 0, 0.1);
            width: 9px;
            border-radius: 4px;
            top: 0;
            z-index: 2;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.25s linear;
        }

        .ss-hidden {
            display: none;
        }

        .ss-container:hover .ss-scroll,
        .ss-container:active .ss-scroll {
            opacity: 1;
        }

        .ss-grabbed {
            -o-user-select: none;
            -ms-user-select: none;
            -moz-user-select: none;
            -webkit-user-select: none;
            user-select: none;
        }
    </style>

<div class="w3eden" id="mainfmarea">
    <?php do_action("wpdm_frontend_filemanager_top", ""); ?>
    <div id="loadingfm" class="blockui" style="position: fixed;width: 100%;height: 100%;z-index: 99"></div>

    <div id="wpdmam-create" class="wpdmam-create" role="dialog" aria-labelledby="wpdmam-create-title" hidden>
        <form id="wpdmam-create-form" novalidate>
            <div class="wpdmam-create__head">
                <span class="wpdmam-create__icon" aria-hidden="true"><i class="fa fa-folder-open" data-wpdmam-create-icon></i></span>
                <p class="wpdmam-create__title" id="wpdmam-create-title"></p>
            </div>
            <label class="wpdmam-create__label" for="wpdmam-create-name"></label>
            <input type="text" id="wpdmam-create-name" class="form-control wpdmam-create__input" autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="255" aria-describedby="wpdmam-create-error">
            <p class="wpdmam-create__error" id="wpdmam-create-error" role="alert"></p>
            <div class="wpdmam-create__actions">
                <button type="button" class="btn btn-simple btn-sm" data-wpdmam-create-cancel><?php esc_html_e( "Cancel", "download-manager" ); ?></button>
                <button type="submit" class="btn btn-primary btn-sm" id="wpdmam-create-submit"></button>
            </div>
        </form>
    </div>
    <div id="mainfmc" class="panel panel-default wpdm-file-manager-panel" style="display: none;">
        <div class="panel-body">
            <div class="media well-sm well-file" style="margin: 0;padding: 0">
                <div class="pull-right">
                    <span id="__file_search"><input  v-on:keyup.enter="searchAsset.execute()" type="search" v-model="keyword" placeholder="Search File..." class="form-control input-sm" style="font-family: 'Overpass Mono', monospace;width: 140px;display: inline-block;padding: 0 10px;min-height: 20px;height: 27px;"></span>
                    <button class="btn btn-primary btn-simple btn-sm ttip" title="Reload" id="reload"><i class="fa fa-sync"></i></button>
                    <div class="btn-group">
                        <button class="btn btn-simple btn-sm" type="button" data-wpdmam-create="folder" aria-haspopup="dialog" aria-expanded="false" aria-controls="wpdmam-create"><i class="fa fa-folder-open" aria-hidden="true"></i> <?php esc_html_e( "New Folder", "download-manager" ); ?></button>
                        <button class="btn btn-simple btn-sm" type="button" data-wpdmam-create="file" aria-haspopup="dialog" aria-expanded="false" aria-controls="wpdmam-create"><i class="far fa-file" aria-hidden="true"></i> <?php esc_html_e( "New File", "download-manager" ); ?></button>
                        <button class="btn btn-simple btn-sm" id="btn-upload-file" type="button" aria-expanded="false" aria-controls="upfile"><i class="fa fa-cloud-upload-alt" aria-hidden="true"></i> <?php echo __( "Upload File", "download-manager" ) ?></button>
                    </div>
                    <button class="btn btn-info btn-simple btn-sm ttip" id="btn-paste" disabled="disabled" title="Paste"><i class="fa fa-clipboard"></i></button>
                    <button class="btn btn-simple btn-sm ttip" title="Full Screen"  onclick="openFullscreen('mainfmarea');"><i class="fa fa-expand-arrows-alt"></i></button>
                </div>
                <h3 style="display: inline-block;font-size: 14pt;letter-spacing: 0.5px;font-weight: 600;font-family: var(--wpdm-font)">
                   <img src="<?php echo WPDM_BASE_URL ?>assets/images/asset-manager.svg" style="height: 20px;margin-right: 5px" /> <?php echo __( "Digital Asset Manager", "download-manager" ) ?>
                </h3>
                <?php /* if(!current_user_can('manage_options')){ ?>
                <div class="media-body">
                    <div class="progress text-center" style="margin: 2px 0 0 5px;height: 27px;line-height: 27px;border-radius;border-radius: 2px;font-family: 'Overpass Mono', sans-serif !important;">
                        Used: <span id="disklimit"><i class="fa fa-sun fa-spin"></i></span> | Limit: <?php echo wpdm_user_space_limit(); ?> MB
                        <div title="15% Used" class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%;line-height: 31px;font-size: 13px;overflow: visible">
                        </div>
                    </div>
                </div>
                <?php } */ ?>
            </div>
        </div>
        <div class="panel-heading" style="border-radius: 0;border-top: 1px solid #dddddd">
            <div id="ldn" style="float:right;font-size: 9pt;margin-top: 10px;display: none" class="text-danger"><i class="fa fa-sun fa-spin"></i> <?php echo  esc_attr__( 'Loading', "download-manager" ); ?>...</div>
            <div v-if="total_pages > 1" id="__asset_pages" style="margin: 0;float: right;font-weight: 400;font-family: 'Overpass Mono', sans-serif !important;white-space: nowrap">
                <div style="float:left;">
                    <div class="c-pointer d-inline-block" v-on:click="prevPage()"><i v-if="current_page > 1" class="fa fa-arrow-alt-circle-left"></i></div> <span class="text-muted">On Page</span> <strong>{{current_page}}</strong> <span class="text-muted">of total</span> <strong>{{total_pages}}</strong> <div class="c-pointer d-inline-block" v-if="current_page < total_pages" v-on:click="nextPage()"><i class="fa fa-arrow-alt-circle-right"></i></div>
                </div>
                <div style="display: inline-block;margin-left: 10px">
                    <div class="input-group input-group-xs" style="width: 90px;">
                        <input type="number" @input="event => goto_page = event.target.value" :value="current_page" :max="total_pages" min="1" placeholder="Page" class="form-control" style="min-height: 16px; line-height: 20px; height: 20px; padding: 0px; font-size: 10px;text-align: center;font-family: 'Overpass Mono', monospace;">
                        <div class="input-group-btn"><button type="button" v-on:click="gotoPage()" class="btn btn-secondary btn-xs">GO</button></div>
                    </div>
                </div>
            </div>
            <div id="breadcrumb" style="margin: 0"></div>
        </div>
        <div class="panel-body-c" id="wpdmfm_explorer">

            <div class="row" style="margin: 0">
                <?php do_action("wpdm_frontend_filemanager_after_breadcrumb", ""); ?>
                <div class="col-md-3 wpdm-dir-locator">
                    <div data-simplebar ss-container>
                        <ul class="wpdmfm-folder-tree" id="wpdmfm-folder-tree">
                            <li data-path="" id="<?php echo md5('home'); ?>" class="expand-dir"><i class="fa fa-hdd color-purple"></i> <a class="explore-dir" href="#" data-path=""> <?php echo  esc_attr__( 'Home', "download-manager" ); ?></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-9 wpdm-file-locator" id="filewin">
                    <div id="wpdmeditor" class="panel panel-default blockui" style="display: none;">
                        <div class="panel-heading"><div class="pull-right"><a id="close-editor" href="#"><i class="fa fa-times-circle text-muted"></i></a></div><span id="wpdmefn"></span></div>
                        <textarea id="filecontent"></textarea>
                        <div id="filecontent_alt" style="display: none"></div>
                        <div class="panel-footer text-right">
                            <button type="button" id="savefile" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo __( "Save Changes", "download-manager" ) ?></button>
                        </div>
                    </div>
                    <div data-simplebar ss-container>
                        <div id="scandir">
                        </div>
                    </div>
                </div>
                <div class="col-md-3" id="cogwin" style="display: none;">
                    <div class="panel panel-default blockui" style="left:0;width: 100%;border: 0;border-left: 1px solid #eee;border-radius: 0;position: absolute;margin: 0">
                        <div class="panel-heading" style="border-radius: 0;border-top: 0 !important;">
                            <a href="#" class="pull-right" id="close-settings"><i class="fas fa-times-circle text-muted"></i></a>
                            <?php echo  esc_attr__( 'Asset Settings', "download-manager" ); ?>
                        </div>
                        <div class="panel-body" id="__asset_settings">
                            <div class="thumbnail text-center" v-html="asset.preview"></div>
                            <div class="form-group">
                                <div class="media" style="border:1px solid #e8e8e8;border-radius: 3px;padding: 10px;">
                                    <div class="pull-right">
                                        <button class="btn btn-xs btn-secondary btn-rename" type="button" title="<?php echo  esc_attr__( 'Rename', "download-manager" ); ?>" data-toggle="modal" data-target="#rename"><i class="fas fa-i-cursor"></i> <?php echo  esc_attr__( 'Rename', "download-manager" ); ?></button>
                                    </div>
                                    <div class="media-body">
                                        &nbsp;{{ asset.name }}
                                    </div>
                                </div>

                                <div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" id="rename">
                                    <div class="modal-dialog modal-sm" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <strong class="modal-title" id="myModalLabel"><?php echo  esc_attr__( 'Rename', "download-manager" ); ?></strong>
                                                <button type="button" class="close pull-right p-0" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i></span></button>
                                            </div>
                                            <div id="upload" class="modal-body">
                                                <input type="text" v-bind:value="asset.name" placeholder="<?php echo  esc_attr__( 'New Name', "download-manager" ); ?>" id="newname" class="form-control form-control-lg" style="margin: 0px;border: 1px dashed #d4d4d4;">
                                            </div>
                                            <div class="modal-footer text-right">
                                                <button type="button" id="renamenow" class="btn btn-info"  :data-assetid="asset.ID" :data-oldname="asset.name" :data-path="asset.path"><?php echo  esc_attr__( 'Rename', "download-manager" ); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div>

                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" id="__file_settings_tabs" role="tablist" style="margin-left: -15px;padding-left: 15px;margin-right: -15px;border-bottom: 1px solid #e8e8e8;">
                                    <li role="presentation" class="active"><a href="#share" aria-controls="share" role="tab" data-toggle="tab"><?php echo  esc_attr__( 'Share', "download-manager" ); ?></a></li>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content">
                                    <!-- div role="tabpanel" class="tab-pane" id="activity">...</div -->
                                    <div role="tabpanel" class="tab-pane active" id="share">
                                        <div class="panel panel-default" style="margin-top: 15px;margin-bottom: 0;" v-if="asset.type === 'file'">
                                            <div class="input-group" style="border: 0 !important;">
                                                <input type="text" readonly="readonly" id="sharecode" class="form-control form-control-lg" v-bind:value="asset.sharecode" />
                                                <div onclick="WPDM.copy('sharecode');" class="input-group-addon ttip" title="<?php echo __( "Copy Shortcode", "download-manager" ) ?>" style="border: 0 !important;background: #ffffff;cursor: pointer;"><i class="fa fa-copy color-purple"></i></div>
                                            </div>
                                            <div class="panel-footer text-center">
                                                <?php echo  esc_attr__( 'Use the shortcode to embed this asset on any page or post', "download-manager" ); ?>
                                            </div>
                                        </div>




                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <?php do_action("wpdm_frontend_filemanager_bottom", ""); ?>
            </div>

        </div>
    </div>



    <div id="dirTPL" style="display: none">
        <div class="dir-row">
            <div class="row panel-file panel-folder">

                <div class="col-md-8">
                    <div class="media-folder media" data-id="{{dirid}}" data-path="{{path}}" style="cursor: pointer">
                        <img class="icon pull-left" src="<?php echo plugins_url('download-manager/assets/file-type-icons/folder.png'); ?>" />
                        <div class="dir-info"><div class="item_label" title="{{item}}">{{item_label}}</div><small class="color-purple">{{note}}</small></div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
	                <?php if(current_user_can('WPDM_ADMIN_CAP') || 1) { ?>
                    <div class="action-btns" id="action-btns-{{id}}">
                        <button class="btn btn-xs btn-simple btn-primary btn-settings" type="button" title="Settings" data-oldname="{{item}}" data-path="{{path}}"><i class="far fa-sun"></i></button>
                        <button class="btn btn-xs btn-simple btn-success btn-zip" type="button" title="Zip" data-dirname="{{item}}" data-dirpath="{{path}}"><i class="fa fa-archive"></i></button>
                        <button class="btn btn-xs btn-info btn-simple" data-item="{{item}}" title="Cut" type="button"><i class="fa fa-cut"></i></button>
                        <button class="btn btn-xs btn-primary btn-simple btn-copy" data-item="{{item}}" title="Copy" type="button"><i class="fa fa-copy"></i></button>

                        <button class="btn btn-xs btn-danger btn-simple btn-delete" type="button" title="Delete" data-path="{{path}}"><i class="fas fa-trash"></i></button>
                    </div>
                    <a href="#" class="text-muted pull-right action-btns-ctrl" data-target="#action-btns-{{id}}"><i class="fas fa-ellipsis-v"></i></a>
	                <?php } ?>
                </div>

            </div>
        </div>
    </div>

    <div id="fileTPL" style="display: none">

            <div class="file-row">
                <div class="row panel-file file-tpl">
                    <div class="col-md-8  text-left btn-open-file c-pointer" data-filetype="{{contenttype}}" data-path="{{path}}">

                        <div class="file-info media">
                            <img class="icon pull-left" src="{{icon}}" />
                            <div class="dir-info"><div class="item_label" title="{{item}}">{{item_label}}</div><small class="color-purple">{{note}}</small></div>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
	                    <?php if(current_user_can('WPDM_ADMIN_CAP') || 1) { ?>
                        <div class="action-btns" id="action-btns-{{id}}">
                            <button class="btn btn-xs btn-simple btn-primary btn-settings" type="button" title="Settings" data-oldname="{{item}}" data-path="{{path}}"><i class="far fa-sun"></i></button>
                            <button class="btn btn-xs btn-simple btn-warning btn-unzip {{ext}}"  type="button" title="UnZip" data-dirname="{{item}}" data-dirpath="{{path}}"><i class="fas fa-box-open"></i></button>
                            <button class="btn btn-xs btn-simple btn-cut" data-item="{{item}}" title="Cut" type="button"><i class="fa fa-cut"></i></button>
                            <button class="btn btn-xs btn-simple btn-copy" data-item="{{item}}" title="Copy" type="button"><i class="fa fa-copy"></i></button>
                            <a class="btn btn-xs btn-simple btn-download" type="button" title="Download" href="<?php echo home_url('/?wpdmfmdl={{path}}') ?>"><i class="fas fa-arrow-down"></i></a>
                            <button class="btn btn-xs btn-simple btn-danger btn-delete" title="Delete" type="button" data-path="{{path}}"><i class="fas fa-trash"></i></button>
                        </div>
                        <a href="#" class="text-muted pull-right action-btns-ctrl" data-target="#action-btns-{{id}}"><i class="fas fa-ellipsis-v"></i></a>
	                    <?php } ?>
                    </div>
                </div>
            </div>

    </div>

    <div id="upfile" class="wpdmam-upload" role="region" aria-labelledby="wpdmam-upload-title" style="display: none">
        <div class="wpdmam-upload__head">
            <div class="wpdmam-upload__heading">
                <p class="wpdmam-upload__title" id="wpdmam-upload-title"><?php esc_html_e( "Upload File", "download-manager" ); ?></p>
                <p class="wpdmam-upload__summary" id="wpdmam-upload-summary" aria-live="polite"></p>
            </div>
            <button type="button" class="wpdmam-upload__text-btn" data-wpdmam-upload-clear hidden><?php esc_html_e( "Clear finished", "download-manager" ); ?></button>
            <button type="button" class="wpdmam-upload__close" data-wpdmam-upload-close aria-label="<?php esc_attr_e( "Close", "download-manager" ); ?>"><i class="fa fa-times" aria-hidden="true"></i></button>
        </div>
        <div class="wpdmam-upload__body">
            <div id="plupload-upload-ui" class="hide-if-no-js">
                <div id="drag-drop-area" class="wpdmam-upload__drop">
                    <span class="wpdmam-upload__drop-icon" aria-hidden="true"><i class="fa fa-cloud-upload-alt"></i></span>
                    <p class="wpdmam-upload__drop-title"><?php esc_html_e( "Drop files here", "download-manager" ); ?></p>
                    <p class="wpdmam-upload__drop-hint">
                        <?php echo esc_html_x( "or", "Uploader: Drop files here - or - Select Files", "download-manager" ); ?>
                        <button id="plupload-browse-button" type="button" class="wpdmam-upload__browse"><?php esc_html_e( "Select Files", "download-manager" ); ?></button>
                    </p>
                </div>
            </div>

                <?php
                $slimit = get_option('__wpdm_max_upload_size',0);
                if($slimit>0)
                    $slimit = wp_convert_hr_to_bytes($slimit.'M');
                else
                    $slimit = wp_max_upload_size();

                $plupload_init = array(
                    'runtimes'            => 'html5,silverlight,flash,html4',
                    'browse_button'       => 'plupload-browse-button',
                    'container'           => 'plupload-upload-ui',
                    'drop_element'        => 'drag-drop-area',
                    'file_data_name'      => 'package_file', //(current_user_can(WPDM_ADMIN_CAP)?'package_file':'attach_file'),
                    'multiple_queues'     => true,
                    'max_file_size'       => $slimit.'b',
                    'url'                 => admin_url('admin-ajax.php'),
                    'flash_swf_url'       => includes_url('js/plupload/plupload.flash.swf'),
                    'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
                    'filters'             => array(array('title' => __('Allowed Files'), 'extensions' =>  implode(",", WPDM()->fileSystem->getAllowedFileTypes()))),
                    'multipart'           => true,
                    'urlstream_upload'    => true,

                    // additional post data to send to our ajax hook
                    'multipart_params'    => array(
                        '_ajax_nonce' => wp_create_nonce(NONCE_KEY),
                        '__noconflict' => 1,
                        '__wpdmfm_upload' => wp_create_nonce(NONCE_KEY),
                        'action'      => 'wpdm_admin_upload_file', //(current_user_can(WPDM_ADMIN_CAP)?'wpdm_admin_upload_file':'wpdm_frontend_file_upload'),            // the ajax action name
                    ),
                );

                if(get_option('__wpdm_chunk_upload',0) == 1){
                    $plupload_init['chunk_size'] = get_option('__wpdm_chunk_size', 1024).'kb';
                    $plupload_init['max_retries'] = 3;
                } else
                    $plupload_init['max_file_size'] = wp_max_upload_size().'b';

                // we should probably not apply this filter, plugins may expect wp's media uploader...
                $plupload_init = apply_filters('plupload_init', $plupload_init); ?>
            <p class="wpdmam-upload__limit"><?php printf( esc_html__( "Maximum file size: %s", "download-manager" ), esc_html( size_format( wp_convert_hr_to_bytes( $plupload_init['max_file_size'] ) ) ) ); ?></p>
        </div>
        <div id="filelist" class="wpdmam-upload__queue"></div>
    </div>

    <div class="modal fade" tabindex="-1" role="dialog" id="__link_settings">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close pull-right" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fa fa-times-circle"></i></span></button>
                    <strong class="modal-title"><?php echo  esc_attr__( 'Update link', "download-manager" ); ?></strong>
                </div>
                <div class="modal-body">
                    <form id="update_sharelink_form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
                        <?php wp_nonce_field(WPDMAM_NONCE_KEY, '__wpdm_updatelink'); ?>
                        <input type="hidden" name="action" value="wpdm_updatelink" />
                        <input type="hidden" name="ID" v-bind:value="link.ID" />
                        <div class="panel panel-default">
                            <div class="panel-heading" style="box-shadow: none !important;border-bottom: 1px solid #e3e3e3 !important;border-top: 0 !important;"><?php echo __( "Authorized User Groups:", "download-manager" ) ?></div>
                            <div class="panel-body allow-roles">

                                <div class="row">
                                    <div class="col-md-3">
                                        <label><input v-bind:checked="roleSelected('guest')" type="checkbox" name="access[roles][]" value="guest" /> <?php echo __( "All Visitors" , "download-manager" ); ?></label>
                                    </div>

                                    <?php
                                    global $wp_roles;
                                    $roles = array_reverse($wp_roles->role_names);
                                    foreach( $roles as $role => $name ) {
                                        ?>
                                        <div class="col-md-3"><label><input v-bind:checked="roleSelected('<?php echo $role; ?>')" type="checkbox" name="access[roles][]" value="<?php echo $role; ?>" /> <?php echo $name; ?></label></div>
                                    <?php } ?>
                                </div>
                            </div>

                            <?php
                            if(is_plugin_active('wpdm-custom-access-level/wpdm-custom-access-level.php')){ ?>


                                <div class="panel-heading" style="box-shadow: none !important;border-radius: 0;border-top: 1px solid #e3e3e3;"><?php echo __('Authorized Users:','download-manager'); ?></div>
                                <div class="panel-body" id="_uaco">


                                    <span style="margin-right: 3px" class="btn btn-simple btn-sm" id="uaco-admin" v-for="user in link.access.users">
                                        <input type="hidden" name="access[users][]" value="admin">
                                        <a class="uaco-del" onclick="jQuery(this.rel).remove()" v-bind:rel="'#uaco-' + user"><i class="far fa-times-circle"></i></a>&nbsp;{{ user }}
                                    </span>


                                </div>
                                <div class="panel-footer"><input id="_maname" placeholder="Start typing to search members..." style="width: 100%" type="text" class="form-control"></div>


                            <?php } ?>

                        </div>

                        <div class="text-right">
                            <button type="submit" id="create_sharelink" class="btn btn-info"><?php echo  esc_attr__( 'Update Link', "download-manager" ); ?></button>
                        </div>


                    </form>

                </div>
            </div>
        </div>
    </div>




</div>

    <script src="<?php echo WPDM_BASE_URL ?>assets/js/simple-scrollbar.min.js"></script>
<script>
    var current_path = '', editor = '', opened = '', wpdmfm_active_asset = '', wpdmfm_active_asset_settings, $ = jQuery;
    var _path = localStorage.getItem('__wpdm_am_cp');

    /* === searchAsset === */
    var searchAsset = Vue.createApp({
        data() {
            return { keyword: '' };
        },
        methods: {
            execute() {
                WPDMAM.searchDir(current_path, this.keyword);
            }
        }
    }).mount('#__file_search');

    /* === assetPages === */
    var assetPages = Vue.createApp({
        data() {
            return {
                total_pages: 1,
                current_page: 1,
                items_per_page: 15,
                goto_page: 1
            };
        },
        methods: {
            nextPage() {
                let topage = this.current_page + 1;
                topage = topage > this.total_pages ? 1 : topage;
                WPDMAM.scanDir(current_path, topage);
            },
            prevPage() {
                let topage = this.current_page - 1;
                topage = topage < 1 ? 1 : topage;
                WPDMAM.scanDir(current_path, topage);
            },
            gotoPage() {
                WPDMAM.scanDir(current_path, this.goto_page);
            }
        }
    }).mount('#__asset_pages');

    /* keep your line as-is */
    if (typeof _path !== 'undefined' && _path) current_path = _path;

    /* === assetSettings === */
    var assetSettings = Vue.createApp({
        data() {
            return { asset: [] };
        }
    }).mount('#__asset_settings');

    /* === linkSettings === */
    var linkSettings = Vue.createApp({
        data() {
            return {
                link: {
                    access: {
                        roles: ['guest'],
                        users: ['admin']
                    }
                }
            };
        },
        methods: {
            roleSelected(role) {
                for (var i = 0; i < this.link.access.roles.length; i++) {
                    if (this.link.access.roles[i] == role) return true;
                }
                return false;
            }
        }
    }).mount('#__link_settings');

    /*var searchAsset = new Vue({
        el: '#__file_search',
        data: {
            keyword: '',
        },
        methods: {
            execute: function () {
                WPDMAM.searchDir(current_path, this.keyword);
            }

        }
    });

    var assetPages = new Vue({
        el: '#__asset_pages',
        data: {
            total_pages: 1,
            current_page: 1,
            items_per_page: 15,
            goto_page: 1
        },
        methods: {
            nextPage: function () {
                let topage = this.current_page + 1;
                topage = topage > this.total_pages ? 1 : topage;
                WPDMAM.scanDir(current_path, topage)
            },
            prevPage: function () {
                let topage = this.current_page - 1;
                topage = topage < 1 ? 1 : topage;
                WPDMAM.scanDir(current_path, topage)
            },
            gotoPage: function() {
                WPDMAM.scanDir(current_path, this.goto_page);
            }

        }
    });
    if(_path) current_path = _path;
    var assetSettings = new Vue({
        el: '#__asset_settings',
        data: {
            asset: []
        }
    });

    var linkSettings = new Vue({
        el: '#__link_settings',
        data: {
            link: {
                access: {
                    roles: ['guest'],
                    users:['admin']
                }
            }
        },
        methods: {
            roleSelected: function (role) {
                for(var i=0; i < this.link.access.roles.length; i++){
                    if( this.link.access.roles[i] == role){
                        return true
                    }
                }
                return false
            }
        }
    });*/

    /* View in fullscreen */
    function openFullscreen(elementid) {
        var elem = document.getElementById(elementid);
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.mozRequestFullScreen) { /* Firefox */
            elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullscreen) { /* Chrome, Safari and Opera */
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) { /* IE/Edge */
            elem.msRequestFullscreen();
        }
    }

    var WPDMAM = {
        assetSettings: function (asset, row) {
            $('.item-row').removeClass('active');
            row.addClass('active');
            wpdmfm_active_asset = asset;
            $('#filewin').removeClass('col-md-9').addClass('col-md-6');
            $('#cogwin').fadeIn();
            $('#cogwin > .panel').addClass('blockui');
            $.get(ajaxurl, {__wpdm_filesettings:'<?php echo wp_create_nonce(WPDMAM_NONCE_KEY); ?>', action: 'wpdm_filesettings', file: asset }, function (data) {
                data.sharecode = "[wpdm_asset id='"+data.ID+"']";
                assetSettings.asset = data;
                $('#cogwin > .panel').removeClass('blockui');
            });
        },
        copy: function (item) {
            localStorage.setItem("__wpdm_fm_copy", current_path+"|||"+item);
            localStorage.setItem("__wpdm_fm_move", 0);
            $('.btn-copy').html('<i class="fa fa-copy"></i>');
            $('.btn-cut').html('<i class="fa fa-cut"></i>');
            $('#btn-paste').removeAttr('disabled').attr("data-item", localStorage.getItem("__wpdm_fm_copy"));
        },
        cut: function (item) {
            localStorage.setItem("__wpdm_fm_copy", current_path+"|||"+item);
            localStorage.setItem("__wpdm_fm_move", 1);
            $('.btn-copy').html('<i class="fa fa-copy"></i>');
            $('.btn-cut').html('<i class="fa fa-cut"></i>');
            $('#btn-paste').removeAttr('disabled').attr("data-item", localStorage.getItem("__wpdm_fm_copy"));
        },
        delete: function (filepath) {
            if(!confirm('Are you sure?')) return false;
            $.get(ajaxurl, {__wpdm_unlink:'<?php echo wp_create_nonce(WPDMAM_NONCE_KEY); ?>', action: 'wpdm_unlink', path: current_path, delete: filepath}, function (data) {
                WPDMAM.scanDir(current_path);
            });
        },
        scanDir: function(path, page) {
            this.hideEditor();
            this.hideSettings();
            $('#reload').attr('disabled', 'disabled').find('.fa').addClass('fa-spin');
            WPDM.blockUI('#filewin');
            if(typeof page === 'undefined') page = 1;
            localStorage.setItem('__wpdm_am_cp', path);
            $.get(ajaxurl, {__wpdm_scandir:'<?php echo wp_create_nonce(NONCE_KEY); ?>', action: 'wpdm_scandir', path: path, sdpage: page}, function (data) {
                if(data.success === true) {
                    assetPages.total_pages = data.total_pages;
                    assetPages.current_page = data.current_page;
                    $('#scandir').html('');
                    var items = data.items;
                    $.each(items, function (index, entry) {
                        if (entry.type == 'file') {
                            ext = entry.contenttype.replace("/", "_");
                            var tpl = $('#fileTPL').html();
                            tpl = tpl.replace("{{icon}}", entry.icon);
                            tpl = tpl.replace("{{ext}}", ext);
                            tpl = tpl.replace("{{contenttype}}", entry.contenttype);
                            tpl = tpl.replace("{{item_label}}", entry.item_label);
                            tpl = tpl.replace("{{note}}", entry.note);
                            tpl = tpl.replace("{{file_size}}", entry.file_size);
                            tpl = tpl.replace(/\{\{path\}\}/ig, entry.path);
                            tpl = tpl.replace(/\{\{item\}\}/ig, entry.item);
                            tpl = tpl.replace(/\{\{id\}\}/ig, index);
                            var _star = entry.featured ? 'fas' : 'far';
                            tpl = tpl.replace(/\{\{star\}\}/ig, _star);
                        } else {
                            var tpl = $('#dirTPL').html();
                            tpl = tpl.replace("{{icon}}", entry.icon);
                            tpl = tpl.replace("{{item_label}}", entry.item_label);
                            tpl = tpl.replace("{{note}}", entry.note);
                            tpl = tpl.replace("{{file_size}}", entry.file_size);
                            tpl = tpl.replace(/\{\{path\}\}/ig, entry.path);
                            tpl = tpl.replace(/\{\{item\}\}/ig, entry.item);
                            tpl = tpl.replace(/\{\{id\}\}/ig, index);
                            tpl = tpl.replace(/\{\{dirid\}\}/ig, entry.id);
                        }
                        $('#scandir').append(tpl);
                    });
                    WPDM.unblockUI('#filewin');
                    $('#reload').removeAttr('disabled', 'disabled').html("<i class='fa fa-sync'></i>");
                    $('#breadcrumb').html(data.breadcrumb);
                } else {
                    WPDM.pushNotify("<?=esc_attr__( 'Error', 'download-manager' ); ?>!", data.message, 'https://cdn0.iconfinder.com/data/icons/small-n-flat/24/678080-shield-error-256.png', 'https://cdn0.iconfinder.com/data/icons/small-n-flat/24/678080-shield-error-256.png');
                }
            });
        },
        searchDir: function(path, keyword) {
            this.hideEditor();
            this.hideSettings();
            $('#reload').attr('disabled', 'disabled').find('.fa').addClass('fa-spin');
            WPDM.blockUI('#filewin');
            if(typeof page === 'undefined') page = 1;
            localStorage.setItem('__wpdm_am_cp', path);
            $.get(ajaxurl, {__wpdm_scandir:'<?php echo wp_create_nonce(NONCE_KEY); ?>', action: 'wpdm_scandir', path: path, keyword: keyword}, function (data) {
                if(data.success === true) {
                    assetPages.total_pages = data.total_pages;
                    assetPages.current_page = data.current_page;
                    $('#scandir').html('');
                    var items = data.items;
                    $.each(items, function (index, entry) {
                        if (entry.type == 'file') {
                            ext = entry.contenttype.replace("/", "_");
                            var tpl = $('#fileTPL').html();
                            tpl = tpl.replace("{{icon}}", entry.icon);
                            tpl = tpl.replace("{{ext}}", ext);
                            tpl = tpl.replace("{{contenttype}}", entry.contenttype);
                            tpl = tpl.replace("{{item_label}}", entry.item_label);
                            tpl = tpl.replace("{{note}}", entry.note);
                            tpl = tpl.replace("{{file_size}}", entry.file_size);
                            tpl = tpl.replace(/\{\{path\}\}/ig, entry.path);
                            tpl = tpl.replace(/\{\{item\}\}/ig, entry.item);
                            tpl = tpl.replace(/\{\{id\}\}/ig, index);
                            var _star = entry.featured ? 'fas' : 'far';
                            tpl = tpl.replace(/\{\{star\}\}/ig, _star);
                        } else {
                            var tpl = $('#dirTPL').html();
                            tpl = tpl.replace("{{icon}}", entry.icon);
                            tpl = tpl.replace("{{item_label}}", entry.item_label);
                            tpl = tpl.replace("{{note}}", entry.note);
                            tpl = tpl.replace("{{file_size}}", entry.file_size);
                            tpl = tpl.replace(/\{\{path\}\}/ig, entry.path);
                            tpl = tpl.replace(/\{\{item\}\}/ig, entry.item);
                            tpl = tpl.replace(/\{\{id\}\}/ig, index);
                            tpl = tpl.replace(/\{\{dirid\}\}/ig, entry.id);
                        }
                        $('#scandir').append(tpl);
                    });
                    WPDM.unblockUI('#filewin');
                    $('#reload').removeAttr('disabled', 'disabled').html("<i class='fa fa-sync'></i>");
                    $('#breadcrumb').html(data.breadcrumb);
                } else {
                    WPDM.pushNotify("<?=esc_attr__( 'Error', 'download-manager' ); ?>!", data.message, 'https://cdn0.iconfinder.com/data/icons/small-n-flat/24/678080-shield-error-256.png', 'https://cdn0.iconfinder.com/data/icons/small-n-flat/24/678080-shield-error-256.png');
                }
            });
        },
        hideEditor: function() {
            $('#wpdmeditor').fadeOut();
            if(editor == '') return;
            editor.codemirror.toTextArea();
            $('#filecontent').val('');
            $('#wpdmeditor').addClass('blockui');
        },
        hideSettings: function() {
            $('#cogwin').hide();
            $('#filewin').removeClass('col-md-6').addClass('col-md-9');
            $('#cogwin > .panel').addClass('blockui');
        }
    }


    jQuery(function ($) {

        //$('#wpdmfm_explorer').css('height', (window.innerHeight - 190)+'px');

        $('#mainfmc').fadeIn();
        $('#loadingfm').hide();



        function refresh_scandir(path) {
            hide_editor();
            hide_settings();
            $('#reload').attr('disabled', 'disabled').find('.fa').addClass('fa-spin');
            WPDM.blockUI('#filewin');
            localStorage.setItem('__wpdm_am_cp', path);
            $.get(ajaxurl, {__wpdm_scandir:'<?php echo wp_create_nonce(NONCE_KEY); ?>', action: 'wpdm_scandir', path: path}, function (data) {
                if(data.success === true) {
                    assetPages.total_pages = data.total_pages;
                    assetPages.current_page = data.current_page;
                    $('#scandir').html('');
                    var items = data.items;
                    $.each(items, function (index, entry) {
                        if (entry.type == 'file') {
                            ext = entry.contenttype.replace("/", "_");
                            var tpl = $('#fileTPL').html();
                            tpl = tpl.replace("{{icon}}", entry.icon);
                            tpl = tpl.replace("{{ext}}", ext);
                            tpl = tpl.replace("{{contenttype}}", entry.contenttype);
                            tpl = tpl.replace("{{item_label}}", entry.item_label);
                            tpl = tpl.replace("{{note}}", entry.note);
                            tpl = tpl.replace("{{file_size}}", entry.file_size);
                            tpl = tpl.replace(/\{\{path\}\}/ig, entry.path);
                            tpl = tpl.replace(/\{\{item\}\}/ig, entry.item);
                            tpl = tpl.replace(/\{\{id\}\}/ig, index);
                        } else {
                            var tpl = $('#dirTPL').html();
                            tpl = tpl.replace("{{icon}}", entry.icon);
                            tpl = tpl.replace("{{item_label}}", entry.item_label);
                            tpl = tpl.replace("{{note}}", entry.note);
                            tpl = tpl.replace("{{file_size}}", entry.file_size);
                            tpl = tpl.replace(/\{\{path\}\}/ig, entry.path);
                            tpl = tpl.replace(/\{\{item\}\}/ig, entry.item);
                            tpl = tpl.replace(/\{\{id\}\}/ig, index);
                            tpl = tpl.replace(/\{\{dirid\}\}/ig, entry.id);
                        }
                        $('#scandir').append(tpl);
                    });
                    WPDM.unblockUI('#filewin');
                    $('#reload').removeAttr('disabled', 'disabled').html("<i class='fa fa-sync'></i>");
                    $('#breadcrumb').html(data.breadcrumb);

                } else {
                    WPDM.pushNotify("<?php echo esc_attr__( 'Error', 'download-manager' ); ?>!", data.message, 'https://cdn0.iconfinder.com/data/icons/small-n-flat/24/678080-shield-error-256.png', 'https://cdn0.iconfinder.com/data/icons/small-n-flat/24/678080-shield-error-256.png');
                }
            });
        }

        function hide_editor() {
            $('#wpdmeditor').fadeOut();
            if(editor == '') return;
            editor.codemirror.toTextArea();
            $('#filecontent').val('');
            $('#wpdmeditor').addClass('blockui');
        }

        function hide_settings() {
            $('#cogwin').hide();
            $('#filewin').removeClass('col-md-6').addClass('col-md-9');
            $('#cogwin > .panel').addClass('blockui');
        }

        function auto_expand()
        {
            var expanded = localStorage.getItem('__wpdmam_xdirs');
            if(!expanded) expanded = "";
            expanded = expanded.split(",");

        }

        function expand_dir(id) {

            /*var expanded = localStorage.getItem('__wpdmam_xdirs');
            if(!expanded) expanded = "";
            expanded = expanded.split(",");
            expanded.push(id);
            localStorage.setItem('__wpdmam_xdirs', expanded);*/

            localStorage.setItem('__expanded_'+id, true);

            var $this = $('#'+id);
            $this.addClass('busy');
            var chid = "expanded_" + id;
            var slide = 1;
            var _ajaxurl = ajaxurl == undefined ? wpdm_url.ajax : ajaxurl;
            $.get(_ajaxurl, {__wpdm_scandir:'<?php echo wp_create_nonce(NONCE_KEY); ?>', action: 'wpdm_scandir', dirs: 1, path: $this.data('path')}, function (dirs){
                if($("#"+chid).length == 1) {
                    $("#" + chid).remove();
                    slide = 0;
                }

                $this.append("<ul id='"+chid+"' style='display: none'></ul>");
                $.each(dirs, function (id, dir) {
                    $('#'+chid).append("<li class='expand-dir' id='"+dir.id+"' data-path='"+dir.path+"'><span class='handle'></span><a href='#' class='explore-dir' data-path='"+dir.path+"'>"+dir.item_label+"</a></li>");
                    var xpanded = localStorage.getItem('__expanded_'+dir.id);
                    if(xpanded) {
                        expand_dir(dir.id);
                    }
                });
                $this.removeClass('busy').addClass('expanded');
                if(slide == 1)
                    $('#'+chid).slideDown();
                else
                    $('#'+chid).show();
            });
        }

        // create the uploader and pass the config from above
        var uploader = new plupload.Uploader(<?php echo json_encode($plupload_init); ?>);

        // checks if browser supports drag and drop upload, makes some css adjustments if necessary
        uploader.bind('Init', function(up){
            var uploaddiv = jQuery('#plupload-upload-ui');

            if(up.features.dragdrop){
                uploaddiv.addClass('drag-drop');
                jQuery('#drag-drop-area')
                    .bind('dragover.wp-uploader', function(){ uploaddiv.addClass('drag-over'); })
                    .bind('dragleave.wp-uploader, drop.wp-uploader', function(){ uploaddiv.removeClass('drag-over'); });

            }else{
                uploaddiv.removeClass('drag-drop');
                jQuery('#drag-drop-area').unbind('.wp-uploader');
            }
        });

        uploader.init();

        /* Upload queue */
        var uploadText = <?php echo wp_json_encode( array(
            'idle'       => __( 'Files are added to the folder you are viewing.', 'download-manager' ),
            /* translators: 1: number of files uploaded, 2: number of files in the queue */
            'progress'   => __( '%1$s of %2$s uploaded', 'download-manager' ),
            'complete'   => __( 'All uploads complete', 'download-manager' ),
            /* translators: 1: number of files uploaded, 2: number of files that failed */
            'withErrors' => __( '%1$s uploaded, %2$s failed', 'download-manager' ),
            'uploaded'   => __( 'Uploaded', 'download-manager' ),
            'failed'     => __( 'Upload failed. Please try again.', 'download-manager' ),
            /* translators: %s: maximum upload size, e.g. 64 MB */
            'tooLarge'   => sprintf( __( 'This file is larger than the %s limit.', 'download-manager' ), size_format( wp_convert_hr_to_bytes( $plupload_init['max_file_size'] ) ) ),
            'badType'    => __( 'This file type is not allowed.', 'download-manager' ),
            'expired'    => __( 'Your session has expired. Refresh the page and try again.', 'download-manager' ),
        ) ); ?>;
        var $uploadQueue = $('#filelist'),
            $uploadSummary = $('#wpdmam-upload-summary'),
            $uploadClear = $('[data-wpdmam-upload-clear]');

        function uploadItem(file) {
            var $item = $('#' + file.id), $main;

            if ($item.length) return $item;

            $main = $('<div class="wpdmam-upload__item-main"></div>');
            $('<div class="wpdmam-upload__item-row"></div>')
                .append($('<span class="wpdmam-upload__item-name"></span>').text(file.name).attr('title', file.name))
                .append($('<span class="wpdmam-upload__item-meta"></span>').text(plupload.formatSize(file.size || 0)))
                .appendTo($main);
            $('<div class="wpdmam-upload__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span class="wpdmam-upload__bar-fill"></span></div>')
                .attr('aria-label', file.name)
                .appendTo($main);
            $('<p class="wpdmam-upload__item-status"></p>').appendTo($main);

            return $('<div class="wpdmam-upload__item" data-state="queued"></div>')
                .attr('id', file.id)
                .append('<span class="wpdmam-upload__item-icon" aria-hidden="true"><i class="far fa-file"></i></span>')
                .append($main)
                .appendTo($uploadQueue);
        }

        function setUploadState($item, state, message) {
            var icons = { done: 'fas fa-check', failed: 'fas fa-exclamation' };

            $item.attr('data-state', state);
            $item.find('.wpdmam-upload__item-icon i').attr('class', icons[state] || 'far fa-file');
            $item.find('.wpdmam-upload__item-status').text(message || '');
            $item.find('.wpdmam-upload__bar-fill').css('width', '100%');
            $item.find('.wpdmam-upload__bar').attr('aria-valuenow', 100);
            updateUploadSummary();
        }

        function updateUploadSummary() {
            var $items = $uploadQueue.children(),
                done = $items.filter('[data-state="done"]').length,
                failed = $items.filter('[data-state="failed"]').length,
                active = $items.length - done - failed;

            if (!$items.length) $uploadSummary.text(uploadText.idle);
            else if (active) $uploadSummary.text(uploadText.progress.replace('%1$s', done).replace('%2$s', $items.length));
            else if (failed) $uploadSummary.text(uploadText.withErrors.replace('%1$s', done).replace('%2$s', failed));
            else $uploadSummary.text(uploadText.complete);

            $uploadClear.prop('hidden', !$items.length || active > 0);
        }

        /* Non-JSON replies from the upload endpoint: -2 (expired nonce), -3 (blocked type) or an HTML alert wrapped in |||. */
        function uploadError(raw) {
            var text = $.trim(String(raw || '')),
                wrapped = text.match(/^\|\|\|([\s\S]*)\|\|\|$/),
                message;

            if (text === '-2') return uploadText.expired;
            if (text === '-3') return uploadText.badType;
            if (wrapped && /<[a-z]/i.test(wrapped[1])) {
                message = $.trim(new DOMParser().parseFromString(wrapped[1], 'text/html').body.textContent || '');
                if (message) return message;
            }
            return uploadText.failed;
        }

        updateUploadSummary();

        uploader.bind('FilesAdded', function (up, files) {
            uploader.settings.multipart_params.current_path = current_path;
            plupload.each(files, function (file) {
                uploadItem(file);
            });
            updateUploadSummary();
            up.refresh();
            up.start();
        });

        uploader.bind('UploadProgress', function (up, file) {
            var $item = uploadItem(file),
                loaded = typeof file.loaded === 'number' ? file.loaded : Math.round(file.size * file.percent / 100);

            $item.attr('data-state', 'uploading');
            $item.find('.wpdmam-upload__bar-fill').css('width', file.percent + '%');
            $item.find('.wpdmam-upload__bar').attr('aria-valuenow', file.percent);
            $item.find('.wpdmam-upload__item-meta').text(plupload.formatSize(loaded) + ' / ' + plupload.formatSize(file.size));
        });

        uploader.bind('FileUploaded', function (up, file, result) {
            var $item = uploadItem(file), response = null, isJson;

            try {
                response = JSON.parse(result.response);
            } catch (e) {}
            isJson = response !== null && typeof response === 'object';

            if (result.status == 200 && isJson && response.success) {
                setUploadState($item, 'done', uploadText.uploaded);
                refresh_scandir(current_path);
            } else {
                setUploadState($item, 'failed', isJson ? (response.message || uploadText.failed) : uploadError(result.response));
            }
        });

        uploader.bind('Error', function (up, error) {
            var message = uploadText.failed;

            if (!error.file) return;
            if (error.code === plupload.FILE_SIZE_ERROR) message = uploadText.tooLarge;
            else if (error.code === plupload.FILE_EXTENSION_ERROR) message = uploadText.badType;
            else if (error.response) message = uploadError(error.response);

            setUploadState(uploadItem(error.file), 'failed', message);
            up.refresh();
        });

        $('#reload').on('click', function () {
            refresh_scandir(current_path);
        });

        /* New folder / new file */
        (function () {
            var modes = <?php echo wp_json_encode( array(
                'folder' => array(
                    'action'     => 'wpdm_mkdir',
                    'nonceField' => '__wpdm_mkdir',
                    'icon'       => 'fa fa-folder-open',
                    'title'      => __( 'New folder', 'download-manager' ),
                    'label'      => __( 'Folder name', 'download-manager' ),
                    'submit'     => __( 'Create folder', 'download-manager' ),
                    'failed'     => __( 'The folder could not be created.', 'download-manager' ),
                ),
                'file'   => array(
                    'action'     => 'wpdm_newfile',
                    'nonceField' => '__wpdm_newfile',
                    'icon'       => 'far fa-file',
                    'title'      => __( 'New file', 'download-manager' ),
                    'label'      => __( 'File name', 'download-manager' ),
                    'submit'     => __( 'Create file', 'download-manager' ),
                    'failed'     => __( 'The file could not be created.', 'download-manager' ),
                ),
            ) ); ?>;
            var i18n = <?php echo wp_json_encode( array(
                'required' => __( 'Enter a name.', 'download-manager' ),
                'invalid'  => __( 'Names cannot contain slashes.', 'download-manager' ),
                'creating' => __( 'Creating…', 'download-manager' ),
                'failed'   => __( 'Something went wrong. Refresh the page and try again.', 'download-manager' ),
            ) ); ?>;
            var nonce = '<?php echo esc_js( wp_create_nonce( WPDMAM_NONCE_KEY ) ); ?>';

            var $panel = $('#wpdmam-create'),
                $form = $('#wpdmam-create-form'),
                $title = $('#wpdmam-create-title'),
                $label = $form.find('label[for="wpdmam-create-name"]'),
                $icon = $panel.find('[data-wpdmam-create-icon]'),
                $name = $('#wpdmam-create-name'),
                $error = $('#wpdmam-create-error'),
                $submit = $('#wpdmam-create-submit'),
                $triggers = $('[data-wpdmam-create]'),
                $trigger = $(),
                mode = null,
                busy = false;

            function isOpen() {
                return !$panel.prop('hidden');
            }

            function setError(message) {
                $error.text(message || '');
                $name.attr('aria-invalid', message ? 'true' : 'false');
            }

            function place() {
                var area = document.getElementById('mainfmarea').getBoundingClientRect(),
                    button = $trigger[0].getBoundingClientRect(),
                    left = Math.min(button.left - area.left, area.width - $panel.outerWidth() - 16);

                $panel.css({ top: button.bottom - area.top + 8, left: Math.max(16, left) });
            }

            function open(nextMode, $button) {
                var config = modes[nextMode];

                mode = nextMode;
                $triggers.attr('aria-expanded', 'false');
                $trigger = $button.attr('aria-expanded', 'true');
                $icon.attr('class', config.icon);
                $title.text(config.title);
                $label.text(config.label);
                if (!busy) $submit.text(config.submit);
                $name.val('');
                setError('');
                $panel.prop('hidden', false);
                place();
                $name.trigger('focus');
            }

            function close(restoreFocus) {
                if (!isOpen()) return;
                $panel.prop('hidden', true);
                $triggers.attr('aria-expanded', 'false');
                if (restoreFocus) $trigger.trigger('focus');
                mode = null;
            }

            $triggers.on('click', function (event) {
                var nextMode = $(this).attr('data-wpdmam-create');

                event.preventDefault();
                if (isOpen() && mode === nextMode) close(true);
                else open(nextMode, $(this));
            });

            $form.on('submit', function (event) {
                var config, name, payload;

                event.preventDefault();
                if (busy || !mode) return;

                config = modes[mode];
                name = $.trim($name.val());

                if (!name) { setError(i18n.required); $name.trigger('focus'); return; }
                if (/[\\/]/.test(name)) { setError(i18n.invalid); $name.trigger('focus'); return; }

                payload = { action: config.action, path: current_path, name: name };
                payload[config.nonceField] = nonce;

                busy = true;
                setError('');
                $submit.prop('disabled', true).attr('aria-busy', 'true').text(i18n.creating);

                $.post(ajaxurl, payload, null, 'json')
                    .done(function (response) {
                        if (response && response.success) {
                            close(true);
                            refresh_scandir(current_path);
                        } else {
                            setError((response && response.message) || config.failed);
                        }
                    })
                    .fail(function () {
                        setError(i18n.failed);
                    })
                    .always(function () {
                        busy = false;
                        $submit.prop('disabled', false).removeAttr('aria-busy').text(mode ? modes[mode].submit : config.submit);
                    });
            });

            $panel.on('click', '[data-wpdmam-create-cancel]', function () {
                close(true);
            });

            $panel.on('keydown', function (event) {
                if (event.key !== 'Escape') return;
                event.stopPropagation();
                close(true);
            });

            $(document).on('pointerdown', function (event) {
                if (isOpen() && !$(event.target).closest('#wpdmam-create, [data-wpdmam-create]').length) close(false);
            });

            $(window).on('resize', function () {
                if (isOpen()) place();
            });
        })();

        /* Delete */
        $('body').on('click', '.btn-delete', function (e) {
            e.preventDefault();
            if(!confirm('Are you sure?')) return false;
            $(this).html('<i class="fa fa-spinner fa-spin"></i>');
            var filepath = $(this).data('path');
            $.get(ajaxurl, {__wpdm_unlink:'<?php echo wp_create_nonce(WPDMAM_NONCE_KEY); ?>', action: 'wpdm_unlink', path: current_path, delete: filepath}, function (data) {
                refresh_scandir(current_path);
            });
        });


        $('body').on('click', '.btn-open-file', function (e) {
            e.preventDefault();
            $('#wpdmeditor').fadeIn();
            var filepath = $(this).data('path');
            var filename = $(this).find('.item_label').attr('title');
            $('#wpdmefn').text(filename);
            $.get(ajaxurl, {__wpdm_openfile:'<?php echo wp_create_nonce(WPDMAM_NONCE_KEY); ?>', action: 'wpdm_openfile', path: current_path, file: filepath}, function (data) {
                if(data.content != '') {
                    $('#filecontent').show();
                    $('#filecontent_alt').hide();
                    $('#filecontent').val(data.content);
                    opened = data.id;
                    $('#savefile').data('path', filepath);
                    $('#wpdmeditor').removeClass('blockui');
                    $('#wpdmeditor .panel-footer').fadeIn();
                    editor = wp.codeEditor.initialize($('#filecontent'), wpdmcm_settings);
                    var fileext = filename.split('.').pop();
                    fileext = fileext == 'html' ? 'htmlmixed' : fileext;
                    fileext = fileext == 'js' ? 'javascript' : fileext;
                    editor.codemirror.setOption('mode', fileext);
                } else {
                    $('#filecontent').hide();
                    $('#filecontent_alt').html(data.embed).show();
                    $('#wpdmeditor .panel-footer').fadeOut();
                    $('#wpdmeditor').removeClass('blockui');
                }
            });
        });

        $('body').on('click', '#close-editor', function (e) {
            e.preventDefault();
            hide_editor();
        });

        $('body').on('click', '#close-settings', function (e) {
            e.preventDefault();
            hide_settings();
        });

        $('body').on('click', '#savefile', function (e) {
            e.preventDefault();
            $('#wpdmeditor').addClass('blockui');
            var filepath = $(this).data('path');
            var content = editor.codemirror.getValue();
            $.post(ajaxurl, {__wpdm_savefile:'<?php echo wp_create_nonce(WPDMAM_NONCE_KEY); ?>', action: 'wpdm_savefile', content: content, file: filepath, opened: opened }, function (data) {
                WPDM.pushNotify("Save File", data.message);
                $('#wpdmeditor').removeClass('blockui');
            });
        });


        $('body').on('click', '.btn-edit-link', function () {
            $('#update_sharelink_form').addClass('blockui');
            var linkid = $(this).data('linkid');
            $.post(ajaxurl, {__wpdm_getlinkdet:'<?php echo wp_create_nonce(WPDMAM_NONCE_KEY); ?>', action: 'wpdm_getlinkdet', linkid: linkid }, function (data) {
                linkSettings.link = data;
                $('#update_sharelink_form').removeClass('blockui');
            });
        });


        $('body').on('click', '.btn-delete-link', function (e) {
            e.preventDefault();
            var linkid = $(this).data('linkid');
            WPDM.confirm("Delete Link", "Are you sure?",[
                {
                    'label': 'No',
                    'class': 'btn btn-secondary',
                    'callback': function () {
                        $(this).modal('hide');
                    }
                },
                {
                    'label': 'Yes, Remove',
                    'class': 'btn btn-danger',
                    'callback': function () {
                        $(this).find('.modal-body').html('<i class="fa fa-sun fa-spin"></i> Deleting...');
                        var confirm = $(this);
                        $.post(ajaxurl, {__wpdm_deletelink:'<?php echo wp_create_nonce(WPDMAM_NONCE_KEY); ?>', action: 'wpdm_deletelink', linkid: linkid }, function (data) {
                            confirm.modal('hide');
                            $('#asset-link-'+linkid).slideUp();
                        });
                    }
                }
            ]);

        });


        $('#sharelink_form').on('submit', function (e) {
            e.preventDefault();
            $('#sharelink_form').addClass('blockui');
            $(this).ajaxSubmit({
                success: function(data){
                    assetSettings.asset.links = data;
                    $('#sharelink_form').removeClass('blockui');
                    $('#sharelink').modal('hide');

                }
            });
        });

        $('#update_sharelink_form').on('submit', function (e) {
            e.preventDefault();
            $('#update_sharelink_form').addClass('blockui');
            $(this).ajaxSubmit({
                success: function(data){
                    console.log(data);
                    $('#update_sharelink_form').removeClass('blockui');
                    $('#__link_settings').modal('hide');

                }
            });
        });


        $('body').on('click', '.media-folder', function (e) {
            e.preventDefault();
            current_path = $(this).data('path');
            refresh_scandir(current_path);
            expand_dir($(this).data('id'));
        });

        $('body').on('click', '.btn-copy', function (e) {
            e.preventDefault();
            localStorage.setItem("__wpdm_fm_copy", current_path+"|||"+$(this).data('item'));
            localStorage.setItem("__wpdm_fm_move", 0);
            $('.btn-copy').html('<i class="fa fa-copy"></i>');
            $(this).html('<i class="fa fa-check-circle"></i>');
            $('#btn-paste').removeAttr('disabled').attr("data-item", localStorage.getItem("__wpdm_fm_copy"));
        });

        $('body').on('click', '.btn-cut', function (e) {
            e.preventDefault();
            localStorage.setItem("__wpdm_fm_copy", current_path+"|||"+$(this).data('item'));
            localStorage.setItem("__wpdm_fm_move", 1);
            $('.btn-copy').html('<i class="fa fa-copy"></i>');
            $(this).html('<i class="fa fa-check-circle"></i>');
            $('#btn-paste').removeAttr('disabled').attr("data-item", localStorage.getItem("__wpdm_fm_copy"));
        });

        /* Rename */
        $('body').on('click', '#renamenow', function (e) {
            e.preventDefault();
            $(this).html('<i class="fa fa-spinner fa-spin"></i>');
            var filepath = $(this).data('path');
            $.post(ajaxurl, {__wpdm_rename:'<?php echo wp_create_nonce(WPDMAM_NONCE_KEY); ?>', action: 'wpdm_rename', path: current_path, newname: $('#newname').val(), assetid: assetSettings.asset.ID}, function (data) {
                refresh_scandir(current_path);
                $(this).data('oldname', $('#newname').val());
                $('#renamenow').html('Rename');
                $('#rename').modal('hide');
            });
        });

        $('body').on('click', '#btn-upload-file', function () {
            var $panel = $('#upfile').stop(true, true),
                opening = !$panel.is(':visible');

            $(this).attr('aria-expanded', opening ? 'true' : 'false');
            $panel.slideToggle(180, function () {
                if (opening) uploader.refresh();
            });
        });

        $('body').on('click', '[data-wpdmam-upload-close]', function () {
            $('#upfile').stop(true, true).slideUp(180);
            $('#btn-upload-file').attr('aria-expanded', 'false').trigger('focus');
        });

        $('body').on('click', '[data-wpdmam-upload-clear]', function () {
            $('#filelist').children('[data-state="done"], [data-state="failed"]').remove();
            updateUploadSummary();
        });

        $('body').on('keydown', '#upfile', function (event) {
            if (event.key === 'Escape') $('[data-wpdmam-upload-close]').trigger('click');
        });

        $('body').on('click', '.btn-settings', function (e) {
            e.preventDefault();
            var file_path = $(this).data('path');
            wpdmfm_active_asset = file_path;
            $('#filewin').removeClass('col-md-9').addClass('col-md-6');
            $('#cogwin').fadeIn();
            $('#cogwin > .panel').addClass('blockui');
            $.get(ajaxurl, {__wpdm_filesettings:'<?php echo wp_create_nonce(WPDMAM_NONCE_KEY); ?>', action: 'wpdm_filesettings', file: file_path }, function (data) {
                data.sharecode = "[wpdm_asset id='"+data.ID+"']";
                assetSettings.asset = data;
                $('#cogwin > .panel').removeClass('blockui');
            });

        });

        $('body').on('click', '.btn-zip', function (e) {
            e.preventDefault();
            var zip_dir_path = $(this).data('dirpath');
            //WPDM.blockUI("#wpdmfm_explorer");
            $(this).html('<i class="fa fa-spinner fa-spin"></i>');
            $.get(ajaxurl, {__wpdm_createzip:'<?php echo wp_create_nonce(WPDMAM_NONCE_KEY); ?>', action: 'wpdm_createzip', dir_path: zip_dir_path }, function (response) {
                if(!response.success){
                    WPDM.notify(response.message, 'error');
                } else {
                    refresh_scandir(current_path);
                }
            });
        });

        $('body').on('click', '.btn-unzip.application_zip', function (e) {
            e.preventDefault();
            var zip_dir_path = $(this).data('dirpath');
            //WPDM.blockUI("#wpdmfm_explorer");
            $(this).html('<i class="fa fa-spinner fa-spin"></i>');
            $.get(ajaxurl, {__wpdm_unzipit:'<?php echo wp_create_nonce(WPDMAM_NONCE_KEY); ?>', action: 'wpdm_unzipit', dir_path: zip_dir_path }, function (response) {
                if(!response.success){
                    WPDM.notify(response.message, 'error');
                } else {
                    refresh_scandir(current_path);
                }
            });
        });

        $('body').on('click', '.action-btns-ctrl', function (e) {
            e.preventDefault();
            $($(this).data('target')).toggleClass("action-btns-show");
        });
        $('body').on('click', '#btn-paste', function (e) {
            e.preventDefault();
            $(this).html('<i class="fa fa-spinner fa-spin"></i>');
            var params = {__wpdm_copypaste:'<?php echo wp_create_nonce(WPDMAM_NONCE_KEY); ?>', action: 'wpdm_copypaste', source: localStorage.getItem("__wpdm_fm_copy"), dest: current_path};
            if(localStorage.getItem("__wpdm_fm_move") == 1)
                params = {__wpdm_cutpaste:'<?php echo wp_create_nonce(WPDMAM_NONCE_KEY); ?>', action: 'wpdm_cutpaste', source: localStorage.getItem("__wpdm_fm_copy"), dest: current_path};
            $.get(ajaxurl, params, function (data) {
                if(!data.success){
                    WPDM.notify(data.message, 'error');
                } else {
                    refresh_scandir(current_path);
                }
                $('#btn-paste').html('<i class="fa fa-clipboard"></i>');
                if(localStorage.getItem("__wpdm_fm_move") == 1){
                    localStorage.setItem("__wpdm_fm_move", 0);
                    localStorage.setItem("__wpdm_fm_copy", '');
                    $('#btn-paste').attr('disabled','disabled');
                }
            });
        });

        $('body').on('click', '.asset-link .form-control', function () {
            $(this).select();
            document.execCommand('copy');
        });

        $('body').on('click', '.expand-dir > .handle, .explore-dir', function (e) {
            e.preventDefault();

            var $this = $(this).parent('.expand-dir');
            var chid = "expanded_"+$(this).parent('.expand-dir').attr('id');

            if ($(this).hasClass('explore-dir')){
                current_path = $this.data('path');
                refresh_scandir($this.data('path'));
            }

            if($this.hasClass('expanded') && !$(this).hasClass('explore-dir')){
                $('#'+chid).slideUp(function () {
                    $(this).remove();
                    $this.removeClass('expanded');
                    localStorage.removeItem('__expanded_'+$this.attr('id'));
                });
                return false;
            }

            $this.addClass('busy');

            expand_dir($(this).parent('.expand-dir').attr('id'));
        });

        var uacc = '';

        function split(val) {
            return val.split(/,\s*/);
        }

        function extractLast(term) {
            return split(term).pop();
        }

        $("#maname")
            .bind("keydown", function (event) {
                if (event.keyCode === $.ui.keyCode.TAB &&
                    $(this).data("ui-autocomplete").menu.active) {
                    event.preventDefault();
                }
            })
            .autocomplete({
                source: function (request, response) {
                    $.getJSON(ajaxurl + '?action=wpdm_cal_suggest_members', {
                        action: 'wpdm_cal_suggest_members',
                        term: extractLast($("#maname").val())
                    }, response);
                },
                search: function () {

                    var term = extractLast($("#maname").val());
                    if (term.length < 2) {
                        return false;
                    }
                },
                focus: function () {

                    return false;
                },
                select: function (event, ui) {
                    $('#uaco').prepend('<span style="margin-right: 3px" class="btn btn-simple btn-sm" id="uaco-' + ui.item.value.replace(/[^a-zA-Z]/ig, '-') + '"><input type="hidden" name="access[users][]" value="' + ui.item.value + '" /> <a class="uaco-del" onclick="jQuery(this.rel).remove()" rel="#uaco-' + ui.item.value.replace(/[^a-zA-Z]/ig, '-') + '"><i class="far fa-times-circle"></i></a>&nbsp;' + ui.item.value + '</span>');
                    this.value = "";
                    return false;
                }
            });

        $("#_maname")
            .bind("keydown", function (event) {
                if (event.keyCode === $.ui.keyCode.TAB &&
                    $(this).data("ui-autocomplete").menu.active) {
                    event.preventDefault();
                }
            })
            .autocomplete({
                source: function (request, response) {
                    $.getJSON(ajaxurl + '?action=wpdm_cal_suggest_members', {
                        action: 'wpdm_cal_suggest_members',
                        term: extractLast($("#_maname").val())
                    }, response);
                },
                search: function () {

                    var term = extractLast($("#_maname").val());
                    if (term.length < 2) {
                        return false;
                    }
                },
                focus: function () {

                    return false;
                },
                select: function (event, ui) {
                    $('#_uaco').prepend('<span style="margin-right: 3px" class="btn btn-simple btn-sm" id="uaco-' + ui.item.value.replace(/[^a-zA-Z]/ig, '-') + '"><input type="hidden" name="access[users][]" value="' + ui.item.value + '" /> <a class="uaco-del" onclick="jQuery(this.rel).remove()" rel="#uaco-' + ui.item.value.replace(/[^a-zA-Z]/ig, '-') + '"><i class="far fa-times-circle"></i></a>&nbsp;' + ui.item.value + '</span>');
                    this.value = "";
                    return false;
                }
            });


        if(localStorage.getItem("__wpdm_fm_copy") != undefined && localStorage.getItem("__wpdm_fm_copy") != ''){
            $('#btn-paste').removeAttr('disabled').attr("data-item", localStorage.getItem("__wpdm_fm_copy"));
        }

        refresh_scandir(current_path);
        expand_dir('<?php echo md5('home'); ?>');

        $('.ttip').tooltip();


    });

</script>


<?php if(is_admin()){ ?>
    <script>
        jQuery(function ($) {
            /*$('[data-simplebar], #cogwin > .panel').css('height', (window.innerHeight - 160)+'px');
            $(window).on('resize', function() {
                $('[data-simplebar], #cogwin > .panel').css('height', (window.innerHeight - 160) + 'px');
                if($('#mainfmarea').is(":fullscreen")){
                    $('[data-simplebar]').css('height', (window.innerHeight - 135) + 'px');
                }
            });*/
        });
    </script>
    </div>
<?php }
