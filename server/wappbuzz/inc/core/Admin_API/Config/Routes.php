<?php
$config = include realpath(__DIR__ . "/../Config.php");
if (!defined('MODULE_CONFIG')) {
    define("MODULE_CONFIG", $config);
}

if(
    isset($config['menu']) && 
    isset($config['menu']['sub_menu']) && 
    isset($config['menu']['sub_menu']["id"]) && 
    (url_is( $config['menu']['sub_menu']["id"] ) || url_is( $config['menu']['sub_menu']["id"].'/*' )) 
){
    $routes->setDefaultNamespace( ucfirst($config['folder']) . "/" . ucfirst($config['menu']['sub_menu']["id"]) . "/Controllers");
}else if( url_is( $config["id"] ) || url_is( $config["id"].'/*' ) ){
    $routes->setDefaultNamespace( ucfirst($config['folder']) . "/" . ucfirst($config['id']) . "/Controllers");
}


$routes->group('', ['namespace' => 'Core\Admin_API\Controllers'], static function ($routes) {
    $routes->get('admin_api/', 'Admin_API::index');
    $routes->get('admin_api/users', 'Admin_API::get_users');
    $routes->post('admin_api/users', 'Admin_API::create_user');
    $routes->put('admin_api/users', 'Admin_API::update_user');
    $routes->delete('admin_api/users', 'Admin_API::delete_user');

    $routes->get('admin_api/get_autologin', 'Admin_API::get_autologin');
    $routes->get('admin_api/check_token', 'Admin_API::check_token');
    $routes->get('admin_api/migrate_users', 'Admin_API::migrate_users');
    $routes->post('admin_api/provision_waziper_user', 'Admin_API::provision_waziper_user');
    $routes->post('admin_api/save_campaign_status', 'Admin_API::save_campaign_status');
    $routes->post('admin_api/list_campaign_status', 'Admin_API::list_campaign_status');
    $routes->post('admin_api/get_campaign_status_detail', 'Admin_API::get_campaign_status_detail');
    $routes->post('admin_api/save_group_sender_status', 'Admin_API::save_group_sender_status');
    $routes->post('admin_api/list_group_sender_status', 'Admin_API::list_group_sender_status');
    $routes->post('admin_api/get_group_sender_status_detail', 'Admin_API::get_group_sender_status_detail');
    $routes->post('admin_api/list_recent_forward_messages', 'Admin_API::list_recent_forward_messages');
    $routes->post('admin_api/save_whatsapp_parent_group', 'Admin_API::save_whatsapp_parent_group');
    $routes->post('admin_api/list_whatsapp_parent_groups', 'Admin_API::list_whatsapp_parent_groups');
    $routes->get('admin_api/list_whatsapp_parent_groups', 'Admin_API::list_whatsapp_parent_groups');
    $routes->post('admin_api/delete_whatsapp_parent_group', 'Admin_API::delete_whatsapp_parent_group');
    $routes->get('admin_api/delete_whatsapp_parent_group', 'Admin_API::delete_whatsapp_parent_group');
});

if (file_exists(realpath(__DIR__ . "/../Helpers"))) {
    $helperPath = realpath(__DIR__ . "/../Helpers/") . "/";
    $helpers = scandir($helperPath);
    foreach ($helpers as $helper) {
        if ($helper === '.' || $helper === '..' || stripos($helper, "_helper.php") === false) continue;
        if (file_exists($helperPath . $helper)) {
            require_once($helperPath . $helper);
        }
    }
}
