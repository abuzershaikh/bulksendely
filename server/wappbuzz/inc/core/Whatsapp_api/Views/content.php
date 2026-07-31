<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center">
    <div class="bd-search position-relative me-auto mt-5">
        <div class="mb-5">
            <h2><i class="<?php _ec($config['icon']) ?> me-2" style="color: <?php _ec($config['color']) ?>;"></i> <?php _ec($config['name']) ?></h2>
            <p><?php _e($config['desc']) ?></p>
        </div>
    </div>
</div>
<div class="container">
    <form method="POST">
        <div class="card b-r-10 mb-5">
            <div class="card-body p-10">

                <select name="account" data-control="select2" data-hide-search="true" class="form-select form-select-sm bg-body fw-bold border-0 miw-130 auto-submit">
                    <option value="609ACF283XXXX" data-icon="fab fa-whatsapp" data-icon-color="#25d366"><span><?php _e("Select WhatsApp account") ?></span></option>
                    <?php if (!empty($accounts)) : ?>

                        <?php foreach ($accounts as $key => $value) : ?>
                            <option value="<?php _ec($value->token) ?>" <?php _ec($account == $value->token ? 'selected' : '')  ?> data-img="<?php _ec(get_file_url($value->avatar)) ?>"><?php _ec($value->name) ?></option>
                        <?php endforeach ?>

                    <?php else : ?>

                    <?php endif ?>
                </select>

            </div>
        </div>
    </form>
</div>

<div class="container mb-5 card p-25 b-r-10 text-gray-700">
    <div class="row">
        <div class="col-12">
            <div class="alert alert-success p-20 m-b-30" role="alert">
                <?php _e("Your Access Token:") ?> <strong><?php _ec(get_team("ids")) ?></strong>
            </div>

            <h5 class="border-bottom m-b-30 p-b-20 text-dark text-uppercase"><?php _e("Instance Api") ?></h5>
            <h6 class="border-bottom m-b-30 p-b-20 p-t-20" id="create-instance"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Create Instance") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/create_instance?access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <div class="text">
                <?php _e("Create a new Instance ID") ?>
            </div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>
