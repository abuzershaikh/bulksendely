<?php 
if(!function_exists("push_audience_field")){
	function push_audience_field($field){
		switch ($field) {
		    case 'title':
		        $field = "title";
		        break;

		    case 'description':
		        $field = "message";
		        break;


		    case 'url':
		        $field = "url";
		        break;

		    case 'subscription_url':
		        $field = "subscription_url";
		        break;

		    case 'subscription_date':
		        $field = "subscription_date";
		        break;

		    case 'total_visits':
		        $field = "total_visits";
		        break;

		    case 'first_visit':
		        $field = "first_visit";
		        break;

		    case 'last_visit':
		        $field = "last_visit";
		        break;

		    case 'city':
		        $field = "city";
		        break;

		    case 'country':
		        $field = "country";
		        break;

		    case 'language':
		        $field = "language";
		        break;

		    case 'device':
		        $field = "device";
		        break;

		    case 'os':
		        $field = "os";
		        break;

		    case 'browser':
		        $field = "browser";
		        break;

		    
		    default:
		        $field = false;
		        break;
		}

		return $field;
	}
}


if(!function_exists("push_audience_creator")){
	function push_audience_creator( $audience_id , $fields = "*", $return_list_id = false ){
        $audience_item = db_get("id,data,team_id,domain_id", TB_STACKPUSH_AUDIENCE_CREATOR, [ "id" => $audience_id, "status" => 1 ]);

        if(!$audience_item) return false;

        $audience_data = false;
        if($audience_item->data == "") return false;
        $audience_data = json_decode($audience_item->data, 1);

        if(!isset($audience_data['filters']) || !isset($audience_data['comparators'])) return false;

        $filters = $audience_data['filters'];
        $comparators = $audience_data['comparators'];
        $values = $audience_data['values'];
        $count = 0;

        $db = \Config\Database::connect();
        $builder = $db->table(TB_STACKPUSH_SUBSCRIBER);

        if(is_string($fields)){
        	$builder->select($fields);
        }

        if(is_array($fields)){
        	$builder->select( implode(",", $fields) );
        }

        $contains_arr = [];
        $builder->groupStart();
        $builder->where("domain_id", $audience_item->domain_id);
        $builder->where("team_id", $audience_item->team_id);
        $builder->where("status", 1);
        $builder->groupEnd();

        foreach ($filters as $index => $filter) {
            $builder->groupStart();
            if($filter == ""){
                return false;
            }

            if(empty($comparators[$index])){
                return false;
            }

            $field = push_audience_field($filter);
            $sub_count = 0;
            foreach ($comparators[$index] as $sub_index => $comparator) {

                switch ($comparator) {
                    case 'contains':
                        if( isset($values[$index][$sub_index]) ){
                            if(!$sub_count){
                                $builder->Like($field, $values[$index][$sub_index]);
                            }else{
                                $builder->orLike($field, $values[$index][$sub_index]);
                            }
                        }
                        
                        break;

                    case 'not_contains':
                        if( isset($values[$index][$sub_index]) ){
                            if(!$sub_count){
                                $builder->notLike($field, $values[$index][$sub_index]);
                            }else{
                                $builder->orNotLike($field, $values[$index][$sub_index]);
                            }
                        }
                        break;

                    case 'equal':
                        if( isset($values[$index][$sub_index]) ){
                            if(!$sub_count){
                                $builder->where($field, $values[$index][$sub_index]);
                            }else{
                                $builder->orWhere($field, $values[$index][$sub_index]);
                            }
                        }
                        
                        break;

                    case 'less_than':
                        if( isset($values[$index][$sub_index]) ){
                            if(!$sub_count){
                                $builder->where($field." <= ", $values[$index][$sub_index]);
                            }else{
                                $builder->orWhere($field." <= ", $values[$index][$sub_index]);
                            }
                        }
                        
                        break;

                    case 'greater_than':
                        if( isset($values[$index][$sub_index]) ){
                            if(!$sub_count){
                                $builder->where($field." >= ", $values[$index][$sub_index]);
                            }else{
                                $builder->orWhere($field." >= ", $values[$index][$sub_index]);
                            }
                        }
                        
                        break;

                    case 'within':
                        if( isset($values[$index]["hour"]) && is_array($values[$index]["hour"]) &&  isset($values[$index]["hour"][$sub_index])){
                            if(!$sub_count){
                                $builder->where($field." <= ", time() - (int)$values[$index]["hour"][$sub_index]);
                            }else{
                                $builder->orWhere($field." <= ", time() - (int)$values[$index]["hour"][$sub_index]);
                            }
                        }
                        break;

                    case 'earlier_than':
                        if( isset($values[$index]["hour"]) && is_array($values[$index]["hour"]) &&  isset($values[$index]["hour"][$sub_index])){
                            if(!$sub_count){
                                $builder->where($field." >= ", time() - (int)$values[$index]["hour"][$sub_index]);
                            }else{
                                $builder->orWhere($field." >= ", time() - (int)$values[$index]["hour"][$sub_index]);
                            }
                        }

                    case 'earlier_than':
                        if( isset($values[$index]["hour"]) && is_array($values[$index]["hour"]) &&  isset($values[$index]["hour"][$sub_index])){
                            if(!$sub_count){
                                $builder->where($field." >= ", time() - (int)$values[$index]["hour"][$sub_index]);
                            }else{
                                $builder->orWhere($field." >= ", time() - (int)$values[$index]["hour"][$sub_index]);
                            }
                        }

                    case 'on':
                        if( isset($values[$index]["date"]) && is_array($values[$index]["date"]) &&  isset($values[$index]["date"][$sub_index])){
                            $date_start = date_sql($values[$index]["date"][$sub_index])." 00:00:00";
                            $date_end = date_sql($values[$index]["date"][$sub_index])." 23:59:59";

                            if(!$sub_count){
                                $builder->where($field." >= ", strtotime($date_start));
                            }else{
                                $builder->orWhere($field." <= ", strtotime($date_end));
                            }
                        }
                        break;

                    case 'before':
                        if( isset($values[$index]["date"]) && is_array($values[$index]["date"]) &&  isset($values[$index]["date"][$sub_index])){
                            $before = date_sql($values[$index]["date"][$sub_index])." 00:00:00";

                            if(!$sub_count){
                                $builder->where($field." <= ", strtotime($before));
                            }else{
                                $builder->orWhere($field." <= ", strtotime($before));
                            }
                        }
                        break;

                    case 'after':
                        if( isset($values[$index]["date"]) && is_array($values[$index]["date"]) &&  isset($values[$index]["date"][$sub_index])){
                            $after = date_sql($values[$index]["date"][$sub_index])." 23:59:59";

                            if(!$sub_count){
                                $builder->where($field." >= ", strtotime($after));
                            }else{
                                $builder->orWhere($field." >= ", strtotime($after));
                            }
                        }

                    
                    default:

                        if(is_array($comparator)){
                            $sub_count_two = 0;
                            foreach ($comparator as $key => $sub_comparator) {

                                switch ($sub_index) {
                                    case 'city':
                                        $value = $sub_comparator;
                                        if(!$sub_count_two){
                                            $builder->where($field, $value);
                                        }else{
                                            $builder->orWhere($field, $value);
                                        }
                                        break;

                                    case 'country':
                                        $value = $sub_comparator;
                                        if(!$sub_count_two){
                                            $builder->where($field, $value);
                                        }else{
                                            $builder->orWhere($field, $value);
                                        }
                                        break;

                                    case 'language':
                                        $value = $sub_comparator;
                                        $language_codes = get_language_codes();
                                        if( array_key_exists($value, $language_codes) ){
                                            if(!$sub_count_two){
                                                $builder->where($field, $value);
                                            }else{
                                                $builder->orWhere($field, $value);
                                            }
                                        }

                                        break;

                                    case 'os':
                                        $value = $sub_comparator;
                                        if($value == "windows" || $value == "mac" || $value == "android" || $value == "linux"){
                                            if(!$sub_count_two){
                                                $builder->where($field, $value);
                                            }else{
                                                $builder->orWhere($field, $value);
                                            }
                                        }
                                        break;

                                    case 'browser':
                                        $value = $sub_comparator;
                                        if($value == "chrome" || $value == "safari" || $value == "firefox" || $value == "opera"){
                                            if(!$sub_count_two){
                                                $builder->where($field, $value);
                                            }else{
                                                $builder->orWhere($field, $value);
                                            }
                                        }
                                        break;

                                    case 'device':
                                        $value = $sub_comparator;
                                        if($value == "desktop" || $value == "mobile" || $value == "tablet"){
                                            if(!$sub_count_two){
                                                $builder->where($field, $value);
                                            }else{
                                                $builder->orWhere($field, $value);
                                            }
                                        }
                                        break;
                                    
                                    default:
                                        // code...
                                        break;
                                }

                                $sub_count_two++;

                            }

                        }

                        break;
                }

                $sub_count++;
            }
            
            $count++;
            $builder->groupEnd();
        }

        $sql = $builder->getCompiledSelect();
        $sql = str_replace("\n"," ",$sql);
        $sql = str_replace("AND   ( )", " AND 1=1 ", $sql);
        $sql = str_replace("( )", " 1=1 ", $sql);

        $query = $db->query($sql);
        if ( $result = $query->getResult() ) {

        	$ids = [];
        	if ($return_list_id) {
        		foreach ($result as $key => $value) {
        			$ids[] = $value->id;
        		}
        		return $ids;
        	}else{
        		return $result;
        	}
            
        } else {
            return false;
        }
    }
}

