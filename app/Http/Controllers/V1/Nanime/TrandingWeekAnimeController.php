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

// done tinggal token
class TrandingWeekAnimeController extends Controller
{
    public function TrandingWeekAnime(Request $request){
        $awal = microtime(true);
        $param = $request->all();
        $ApiKey = $request->header("X-API-KEY");
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            // try{
                return $this->TrandingWeekAnimValue($param,$awal);
            // }catch(\Exception $e){
            //     return ResponseConnected::InternalServerError("Trending Week Anime","Internal Server Error",$awal);
            // }
            
        }else{
            return ResponseConnected::InvalidToken("Trending Week Anime","Invalid Token", $awal);
        }
    }
    

    public function TrandingWeekAnimValue($param,$awal){
        $status = isset($param['params']['status']) ? $param['params']['status'] : '';
        $rating = isset($param['params']['rating']) ? $param['params']['rating'] : 7;
        if(!empty($status) || !empty($status)){
            $dataDetail = MainModel::getDetailAnime([
                'status' => $status,
                'rating' => $rating,
                'tranding' => True
            ]);
        }else{
            $dataDetail['collection'] = array();
        }
        
        if(count($dataDetail['collection']) > 0){
            $dataDetailAs = $dataDetail['collection'];
            foreach($dataDetailAs as $detail){
                $Title = ucwords(str_replace('-',' ',$detail['slug']));
                $TrendingWeekAnime[] = array(
                    "Image" => $detail['image'],
                    "SlugDetail" => $detail['slug'],
                    "Title" => $Title,
                    "Status" => $detail['status'],
                    "IdDetailAnime" => $detail['id_detail_anime']
                );
            }
            $LogSave = [
                'TrendingWeekAnime' => $TrendingWeekAnime
            ];
            return ResponseConnected::Success("Detail Anime", NULL, $LogSave, $awal);
        }else{
            return ResponseConnected::PageNotFound("Detail Anime","Page Not Found.", $awal);
        }
        
    }

}