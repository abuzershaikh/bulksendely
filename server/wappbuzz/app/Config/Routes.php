<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php'))
{
	require SYSTEMPATH . 'Config/Routes.php';
}

/**
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('Core\Home\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');
$routes->match(['get', 'post'], 'login', '\Core\Home\Controllers\Home::login');
$routes->match(['get', 'post'], 'signup', '\Core\Home\Controllers\Home::signup');
$routes->match(['get', 'post'], 'forgot_password', '\Core\Home\Controllers\Home::forgot_password');
$routes->match(['get', 'post'], 'recovery_password', '\Core\Home\Controllers\Home::recovery_password');
$routes->get('pricing', '\Core\Home\Controllers\Home::pricing');
$routes->get('faqs', '\Core\Home\Controllers\Home::faqs');
$routes->get('blogs', '\Core\Home\Controllers\Home::blogs');
$routes->get('blogs/(:segment)/(:num)', '\Core\Home\Controllers\Home::blogs/$1/$2');
$routes->match(['get', 'post'], 'privacy_policy', '\Core\Home\Controllers\Home::privacy_policy');
$routes->match(['get', 'post'], 'terms_of_service', '\Core\Home\Controllers\Home::terms_of_service');
$routes->get('activation/(:segment)', '\Core\Home\Controllers\Home::activation/$1');
$routes->get('local-whatsapp-access', '\Core\Home\Controllers\Home::local_whatsapp_access');
$routes->get('local-dashboard-access', '\Core\Home\Controllers\Home::local_dashboard_access');
$routes->get('workspace/crm', '\App\Controllers\Workspace::crm');
$routes->get('workspace/ai-agent', '\App\Controllers\Workspace::ai_agent');
$routes->get('workspace/form-builder', '\App\Controllers\Workspace::form_builder');
$routes->post('auth/login', '\Core\Auth\Controllers\Auth::login');
$routes->post('auth/signup', '\Core\Auth\Controllers\Auth::signup');
$routes->post('auth/forgot_password', '\Core\Auth\Controllers\Auth::forgot_password');
$routes->post('auth/recovery_password', '\Core\Auth\Controllers\Auth::recovery_password');
$routes->get('login/(:segment)', '\Core\Auth\Controllers\Auth::social_login/$1');
$routes->match(['get', 'post'], 'Android_api/request_pairing', '\Core\Android_api\Controllers\Android_api::request_pairing');
$routes->match(['get', 'post'], 'Android_api/sync_contacts', '\Core\Android_api\Controllers\Android_api::sync_contacts');
$routes->match(['get', 'post'], 'Android_api/sync_templates', '\Core\Android_api\Controllers\Android_api::sync_templates');
$routes->match(['get', 'post'], 'Android_api/sync_list_templates', '\Core\Android_api\Controllers\Android_api::sync_list_templates');
$routes->match(['get', 'post'], 'Android_api/launch_campaign', '\Core\Android_api\Controllers\Android_api::launch_campaign');
$routes->match(['get', 'post'], 'Android_api/logout_instance', '\Core\Android_api\Controllers\Android_api::logout_instance');
$routes->match(['get', 'post'], 'Android_api/reset_instance', '\Core\Android_api\Controllers\Android_api::reset_instance');
$routes->match(['get', 'post'], 'api/campaigns/launch', '\Core\Android_api\Controllers\Android_api::launch_campaign');
$routes->match(['get', 'post'], 'android_api/request_pairing', '\Core\Android_api\Controllers\Android_api::request_pairing');
$routes->match(['get', 'post'], 'android_api/sync_contacts', '\Core\Android_api\Controllers\Android_api::sync_contacts');
$routes->match(['get', 'post'], 'android_api/sync_templates', '\Core\Android_api\Controllers\Android_api::sync_templates');
$routes->match(['get', 'post'], 'android_api/sync_list_templates', '\Core\Android_api\Controllers\Android_api::sync_list_templates');
$routes->match(['get', 'post'], 'android_api/launch_campaign', '\Core\Android_api\Controllers\Android_api::launch_campaign');
$routes->match(['get', 'post'], 'android_api/logout_instance', '\Core\Android_api\Controllers\Android_api::logout_instance');
$routes->match(['get', 'post'], 'android_api/reset_instance', '\Core\Android_api\Controllers\Android_api::reset_instance');
$routes->match(['get', 'post'], 'admin_api/list_whatsapp_parent_groups', '\Core\Admin_API\Controllers\Admin_API::list_whatsapp_parent_groups');
$routes->match(['get', 'post'], 'admin_api/save_whatsapp_parent_group', '\Core\Admin_API\Controllers\Admin_API::save_whatsapp_parent_group');
$routes->match(['get', 'post'], 'admin_api/delete_whatsapp_parent_group', '\Core\Admin_API\Controllers\Admin_API::delete_whatsapp_parent_group');
$routes->match(['get', 'post'], 'admin_api/provision_waziper_user', '\Core\Admin_API\Controllers\Admin_API::provision_waziper_user');
$routes->match(['get', 'post'], 'admin_api/save_campaign_status', '\Core\Admin_API\Controllers\Admin_API::save_campaign_status');
$routes->match(['get', 'post'], 'admin_api/list_campaign_status', '\Core\Admin_API\Controllers\Admin_API::list_campaign_status');
$routes->match(['get', 'post'], 'admin_api/save_group_sender_status', '\Core\Admin_API\Controllers\Admin_API::save_group_sender_status');
$routes->match(['get', 'post'], 'admin_api/list_group_sender_status', '\Core\Admin_API\Controllers\Admin_API::list_group_sender_status');
$routes->match(['get', 'post'], 'admin_api/list_recent_forward_messages', '\Core\Admin_API\Controllers\Admin_API::list_recent_forward_messages');
$routes->match(['get', 'post'], 'admin_api/storage_status', '\Core\Admin_API\Controllers\Admin_API::storage_status');
$routes->match(['get', 'post'], 'admin_api/request_storage_cleanup', '\Core\Admin_API\Controllers\Admin_API::request_storage_cleanup');

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php'))
{
	require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}

/**
 * --------------------------------------------------------------------
 * Include Modules Routes Files
 * --------------------------------------------------------------------
 */