if(!function_exists("push_segment_filter")){
	function push_segment_filter($post, $fields = "*", $return_list_id = false){
        if(is_array($post)){
            $post = (object)$post;
        }
		$audience_status = $post->audience_status;
        $audience_id = $post->audience_id;
        $segment_id = $post->segment_id;
        $country = $post->country;
        $device = $post->device;
        $os = $post->os;
        $browser = $post->browser;

		$db = \Config\Database::connect();
        $builder = $db->table(TB_STACKPUSH_SUBSCRIBER." as a");

        if(is_string($fields)){
        	$builder->select("a.".$fields);
        }



        if(is_array($fields)){
        	$builder->select( "a.".implode(", a.", $fields) );
        }
        

        if($segment_id){
            $builder = $builder->join(TB_STACKPUSH_SEGMENTATION_MAP." as b", "b.subscriber_id = a.id AND b.segment_id = '{$segment_id}'");
        }

        $builder->where("a.status", 1);
        $builder->where("a.team_id", $post->team_id);
        $builder->where("a.domain_id", $post->domain_id);

        if($country != ""){
            $builder->where("a.country", $country);
        }

        if($os != ""){
            $builder->where("a.os", $os);
        }

        if($device != ""){
            $builder->where("a.device", $device);
        }

        if($browser != ""){
            $builder->where("a.browser", $browser);
        }

        $query = $builder->get();
        $result = $query->getResult();
        $query->freeResult();

        if($result){
        	$ids = [];
        	if ($return_list_id) {
        		foreach ($result as $key => $value) {
        			$ids[] = $value->id;
        		}
        		return $ids;
        	}else{
        		return $result;
        	}
        }
	}
}

