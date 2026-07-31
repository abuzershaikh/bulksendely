<?php 
if(!function_exists("geoip")){
	function geoip(){
		include get_module_dir( __DIR__ , 'Libraries/vendor/autoload.php');
		$mmdb_file = get_module_dir( __DIR__ , 'Libraries/geocity.mmdb');

		$data = [
		    "city" => "",
		    "timezone" => "",
		    "country" => "",
		    "country_code" => "",
		    "longitude" => 0,
		    "latitude" => 0,
		    "continent_name" => "",
		    "continent_code" => "",
		    "postal_code" => 0,
		    "ip" => 0,
		    "region_id" => 0,
		    "region_code" => "",
		    "region_name" => "",
		];

		try {
			
			$cityDbReader = new \GeoIp2\Database\Reader($mmdb_file);
			$record = $cityDbReader->city( get_client_ip() );

			$data["city"] = $record->city->name; 
			$data["timezone"] = $record->location->timeZone; 
			$data["country"] = $record->country->name; 
			$data["country_code"] = $record->country->isoCode; 
			$data["longitude"] = $record->location->longitude; 
			$data["latitude"] = $record->location->latitude; 
			$data["postal_code"] = $record->postal->code; 
			$data["continent_code"] = $record->continent->code; 
			$data["continent_name"] = $record->continent->name; 
			$data["ip"] = $record->traits->ipAddress; 
			$data["region_id"] = $record->mostSpecificSubdivision->geonameId; 
			$data["region_code"] = $record->mostSpecificSubdivision->isoCode; 
			$data["region_name"] = $record->mostSpecificSubdivision->name; 
			return $data;
		} catch (\Exception $e) {
			return $data;
		}
	}
}


if (!function_exists('get_client_ip')) {
    function get_client_ip() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])){
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        }
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else if(isset($_SERVER['HTTP_X_FORWARDED'])){
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        }
        else if(isset($_SERVER['HTTP_FORWARDED_FOR'])){
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        }
        else if(isset($_SERVER['HTTP_FORWARDED'])){
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        }
        else if(isset($_SERVER['REMOTE_ADDR'])){
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        }
        else{
            $ipaddress = 'UNKNOWN';
		}

		if($ipaddress == "::1"){
			$ipaddress = "116.111.184.103";
		}
        return $ipaddress;
    }
}