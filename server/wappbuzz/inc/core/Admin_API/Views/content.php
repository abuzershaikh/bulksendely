<?php

$categories = [
    [
        "name" => "Manage Users",
        "endpoints" => [
            [
                "name" => "Get All Users",
                "method" => "GET",
                "description" => __("Get all users registered"),
                "endpoint" => base_url("admin_api/users?api_key=" . $api_key . '&page=1&per_page=20&search=user@email.com'),
                "params" => [
                    ['api_key', "String", __("API Key to authorize request"), 1],
                    ["page", "Integer", __("Page to request"), 0],
                    ["per_page", "Integer", __("Number of items per page"), 0],
                    ["search", "String", __("String to search"), 0]
                ],
                "body" => null
            ], [
                "name" => "Create User",
                "method" => "POST",
                "description" => __("Create a new user"),
                "endpoint" => base_url("admin_api/users?api_key=" . $api_key),
                "params" => [
                    ['api_key', "String", __("API Key to authorize request"), 1],
                ],
                "body" => json_encode([
                    "username" => "newuser",
                    "fullname" => "New User Full Name",
                    "email" => "user@email.com",
                    "password" => "userpassword",
                    "expired_date" => "01-06-2023",
                    "timezone" => "America/Mexico_City",
                    "plan_id" => 1,
                    "is_admin" => 0,
                    "status" => 2
                ], JSON_PRETTY_PRINT)
            ], [
                "name" => "Update User",
                "method" => "PUT",
                "description" => __("Update a user's data by id, the id is in the last url segment, if you don't want to modify some values you can omit them from the body."),
                "endpoint" => base_url("admin_api/users?api_key=" . $api_key . '&user=user@email.com'),
                "params" => [
                    ['api_key', "String", __("API Key to authorize request"), 1],
                    ['user', "String", __("user email of user to update"), 1],
                ],
                "body" => json_encode([
                    "username" => "newuser",
                    "fullname" => "New User Full Name",
                    "password" => "userpassword",
                    "expired_date" => "01-06-2023",
                    "timezone" => "America/Mexico_City",
                    "plan_id" => 1,
                    "is_admin" => 0,
                    "status" => 2
                ], JSON_PRETTY_PRINT)
            ], [
                "name" => "Delete User",
                "method" => "DELETE",
                "description" => __("Delete a user's data by id, the id is in the last url segment."),
                "endpoint" => base_url("admin_api/users?api_key=" . $api_key . '&user=user@email.com'),
                "params" => [
                    ['api_key', "String", __("API Key to authorize request"), 1],
                    ['user', "String", __("user email of user to delete"), 1],
                ],
                "body" => null
            ], [
                "name" => "Get Auto-login Token",
                "method" => "GET",
                "description" => __("Gets the token and the url to autologin."),
                "endpoint" => base_url("admin_api/get_autologin?api_key=" . $api_key . '&user=user@email.com'),
                "params" => [
                    ['api_key', "String", __("API Key to authorize request"), 1],
                    ['user', "String", __("valid Email of user"), 1],
                ],
                "body" => null
            ]
        ]
    ]
]

?>

<style>
    .td200 {
        width: 200px;
        min-width: 200px;
    }

    .td300 {
        min-width: 400px;
    }

    td req::before {
        content: "*";
        color: var(--danger);
    }
</style>

<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-between">
    <div class="bd-search position-relative mt-5">
        <div class="mb-5">
            <h2><i class="<?php _ec($config['icon']) ?> me-2" style="color: <?php _ec($config['color']) ?>;"></i> <?php _ec($config['name']) ?></h2>
            <p><?php _e($config['desc']) ?></p>
        </div>
    </div>
    <div class="mt-5">
        <div class="text-end">
            <span class="badge bg-primary fs-14 px-3 py-2">
                <i class="fad fa-code-branch me-1"></i>
                <?php _e("Wappbuzz Version:") ?> <strong><?php _ec(get_option('app_version', '6.0.3')) ?></strong>
            </span>
        </div>
    </div>
</div>


<div class="container mb-5 card p-25 b-r-10 text-gray-700">
    <div class="row">
        <div class="col-12">
            <div class="alert alert-success p-20 m-b-30" role="alert">
                <?php _e("Your Admin API Key:") ?> <strong><?php _ec($api_key) ?></strong><br /><?php _e("You can change this on settings") ?>
            </div>

            <?php foreach ($categories as $key => $cat) : ?>
                <h5 class="border-bottom m-b-30 p-b-20 text-dark text-uppercase"><?php _e($cat['name']) ?></h5>
                <?php foreach ($cat['endpoints'] as $key => $ep) :
                    switch ($ep["method"]) {
                        case 'GET':
                            $color = "success";
                            break;
                        case 'POST':
                            $color = "info";
                            break;
                        case 'PUT':
                            $color = "warning";
                            break;
                        case 'DELETE':
                            $color = "danger";
                            break;

                        default:
                            $color = 'info';
                            break;
                    }
                ?>

                    <h6 class="border-bottom m-b-30 p-b-20 p-t-20" id="create-instance">
                        <span class="text-<?php echo $color ?> fw-6 m-r-5"><?php echo $ep["method"] ?></span>
                        <span class="fs-18"> <?php echo $ep["name"] ?></span>
                    </h6>
                    <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                        <code class="text-gray-800 fs-12"><?php _ec($ep["endpoint"] ?? '') ?></code>
                    </div>
                    <div class="text">
                        <?php echo $ep['description'] ?? '' ?>
                    </div>

                    <div class="badge bg-primary mb-1 mt-2 fs-12"><?php _e("Params") ?></div>
                    <div class="table-responsive-lg">
                        <table class="table table-striped table-borderless mt-1">
                            <thead>
                                <tr>
                                    <th scope="col"><?php _e("Param") ?></th>
                                    <th scope="col"><?php _e("Type") ?></th>
                                    <th scope="col"><?php _e("Description") ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ep['params'] ?? [] as $key => $par) : ?>
                                    <tr>
                                        <td scope="row" class="td200">
                                            <span><?php echo $par[0] ?></span>
                                            <?php echo $par[3] ?? 0 == 1 ? '<req />' : '' ?>
                                        </td>
                                        <td class="td200"><span><?php echo $par[1] ?></span></td>
                                        <td class="td300"><span><?php echo $par[2] ?></span></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($ep['body']) : ?>
                        <div class="badge bg-primary mb-1 mt-2 fs-12"><?php _e("Body") ?></div>
                        <div class="alert alert-dark bg-gray-100 border-gray-500" role="alert" onclick='window.getSelection().selectAllChildren(this)'>
                            <pre> <code class="text-gray-800 fs-12"><?php _ec($ep['body']) ?></code></pre>
                        </div>
                    <?php endif ?>
                    <div class="border border-bottom border-3 my-3 border-info"></div>
                <?php endforeach ?>
            <?php endforeach ?>
        </div>
    </div>
</div>