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
use App\Helpers\V1\HelpersController as HelpersController;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

// done tinggal token db
class LastUpdateEpsAnimController extends Controller
{
    public function LastUpdateAnime(Request $request){
        $awal = microtime(true);
        $param = $request->all();
        $ApiKey = $request->header("X-API-KEY");
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            try{
                return $this->LastUpdateAnimValue($param,$awal);
            }catch(\Exception $e){
                return ResponseConnected::InternalServerError("Last Update Anime","Internal Server Error",$awal);
            }
            
        }else{
            return ResponseConnected::InvalidToken("Last Update Anime","Invalid Token", $awal);
        }

        
    }
    
    public function LastUpdateAnimValue($param,$awal){
        $limitRange = (isset($param['params']['limit_range'])) ? (int)($param['params']['limit_range']) : 20;
        $starIndex = (isset($param['params']['star_index'])) ? (int)($param['params']['star_index']) : 0;
        $minRowPegination = (isset($param['params']['min_row_pegination'])) ? (int)($param['params']['min_row_pegination']) : 5;
        $isUpdated = (isset($param['params']['is_updated']) ? filter_var($param['params']['is_updated'], FILTER_VALIDATE_BOOLEAN) : FALSE);
        
        if(!empty($limitRange) || !empty($starIndex) || ($isUpdated)){
            $dataLastUpdate = MainModel::getDataLastUpdate([
                'limit_range' => $limitRange,
                'star_index' => $starIndex,
                'is_updated' => $isUpdated
            ]);
            $TotalSearch = MainModel::getDataLastUpdate([
                'cek_count' => TRUE
            ]);
        }else{
            $dataLastUpdate['collection'] = array();
            $TotalSearch['collection'] = array();
        }
        
        if(count($dataLastUpdate['collection']) > 0){
            $LastUpdateAnime1 =[];
            $LastUpdateAnime2 = [];
            foreach($dataLastUpdate['collection'] as $key => $dataLastUpdateAs){
                
                $dataDetail = MainModel::getDetailAnime([
                    'id_detail' => $dataLastUpdateAs['id_detail_anime'],
                ]);
                $SlugDetail = '';
                $rating = 0;
                $genre = '';
                foreach($dataDetail['collection'] as $dataDetailAs){
                    $SlugDetail = $dataDetailAs['slug'];
                    $rating = $dataDetailAs['rating'];
                    $rating = $dataDetailAs['rating'];
                    $dataGenre = $dataDetailAs['genre'];
                    foreach($dataGenre as $dataGenreAs){
                        $genre .= $dataGenreAs.',';
                    }
                }
                $Episode_ = substr(strrchr($dataLastUpdateAs['slug'], 'e-'), 2);
                // /forr find chacter - after get data number episode
                $findChacater = strpos($Episode_, '-');
                $Episode_ = ($findChacater) ? substr($Episode_, 0, strpos($Episode_, "-")) : $Episode_;
                $TitleAlias = ucwords(str_replace('-',' ',$dataLastUpdateAs['slug']));
                $Title = ucwords($dataLastUpdateAs['title']);
                $Episode = is_numeric($Episode_) ? round($Episode_) : 'Movie';
                $Star = 5;
                if($rating >= 8){
                    $Star = 5;
                }elseif($rating >=6){
                    $Star = 4;
                }elseif($rating >=4){
                    $Star = 3;
                }elseif($rating >= 2.5){
                    $Star = 2;
                }

                if($key < 12 ){
                    $LastUpdateAnime1[] = array(
                        "Image" => $dataLastUpdateAs['image'],
                        "Title" => $Title,
                        "TitleAlias" => $TitleAlias,
                        "Status" => $dataLastUpdateAs['status'],
                        "Genre" => $genre,
                        "Rating" => $rating,
                        "Star" => $Star,
                        "Episode" => $Episode,
                        "IdDetailAnime" => $dataLastUpdateAs['id_detail_anime'],
                        "IdListEpisode" => $dataLastUpdateAs['id_list_episode'],
                        "SlugDetail" => $SlugDetail,
                        "SlugEp" => $dataLastUpdateAs['slug'],
                        'PublishDate' => Carbon::parse($dataLastUpdateAs['cron_at'])->format('Y-m-d\TH:i:s'),
                        'PublishDateAgo' => Carbon::parse($dataLastUpdateAs['cron_at'])->diffForHumans(),
                        'cron_at' => $dataLastUpdateAs['cron_at']
                    );
                }else{
                    $LastUpdateAnime2[] = array(
                        "Image" => $dataLastUpdateAs['image'],
                        "Title" => $Title,
                        "TitleAlias" => $TitleAlias,
                        "Status" => $dataLastUpdateAs['status'],
                        "Genre" => $genre,
                        "Episode" => $Episode,
                        "Rating" => $rating,
                        "Star" => $Star,
                        "IdDetailAnime" => $dataLastUpdateAs['id_detail_anime'],
                        "IdListEpisode" => $dataLastUpdateAs['id_list_episode'],
                        "SlugDetail" => $SlugDetail,
                        "SlugEp" => $dataLastUpdateAs['slug'],
                        'PublishDate' => Carbon::parse($dataLastUpdateAs['cron_at'])->format('Y-m-d\TH:i:s'),
                        'PublishDateAgo' => Carbon::parse($dataLastUpdateAs['cron_at'])->diffForHumans(),
                        'cron_at' => $dataLastUpdateAs['cron_at']
                    );
                }
                
            }
            // mengurutkan ulang data lats update anime
            $DataLastUpdateComb = [
                $LastUpdateAnime1,
                $LastUpdateAnime2,
            ];
            
            $LastUpdateAnime = $this->combinationArray($DataLastUpdateComb);
            $seachTotal = $TotalSearch['collection'];
            $TotalSearchPage = HelpersController::TotalSeachPage($limitRange, $seachTotal);
            $PageSearch = HelpersController::PageSearch($starIndex, $limitRange);
            $getDataLastUpdate = [
                "TotalSearchPage" => $TotalSearchPage,
                "PageSearch" => $PageSearch,
                "FirstPagination" => self::FirstPagination($PageSearch,$minRowPegination),
                'LastUpdateAnime' => $LastUpdateAnime
            ];
            
            return ResponseConnected::Success("Last Update Anime", NULL, $getDataLastUpdate, $awal);
        }else{
            return ResponseConnected::PageNotFound("Last Update Anime","Page Not Found.", $awal);
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

    // mengurutkan ulang data last update
    public function reSortKeyArrayLastUpdate($ArrayLastUpdate){
        krsort($ArrayLastUpdate);
        $LastUpdateAnime = array_values($ArrayLastUpdate);
        return $LastUpdateAnime;
    }

    public function combinationArray($DataLastupdate){
        $combination = [];
        for($i = 0 ;$i <count($DataLastupdate); $i++){
            for($j = 0 ; $j < count($DataLastupdate[$i]); $j++){
                $combination[] = [
                    "Image" => $DataLastupdate[$i][$j]['Image'],
                    "Title" => $DataLastupdate[$i][$j]['Title'],
                    "TitleAlias" => $DataLastupdate[$i][$j]['TitleAlias'],
                    "Status" => $DataLastupdate[$i][$j]['Status'],
                    "Genre" => $DataLastupdate[$i][$j]['Genre'],
                    "Rating" => $DataLastupdate[$i][$j]['Rating'],
                    "Star" => $DataLastupdate[$i][$j]['Star'],
                    "Episode" => $DataLastupdate[$i][$j]['Episode'],
                    "IdDetailAnime" => $DataLastupdate[$i][$j]['IdDetailAnime'],
                    "IdListEpisode" => $DataLastupdate[$i][$j]['IdListEpisode'],
                    "SlugDetail" => $DataLastupdate[$i][$j]['SlugDetail'],
                    "SlugEp" => $DataLastupdate[$i][$j]['SlugEp'],
                    'PublishDate' => $DataLastupdate[$i][$j]['PublishDate'],
                    'PublishDateAgo' => $DataLastupdate[$i][$j]['PublishDateAgo'],
                    'cron_at' => $DataLastupdate[$i][$j]['cron_at']
                ];       
            }
        }
        return $combination;
    }
}