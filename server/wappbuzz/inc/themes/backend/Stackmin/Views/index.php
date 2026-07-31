<!DOCTYPE html>
<html lang="en" dir="<?php _ec( request_service("language")->dir )?>" data-theme="<?php _ec( get_option("theme_color", "light") )?>">
    <head><base href="">
        <meta charset="utf-8" />
        <title><?php _e($title)?></title>
        <meta name="description" content="<?php _e( get_option("website_description", "") )?>" />
        <meta name="keywords" content="<?php _e( get_option("website_description", "") )?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="shortcut icon" href="<?php _ec( get_option("website_favicon", base_url("assets/img/favicon.svg")) )?>" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/fonts/fontawesome/css/all.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/fonts/icomoon/icomoon.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/fonts/flags/flag-icon.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/pagination/pagination.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/izitoast/izitoast.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/webui-popover/webui-popover.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery-ui/jquery-ui.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/datetimepicker/timepicker-addon.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/emojionearea/emojionearea.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/tagsinput/bootstrap-tagsinput.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/owlcarousel/owl.carousel.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/owlcarousel/owl.theme.default.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/fancybox/jquery.fancybox.min.css" rel="stylesheet" type="text/css"></link>
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/minicolors/jquery.minicolors.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/select2/css/select2.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/plugins/monthly/monthly.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/css/animate.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php _ec( get_theme_url() ) ?>Assets/css/style.css" rel="stylesheet" type="text/css" />
        <style>
            body.wagrow-sidebar {
                background: linear-gradient(180deg, #f7f9ff 0%, #eef4ff 100%);
                font-family: "Inter", "Poppins", sans-serif;
            }

            body.wagrow-sidebar .header {
                display: flex;
                width: calc(100% - 360px);
                height: 78px;
                background: rgba(255, 255, 255, 0.88);
                border-bottom: 1px solid rgba(148, 163, 184, 0.14);
                backdrop-filter: blur(14px);
                box-shadow: 0 18px 40px rgba(15, 23, 42, 0.05);
            }

            body.wagrow-sidebar .sidebar-wrapper {
                width: 290px;
                padding: 18px 0 18px 18px;
                background: transparent;
            }

            body.wagrow-sidebar .sidebar {
                width: 100%;
                border-radius: 28px;
                border: 1px solid rgba(148, 163, 184, 0.18);
                background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
                box-shadow: 0 28px 70px rgba(30, 41, 59, 0.10);
            }

            body.wagrow-sidebar .sidebar-logo {
                width: 100%;
                padding: 22px 26px 10px;
                align-items: flex-start !important;
            }

            body.wagrow-sidebar .sidebar .logo-big {
                display: block;
                max-width: 132px;
                height: auto;
            }

            body.wagrow-sidebar .sidebar .logo-small {
                display: none;
            }

            body.wagrow-sidebar .sidebar-nav,
            body.wagrow-sidebar .sidebar-footer {
                width: 100%;
                padding: 0 14px;
            }

            body.wagrow-sidebar .sidebar-nav .nav,
            body.wagrow-sidebar .sidebar-footer .nav {
                gap: 2px;
            }

            body.wagrow-sidebar .sb-head-text .menu-content {
                padding: 14px 14px 8px !important;
            }

            body.wagrow-sidebar .sb-head-text .menu-section {
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.18em;
                color: #94a3b8 !important;
            }

            body.wagrow-sidebar .sidebar .nav-item {
                width: 100%;
            }

            body.wagrow-sidebar .sidebar .nav-link {
                display: flex;
                align-items: center;
                gap: 12px;
                min-height: 52px;
                margin: 0;
                padding: 10px 14px !important;
                border-radius: 18px;
                color: #475569;
                transition: all 0.2s ease;
            }

            body.wagrow-sidebar .sidebar .nav-link i {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 38px !important;
                height: 38px;
                min-width: 38px;
                border-radius: 14px;
                background: #eef2ff;
                box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.08);
            }

            body.wagrow-sidebar .sidebar .nav-link span {
                display: inline-block !important;
                opacity: 1 !important;
                font-size: 14px;
                font-weight: 600;
                color: #334155;
            }

            body.wagrow-sidebar .sidebar .nav-link.hoverable:hover,
            body.wagrow-sidebar .sidebar .nav-link.active {
                background: linear-gradient(135deg, #eff6ff 0%, #eef2ff 100%);
                color: #1d4ed8;
                box-shadow: 0 14px 30px rgba(59, 130, 246, 0.12);
            }

            body.wagrow-sidebar .sidebar .nav-link.active span,
            body.wagrow-sidebar .sidebar .nav-link.hoverable:hover span {
                color: #1d4ed8;
            }

            body.wagrow-sidebar .sidebar .menu-sub {
                margin: 6px 0 10px 54px;
                padding: 4px 0 0 12px;
                border-left: 1px dashed rgba(148, 163, 184, 0.45);
            }

            body.wagrow-sidebar .sidebar .menu-link {
                display: block;
                padding: 8px 0;
                font-size: 13px;
                font-weight: 500;
            }

            body.wagrow-sidebar .sidebar .nav-line,
            body.wagrow-sidebar .menu-separator {
                display: none;
            }

            body.wagrow-sidebar .main {
                padding-left: 18px;
            }

            body.wagrow-sidebar .sidebar-footer {
                padding-bottom: 16px;
            }

            @media (max-width: 991.98px) {
                body.wagrow-sidebar .header {
                    width: 100%;
                }

                body.wagrow-sidebar .sidebar-wrapper {
                    width: 0;
                    padding-left: 0;
                }
            }
        </style>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery/jquery.min.js"></script>
        <?php _ec( load_files("css") );?>
        <?php _ec( add_script_to_header() )?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@15.0.1/build/css/intlTelInput.css">
        
    </head>
    <body class="sidebar-open wagrow-sidebar <?php _ec( get_option("theme_color", "light") )?>">
        <div class="loading">
            <div class="loading-icon">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <?php _ec( $this->include('Backend\Stackmin\Views\header'), false )?>

        <div class="d-flex h-100">
            <?php _ec( $this->include('Backend\Stackmin\Views\sidebar'), false )?>
            <?php _ec( $this->renderSection('content'), false )?>
        </div>

        <div class="sidebar-popover"></div>
        
        <?php _ec( add_script_to_footer() )?>
        <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@15.0.2/build/js/intlTelInput.js"></script>
        <script src="https://code.highcharts.com/highcharts.js"></script>
        <script src="https://code.highcharts.com/maps/modules/map.js"></script>
        <script src="https://code.highcharts.com/mapdata/custom/world.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/tinymce/tinymce.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/nicescroll/nicescroll.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/izitoast/izitoast.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/pagination/pagination.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/webui-popover/webui-popover.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery-ui/jquery-ui.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/datetimepicker/timepicker-addon.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/emojionearea/emojionearea.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/tagsinput/bootstrap-tagsinput.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/owlcarousel/owl.carousel.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/daterangepicker/moment.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/daterangepicker/daterangepicker.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/fancybox/jquery.fancybox.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/minicolors/jquery.minicolors.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/select2/js/select2.full.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery.md5/jquery.md5.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/monthly/monthly.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery-ace/ace/ace.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery-ace/jquery-ace.min.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery-ace/ace/mode-php.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/plugins/jquery-ace/ace/theme-monokai.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/js/layout.js"></script>
        <script src="<?php _ec( get_theme_url() ) ?>Assets/js/core.js"></script>
        <?php _ec( load_files("js") );?>

    </body>
</html>
