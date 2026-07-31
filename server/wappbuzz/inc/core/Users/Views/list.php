<form class="" action="<?php _ec( get_module_url("delete") )?>">
<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center">
    <div class="bd-search position-relative me-auto">
        <h2 class="mb-0 py-4 text-gray-800"> <i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e($config['name'])?></h2>
    </div>
    <div class="">
        <div class="dropdown me-2">
            <div class="input-group input-group-sm sp-input-group border b-r-4">
                <span class="input-group-text border-0 fs-20 bg-gray-100 text-gray-800" id="sub-menu-search"><i class="fad fa-search"></i></span>
                <input type="text" class="ajax-pages-search ajax-filter form-control form-control-solid ps-15 border-0" name="keyword" value="" placeholder="<?php _e("Search")?>" autocomplete="off">
                <a href="<?php _ec( get_module_url("index/update") )?>" class="btn btn-light btn-active-light-primary m-r-1 border-end" title="<?php _e("Add new")?>" data-toggle="tooltip" data-placement="top"><i class="fad fa-plus text-primary pe-0"></i></a>
                <a href="javascript:void(0);" class="btn btn-light btn-active-light-info m-r-1 border-end" title="<?php _e("Bulk Upload Users")?>" data-toggle="tooltip" data-placement="top" onclick="$('#bulkUploadModal').modal('show');"><i class="fad fa-file-upload text-info pe-0"></i></a>
                <a href="<?php _e( get_module_url('export') )?>" class="btn btn-light btn-active-light-success m-r-1 border-end" title="<?php _e("Export users")?>" data-toggle="tooltip" data-placement="top"><i class="fad fa-file-export text-success pe-0"></i></a>
                <a href="<?php _e( get_module_url('delete') )?>" class="btn btn-light btn-active-light-danger actionMultiItem" data-confirm="<?php _e('Are you sure to delete this items?')?>" data-remove-other-active="true" data-active="bg-light-danger" data-result="html" data-content="main-wrapper" data-redirect="<?php _ec( current_url() )?>" title="<?php _e("Delete")?>" data-toggle="tooltip" data-placement="top"><i class="fad fa-trash-alt text-danger pe-0"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="container my-4">
    <div class="card card-flush b-r-10">
        <div class="card-body py-0 px-0 pb-5">

            <?php if ( isset($datatable) ): ?>

                <div class="<?php _e( get_data($datatable, "responsive")? "table-responsive":"" )?>">

                    <?php if ( is_array( get_data($datatable, "columns") ) ): ?>

                        <table 
                            class="ajax-pages table table align-middle table-row-dashed fs-13 gy-5" 
                            data-url="<?php _ec( get_module_url("ajax_list") )?>" 
                            data-response=".ajax-result" 
                            data-per-page="<?php _ec( get_data($datatable, "per_page") )?>"
                            data-current-page="<?php _ec( get_data($datatable, "current_page") )?>"
                            data-total-items="<?php _ec( get_data($datatable, "total_items") )?>"
                        >
                            <thead>
                                <tr class="text-start text-muted fw-bolder text-uppercase gs-0">

                                    <?php foreach ( get_data($datatable, "columns") as $key => $value ): ?>

                                        <?php if ( $key == "id" ): ?>
                                        <th scope="col" class="w-20 border-bottom py-4 ps-4">
                                            <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                                <input class="form-check-input checkbox-all" type="checkbox">
                                            </div>
                                        </th>
                                        <?php else: ?>
                                        <th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e( $value )?></th>
                                        <?php endif ?>
                                    <?php endforeach ?>
                                    <th class="border-bottom py-4 pe-4"></th>
                                </tr>
                            </thead>
                            <tbody class="ajax-result"></tbody>
                        </table>

                    <?php endif ?>

                </div>
                
            <?php endif ?>

            <?php if (get_data($datatable, "total_items") != 0): ?>
            <nav class="m-t-50 ajax-pagination m-auto text-center mb-4"> </nav>
            <?php endif ?>

        </div>
    </div>
