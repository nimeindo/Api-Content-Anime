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

class GenreListAnimeController extends Controller
{
    public function GenreListAnime(Request $request){
        $awal = microtime(true);
        $param = $request->all();
        $ApiKey = $request->header("X-API-KEY");
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            try{
                return $this->GenreListAnimValue($param,$awal);
            }catch(\Exception $e){
                return ResponseConnected::InternalServerError("Genre List Anime","Internal Server Error",$awal);
            }
            
        }else{
            return ResponseConnected::InvalidToken("Genre List Anime","Invalid Token", $awal);
        }
    }

    public function GenreListAnimValue($param,$awal){
        $nameIndex = (isset($param['params']['name_index']) || !empty($param['params']['name_index']))? $param['params']['name_index'] : '';
        $allIndex  = (isset($param['params']['all_index']) || !empty($param['params']['all_index'])) ? filter_var($param['params']['all_index'], FILTER_VALIDATE_BOOLEAN): FALSE ;
        
        if(($allIndex) || !empty($nameIndex)){
            $getListGenreAnime  = MainModel::getListGenreAnime([
                'name_index' => $nameIndex,
                'All_index' => $allIndex
            ]);
        }else{
            $getListGenreAnime['collection'] = array();
        }
        
        
        if(count($getListGenreAnime['collection']) > 0){
            // Get the latest post in this category and display the titles
            $NameIndex = array();
            foreach($getListGenreAnime['collection'] as $dataListGenreAnimeAs){
                $NameIndex [] = $dataListGenreAnimeAs['name_index'];
            }
            $ListAnime = array();
            $NameIndex = HelpersController::__rearrangeArrayIndex($NameIndex);
            for($i = 0 ; $i < count($NameIndex); $i++){
                $ListSubIndex = array();
                foreach($getListGenreAnime['collection'] as $dataListGenreAss){
                    $NameIndexVal = $dataListGenreAss['name_index'];
                    if($NameIndexVal == $NameIndex[$i]){
                        $ListSubIndex[] = array(
                            "Genre" => $dataListGenreAss['genre'],
                            "Slug" => $dataListGenreAss['slug'],
                        );
                    }
                }
                $GenreListAnime[] = array(
                    "NameIndex"=> $NameIndex[$i],
                    "ListSubIndex"=> $ListSubIndex
                );
            }
            $LogSave = [
                'GenreListAnime' => $GenreListAnime
            ];
            return ResponseConnected::Success("Genre List Anime", NULL, $LogSave, $awal);
        }else{
            return ResponseConnected::PageNotFound("Genre List Anime","Page Not Found.", $awal);
        }
    }
}