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

// done
class DetailListAnimeController extends Controller
{

    public function testing(){
        echo "WELCOM TO API CONTENT Detail ANIME NIMEINDO V1";
    }
    // KeyListAnim
    public function DetailListAnim(Request $request){
        $awal = microtime(true);
        $param = $request->all();
        $ApiKey = $request->header("X-API-KEY");
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        
        if($Token){
            // try{
                return $this->DetailListAnimeValue($param,$awal);     
            // }catch(\Exception $e){
            //     return ResponseConnected::InternalServerError("Detail Anime","Internal Server Error",$awal);
            // }
        }else{
            return ResponseConnected::InvalidToken("Detail Anime","Invalid Token", $awal);
        }
    }
    
    public function DetailListAnimeValue($param,$awal){
        $idDetail = isset($param['params']['id_detail']) ? $param['params']['id_detail'] : '';
        $slugDetail = isset($param['params']['slug_detail']) ? $param['params']['slug_detail'] : '';
        $starIndexRelated = (isset($param['params']['star_index_related'])) ? (int)($param['params']['star_index_related']) : 0;
        $limitRangeRelated = (isset($param['params']['limit_range_related'])) ? (int)($param['params']['limit_range_related']) : 20;
        if(!empty($idDetail) || !empty($slugDetail)){
            $dataDetail = MainModel::getDetailAnime([
                'id_detail' => $idDetail,
                'slug' => $slugDetail
            ]);
        }else{
            $dataDetail['collection'] = array();
        }
        
        if(count($dataDetail['collection']) > 0){
            $dataDetailAss = $dataDetail['collection'];
            $genre = '';
            foreach($dataDetailAss as $dataDetailAs){
                foreach($dataDetailAs['genre'] as $genree){
                    $genre .= $genree.' | ';
                }
                $getRelatedData = MainModel::getRelatedData([
                    'genre' => $dataDetailAs['genre'],
                    'slug' => $dataDetailAs['slug'],
                    'star_index' => $starIndexRelated,
                    'limit_range' => $limitRangeRelated

                ]);
                $RelatedData =[];
                if(count($getRelatedData['collection']) > 0){
                    
                    foreach($getRelatedData['collection'] as $dataRelatedV){
                        $genreAs = '';
                        $rating = 0;
                        $dataGenre = $dataRelatedV['genre'];
                        $rating = $dataRelatedV['rating'];
                        foreach($dataGenre as $dataGenreAs){
                            $genreAs .= $dataGenreAs.',';
                        }
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
                        $RelatedData[] = [
                            "Title" => ucwords(str_replace('-',' ',$dataRelatedV['slug'])),
                            "Image" => $dataRelatedV['image'],
                            "Status" => $dataRelatedV['status'],
                            "Genre" => $genreAs,
                            "Star" => $Star,
                            "Rating" => $rating,
                            'PublishDate' => Carbon::parse($dataRelatedV['cron_at'])->format('Y-m-d\TH:i:s'),
                            "SlugDetail" => $dataRelatedV['slug'],
                        ];
                        
                    }
                }
                $ListInfo = array(
                    "Tipe" =>$dataDetailAs['type'],
                    "Genre" => rtrim($genre,'| '),
                    "Status" => $dataDetailAs['status'],
                    "Episode" => $dataDetailAs['episode_total'],
                    "Years" => '',
                    "Score" => $dataDetailAs['score'],
                    "Rating" => $dataDetailAs['rating'],
                    "Studio" => $dataDetailAs['studio'],
                    "Duration" => $dataDetailAs['duration'],
                );
                foreach($dataDetailAs['episode'] as $episodeAs){
                    $getDataStream = MainModel::getDataStream([
                        'slug_eps' => $episodeAs['slug'],
                    ]);
                    $dataStreamAs = $getDataStream['collection'];
                    $ListDownload =[];
                    foreach($dataStreamAs['data_download'] as $key => $DownloadList){
                        $ListDownload[]= [
                            'IdDownload' => $DownloadList[0]['id_download'],
                            'NameDownload' => $DownloadList[0]['name_download'],
                            'AdflyLink' => $DownloadList[0]['adfly_link']
                        ];
                    }
                    
                    $ListEpisode[] = array(
                        'IDEpisode' => $episodeAs['id_episode'],
                        'IDStream' => $episodeAs['id_stream_anime'],
                        "SlugEp" => $episodeAs['slug'],
                        "Episode" => $episodeAs['episode'],
                        "ListDownload" => $ListDownload
                    );
                }

                $Synopsis = $dataDetailAs['synopsis'];
                $Title = ucwords(str_replace('-',' ',$dataDetailAs['slug']));
                $imageUrl = $dataDetailAs['image'];
                $Slug = $dataDetailAs['slug'];
                
                $ListDetail[] = array(
                    "ListInfo" => $ListInfo,
                    "Synopsis" => $Synopsis
                );
                $DetailListAnime[] = array(
                    "Title" => $Title,
                    "Image" => $imageUrl,
                    'PublishDate' => Carbon::parse($dataDetailAs['cron_at'])->format('Y-m-d\TH:i:s'),
                    "SlugDetail" => $Slug,
                    "ListDetail" =>$ListDetail,
                    "ListEpisode" => $ListEpisode,
                    "RelatedData" => $RelatedData
                );
            }
            $LogSave = [
                'SingleListAnime' => $DetailListAnime
            ];
            return ResponseConnected::Success("Detail Anime", NULL, $LogSave, $awal);
        }else{
            return ResponseConnected::PageNotFound("Detail Anime","Page Not Found.", $awal);
        }
    }
}