</div>
</form>

<!-- Bulk Upload Modal -->
<div class="modal fade" id="bulkUploadModal" tabindex="-1" role="dialog" aria-labelledby="bulkUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkUploadModalLabel"><i class="fad fa-file-upload text-info me-2"></i><?php _e("Bulk Upload Users") ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bulkUploadForm" action="<?php _ec( get_module_url("bulk_upload") )?>" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info" role="alert">
                        <h6 class="alert-heading"><i class="fad fa-info-circle"></i> <?php _e("Instructions") ?></h6>
                        <ul class="mb-0">
                            <li><?php _e("Upload a CSV file with user data") ?></li>
                            <li><?php _e("The CSV format should match the 'Export users' format") ?></li>
                            <li><?php _e("Required columns: ids, is_admin, fullname, username, email, password, plan, expiration_date, timezone, status, last_login, changed, created") ?></li>
                            <li><?php _e("Existing users (matched by ids, username, or email) can be updated or skipped") ?></li>
                            <li><?php _e("New users will be created with default password: 123456 (if password field is empty)") ?></li>
                            <li><?php _e("If duplicates are found, you'll be asked to choose: Update or Skip") ?></li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label for="csv_file" class="form-label fw-bold"><?php _e("Select CSV File") ?> <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                        <div class="form-text"><?php _e("Only CSV files are accepted") ?></div>
                    </div>

                    <div class="alert alert-warning" role="alert">
                        <h6 class="alert-heading"><i class="fad fa-exclamation-triangle"></i> <?php _e("CSV Format & Required Columns") ?></h6>
                        <p class="mb-2"><strong><?php _e("Required columns:") ?></strong></p>
                        <code class="d-block bg-dark text-light p-2 rounded small mb-2">
                            ids, is_admin, fullname, username, email, password, plan, expiration_date, timezone, status, last_login, changed, created
                        </code>
                        <p class="mb-2 small"><strong><?php _e("Full CSV format (use Export to get all columns):") ?></strong></p>
                        <code class="d-block bg-dark text-light p-2 rounded small">
                            id,ids,oauth_id,is_admin,role,fullname,username,email,whatsapp,password,plan,expiration_date,timezone,lang,login_type,avatar,data,status,last_login,desc,changed,created
                        </code>
                        <p class="mt-2 mb-0 small"><?php _e("Tip: Export existing users to get the correct format, then modify the data as needed") ?></p>
                    </div>

                    <div id="uploadProgress" class="d-none">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                        </div>
                        <p class="text-center mt-2"><?php _e("Processing...") ?></p>
                    </div>

                    <div id="uploadResult" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e("Cancel") ?></button>
                    <button type="submit" class="btn btn-primary" id="uploadBtn"><i class="fad fa-upload me-2"></i><?php _e("Upload & Import") ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Duplicate Confirmation Modal -->
