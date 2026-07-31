<?php
namespace Core\Whatsapp_link_generator\Models;
use CodeIgniter\Model;

class Whatsapp_link_generatorModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function block_plans(){
        return [
            "tab" => 15,
            "position" => 700,
            "label" => __("Whatsapp tool"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => $this->config['name'],
                ],
            ]
        ];
    }

    public function block_whatsapp(){
        return array(
            "position" => 8500,
            "config" => $this->config,
        );
    }
}
