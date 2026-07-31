<?php
namespace Core\Push_integrate\Models;
use CodeIgniter\Model;

class Push_integrateModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function block_push_quicks($path = ""){
        return [
            "position" => 3000
        ];
    }

    public function block_push_permissions($path = ""){
        return [
            "position" => 1100
        ];
    }

    public function block_push(){
        return array(
            "position" => isset($this->config['parent']['position'])?$this->config['parent']['position']:10000,
            "config" => $this->config
        );
    }
}
