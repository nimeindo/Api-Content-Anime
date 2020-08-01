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

class SearchAnimeControoler extends Controller
{
    public function SearchAnime(Request $request){
        $awal = microtime(true);
        $param = $request->all();
        $ApiKey = $request->header("X-API-KEY");
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            try{    
                return $this->SearchAnimValue($param,$awal);
            }catch(\Exception $e){
                return ResponseConnected::InternalServerError("Search Anime","Internal Server Error",$awal);
            }
            
        }else{
            return ResponseConnected::InvalidToken("Search Anime","Invalid Token", $awal);
        }
    }
    
    public function SearchAnimValue($param,$awal){
        $keyword = (isset($param['params']['keyword'])) ? explode(" ",$param['params']['keyword']) : '';
        $status = (isset($param['params']['status'])) ? $param['params']['status'] : '';
        $limitRange = (isset($param['params']['limit_range'])) && (!empty($param['params']['limit_range'])) ? (int)($param['params']['limit_range']) : (int)20;
        $starIndex = (isset($param['params']['star_index'])) ? (int)($param['params']['star_index']) : 0;
        $minRowPegination = (isset($param['params']['min_row_pegination'])) ? (int)($param['params']['min_row_pegination']) : 5;
        $isUpdated = (isset($param['params']['is_updated']) ? filter_var($param['params']['is_updated'], FILTER_VALIDATE_BOOLEAN) : FALSE);
        if(!empty($keyword)){
            $dataSearch = MainModel::getSearchWithDetailAnime([
                'keyword' => $keyword,
                'limit_range' => $limitRange,
                'star_index' => $starIndex,
                'is_updated' => $isUpdated
            ]);
            
            $TotalSearch = MainModel::getSearchWithDetailAnime([
                'keyword' => $keyword,
                'cek_count' => TRUE
            ]);
        }elseif(!empty($status)){
            $dataSearch = MainModel::getSearchWithDetailAnime([
                'status' => $status,
                'limit_range' => $limitRange,
                'star_index' => $starIndex,
                'is_updated' => $isUpdated
            ]);
            
            $TotalSearch = MainModel::getSearchWithDetailAnime([
                'status' => $status,
                'cek_count' => TRUE
            ]);
            
        }
        else{
            $dataSearch['collection'] = array();
            $TotalSearch['collection'] = array();
        }
        
        if(count($dataSearch['collection']) > 0){
            // Get the latest post in this category and display the titles
            foreach($dataSearch['collection'] as $dataSearchAs){
                $genre = '';
                foreach($dataSearchAs['genre'] as $genree){
                    $genre .= $genree.', ';
                }
                $rating = $dataSearchAs['rating'];
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
                $ListDetail[] = [
                    "ListInfo" => [
                        "Status" => ($dataSearchAs['status'] == '0') ? 'Ended': $dataSearchAs['status'], 
                        "Years" => '', 
                        "Star" => $Star,
                        "Rating" => $rating,
                        "Duration" => $dataSearchAs['duration'], 
                        "Genre" => rtrim($genre,', '),
                    ],
                    "Synopsis" => $dataSearchAs['synopsis'], 
                ];
                $Title = ucwords(str_replace('-',' ',$dataSearchAs['slug']));
                $SearchAnime [] = [
                    "Title" => $Title,
                    "Image" => $dataSearchAs['image'],
                    "IdDetailAnime" => $dataSearchAs['id_detail_anime'],
                    "SlugDetail" => $dataSearchAs['slug'],
                    "ListDetail" => $ListDetail
                ];
                $ListDetail = array();
            }

            $seachTotal = $TotalSearch['collection'];
            
            $TotalSearchPage = HelpersController::TotalSeachPage($limitRange, $seachTotal);
            $PageSearch = HelpersController::PageSearch($starIndex, $limitRange);
            $SearchDataAnime = [
                "TotalSearchPage" => $TotalSearchPage,
                "PageSearch" => $PageSearch,
                "FirstPagination" => self::FirstPagination($PageSearch,$minRowPegination),
                'SearchAnime' => $SearchAnime
            ];
            return ResponseConnected::Success("Search Anime", NULL, $SearchDataAnime, $awal);
        }else{
            return ResponseConnected::PageNotFound("Search Anime","Page Not Found.", $awal);
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