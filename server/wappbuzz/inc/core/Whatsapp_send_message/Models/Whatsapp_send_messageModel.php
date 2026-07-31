<?php
namespace Core\Whatsapp_send_message\Models;
use CodeIgniter\Model;

class Whatsapp_send_messageModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function block_quicks($path = ""){
        return [
            "position" => 1100
        ];
    }

    public function block_plans(){
        return [
            "tab" => 15,
            "position" => 500,
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
            "position" => 5000,
            "config" => $this->config
        );
    }
}
