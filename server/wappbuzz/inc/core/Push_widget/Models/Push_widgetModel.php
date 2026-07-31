<?php
namespace Core\Push_widget\Models;
use CodeIgniter\Model;

class Push_widgetModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function block_push_permissions($path = ""){
        return [
            "position" => 1100
        ];
    }

    public function block_push_settings($path = ""){
        return [
            "position" => 7000,
            "menu" => view( 'Core\Push_widget\Views\settings\menu', [ 'config' => $this->config ] ),
            "content" => view( 'Core\Push_widget\Views\settings\content', [ 'config' => $this->config ] )
        ];
    }

    public function block_push(){
        return array(
            "position" => isset($this->config['parent']['position'])?$this->config['parent']['position']:10000,
            "config" => $this->config
        );
    }
}