if(!function_exists("get_subscribers_by_filter")){
    function get_subscribers_by_filter($post_id){
        if(is_array($post_id) || is_object($post_id)){
            $post = $post_id;
            if(is_array($post)){
                $post = (object)$post;
            }
        }else{
            $post = db_get("*", TB_STACKPUSH_SCHEDULES, ["id" => $post_id]);
            if(!$post){
                return false;
            }
        }

        $audience_status = $post->audience_status;
        $audience_id = $post->audience_id;
        $segment_id = $post->segment_id;
        $country = $post->country;
        $device = $post->device;
        $os = $post->os;
        $browser = $post->browser;

        switch ($audience_status) {
            case 1:
                return push_audience_creator($audience_id, "id", true);

            case 2:
                return push_segment_filter( $post, "id", true );
            
            default:
                return false;
        }
    }
}

if(!function_exists("count_subscribers_suite")){
    function count_subscribers_suite($post_id, $domain_id){
        if(is_array($post_id) || is_object($post_id)){
            $post = $post_id;
            if(is_array($post)){
                $post = (object)$post;
            }
        }else{
            $post = db_get("*", TB_STACKPUSH_SCHEDULES, ["id" => $post_id]);
            if(!$post){
                return false;
            }
        }

        $audience_status = $post->audience_status;
        $audience_id = $post->audience_id;
        $segment_id = $post->segment_id;
        $country = $post->country;
        $device = $post->device;
        $os = $post->os;
        $browser = $post->browser;

        switch ($audience_status) {
            case 1:
                $result = push_audience_creator($audience_id, "id", true);
                return $result?count($result):0;
                break;

            case 2:
                $result = push_segment_filter( $post, "id", true );
                return $result?count($result):0;
                break;

            default:
                return (int)db_get("count(id) as count", TB_STACKPUSH_SUBSCRIBER, ["domain_id" => $domain_id, "status" => 1])->count;
                break;
        }
    }
}