<h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="get-qr-code"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Send Pedido") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send_pedido?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <div class="text"><?php _e("Envie notificações de <b>status de pedido<b>")?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params")?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec( get_team("ids") )?></td>
                    </tr>
                </tbody>
            </table>
            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="get-qr-code"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Get QR Code") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/get_qrcode?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <div class="text"><?php _e("Display QR code to login to Whatsapp web. You can get the results returned via Webhook") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="set-receving-webhook"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Set Receving Webhook") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/set_webhook?webhook_url=https://webhook.site/1b25464d6833784f96eef4xxxxxxxxxx&enable=true&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <div class="text"><?php _e("Get all return values from Whatsapp. Like connection status, Incoming message, Outgoing message, Disconnected, Change Battery,...") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">webhook_url</td>
                        <td>https://webhook.site/1b25464d6833784f96eef4xxxxxxxxxx</td>
                    </tr>
                    <tr>
                        <td class="fw-6">enable</td>
                        <td>true</td>
                    </tr>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="reboot-instance"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Reboot Instance") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/reboot?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <div class="text">
                <?php _e("Logout Whatsapp web and do a fresh scan") ?>
            </div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="reset-instance"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Reset Instance") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/reset_instance?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <div class="text">
                <?php _e("This will logout Whatsapp web, Change Instance ID, Delete all old instance data") ?>
            </div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="reconnect"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> <?php _e("Reconnect") ?></h6>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/reconnect?instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>

            <div class="text">
                <?php _e("Re-initiate connection from app to Whatsapp web when lost connection") ?>
            </div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td><?php _e($account) ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td><?php _ec(get_team("ids")) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row p-t-25 p-b-25">
        <div class="col-12">
            <h5 class="border-bottom m-b-30 p-b-20 text-dark text-uppercase"><?php _e("Send Direct Message Api") ?></h5>
            <h6 class="border-bottom m-b-30 p-b-20 p-t-20" id="send-text"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> 1. ✅ <?php _e("Send Text") ?></h6>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send?number=84933313xxx&type=text&message=test%20message&instance_id=".  $account ."&access_token=" . get_team("ids"))) ?>
                </code>
            </div>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"number": "911234567890",</span><br>
                    <span class="ms-4">"type": "text",</span><br>
                    <span class="ms-4">"message": "Hello from WappBuzz!",</span><br>
                    <span class="ms-4">"instance_id": "695E1B4E*****",</span><br>
                    <span class="ms-4">"access_token": "692fbc0******"</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Send a text message to a phone number through the app") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">number</td>
                        <td>
                            <?php _e("Individual:") ?> <code>911234567890</code><br>
                            <?php _e("Group:") ?> <code>120363290960******@g.us</code>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-6">type</td>
                        <td>text</td>
                    </tr>
                    <tr>
                        <td class="fw-6">message</td>
                        <td><?php _ec("Hello from WappBuzz!") ?></td>
                    </tr>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td>695E1B4E*****</td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td>692fbc0******</td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="send-image"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> 2. 📸✅ <?php _e("Send Image") ?></h6>

            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"number": "911234567890",</span><br>
                    <span class="ms-4">"type": "image",</span><br>
                    <span class="ms-4">"media_url": "https://example.in/writable/uploads/1764765894_cc08eccb3d42c7353aab.png",</span><br>
                    <span class="ms-4">"message": "Hello from WappBuzz!",</span><br>
                    <span class="ms-4">"instance_id": "695E1B4E*****",</span><br>
                    <span class="ms-4">"access_token": "692fbc0******"</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Send an image with optional caption to a phone number") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">number</td>
                        <td>
                            <?php _e("Individual:") ?> <code>911234567890</code><br>
                            <?php _e("Group:") ?> <code>120363290960******@g.us</code>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-6">type</td>
                        <td>image</td>
                    </tr>
                    <tr>
                        <td class="fw-6">media_url</td>
                        <td>https://example.in/writable/uploads/1764765894_cc08eccb3d42c7353aab.png</td>
                    </tr>
                    <tr>
                        <td class="fw-6">message</td>
                        <td><?php _ec("Hello from WappBuzz!") ?> <span class="text-muted small">(<?php _e("Optional caption") ?>)</span></td>
                    </tr>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td>695E1B4E*****</td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td>692fbc0******</td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="send-video"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> 3. 🎥✅ <?php _e("Send Video") ?></h6>

            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"number": "911234567890",</span><br>
                    <span class="ms-4">"type": "video",</span><br>
                    <span class="ms-4">"media_url": "https://example.in/writable/uploads/1767849002_ddd0b7dbc9fa9176a1f2.mp4",</span><br>
                    <span class="ms-4">"message": "Hello from WappBuzz!",</span><br>
                    <span class="ms-4">"instance_id": "695E1B4E*****",</span><br>
                    <span class="ms-4">"access_token": "692fbc0******"</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Send a video with optional caption to a phone number") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">number</td>
                        <td>
                            <?php _e("Individual:") ?> <code>911234567890</code><br>
                            <?php _e("Group:") ?> <code>120363290960******@g.us</code>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-6">type</td>
                        <td>video</td>
                    </tr>
                    <tr>
                        <td class="fw-6">media_url</td>
                        <td>https://example.in/writable/uploads/1767849002_ddd0b7dbc9fa9176a1f2.mp4</td>
                    </tr>
                    <tr>
                        <td class="fw-6">message</td>
                        <td><?php _ec("Hello from WappBuzz!") ?> <span class="text-muted small">(<?php _e("Optional caption") ?>)</span></td>
                    </tr>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td>695E1B4E*****</td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td>692fbc0******</td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="send-audio"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> 4. 🔉✅ <?php _e("Send Audio") ?></h6>

            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"number": "911234567890",</span><br>
                    <span class="ms-4">"type": "audio",</span><br>
                    <span class="ms-4">"media_url": "https://example.in/writable/uploads/1767849932_4bdb24de12a18348f371.ogg",</span><br>
                    <span class="ms-4">"message": "Hello from WappBuzz!",</span><br>
                    <span class="ms-4">"instance_id": "695E1B4E*****",</span><br>
                    <span class="ms-4">"access_token": "692fbc0******"</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Send an audio file with optional caption to a phone number") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">number</td>
                        <td>
                            <?php _e("Individual:") ?> <code>911234567890</code><br>
                            <?php _e("Group:") ?> <code>120363290960******@g.us</code>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-6">type</td>
                        <td>audio</td>
                    </tr>
                    <tr>
                        <td class="fw-6">media_url</td>
                        <td>https://example.in/writable/uploads/1767849932_4bdb24de12a18348f371.ogg</td>
                    </tr>
                    <tr>
                        <td class="fw-6">message</td>
                        <td><?php _ec("Hello from WappBuzz!") ?> <span class="text-muted small">(<?php _e("Optional caption") ?>)</span></td>
                    </tr>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td>695E1B4E*****</td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td>692fbc0******</td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom m-b-30 p-b-20 m-t-40 p-t-20" id="send-document"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> 5. 📃✅ <?php _e("Send Document") ?></h6>

            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"number": "911234567890",</span><br>
                    <span class="ms-4">"type": "document",</span><br>
                    <span class="ms-4">"media_url": "https://example.in/writable/uploads/1767850332_e8cc9d8be985b7347181.pdf",</span><br>
                    <span class="ms-4">"message": "Hello from WappBuzz!",</span><br>
                    <span class="ms-4">"instance_id": "695E1B4E*****",</span><br>
                    <span class="ms-4">"access_token": "692fbc0******"</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Send a document file (PDF, DOC, XLS, etc.) with optional caption to a phone number") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">number</td>
                        <td>
                            <?php _e("Individual:") ?> <code>911234567890</code><br>
                            <?php _e("Group:") ?> <code>120363290960******@g.us</code>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-6">type</td>
                        <td>document</td>
                    </tr>
                    <tr>
                        <td class="fw-6">media_url</td>
                        <td>https://example.in/writable/uploads/1767850332_e8cc9d8be985b7347181.pdf</td>
                    </tr>
                    <tr>
                        <td class="fw-6">message</td>
                        <td><?php _ec("Hello from WappBuzz!") ?> <span class="text-muted small">(<?php _e("Optional caption") ?>)</span></td>
                    </tr>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td>695E1B4E*****</td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td>692fbc0******</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>



    <div class="row p-t-25 p-b-25">
        <div class="col-12">
            <h5 class="border-bottom m-b-30 p-b-20 text-dark text-uppercase"><?php _e("Button / List / Poll / Others Template API") ?></h5>
            <h6 class="border-bottom m-b-30 p-b-20 p-t-20" id="send-template"><span class="text-success fw-6 m-r-5"><?php _e("POST") ?></span> 6. 📋✅ <?php _e("Send Template Message") ?></h6>
            <label><?php _e("Resource URL:") ?></label>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    <?php _ec(base_url("api/send")) ?>
                </code>
            </div>

            <label><?php _e("Structure of the POST request body:") ?></label>
            <div class="text-success fs-12 mb-1"><?php _e("Content-Type: application/json") ?></div>
            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"number": "911234567890",</span><br>
                    <span class="ms-4">"type": "template",</span><br>
                    <span class="ms-4">"template_id": "6953a4e7375c8",</span><br>
                    <span class="ms-4">"instance_id": "695E1B4E*****",</span><br>
                    <span class="ms-4">"access_token": "692fbc0******"</span><br>
                    }
                </code>
            </div>

            <div class="text"><?php _e("Send interactive template messages including Button messages, List messages, Poll messages, and other template-based content. Use template_id to reference pre-configured message templates.") ?></div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Params") ?></div>

            <table class="table table-striped table-borderless">
                <tbody>
                    <tr>
                        <td class="fw-6">number</td>
                        <td>
                            <?php _e("Individual:") ?> <code>911234567890</code><br>
                            <?php _e("Group:") ?> <code>120363290960******@g.us</code>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-6">type</td>
                        <td>template</td>
                    </tr>
                    <tr>
                        <td class="fw-6">template_id</td>
                        <td>6953a4e7375c8 <span class="text-muted small">(<?php _e("UUID of the pre-configured template - use the Template ID shown in your dashboard") ?>)</span></td>
                    </tr>
                    <tr>
                        <td class="fw-6">instance_id</td>
                        <td>695E1B4E*****</td>
                    </tr>
                    <tr>
                        <td class="fw-6">access_token</td>
                        <td>692fbc0******</td>
                    </tr>
                </tbody>
            </table>

            <div class="alert alert-info m-t-20" role="alert">
                <strong><i class="fad fa-info-circle"></i> <?php _e("Template Types Supported:") ?></strong>
                <ul class="mb-0 mt-2">
                    <li><?php _e("Button Messages - Interactive buttons with up to 3 options (Individual chats only)") ?></li>
                    <li><?php _e("List Messages - Dropdown menus with multiple sections and rows (Individual chats only)") ?></li>
                    <li><?php _e("Poll Messages - Create polls with multiple choice options (Works in both individual and group chats)") ?></li>
                    <li><?php _e("Custom Templates - Your own pre-configured message templates") ?></li>
                </ul>
            </div>

            <div class="alert alert-warning m-t-20" role="alert">
                <strong><i class="fad fa-exclamation-triangle"></i> <?php _e("Important Notes:") ?></strong>
                <ul class="mb-0 mt-2">
                    <li><?php _e("Template IDs must be created and configured in your dashboard before use") ?></li>
                    <li><?php _e("Use the Template ID (UUID format like '694e5133c38f8') shown in your template list, NOT the auto-increment ID") ?></li>
                    <li><?php _e("The 'message' parameter can be used to override the template's default text") ?></li>
                    <li><?php _e("Templates help maintain consistency and reduce API payload size") ?></li>
                </ul>
            </div>

            <div class="alert alert-danger m-t-20" role="alert">
                <strong><i class="fad fa-users"></i> <?php _e("Group Messaging Compatibility:") ?></strong>
                <ul class="mb-0 mt-2">
                    <li><?php _e("✅ <strong>Poll Templates:</strong> Fully supported in WhatsApp groups") ?></li>
                    <li><?php _e("❌ <strong>Button Templates:</strong> NOT supported in groups (WhatsApp limitation)") ?></li>
                    <li><?php _e("❌ <strong>List Templates:</strong> NOT supported in groups (WhatsApp limitation)") ?></li>
                    <li><?php _e("💡 <strong>Tip:</strong> To send to groups, use group ID format: <code>120363290960******@g.us</code>") ?></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row p-t-25 p-b-25">
        <div class="col-12">
            <h5 class="border-bottom m-b-30 p-b-20 text-dark text-uppercase"><?php _e("Group Messaging Guide") ?></h5>

            <div class="alert alert-info" role="alert">
                <h6 class="alert-heading"><i class="fad fa-info-circle"></i> <?php _e("How to Send Messages to WhatsApp Groups") ?></h6>
                <p class="mb-0"><?php _e("To send messages to WhatsApp groups, use the same <code>/api/send</code> endpoint with the group ID in the <code>number</code> parameter. Group IDs follow the format: <code>120363290960******@g.us</code>") ?></p>
            </div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Number Format Examples") ?></div>

            <table class="table table-striped table-borderless">
                <thead>
                    <tr>
                        <th class="fw-6"><?php _e("Recipient Type") ?></th>
                        <th class="fw-6"><?php _e("Number Format") ?></th>
                        <th class="fw-6"><?php _e("Example") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php _e("Individual Contact") ?></td>
                        <td><code>CountryCode + PhoneNumber</code></td>
                        <td><code>911234567890</code></td>
                    </tr>
                    <tr>
                        <td><?php _e("WhatsApp Group") ?></td>
                        <td><code>GroupID@g.us</code></td>
                        <td><code>120363290960******@g.us</code></td>
                    </tr>
                </tbody>
            </table>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Example: Send Text to Individual") ?></div>

            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"number": "911234567890",</span><br>
                    <span class="ms-4">"type": "text",</span><br>
                    <span class="ms-4">"message": "Hello from WappBuzz!",</span><br>
                    <span class="ms-4">"instance_id": "695E1B4E*****",</span><br>
                    <span class="ms-4">"access_token": "692fbc0******"</span><br>
                    }
                </code>
            </div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Example: Send Text to Group") ?></div>

            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"number": "120363290960******@g.us",</span><br>
                    <span class="ms-4">"type": "text",</span><br>
                    <span class="ms-4">"message": "Hello group members!",</span><br>
                    <span class="ms-4">"instance_id": "695E1B4E*****",</span><br>
                    <span class="ms-4">"access_token": "692fbc0******"</span><br>
                    }
                </code>
            </div>

            <div class="text-uppercase fs-16 border-bottom m-b-15 p-b-10 m-t-30"><?php _e("Example: Send Image to Group") ?></div>

            <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                <code class="text-gray-800 fs-12">
                    {<br>
                    <span class="ms-4">"number": "120363290960******@g.us",</span><br>
                    <span class="ms-4">"type": "image",</span><br>
                    <span class="ms-4">"media_url": "https://example.in/writable/uploads/1764765894_cc08eccb3d42c7353aab.png",</span><br>
                    <span class="ms-4">"message": "Check out this image!",</span><br>
                    <span class="ms-4">"instance_id": "695E1B4E*****",</span><br>
                    <span class="ms-4">"access_token": "692fbc0******"</span><br>
                    }
                </code>
            </div>

            <div class="alert alert-success m-t-20" role="alert">
                <strong><i class="fad fa-check-circle"></i> <?php _e("Supported in Groups:") ?></strong>
                <ul class="mb-0 mt-2">
                    <li><?php _e("✅ Text Messages") ?></li>
                    <li><?php _e("✅ Image Messages") ?></li>
                    <li><?php _e("✅ Video Messages") ?></li>
                    <li><?php _e("✅ Audio Messages") ?></li>
                    <li><?php _e("✅ Document Messages") ?></li>
                    <li><?php _e("✅ Poll Templates") ?></li>
                </ul>
            </div>

            <div class="alert alert-danger m-t-20" role="alert">
                <strong><i class="fad fa-times-circle"></i> <?php _e("NOT Supported in Groups:") ?></strong>
                <ul class="mb-0 mt-2">
                    <li><?php _e("❌ Button Templates - WhatsApp does not support interactive buttons in group chats") ?></li>
                    <li><?php _e("❌ List Templates - WhatsApp does not support list messages in group chats") ?></li>
                </ul>
            </div>

            <div class="alert alert-warning m-t-20" role="alert">
                <strong><i class="fad fa-exclamation-triangle"></i> <?php _e("Important Notes:") ?></strong>
                <ul class="mb-0 mt-2">
                    <li><?php _e("To get group IDs, use the WhatsApp interface to view your groups") ?></li>
                    <li><?php _e("Group IDs always end with <code>@g.us</code>") ?></li>
                    <li><?php _e("Individual phone numbers use country code format (e.g., <code>911234567890</code>)") ?></li>
                    <li><?php _e("The same <code>/api/send</code> endpoint works for both individuals and groups") ?></li>
                    <li><?php _e("Only change the <code>number</code> parameter to switch between individual and group messaging") ?></li>
                </ul>
            </div>
        </div>
    </div>


</div>