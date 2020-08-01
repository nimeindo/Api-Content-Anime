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
class TopAnimeController extends Controller
{
    public function TopAnime(Request $request){
        $awal = microtime(true);
        $param = $request->all();
        $ApiKey = $request->header("X-API-KEY");
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            try{
                return $this->TopAnimeValue($param,$awal);
            }catch(\Exception $e){
                return ResponseConnected::InternalServerError("Top Anime","Internal Server Error",$awal);
            }
            
        }else{
            return ResponseConnected::InvalidToken("Top Anime","Invalid Token", $awal);
        }
    }
    public function TopAnimeValue($param,$awal){
        $limitRange = (isset($param['params']['limit_range'])) ? (int)($param['params']['limit_range']) : 20;
        $starIndex = (isset($param['params']['star_index'])) ? (int)($param['params']['star_index']) : 0;
        $minRowPegination = (isset($param['params']['min_row_pegination'])) ? (int)($param['params']['min_row_pegination']) : 5;
        $isUpdated = (isset($param['params']['is_updated']) ? filter_var($param['params']['is_updated'], FILTER_VALIDATE_BOOLEAN) : FALSE);
        if(!empty($limitRange) || !empty($starIndex) || ($isUpdated)){
            $dataTop = MainModel::getDataTopAnime([
                'limit_range' => $limitRange,
                'star_index' => $starIndex,
                'is_updated' => $isUpdated
            ]);
            $TotalSearch = MainModel::getDataTopAnime([
                'cek_count' => TRUE
            ]);
        }else{
            $dataTop['collection'] = array();
            $TotalSearch['collection'] = array();
        }
        
        if(count($dataTop['collection']) > 0){
            $Top1 = [];
            $Top2 = [];
            foreach($dataTop['collection'] as $key => $TopValueAs){
                $dataDetail = MainModel::getDetailAnime([
                    'id_detail' => $TopValueAs['id_detail_anime'],
                ]);
                $SlugDetail = '';
                $imageUrl = '';
                $genre = '';
                $rating = 0;
                foreach($dataDetail['collection'] as $dataDetailAs){
                    $SlugDetail = $dataDetailAs['slug'];
                    $imageUrl = $dataDetailAs['image'];
                    $dataGenre = $dataDetailAs['genre'];
                    $rating = $dataDetailAs['rating'];
                    foreach($dataGenre as $dataGenreAs){
                        $genre .= $dataGenreAs.',';
                    }
                }
                
                $Chapter_ = substr(strrchr($TopValueAs['slug'], '-'), 1);
                $TitleAlias = ucwords(str_replace('-',' ',$TopValueAs['slug']));
                $Title = ucwords($TopValueAs['title']);
                $Chapter = is_numeric($Chapter_) ? round($Chapter_) : 'Movie';
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
                    $Top1[] = array(
                        "Image" => $imageUrl,
                        "Title" => $Title,
                        "TitleAlias" => $TitleAlias,
                        "Status" => $TopValueAs['status'],
                        "Genre" => $genre,
                        "Star" => $Star,
                        "Rating" => $rating,
                        "IdDetailAnime" => $TopValueAs['id_detail_anime'],
                        // "IdChapter" => $TopValueAs['id_chapter'],
                        "SlugDetail" => $SlugDetail,
                        // "SlugChp" => $TopValueAs['slug'],
                        'PublishDate' => Carbon::parse($TopValueAs['publish_date'])->format('Y-m-d\TH:i:s'),
                    );
                }else{
                    $Top2[] = array(
                        "Image" => $imageUrl,
                        "Title" => $Title,
                        "TitleAlias" => $TitleAlias,
                        "Status" => $TopValueAs['status'],
                        "Genre" => $genre,
                        "Star" => $Star,
                        "Rating" => $rating,
                        "IdDetailAnime" => $TopValueAs['id_detail_anime'],
                        // "IdChapter" => $TopValueAs['id_chapter'],
                        "SlugDetail" => $SlugDetail,
                        // "SlugChp" => $TopValueAs['slug'],
                        'PublishDate' => Carbon::parse($TopValueAs['publish_date'])->format('Y-m-d\TH:i:s'),
                    );
                }
            }

            $TopValueComb = [
                $this->reSortKeyArrayLastUpdate($Top1),
                $this->reSortKeyArrayLastUpdate($Top2),
            ];
            $TopAnime = $this->combinationArray($TopValueComb);
            $seachTotal = $TotalSearch['collection'];
            $TotalSearchPage = HelpersController::TotalSeachPage($limitRange, $seachTotal);
            $PageSearch = HelpersController::PageSearch($starIndex, $limitRange);
            $getTopAnimeValue = [
                "TotalSearchPage" => $TotalSearchPage,
                "PageSearch" => $PageSearch,
                "FirstPagination" => self::FirstPagination($PageSearch,$minRowPegination),
                'TopAnime' => $TopAnime
            ];
            return ResponseConnected::Success("Top Anime", NULL, $getTopAnimeValue, $awal);
        }else{
            return ResponseConnected::PageNotFound("Top Anime","Page Not Found.", $awal);
        }
        
    }

    // mengurutkan ulang data last update
    public function reSortKeyArrayLastUpdate($ArrayLastUpdate){
        krsort($ArrayLastUpdate);
        $LastUpdateManga = array_values($ArrayLastUpdate);
        return $LastUpdateManga;
    }

    public function combinationArray($RecomendationAnimeValue){
        $combination = [];
        for($i = 0 ;$i <count($RecomendationAnimeValue); $i++){
            for($j = 0 ; $j < count($RecomendationAnimeValue[$i]); $j++){
                $combination[] = [
                    "Image" => $RecomendationAnimeValue[$i][$j]['Image'],
                    "Title" => $RecomendationAnimeValue[$i][$j]['Title'],
                    "TitleAlias" => $RecomendationAnimeValue[$i][$j]['TitleAlias'],
                    "Status" => $RecomendationAnimeValue[$i][$j]['Status'],
                    "Genre" => $RecomendationAnimeValue[$i][$j]['Genre'],
                    "Star" => $RecomendationAnimeValue[$i][$j]['Star'],
                    "Rating" => $RecomendationAnimeValue[$i][$j]['Rating'],
                    "IdDetailAnime" => $RecomendationAnimeValue[$i][$j]['IdDetailAnime'],
                    // "IdChapter" => $RecomendationAnimeValue[$i][$j]['IdChapter'],
                    "SlugDetail" => $RecomendationAnimeValue[$i][$j]['SlugDetail'],
                    // "SlugChp" => $RecomendationAnimeValue[$i][$j]['SlugChp'],
                    'PublishDate' => $RecomendationAnimeValue[$i][$j]['PublishDate']
                ];       
            }
        }
        return $combination;
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