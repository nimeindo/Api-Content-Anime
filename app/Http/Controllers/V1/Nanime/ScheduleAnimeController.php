<?php
namespace App\Http\Controllers\V1\Nanime;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;
use \App\Http\Controllers\Controller;
use \Carbon\Carbon;
use Cache;
use Config;

#Load Helper V1
use App\Helpers\V1\ResponseConnected as ResponseConnected;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

class ScheduleAnimeController extends Controller
{
    public function ScheduleAnime(Request $request){
        $awal = microtime(true);
        $param = $request->all();
        $ApiKey = $request->header("X-API-KEY");
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            try{
                return $this->ScheduleAnimeValue($param,$awal);
            }catch(\Exception $e){
                return ResponseConnected::InternalServerError("Schedule Anime","Internal Server Error",$awal);
            }
            
        }else{
            return ResponseConnected::InvalidToken("Schedule Anime","Invalid Token", $awal);
        }
        
    }
    
    
    public function ScheduleAnimeValue($param,$awal){
        $day = isset($param['params']['day']) ? $param['params']['day'] : '';
        $status = isset($param['params']['status']) ? $param['params']['status'] : 'Ong';
        $allDay = (isset($param['params']['all_day']) ? filter_var($param['params']['all_day'], FILTER_VALIDATE_BOOLEAN) : FALSE);
        if(!empty($day) || ($allDay == TRUE)){
            $dayAll = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
            if($allDay){
                $dataDetail = array();
                for($i = 0 ; $i < count($dayAll); $i++){
                    $dataDetail['collection'][] = MainModel::getDetailAnime([
                        'day' => $dayAll[$i],
                        'status' => $status,
                        'Schedule' => TRUE,
                    ]);
                }
            }else{
                $dataDetail = MainModel::getDetailAnime([
                    'day' => $day,
                    'status' => $status,
                    'Schedule' => TRUE,
                ]);
            }
        }else{
            $dataDetail['collection'] = array();
        }
    
        if(count($dataDetail['collection']) > 0){
            if($allDay){
                $i = 0;
                foreach($dataDetail['collection'] as $collection){
                    $ListSubIndex = array();
                    foreach($collection['collection'] as $detailData){
                        $Title = ucwords(str_replace('-',' ',$detailData['slug']));
                        $ListSubIndex[]= array(
                            "Title" => $Title,
                            "Image" => $detailData['image'],
                            "IdDetailAnime" => $detailData['id_detail_anime'],
                            "SlugDetail" => $detailData['slug'],
                        );
                    }
                    $ScheduleAnime[]=array(
                        "NameDay" => $dayAll[$i],
                        "ListSubIndex" => $ListSubIndex
                    );
                    $i++;
                }
            }else{
                foreach($dataDetail['collection'] as $detailData){
                    $Title = ucwords(str_replace('-',' ',$detailData['slug']));
                    $ListSubIndex[]= array(
                        "Title" => $Title,
                        "Image" => $detailData['image'],
                        "IdDetailAnime" => $detailData['id_detail_anime'],
                        "SlugDetail" => $detailData['slug'],
                    );
                }
                $ScheduleAnime[]=array(
                    "NameDay" => $day,
                    "ListSubIndex" => $ListSubIndex
                );
                
            }
            $LogSave = [
                'ScheduleAnime' => $ScheduleAnime
            ];
            return ResponseConnected::Success("Schedule Anime", NULL, $LogSave, $awal);
        }else{
            return ResponseConnected::PageNotFound("Schedule Anime","Page Not Found.", $awal);
        }
    }
}