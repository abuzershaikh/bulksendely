<?php

$routes->group('Android_api', ['namespace' => 'Core\Android_api\Controllers'], static function ($routes) {
    $routes->post('request_pairing', 'Android_api::request_pairing');
    $routes->post('sync_contacts', 'Android_api::sync_contacts');
    $routes->post('sync_templates', 'Android_api::sync_templates');
    $routes->post('sync_list_templates', 'Android_api::sync_list_templates');
    $routes->post('launch_campaign', 'Android_api::launch_campaign');
    $routes->post('sync_chatbot_templates', 'Android_api::sync_chatbot_templates');
    $routes->post('sync_chatbot_list_templates', 'Android_api::sync_chatbot_list_templates');
    $routes->post('delete_chatbot_template', 'Android_api::delete_chatbot_template');
    $routes->post('sync_chatbot_flow', 'Android_api::sync_chatbot_flow');
    $routes->post('delete_chatbot_flow', 'Android_api::delete_chatbot_flow');
    $routes->post('get_chatbot_settings', 'Android_api::get_chatbot_settings');
    $routes->post('save_chatbot_settings', 'Android_api::save_chatbot_settings');
});

$routes->group('', ['namespace' => 'Core\Android_api\Controllers'], static function ($routes) {
    $routes->post('api/request_pairing', 'Android_api::request_pairing');
    $routes->post('api/sync_contacts', 'Android_api::sync_contacts');
    $routes->post('api/sync_templates', 'Android_api::sync_templates');
    $routes->post('api/sync_list_templates', 'Android_api::sync_list_templates');
    $routes->post('api/campaigns/launch', 'Android_api::launch_campaign');
    $routes->post('api/launch_campaign', 'Android_api::launch_campaign');
    $routes->post('api/sync_chatbot_templates', 'Android_api::sync_chatbot_templates');
    $routes->post('api/sync_chatbot_list_templates', 'Android_api::sync_chatbot_list_templates');
    $routes->post('api/delete_chatbot_template', 'Android_api::delete_chatbot_template');
    $routes->post('api/sync_chatbot_flow', 'Android_api::sync_chatbot_flow');
    $routes->post('api/delete_chatbot_flow', 'Android_api::delete_chatbot_flow');
    $routes->post('api/get_chatbot_settings', 'Android_api::get_chatbot_settings');
    $routes->post('api/save_chatbot_settings', 'Android_api::save_chatbot_settings');
    
    // New exact endpoints for Flutter App
    $routes->post('api/welcome_messages/save', 'Android_api::sync_welcome_message');
    $routes->post('api/keyword_replies/save', 'Android_api::sync_keyword_replies');
    $routes->post('api/menu_replies/save', 'Android_api::sync_menu_replies');
});
