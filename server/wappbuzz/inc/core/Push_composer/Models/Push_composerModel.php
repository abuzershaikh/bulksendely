<?php
namespace Core\Push_composer\Models;
use CodeIgniter\Model;

class Push_composerModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function block_push_permissions($path = ""){
        return [
            "position" => 1100
        ];
    }

    public function block_push_quicks($path = ""){
        return [
            "position" => 1100
        ];
    }

    public function block_plans(){
        return [
            "tab" => 17,
            "position" => 100,
            "label" => __("Web Push Notification"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => $this->config['name'],
                ],
            ]
        ];
    }

    public function block_push(){
        return array(
            "position" => isset($this->config['parent']['position'])?$this->config['parent']['position']:10000,
            "config" => $this->config
        );
    }
}
