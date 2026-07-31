<?php
namespace Core\Push_segmentation_audience_creator\Controllers;

class Push_segmentation_audience_creator extends \CodeIgniter\Controller
{
    public function __construct(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: *");
        include get_module_dir( __DIR__ , '../Push/Libraries/vendor/autoload.php');
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
        $this->model = new \Core\Push_segmentation_audience_creator\Models\Push_segmentation_audience_creatorModel();
    }
    
    public function index( $page = false, $ids = "" ) {
        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            'config' => $this->config
        ];

        switch ($page) {
            case 'update':
                $result = db_get("*", TB_STACKPUSH_AUDIENCE_CREATOR, ["ids" => $ids, "team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID]);

                $data_content = [
                    'config' => $this->config,
                    'result' => $result
                ];

                $data['content'] = view('Core\Push_segmentation_audience_creator\Views\update', $data_content);
                break;

            case 'view':
                $result = db_get("*", TB_STACKPUSH_AUDIENCE_CREATOR, ["ids" => $ids, "team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID]);

                if(empty($result)){
                    redirect_to( get_module_url() );
                }

                $start = 0;
                $limit = 1;

                $pager = \Config\Services::pager();
                $total = $this->model->get_view_list(false, $ids);

                $datatable = [
                    "responsive" => true,
                    "columns" => [
                        "id" => __("ID"),
                        "token" => __("Subs ID"),
                        "first_visit" => __("First Session"),
                        "device" => __("Device"),
                        "os" => __("OS"),
                        "browser" => __("Browser"),
                        "resolution" => __("Resolution"),
                        "location" => __("Location"),
                        "language" => __("Language"),
                        "timezone" => __("Timezone"),
                        "subscription_url" => __("Subscription Url"),
                        "last_visit" => __("Last Session"),
                        "last_url_visit" => __("Last URL Visited"),
                    ],
                    "total_items" => $total,
                    "per_page" => 20,
                    "current_page" => 1,

                ];

                $data_content = [
                    'start' => $start,
                    'limit' => $limit,
                    'total' => $total,
                    'pager' => $pager,
                    'ids'   => $ids,
                    'result'=> $result,
                    'datatable'  => $datatable,
                    'config' => $this->config
                ];

                $data['content'] = view('Core\Push_segmentation_audience_creator\Views\view', $data_content);
                break;
            
            default:
                
                $start = 0;
                $limit = 1;

                $pager = \Config\Services::pager();
                $total = $this->model->get_list(false);

                $datatable = [
                    "responsive" => true,
                    "columns" => [
                        "id" => __("Audience ID"),
                        "name" => __("Audience Name"),
                        "ids" => __("Audience ID"),
                    ],
                    "total_items" => $total,
                    "per_page" => 20,
                    "current_page" => 1,

                ];

                $data_content = [
                    'start' => $start,
                    'limit' => $limit,
                    'total' => $total,
                    'pager' => $pager,
                    'datatable'  => $datatable,
                    'config' => $this->config
                ];

                $data['content'] = view('Core\Push_segmentation_audience_creator\Views\content', $data_content);
                break;
        }

        

        return view('Core\Push\Views\index', $data);
    }

    public function ajax_list(){
        $total_items = $this->model->get_list(false);
        $result = $this->model->get_list(true);
        $actions = get_blocks("block_action_user", false);
        $data = [
            "result" => $result,
            "actions" => $actions,
            "total_items" => $total_items,
        ];
        ms( [
            "total_items" => $total_items,
            "data" => view('Core\Push_segmentation_audience_creator\Views\ajax_list', $data)
        ] );
    }

    public function ajax_view_list($ids = ""){
        $total_items = $this->model->get_view_list(false, $ids);
        $result = $this->model->get_view_list(true, $ids);
        $actions = get_blocks("block_action_user", false);
        $data = [
            "result" => $result,
            "actions" => $actions,
            "total_items" => $total_items,
        ];
        ms( [
            "total_items" => $total_items,
            "data" => view('Core\Push_segmentation_audience_creator\Views\ajax_view_list', $data)
        ] );
    }

    public function save( $ids = "" ){
        $name = post("name");
        $filters = post("filter");
        $comparators = post("comparator");
        $values = post("value");
        $count = 0;

        $db = \Config\Database::connect();
        $builder = $db->table(TB_STACKPUSH_SUBSCRIBER." as a");
        $builder->select('a.*');

        $contains_arr = [];

        if($name == ""){
            ms([
                "status" => "error",
                "message" => __("Please enter audience name")
            ]);
        }

        $builder->groupStart();
        $builder->where("status", 1);
        $builder->where("domain_id", PUSH_DOMAIN_ID);
        $builder->where("team_id", TEAM_ID);
        $builder->groupEnd();

        foreach ($filters as $index => $filter) {
            $builder->groupStart();
            if($filter == ""){
                ms([
                    "status" => "error",
                    "message" => __("Please enter all fields to continue")
                ]);
            }

            if(empty($comparators[$index])){
                ms([
                    "status" => "error",
                    "message" => __("Please enter all fields to continue")
                ]);
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

        $item = db_get("id", TB_STACKPUSH_AUDIENCE_CREATOR, ["ids" => $ids, "domain_id" => PUSH_DOMAIN_ID, "team_id" => TEAM_ID]);

        $data = [
            "domain_id" => PUSH_DOMAIN_ID,
            "team_id" => TEAM_ID,
            "name" => $name,
            "data" => json_encode([
                "filters" => $filters,
                "comparators" => $comparators,
                "values" => $values
            ]),
            "query" => $sql,
            "status" => 1,
            "changed" => time(),
        ];

        if(empty($item)){
            $data["ids"] = ids();
            $data["created"] = time();
            db_insert(TB_STACKPUSH_AUDIENCE_CREATOR, $data);
        }else{
            db_update(TB_STACKPUSH_AUDIENCE_CREATOR, $data, ["id" => $item->id]);
        }
        
        return ms([
            "status" => "success",
            "message" => __("Success")
        ]);
    }

    public function delete( $ids = '' ){
        if($ids == ''){
            $ids = post('ids');
        }

        if( empty($ids) ){
            ms([
                "status" => "error",
                "message" => __('Please select an item to delete')
            ]);
        }

        if( is_array($ids) )
        {
            foreach ($ids as $id) 
            {
                db_delete(TB_STACKPUSH_AUDIENCE_CREATOR, ['ids' => $id, "team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID]);
            }
        }
        elseif( is_string($ids) )
        {
            db_delete(TB_STACKPUSH_AUDIENCE_CREATOR, ['ids' => $ids, "team_id" => TEAM_ID, "domain_id" => PUSH_DOMAIN_ID]);
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);

    }
}