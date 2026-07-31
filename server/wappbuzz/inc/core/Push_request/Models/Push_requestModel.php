<?php
namespace Core\Push_request\Models;
use CodeIgniter\Model;

class Push_requestModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }
}
