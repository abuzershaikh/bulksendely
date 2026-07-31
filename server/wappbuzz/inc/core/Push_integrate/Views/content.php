<form class="actionForm" action="<?php _e( get_module_url("save") )?>" method="POST" data-result="html" data-content="ajax-result" date-redirect="false" data-loading="true">
    
    <div class="container my-5">
        <div class="mb-5">
            <h2><i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e("Integration")?></h2>
            <p><?php _e( $config['desc'] )?></p>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card border b-r-10">
                    <div class="card-body p-40">
                        <div class="fs-16 mb-5"><?php _e("Integrating Web Push Notification into your website is straightforward if you have HTTPS enabled. Simply follow these two steps:", false)?></div>
                        <ul>
                            <li class="fs-16 mb-5">
                                <div class="mb-5">
                                    <span class="fw-bold"><i class="fad fa-chevron-circle-right text-primary"></i> <?php _e("Step 1:")?></span> <?php _e( sprintf(__("Upload the file (%s/sw.js) into the root directory of your website, typically the public_html or html folder on your web server. (Required)"), get_data($result, "domain")) )?>
                                </div>
                                <div class="fs-16 mb-5 text-center">
                                    <div class="alert alert-success d-flex align-items-center b-r-10 mw-250 m-auto">
                                        <div class="me-3"><i class="fad fa-arrow-alt-circle-down fs-40"></i></div>
                                        <div>
                                            <span class="fw-bold"><?php _e("Download file:")?></span>
                                            <span><a href="<?php _ec( base_url("push/download_sw_file") )?>">sw.js</a></span>
                                        </div>
                                    </div>
                                    
                                </div>
                            </li>
                            <li class="fs-16 mb-2">
                                <span class="fw-bold"><i class="fad fa-chevron-circle-right text-primary"></i> <?php _e("Step 2:")?></span> 
                                <?php _e("Insert the provided code below into your website, ideally just before the closing head tag.")?>
                            </li>
                        </ul>
<textarea class="code-editor h-275 w-100" name="embed_code" rows="4">
<script type="text/javascript">
    (function(d, t) {
        var g = d.createElement(t),
        s = d.getElementsByTagName(t)[0];
        g.src = "<?php _ec( base_url($result->integrate_file) )?>";
        var u = document.createAttribute("webpush-client-id");
        var d = document.createAttribute("webpush-domain-id");
        u.value = "<?php _ec( get_team("ids") )?>"; d.value = "<?php _ec( get_push_domain("ids") )?>";
        g.attributes.setNamedItem(u); g.attributes.setNamedItem(d);
        s.parentNode.insertBefore(g, s);
    }(document, "script"));
</script></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</form>

<script type="text/javascript">
    $(function(){
        Core.code_editor();
    });
</script>
