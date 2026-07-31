<?php

namespace Core\Extended_Modules\Models;

use CodeIgniter\Model;

class Extended_ModulesModel extends Model
{
    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
    }

    public function block_permissions($path = "")
    {
        return view('Core\Extended_Modules\Views\permissions', ['config' => $this->config]);
    }

    public function get_modules()
    {
        $module_paths = get_module_paths();
        $modules_ext_whatsapp = array();
        $modules_ext_payment = array();
        $modules_ext_integrations = array();
        $modules_ext_general = array();

        if (!empty($module_paths)) {
            foreach ($module_paths as $key => $module_path) {
                $model_paths = $module_path . "/Models/";
                $model_files = glob($model_paths . '*');

                if (!empty($model_files)) {
                    foreach ($model_files as $model_file) {
                        $model_content = get_all_functions($model_file);
                        if (in_array("block_extended_modules", $model_content)) {

                            $config_path = $module_path . "/Config.php";
                            $config_item = include $config_path;

                            include_once $model_file;
                            $class = get_module_class($model_file);

                            $data = new $class;
                            $mod_data = $data->block_whatsapp();

                            $block_content =  $data->block_whatsapp();

                            switch (strtolower($block_content['type'] ?? 'default')) {
                                case 'whatsapp':
                                    $modules_ext_whatsapp[] = $config_item;
                                    break;
                                case 'payment':
                                    $modules_ext_payment = $config_item;
                                    break;
                                case 'integrations':
                                    $modules_ext_integrations = $config_item;
                                    break;
                                default:
                                    $modules_ext_general = $config_item;
                                    break;
                            }
                        }
                    }
                }
            }
        }
    }
}