<div class="modal fade" id="duplicateConfirmModal" tabindex="-1" role="dialog" aria-labelledby="duplicateConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="duplicateConfirmModalLabel"><i class="fad fa-exclamation-triangle me-2"></i><?php _e("Duplicate Users Found") ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning" role="alert">
                    <h6 class="alert-heading"><i class="fad fa-info-circle"></i> <?php _e("Action Required") ?></h6>
                    <p class="mb-0"><?php _e("The following users already exist in the database. Please choose how to handle them:") ?></p>
                </div>

                <div id="duplicateList" class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th><?php _e("Line") ?></th>
                                <th><?php _e("IDs") ?></th>
                                <th><?php _e("Username") ?></th>
                                <th><?php _e("Email") ?></th>
                                <th><?php _e("Full Name") ?></th>
                            </tr>
                        </thead>
                        <tbody id="duplicateTableBody">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info mt-3" role="alert">
                    <h6 class="alert-heading"><i class="fad fa-question-circle"></i> <?php _e("Choose an action:") ?></h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="duplicateAction" id="actionUpdate" value="update" checked>
                        <label class="form-check-label" for="actionUpdate">
                            <strong><?php _e("Update existing users") ?></strong> - <?php _e("Overwrite existing user data with CSV data") ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="duplicateAction" id="actionSkip" value="skip">
                        <label class="form-check-label" for="actionSkip">
                            <strong><?php _e("Skip existing users") ?></strong> - <?php _e("Only create new users, ignore duplicates") ?>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e("Cancel") ?></button>
                <button type="button" class="btn btn-primary" id="proceedWithUpload"><i class="fad fa-check me-2"></i><?php _e("Proceed with Import") ?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function(){
        Core.ajax_pages();

        // Store CSV file and content globally
        var csvFileData = null;
        var csvContent = null;

        // Handle bulk upload form submission - Step 1: Check for duplicates
        $('#bulkUploadForm').on('submit', function(e){
            e.preventDefault();

            var fileInput = $('#csv_file')[0];

            if (!fileInput.files.length) {
                Core.alert('error', '<?php _e("Please select a CSV file") ?>');
                return false;
            }

            // Read CSV file content
            var file = fileInput.files[0];
            var reader = new FileReader();

            reader.onload = function(e) {
                csvContent = e.target.result;
                csvFileData = new FormData($('#bulkUploadForm')[0]);

                // Show progress
                $('#uploadProgress').removeClass('d-none');
                $('#uploadBtn').prop('disabled', true);
                $('#uploadResult').html('');

                // Check for duplicates first
                $.ajax({
                    url: '<?php _ec( get_module_url("bulk_upload") )?>',
                    type: 'POST',
                    data: {
                        action: 'check_duplicates',
                        csv_content: csvContent
                    },
                    dataType: 'json',
                    success: function(response){
                        $('#uploadProgress').addClass('d-none');
                        $('#uploadBtn').prop('disabled', false);

                        if (response.status === 'success') {
                            if (response.total_duplicates > 0) {
                                // Show duplicate confirmation modal
                                showDuplicateModal(response.duplicates);
                            } else {
                                // No duplicates, proceed with upload
                                proceedWithUpload('update');
                            }
                        } else {
                            Core.alert('error', response.message);
                        }
                    },
                    error: function(){
                        $('#uploadProgress').addClass('d-none');
                        $('#uploadBtn').prop('disabled', false);
                        Core.alert('error', '<?php _e("Failed to check for duplicates") ?>');
                    }
                });
            };

            reader.readAsText(file);
            return false;
        });

        // Show duplicate confirmation modal
        function showDuplicateModal(duplicates) {
            var tbody = $('#duplicateTableBody');
            tbody.empty();

            duplicates.forEach(function(dup){
                var row = '<tr>' +
                    '<td>' + dup.line + '</td>' +
                    '<td>' + dup.ids + '</td>' +
                    '<td>' + dup.username + '</td>' +
                    '<td>' + dup.email + '</td>' +
                    '<td>' + dup.fullname + '</td>' +
                    '</tr>';
                tbody.append(row);
            });

            $('#bulkUploadModal').modal('hide');
            $('#duplicateConfirmModal').modal('show');
        }

        // Handle proceed with upload button
        $('#proceedWithUpload').on('click', function(){
            var action = $('input[name="duplicateAction"]:checked').val();
            $('#duplicateConfirmModal').modal('hide');
            proceedWithUpload(action);
        });

        // Step 2: Proceed with actual upload
        function proceedWithUpload(duplicateAction) {
            if (!csvContent) {
                Core.alert('error', '<?php _e("No CSV data available") ?>');
                return;
            }

            // Show progress in original modal
            $('#bulkUploadModal').modal('show');
            $('#uploadProgress').removeClass('d-none');
            $('#uploadBtn').prop('disabled', true);
            $('#uploadResult').html('');

            // Send CSV content and duplicate action
            $.ajax({
                url: '<?php _ec( get_module_url("bulk_upload") )?>',
                type: 'POST',
                data: {
                    action: 'process_upload',
                    csv_content: csvContent,
                    duplicate_action: duplicateAction
                },
                dataType: 'json',
                success: function(response){
                    $('#uploadProgress').addClass('d-none');
                    $('#uploadBtn').prop('disabled', false);

                    if (response.status === 'success') {
                        var resultHtml = '<div class="alert alert-success" role="alert">';
                        resultHtml += '<h6 class="alert-heading"><i class="fad fa-check-circle"></i> ' + response.message + '</h6>';

                        if (response.details) {
                            resultHtml += '<ul class="mb-0">';
                            resultHtml += '<li><?php _e("Created") ?>: ' + response.details.created + '</li>';
                            resultHtml += '<li><?php _e("Updated") ?>: ' + response.details.updated + '</li>';
                            resultHtml += '<li><?php _e("Skipped") ?>: ' + response.details.skipped + '</li>';
                            resultHtml += '<li><?php _e("Errors") ?>: ' + response.details.errors + '</li>';
                            resultHtml += '<li><?php _e("Total Processed") ?>: ' + response.details.total_processed + '</li>';
                            resultHtml += '</ul>';
                        }

                        if (response.errors && response.errors.length > 0) {
                            resultHtml += '<hr><p class="mb-1 fw-bold"><?php _e("Errors:") ?></p><ul class="mb-0">';
                            response.errors.forEach(function(error){
                                resultHtml += '<li class="small">' + error + '</li>';
                            });
                            resultHtml += '</ul>';
                        }

                        resultHtml += '</div>';
                        $('#uploadResult').html(resultHtml);

                        // Reload page after 3 seconds
                        setTimeout(function(){
                            location.reload();
                        }, 3000);

                    } else {
                        var errorHtml = '<div class="alert alert-danger" role="alert">';
                        errorHtml += '<h6 class="alert-heading"><i class="fad fa-times-circle"></i> <?php _e("Upload Failed") ?></h6>';
                        errorHtml += '<p class="mb-0">' + response.message + '</p>';

                        if (response.errors && response.errors.length > 0) {
                            errorHtml += '<hr><ul class="mb-0">';
                            response.errors.forEach(function(error){
                                errorHtml += '<li class="small">' + error + '</li>';
                            });
                            errorHtml += '</ul>';
                        }

                        errorHtml += '</div>';
                        $('#uploadResult').html(errorHtml);
                    }

                    // Reset file data
                    csvFileData = null;
                    csvContent = null;
                },
                error: function(xhr, status, error){
                    $('#uploadProgress').addClass('d-none');
                    $('#uploadBtn').prop('disabled', false);

                    var errorHtml = '<div class="alert alert-danger" role="alert">';
                    errorHtml += '<h6 class="alert-heading"><i class="fad fa-times-circle"></i> <?php _e("Upload Failed") ?></h6>';
                    errorHtml += '<p class="mb-0"><?php _e("An error occurred while uploading the file. Please try again.") ?></p>';
                    errorHtml += '</div>';
                    $('#uploadResult').html(errorHtml);

                    // Reset file data
                    csvFileData = null;
                    csvContent = null;
                }
            });
        }

        // Reset modals when closed
        $('#bulkUploadModal').on('hidden.bs.modal', function(){
            $('#bulkUploadForm')[0].reset();
            $('#uploadResult').html('');
            $('#uploadProgress').addClass('d-none');
            $('#uploadBtn').prop('disabled', false);
            csvFileData = null;
            csvContent = null;
        });

        $('#duplicateConfirmModal').on('hidden.bs.modal', function(){
            $('#duplicateTableBody').empty();
            $('input[name="duplicateAction"][value="update"]').prop('checked', true);
        });
    });
</script>