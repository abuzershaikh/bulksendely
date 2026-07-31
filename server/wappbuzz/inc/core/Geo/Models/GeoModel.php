<?php
namespace Core\Geo\Models;
use CodeIgniter\Model;

class GeoModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }
}