if (file_exists(ROOTPATH.'inc/plugins')) {
    $modulesPath = ROOTPATH.'inc/plugins/';
    $modules = scandir($modulesPath);

    foreach ($modules as $module) {
        if ($module === '.' || $module === '..') continue;
        if (is_dir($modulesPath) . '/' . $module) {
            $routesPath = $modulesPath . $module . '/Config/Routes.php';
            if (file_exists($routesPath)) {
                require($routesPath);
            } else {
                continue;
            }
        }
    }
}

if (file_exists(ROOTPATH.'inc/core')) {
    $modulesPath = ROOTPATH.'inc/core/';
    $modules = scandir($modulesPath);

    foreach ($modules as $module) {
        if ($module === '.' || $module === '..') continue;
        if (is_dir($modulesPath) . '/' . $module) {
            $routesPath = $modulesPath . $module . '/Config/Routes.php';
            if (file_exists($routesPath)) {
                require($routesPath);
            } else {
                continue;
            }
        }
    }
}

if (file_exists(ROOTPATH.'inc/themes/backend')) {
    $modulesPath = ROOTPATH.'inc/themes/backend/';
    $modules = scandir($modulesPath);

    foreach ($modules as $module) {
        if ($module === '.' || $module === '..') continue;
        if (is_dir($modulesPath) . '/' . $module) {
            $routesPath = $modulesPath . $module . '/Config/Routes.php';
            if (file_exists($routesPath)) {
                require($routesPath);
            } else {
                continue;
            }
        }
    }
}

if (file_exists(ROOTPATH.'inc/themes/frontend')) {
    $modulesPath = ROOTPATH.'inc/themes/frontend/';
    $modules = scandir($modulesPath);

    foreach ($modules as $module) {
        if ($module === '.' || $module === '..') continue;
        if (is_dir($modulesPath) . '/' . $module) {
            $routesPath = $modulesPath . $module . '/Config/Routes.php';
            if (file_exists($routesPath)) {
                require($routesPath);
            } else {
                continue;
            }
        }
    }
}

if ( file_exists( realpath(  __DIR__."/../Helpers" ) ) ) {
    $helperPath = realpath(  __DIR__."/../Helpers/" )."/";
    $helpers = scandir($helperPath);
    foreach ($helpers as $helper) {
        if ($helper === '.' || $helper === '..' || stripos( $helper , "_helper.php") === false) continue;
        if (  file_exists( $helperPath.$helper ) ) {
            require_once( $helperPath.$helper );
        }
    }
}
