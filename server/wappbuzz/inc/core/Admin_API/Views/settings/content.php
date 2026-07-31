<div class="container my-5">
    <div class="card card-flush">
        <div class="card-header mt-6">
            <div class="card-title flex-column">
                <h3 class="fw-bolder"><i class="fad fa-network-wired" style="color: <?php _ec($config['color']) ?>;"></i> <?php _e('Admin API') ?></h3>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <label for="admin_api_key" class="form-label"><?php _e('Admin API Key') ?></label>
                <input type="text" class="form-control form-control-solid" id="admin_api_key" name="admin_api_key" value="<?php _ec(get_option("admin_api_key", "asg12345")) ?>">
            </div>
            <div class="mb-4">
                <button class="btn btn-primary" type="button" id="keygen"><?php _e('Create Random Key') ?></button>
            </div>
        </div>
    </div>



    <div class="m-t-25">
        <button type="submit" class="btn btn-primary"><?php _e('Save') ?></button>
    </div>
</div>

<script>
    /**
     * Function to produce UUID.
     * See: http://stackoverflow.com/a/8809472
     */
    function generateUUID() {
        var d = new Date().getTime();

        if (window.performance && typeof window.performance.now === "function") {
            d += performance.now();
        }

        var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = (d + Math.random() * 16) % 16 | 0;
            d = Math.floor(d / 16);
            return (c == 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });

        return uuid;
    }

    /**
     * Generate new key and insert into input value
     */
    $('#keygen').on('click', function() {
        $('#admin_api_key').val(generateUUID());
    });
</script>