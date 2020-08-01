<?php

namespace App\Http\Controllers\V1\Nanime;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;
use \App\Http\Controllers\Controller;
use \Carbon\Carbon;
use Cache;
use Config;

#Load Helper V1
use App\Helpers\V1\Converter as Converter;
use App\Helpers\V1\ResponseConnected as ResponseConnected;
use App\Helpers\V1\HelpersController as HelpersController;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

// done
class ListAnimeController extends Controller
{

    public function testing(){
        // phpinfo();exit;
        echo "WELCOM TO API CONTENT LIST ANIME NIMEINDO V1";
    }

    public function ListAnime(Request $request){
        $awal = microtime(true);
        $param = $request->all();
        $ApiKey = $request->header("X-API-KEY");
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            try{
                return $this->ListAnimeValue($param,$awal);
            }catch(\Exception $e){
                return ResponseConnected::InternalServerError("List Anime","Internal Server Error",$awal);
            }
        }else{
            return ResponseConnected::InvalidToken("List Anime","Invalid Token", $awal);
        }
    }
    
    
    public function ListAnimeValue($param,$awal){
        $nameIndex = isset($param['params']['name_index']) ? $param['params']['name_index'] : '';
        $allIndex  = isset($param['params']['all_index']) ? filter_var($param['params']['all_index'], FILTER_VALIDATE_BOOLEAN): FALSE ;
        $limitRange = (isset($param['params']['limit_range'])) && (!empty($param['params']['limit_range'])) ? (int)($param['params']['limit_range']) : (int)20;
        $starIndex = (isset($param['params']['star_index'])) ? (int)($param['params']['star_index']) : 0;
        $isUpdated = (isset($param['params']['is_updated']) ? filter_var($param['params']['is_updated'], FILTER_VALIDATE_BOOLEAN) : FALSE);
        $minRowPegination = (isset($param['params']['min_row_pegination'])) ? (int)($param['params']['min_row_pegination']) : 5;
        if(!empty($nameIndex) || ($allIndex)){
            $dataListAnime  = MainModel::getDataListAnime([
                'name_index' => $nameIndex,
                'All_index' => $allIndex, 
                'limit_range' => $limitRange,
                'star_index' => $starIndex,
                'is_updated' => $isUpdated,
            ]);
            $TotalSearch = MainModel::getDataListAnime([
                'name_index' => $nameIndex,
                'cek_count' => TRUE,
                'is_updated' => TRUE,
            ]);
        }else{
            $dataListAnime['collection'] = array();
            $TotalSearch['collection'] = array();
        }
        
        if(count($dataListAnime['collection']) > 0){
            
            $NameIndex = array();
            foreach($dataListAnime['collection'] as $dataListAnimeAs){
                $NameIndex [] = $dataListAnimeAs['name_index'];
            }
            $ListAnime = array();
            if($isUpdated){
                $NameIndex = HelpersController::__rearrangeArrayIndexNotSortir($NameIndex);
                for($i = 0 ; $i < count($NameIndex); $i++){
                    $ListSubIndex = array();
                    foreach($dataListAnime['collection'] as $dataListAnimeAss){
                        $NameIndexVal = $dataListAnimeAss['name_index'];
                        $Title = ucwords(str_replace('-',' ',$dataListAnimeAss['slug']));
                        if($NameIndexVal == $NameIndex[$i]){
                            $Status = ($dataListAnimeAss['status'] == '0') ? "Movie" : $dataListAnimeAss['status'];
                            $ListSubIndex[] = array(
                                'IdDetailAnime' => $dataListAnimeAss['id_detail_anime'],
                                "Title" => $Title,
                                "SlugDetail" => $dataListAnimeAss['slug'],
                                "Image" => $dataListAnimeAss['image'],
                                "Status" => $Status,
                                'PublishDate' => Carbon::parse($dataListAnimeAss['cron_at'])->format('Y-m-d\TH:i:s'),
                            );
                        }
                    }
                    $ListAnime[] = [
                        "NameIndex" => $NameIndex[$i],
                        "ListSubIndex" => $ListSubIndex
                    ];  
                }
            }else{
                $NameIndex = HelpersController::__rearrangeArrayIndex($NameIndex);
                for($i = 0 ; $i < count($NameIndex); $i++){
                    $ListSubIndex = array();
                    
                    foreach($dataListAnime['collection'] as $dataListAnimeAss){
                        $NameIndexVal = $dataListAnimeAss['name_index'];
                        $Title = ucwords(str_replace('-',' ',$dataListAnimeAss['slug']));
                        if($NameIndexVal == $NameIndex[$i]){
                            $Status = ($dataListAnimeAss['status'] == '0') ? "Movie" : $dataListAnimeAss['status'];
                            $ListSubIndex[] = array(
                                'IdDetailAnime' => $dataListAnimeAss['id_detail_anime'],
                                "Title" => $Title,
                                "SlugDetail" => $dataListAnimeAss['slug'],
                                "Image" => $dataListAnimeAss['image'],
                                "Status" => $Status,
                                'PublishDate' => Carbon::parse($dataListAnimeAss['cron_at'])->format('Y-m-d\TH:i:s'),
                            );
                        }
                    }
                    $ListAnime[] = [
                        "NameIndex" => $NameIndex[$i],
                        "ListSubIndex" => $ListSubIndex
                    ];   
                }
            }
            
            $seachTotal = ($TotalSearch['collection']);
            $TotalSearchPage = HelpersController::TotalSeachPage($limitRange, $seachTotal);
            
            $PageSearch = HelpersController::PageSearch($starIndex, $limitRange);
            $LogSave = [
                "TotalSearchPage" => $TotalSearchPage,
                "PageSearch" => $PageSearch,
                "FirstPagination" => self::FirstPagination($PageSearch,$minRowPegination),
                'ListAnime' => $ListAnime
            ];
            return ResponseConnected::Success("List Anime", NULL, $LogSave, $awal);
        }else{
            return ResponseConnected::PageNotFound("List Anime","Page Not Found.", $awal);
        }
    }

    public function FirstPagination($PageSearch,$minRowPegination){
        if($PageSearch % $minRowPegination === 0){
            $FirstPagination = $PageSearch;
        }elseif((($PageSearch % $minRowPegination) >= 1) && ($PageSearch > 5)){
            $awal = floor($PageSearch / $minRowPegination);
            $FirstPagination = $awal * $minRowPegination;
        }else{
            $FirstPagination = 1;
        }
        return $FirstPagination;
    }
}
