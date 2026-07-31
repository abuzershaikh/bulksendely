<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center mb-5 mt-2">
    <div class="bd-search position-relative me-auto py-3">
        <h2 class="mb-0 py-0"> <i class="<?php _ec($config['icon']) ?> me-2" style="color: <?php _ec($config['color']) ?>;"></i> <?php _e($config['name']) ?></h2>
        <p class="mb-0"><?php _e($config['desc']) ?></p>
    </div>
    <div class="">
        <div class="dropdown me-2">
            <div class="input-group input-group-sm sp-input-group border b-r-4">
                <span class="input-group-text border-0 fs-20 bg-gray-100 text-gray-800" id="sub-menu-search"><i class="fad fa-search"></i></span>
                <input type="text" class="ajax-pages-search ajax-filter form-control form-control-solid ps-15 border-0" name="keyword" value="" placeholder="<?php _e("Search") ?>" autocomplete="off">
                <a href="<?php _ec(get_module_url("index/update")) ?>" class="btn btn-light btn-active-light-primary m-r-1 border-end" title="<?php _e("New campaign") ?>" data-toggle="tooltip" data-placement="top"><i class="fad fa-plus text-primary"></i></a>
                <a href="<?php _ec(get_module_url('popup_report')) ?>" class="btn btn-light btn-active-light-success actionItem" data-popup="ReportBulkModal" title="<?php _e("Report") ?>" data-toggle="tooltip" data-placement="top"><i class="fad fa-file-chart-line text-success"></i></a>
            </div>
        </div>
    </div>
</div>
<?php if (get_data($datatable, "total_items") != 0) : ?>
    <div class="container">
        <div class="ajax-pages" data-url="<?php _ec(get_module_url("ajax_list")) ?>" data-response=".ajax-result" data-per-page="<?php _ec(get_data($datatable, "per_page")) ?>" data-current-page="<?php _ec(get_data($datatable, "current_page")) ?>" data-total-items="<?php _ec(get_data($datatable, "total_items")) ?>">

            <div class="ajax-result row mt-4"></div>

            <nav class="m-t-50 m-b-50 ajax-pagination m-auto text-center"></nav>
        </div>
    </div>

    <script>
        setTimeout(function() {
            var wa_server = '<?php echo get_option('whatsapp_server_url', '') ?>';
            //console.log(bulk_socket);
            //console.log(wa_server);
            if (!bulk_socket && wa_server != '') {
                var bulk_socket = io(wa_server, {
                    transports: ['polling']
                });

                bulk_socket.on("connect_error", () => {
                    setTimeout(() => {
                        bulk_socket.connect();
                    }, 1000);
                });

                bulk_socket.on('connect', () => {
                    console.log('connected ' + bulk_socket.id + '...');
                });

                bulk_socket.on('update_campaign_<?php echo get_team("id"); ?>', (args) => {
                    console.log(`campaign id ${args.id} is updating...`);
                    $('.bulk-sent-' + args.id).html(args.sent);
                    $('.bulk-failed-' + args.id).html(args.failed);
                    $('.bulk-pending-' + args.id).html(args.total_phone_numbers - args.sent - args.failed);

                    if (args.total_phone_numbers - args.sent - args.failed <= 0) {
                        $('.bulk-status-' + args.id).html('<i class="fs-18 fad fa-check-circle text-success" title="Ended"></i>');
                        $('.bulk-next-' + args.id).html('-');
                    } else {
                        var t = moment.unix(args.next);
                        $('.bulk-next-' + args.id).html(t.format('DD/MM/YYYY h:mm A'));
                        $('.bulk-status-' + args.id).html('<i class="fs-18 fad fa-circle-notch fa-spin text-primary"  title="Running"></i>');
                    }

                    var percent = (args.sent + args.failed) / args.total_phone_numbers;
                    //console.log(args.sent, args.failed, args.sent + args.failed, args.total_phone_numbers, percent);
                    var percent = parseInt(percent.toFixed(2) * 100);
                    $('.bulk-progress-label-' + args.id).html(`${percent}%`);
                    $('.bulk-progress-bar-' + args.id).width(`${percent}%`);
                    $('.bulk-progress-bar-' + args.id).prop('aria-valuenow', percent);


                });

                bulk_socket.on('end_campaign_<?php echo get_team("id"); ?>', (args) => {
                    console.log(`campaign id ${args.id} was completed...`);
                    $('.bulk-status-' + args.id).html('<i class="fs-18 fad fa-check-circle text-success" title="Ended"></i>');
                    $('.bulk-next-' + args.id).html('-');
                });

                bulk_socket.on('pause_campaign_<?php echo get_team("id"); ?>', (args) => {
                    console.log(`campaign id ${args.id} was paused...`);
                    $('.bulk-status-' + args.id).html('<i class="fs-18 fad fa-pause-circle text-warning"  title="Paused"></i>');
                    $('.bulk-next-' + args.id).html('-');
                });

                bulk_socket.on("connect_error", (err) => {
                    console.log(`connect_error due to ${err.message}`);
                });
            }
        }, 3000);
    </script>

    <script type="text/javascript">
        $(function() {
            Core.ajax_pages();
        });
    </script>
<?php else : ?>
    <div class="container">
        <div class="d-flex align-items-center align-self-center h-100 mih-500">
            <div class="w-100">
                <div class="text-center px-4">
                    <img class="mh-190 mb-4" alt="" src="<?php _e(get_theme_url()) ?>Assets/img/empty2.png">
                </div>
            </div>
        </div>
    </div>
<?php endif ?>