<?php
namespace Core\Push_analytics_browser_device\Models;
use CodeIgniter\Model;

class Push_analytics_browser_deviceModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function block_push_permissions($path = ""){
        return [
            "position" => 1100
        ];
    }

    public function block_plans(){
        return [
            "tab" => 17,
            "position" => 1100,
            "label" => __("Web Push Notification"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => __("Browser/Devices analytics"),
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

    public function device_chart(){

        $db = \Config\Database::connect();
        $daterange = post("daterange")!=NULL?addslashes(post("daterange")):"";
        if( $daterange != "" ){
            $daterange = explode(",", $daterange);
        }else{
            $daterange = [date("Y-m-d", time() - 28*24*60*60), date("Y-m-d")];
        }

        if(count($daterange) == 2){
            $date_list = array();
            $date_since = $daterange[0]." 00:00:00";
            $date_until = $daterange[1]." 23:59:59";

            $db = \Config\Database::connect();
            $query = $db->query("SELECT device, COUNT(device) as count, SUM(number_action) as clicked FROM ".TB_STACKPUSH_SUBSCRIBER." WHERE team_id = '".TEAM_ID."' AND domain_id = '".PUSH_DOMAIN_ID."' AND FROM_UNIXTIME(created) > '".$date_since."' AND FROM_UNIXTIME(created) < '".$date_until."' AND status = 1 GROUP BY device");
            $result = $query->getResult();

            $data = [
                "desktop" => 0,
                "mobile" => 0,
                "tablet" => 0,
                "total" => 0,

                "desktop_clicked" => 0,
                "mobile_clicked" => 0,
                "tablet_clicked" => 0,
                "total_clicked" => 0,
            ];

            if($result){
                foreach ($result as $key => $value) {
                    switch ($value->device) {
                        case 'desktop':
                            $data['desktop'] = (int)$value->count;
                            $data['total'] += (int)$value->count;

                            $data['desktop_clicked'] = (int)$value->clicked;
                            $data['total_clicked'] += (int)$value->clicked;
                            break;

                        case 'mobile':
                            $data['mobile'] = (int)$value->count;
                            $data['total'] += $value->count;

                            $data['mobile_clicked'] = (int)$value->clicked;
                            $data['total_clicked'] += (int)$value->clicked;
                            break;

                        case 'tablet':
                            $data['tablet'] = (int)$value->count;
                            $data['total'] += (int)$value->count;

                            $data['tablet_clicked'] = (int)$value->clicked;
                            $data['total_clicked'] += (int)$value->clicked;
                            break;
                    }
                }
                
            }

            $data['percent_desktop'] = $data['total']!=0?round( $data['desktop']/$data['total']*100 ):0;
            $data['percent_mobile'] = $data['total']!=0?round( $data['mobile']/$data['total']*100 ):0;
            $data['percent_tablet'] = $data['total']!=0?round( $data['tablet']/$data['total']*100 ):0;

            $data['percent_desktop_clicked'] = $data['total_clicked']!=0?round( $data['desktop_clicked']/$data['total_clicked']*100 ):0;
            $data['percent_mobile_clicked'] = $data['total_clicked']!=0?round( $data['mobile_clicked']/$data['total_clicked']*100 ):0;
            $data['percent_tablet_clicked'] = $data['total_clicked']!=0?round( $data['tablet_clicked']/$data['total_clicked']*100 ):0;
            
            $columns = "Desktop,Mobile,Tablet";

            return [

                "columns" => $columns,
                "data" => $data,
            ];
        }

        return false;
    }

    public function browser_chart(){

        $db = \Config\Database::connect();
        $daterange = post("daterange")!=NULL?addslashes(post("daterange")):"";
        if( $daterange != "" ){
            $daterange = explode(",", $daterange);
        }else{
            $daterange = [date("Y-m-d", time() - 28*24*60*60), date("Y-m-d")];
        }

        if(count($daterange) == 2){
            $date_list = array();
            $date_since = $daterange[0]." 00:00:00";
            $date_until = $daterange[1]." 23:59:59";


            $db = \Config\Database::connect();
            $query = $db->query("SELECT browser, COUNT(browser) as count, SUM(number_action) as clicked FROM ".TB_STACKPUSH_SUBSCRIBER." WHERE team_id = '".TEAM_ID."' AND domain_id = '".PUSH_DOMAIN_ID."' AND FROM_UNIXTIME(created) > '".$date_since."' AND FROM_UNIXTIME(created) < '".$date_until."' AND status = 1 GROUP BY browser");
            $result = $query->getResult();

            $data = [
                "chrome" => 0,
                "firefox" => 0,
                "safari" => 0,
                "opera" => 0,
                "edge" => 0,
                "total" => 0,
                "chrome_clicked" => 0,
                "firefox_clicked" => 0,
                "safari_clicked" => 0,
                "opera_clicked" => 0,
                "edge_clicked" => 0,
                "total_clicked" => 0,
            ];

            if($result){
                foreach ($result as $key => $value) {
                    switch ($value->browser) {
                        case 'chrome':
                            $data['chrome'] = (int)$value->count;
                            $data['total'] += (int)$value->count;

                            $data['chrome_clicked'] = (int)$value->clicked;
                            $data['total_clicked'] += (int)$value->clicked;
                            break;

                        case 'firefox':
                            $data['firefox'] = (int)$value->count;
                            $data['total'] += (int)$value->count;

                            $data['firefox_clicked'] = (int)$value->clicked;
                            $data['total_clicked'] += (int)$value->clicked;
                            break;

                        case 'safari':
                            $data['safari'] = (int)$value->count;
                            $data['total'] += (int)$value->count;

                            $data['safari_clicked'] = (int)$value->clicked;
                            $data['total_clicked'] += (int)$value->clicked;
                            break;

                        case 'opera':
                            $data['opera'] = (int)$value->count;
                            $data['total'] += (int)$value->count;

                            $data['opera_clicked'] = (int)$value->clicked;
                            $data['total_clicked'] += (int)$value->clicked;
                            break;

                        case 'edge':
                            $data['edge'] = (int)$value->count;
                            $data['total'] += (int)$value->count;

                            $data['edge_clicked'] = (int)$value->clicked;
                            $data['total_clicked'] += (int)$value->clicked;
                            break;
                    }
                }
                
            }

            $data['percent_chrome'] = $data['total']!=0?round( $data['chrome']/$data['total']*100 ):0;
            $data['percent_firefox'] = $data['total']!=0?round( $data['firefox']/$data['total']*100 ):0;
            $data['percent_safari'] = $data['total']!=0?round( $data['safari']/$data['total']*100 ):0;
            $data['percent_opera'] = $data['total']!=0?round( $data['opera']/$data['total']*100 ):0;
            $data['percent_edge'] = $data['total']!=0?round( $data['edge']/$data['total']*100 ):0;

            $data['percent_chrome_clicked'] = $data['total_clicked']!=0?round( $data['chrome_clicked']/$data['total_clicked']*100 ):0;
            $data['percent_firefox_clicked'] = $data['total_clicked']!=0?round( $data['firefox_clicked']/$data['total_clicked']*100 ):0;
            $data['percent_safari_clicked'] = $data['total_clicked']!=0?round( $data['safari_clicked']/$data['total_clicked']*100 ):0;
            $data['percent_opera_clicked'] = $data['total_clicked']!=0?round( $data['opera_clicked']/$data['total_clicked']*100 ):0;
            $data['percent_edge_clicked'] = $data['total_clicked']!=0?round( $data['edge_clicked']/$data['total_clicked']*100 ):0;
            
            $columns = "Chrome,Firefox,safari,Opera,Microsoft Edge";

            return [

                "columns" => $columns,
                "data" => $data,
            ];
        }

        return false;
    }

    public function os_chart(){
        $db = \Config\Database::connect();
        $daterange = post("daterange")!=NULL?addslashes(post("daterange")):"";
        if( $daterange != "" ){
            $daterange = explode(",", $daterange);
        }else{
            $daterange = [date("Y-m-d", time() - 28*24*60*60), date("Y-m-d")];
        }

        if(count($daterange) == 2){
            $date_list = array();
            $date_since = $daterange[0]." 00:00:00";
            $date_until = $daterange[1]." 23:59:59";


            $db = \Config\Database::connect();
            $query = $db->query("SELECT os, COUNT(os) as count, SUM(number_action) as clicked FROM ".TB_STACKPUSH_SUBSCRIBER." WHERE team_id = '".TEAM_ID."' AND domain_id = '".PUSH_DOMAIN_ID."' AND FROM_UNIXTIME(created) > '".$date_since."' AND FROM_UNIXTIME(created) < '".$date_until."' AND status = 1 GROUP BY os");
            $result = $query->getResult();

            $data = [
                "windows" => 0,
                "android" => 0,
                "mac" => 0,
                "linux" => 0,
                "total" => 0,
                "windows_clicked" => 0,
                "android_clicked" => 0,
                "mac_clicked" => 0,
                "linux_clicked" => 0,
                "total_clicked" => 0,
            ];

            if($result){
                foreach ($result as $key => $value) {
                    switch ($value->os) {
                        case 'windows':
                            $data['windows'] = (int)$value->count;
                            $data['total'] += (int)$value->count;

                            $data['windows_clicked'] = (int)$value->clicked;
                            $data['total_clicked'] += (int)$value->clicked;
                            break;

                        case 'android':
                            $data['android'] = (int)$value->count;
                            $data['total'] += (int)$value->count;

                            $data['android_clicked'] = (int)$value->clicked;
                            $data['total_clicked'] += (int)$value->clicked;
                            break;

                        case 'mac':
                            $data['mac'] = (int)$value->count;
                            $data['total'] += (int)$value->count;

                            $data['mac_clicked'] = (int)$value->clicked;
                            $data['total_clicked'] += (int)$value->clicked;
                            break;

                        case 'linux':
                            $data['linux'] = (int)$value->count;
                            $data['total'] += (int)$value->count;

                            $data['linux_clicked'] = (int)$value->clicked;
                            $data['total_clicked'] += (int)$value->clicked;
                            break;
                    }
                }
                
            }

            $data['percent_windows'] = $data['total']!=0?round( $data['windows']/$data['total']*100 ):0;
            $data['percent_android'] = $data['total']!=0?round( $data['android']/$data['total']*100 ):0;
            $data['percent_mac'] = $data['total']!=0?round( $data['mac']/$data['total']*100 ):0;
            $data['percent_linux'] = $data['total']!=0?round( $data['linux']/$data['total']*100 ):0;

            $data['percent_windows_clicked'] = $data['total_clicked']!=0?round( $data['windows_clicked']/$data['total_clicked']*100 ):0;
            $data['percent_android_clicked'] = $data['total_clicked']!=0?round( $data['android_clicked']/$data['total_clicked']*100 ):0;
            $data['percent_mac_clicked'] = $data['total_clicked']!=0?round( $data['mac_clicked']/$data['total_clicked']*100 ):0;
            $data['percent_linux_clicked'] = $data['total_clicked']!=0?round( $data['linux_clicked']/$data['total_clicked']*100 ):0;
            
            $columns = "Windows,Android,Mac,Linux";

            return [

                "columns" => $columns,
                "data" => $data,
            ];
        }

        return false;
    }
}
