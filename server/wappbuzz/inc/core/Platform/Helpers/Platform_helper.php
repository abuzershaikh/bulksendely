<?php 
if( ! function_exists("platfrom") ){
    function platfrom($data)
    {
        $platform = db_get("*", TB_PLATFORM, [ "platform_id" => $data["platform_id"] ], "id", "DESC", true);

       	if( empty($platform) ){

       		$default = db_get("*", TB_PLATFORM, [ "is_default" => 1 ]);
       		$is_default = 1;

       		if( $default ){
       			$is_default = 0;
       		}

       		db_insert( TB_PLATFORM, [
       			"ids" => ids(),
       			"platform_id" => $data['platform_id'],
       			"color" => $data['color'],
       			"icon" => $data['icon'],
       			"name" => $data['name'],
       			"default_page" => $data['default_page'],
       			"is_default" => $is_default,
       			"status" => 1
       		] );

       		return $data;

       	}else{
       		return $platform;
       	}

    }
}

if( ! function_exists("get_platfrom") ){
    function get_platfrom()
    {
        return db_fetch("*", TB_PLATFORM, [ "status" => 1 ], "platform_id", "ASC");
    }
}