<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#Load Component External
use Cache;
use Config;
use Carbon\Carbon;

#Load Helpers V1

#Load Collection V1
use App\Models\V1\Mongo\CollectionListAnimeModel;
use App\Models\V1\Mongo\CollectionDetailAnimeModel;
use App\Models\V1\Mongo\CollectionLastUpdateModel;
use App\Models\V1\Mongo\CollectionStreamAnimeModel;
use App\Models\V1\Mongo\CollectionGenreListModel;
use App\Models\V1\Mongo\CollectionRecomendationAnimeModel;
use App\Models\V1\Mongo\CollectionTopAnimeModel;
use App\Models\V1\Mongo\CollectionSliderAnimeModel;

class MainModel extends Model
{
    function __construct(){
        $mongoConf = Config::get('mongo_config');
        $timeout = $mongoConf['query_timeout'];
        $env = $mongoConf['environtment'];
    }
    /**
     * @author [Prayugo]
     * @create date 2020-02-04 21:26:42
     * @desc [getUser]
     */
    #================ getUser ==================================
    static function getUser($ApiKey){
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table('User')
            ->where('token', $ApiKey);
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;
    }
    #================ End getUser ==================================

    /**
     * @author [Prayugo]
     * @create date 2020-02-04 21:26:42
     * @desc [getDataListAnime]
     */
    #================ getDataListAnime ==================================
    static function getDataListAnime($params = [],$database_name = 'mongodb'){
        
        $nameIndex = (isset($params['name_index']) ? $params['name_index'] : ''); #id yang akan dicari
        $AllIndex = (isset($params['All_index']) ? (int)$params['All_index'] : FALSE);  #limit data related
        $limitRange = (isset($params['limit_range']) ? (int)$params['limit_range'] : '');  #limit data related
        $starIndex = (isset($params['star_index']) ? (int)$params['star_index'] : 0);  #limit data related
        $isUpdated = (isset($params['is_updated']) ? $params['is_updated'] : FALSE); #untuk data terbaru 2 jam terakhir
        
        $enableCache = (isset($params['enable_cache']) ? $params['enable_cache'] : FALSE); #cache
        $cacheTimeout = (isset($params['cache_timeout']) ? $params['cache_timeout'] : 180); #seconds
        $cekCount = (isset($params['cek_count']) ? $params['cek_count'] : FALSE); #untuk data terbaru 2 jam terakhir
        
        /*Read Cache*/
        if($AllIndex){
            $nameIndex = '';
        }
        if($enableCache){
            $pref = 'getDataListAnime';
            $nameCache = '';
            if(empty($nameIndex)){
                $nameCache = 'All';
            }else{
                $nameCache = $nameIndex;
            }
            unset($params['enable_cache'], $params['cache_timeout']); //agar ada cachenya (di cache_timeout berbentuk datetimr jadi dinamis)

            $cacheName = 'List:'.':'.$nameCache.':'.md5($pref.'_'.serialize($params));
            if(Cache::has($cacheName)){
                $data = Cache::get($cacheName);

                return $data;
            }
        }

        { /*Build Query*/
            
            { /*Query Utama*/
                $try['status'] = 200;
                $try['message'] = '';

                try {
                    $query = CollectionListAnimeModel::on($database_name)
                        ->timeout(9000);
                    if($isUpdated){ #ambil data update atau terbaru
                        $query = $query->orderBy('name_index', 'DESC');
                    }else{
                        $query = $query->orderBy('name_index', 'ASC');
                    }
                    if(!empty($nameIndex)) $query = $query->where('name_index', '=', '#'.$nameIndex);
                    $query = $query->offset($starIndex);
                    if(!empty($limitRange)) $query = $query->limit($limitRange);
                    
                } catch (\Exception $e) {

                    $query = [];
                    $try['status'] = 400;
                    $try['message'] = $e->getMessage();

                }
            } /*End Query Utama*/
            
            $collection = '';
            $data['collection_count'] = 0;
            if(!empty($query)){
                if($cekCount){
                    $collection = $query->count();    
                }else{
                    $collection = $query->get();
                }
                // $collection = $query->get();
                // $data['collection_count'] = 1;
            }
        } /*End Build Query*/
        $data['collection'] = $collection;
        
        if($data['collection_count'] > 0){
            $cacheTimeout = Carbon::now()->addSeconds($cacheTimeout);
            /*Save Cache*/
            if($enableCache){
                Cache::add($cacheName, $data, $cacheTimeout);
            }
            /*End*/
        }
        return $data;

    }
    #================ End getDataListAnime ==================================

    /**
     * @author [Prayugo]
     * @create date 2020-07-12 16:29:17
     * @modify date 2020-07-12 16:29:17
     * @desc [getRelatedData]
     */
    #================ getRelatedData ==================================
    static function getRelatedData($params = [],$database_name = 'mongodb'){
        
        $genre = (isset($params['genre']) ? $params['genre'] : ''); #id yang akan dicari
        $slug = (isset($params['slug']) ? $params['slug'] : ''); #id yang akan dicari
        $starIndex = (isset($params['star_index']) ? (int)$params['star_index'] : 0);  #limit data related
        $limitRange = (isset($params['limit_range']) ? (int)$params['limit_range'] : 20);  #limit data related
        $trending = (isset($params['tranding']) ? $params['tranding'] : FALSE); #cache
        $Schedule = (isset($params['Schedule']) ? $params['Schedule'] : FALSE); #cache

        $enableCache = (isset($params['enable_cache']) ? $params['enable_cache'] : TRUE); #cache
        $cacheTimeout = (isset($params['cache_timeout']) ? $params['cache_timeout'] : 180); #seconds
        
        /*Read Cache*/
        if(!empty($idDetail)){
            $slug = '';
        }
        if($enableCache){
            $pref = 'getRelatedData';
            $nameCache = $slug;
            
            unset($params['enable_cache'], $params['cache_timeout']); //agar ada cachenya (di cache_timeout berbentuk datetimr jadi dinamis)

            $cacheName = 'RelatedData:'.':'.$nameCache.':'.md5($pref.'_'.serialize($params));
            if(Cache::has($cacheName)){
                $data = Cache::get($cacheName);

                return $data;
            }
        }
        { /*Build Query*/
            { /*Query Utama*/
                $try['status'] = 200;
                $try['message'] = '';
                try {
                    $query = CollectionDetailAnimeModel::on($database_name)
                        ->timeout(9000);
                    $keywordsGenre = array_unique(array_filter($genre));
                    $query = $query->whereIn('genre', $keywordsGenre);
                    $query = $query->orderBy('cron_at', 'DESC');
                    $query = $query->offset($starIndex)
                            ->limit($limitRange);

                    // $query = $query->get();
                } catch (\Exception $e) {

                    $query = [];
                    $try['status'] = 400;
                    $try['message'] = $e->getMessage();

                }
            } /*End Query Utama*/
            
            $collection = '';
            $data['collection_count'] = 0;
            if(!empty($query)){
                $collection = $query->get();
                $data['collection_count'] = 1;
            }
        } /*End Build Query*/
        $data['collection'] = $collection;
        
        if($data['collection_count'] > 0){
            $cacheTimeout = Carbon::now()->addSeconds($cacheTimeout);
            /*Save Cache*/
            if($enableCache){
                Cache::add($cacheName, $data, $cacheTimeout);
            }
            /*End*/
        }
        return $data;

    }
    #================ End getRelatedData ==================================

    /**
     * @author [Prayugo]
     * @create date 2020-02-04 21:26:42
     * @desc [getDetailAnime]
     */
    #================ getDetailAnime ==================================
    static function getDetailAnime($params = [],$database_name = 'mongodb'){
        
        $idDetail = (isset($params['id_detail']) ? $params['id_detail'] : ''); #id yang akan dicari
        $slug = (isset($params['slug']) ? $params['slug'] : ''); 
        $status = (isset($params['status']) ? $params['status'] : ''); 
        $rating = (isset($params['rating']) ? $params['rating'] : ''); 
        $day = (isset($params['day']) ? $params['day'] : ''); 
        $trending = (isset($params['tranding']) ? $params['tranding'] : FALSE); #cache
        $Schedule = (isset($params['Schedule']) ? $params['Schedule'] : FALSE); #cache

        $enableCache = (isset($params['enable_cache']) ? $params['enable_cache'] : TRUE); #cache
        $cacheTimeout = (isset($params['cache_timeout']) ? $params['cache_timeout'] : 180); #seconds
        
        /*Read Cache*/
        if(!empty($idDetail)){
            $slug = '';
        }
        if($enableCache){
            $pref = 'getDetailAnime';
            $nameCache = '';
            
            if(!empty($idDetail)){
                $nameCache = $idDetail;
            }elseif($trending){
                $nameCache = 'TrendingWeekAnime'.':'.date('Y-m-d');
            }elseif($Schedule){
                $nameCache = 'Schedule'.':'.$day;
            }else{
                $nameCache = $slug;
            }
            
            unset($params['enable_cache'], $params['cache_timeout']); //agar ada cachenya (di cache_timeout berbentuk datetimr jadi dinamis)

            $cacheName = 'Detail:'.':'.$nameCache.':'.md5($pref.'_'.serialize($params));
            if(Cache::has($cacheName)){
                $data = Cache::get($cacheName);

                return $data;
            }
        }
        { /*Build Query*/
            { /*Query Utama*/
                $try['status'] = 200;
                $try['message'] = '';
                try {
                    $query = CollectionDetailAnimeModel::on($database_name)
                        ->timeout(9000);
                    if(!empty($idDetail)) $query = $query->where('id_detail_anime', '=', (int)$idDetail);
                    if(!empty($slug)) $query = $query->where('slug', '=',$slug);
                    if(!empty($status)) $query = $query->where('status', 'LIKE','%'.$status.'%');
                    if(!empty($rating)) $query = $query->where('rating', '>=',$rating);
                    if(!empty($day)) $query = $query->where('hari_tayang', 'LIKE','%'.$day.'%');

                    // $query = $query->get();
                } catch (\Exception $e) {

                    $query = [];
                    $try['status'] = 400;
                    $try['message'] = $e->getMessage();

                }
            } /*End Query Utama*/
            
            $collection = '';
            $data['collection_count'] = 0;
            if(!empty($query)){
                $collection = $query->get();
                $data['collection_count'] = 1;
            }
        } /*End Build Query*/
        $data['collection'] = $collection;
        
        if($data['collection_count'] > 0){
            $cacheTimeout = Carbon::now()->addSeconds($cacheTimeout);
            /*Save Cache*/
            if($enableCache){
                Cache::add($cacheName, $data, $cacheTimeout);
            }
            /*End*/
        }
        return $data;

    }
    #================ End getDetailAnime ==================================

    /**
     * @author [Prayugo]
     * @create date 2020-02-04 21:26:42
     * @desc [getSearchWithDetailAnime]
     */
    #================ getSearchWithDetailAnime ==================================
    static function getSearchWithDetailAnime($params = [],$database_name = 'mongodb'){
        
        $idDetail = (isset($params['id_detail']) ? $params['id_detail'] : ''); #id yang akan dicari
        $keyword = (isset($params['keyword']) ? $params['keyword'] : '');  #limit data related
        $status = (isset($params['status']) ? $params['status'] : '');  #limit data related
        $genre = (isset($params['genre']) ? $params['genre'] : '');  #limit data related
        $limitRange = (isset($params['limit_range']) ? (int)$params['limit_range'] : 20);  #limit data related
        $starIndex = (isset($params['star_index']) ? (int)$params['star_index'] : 0);  #limit data related
        $enableCache = (isset($params['enable_cache']) ? $params['enable_cache'] : TRUE); #cache
        $cacheTimeout = (isset($params['cache_timeout']) ? $params['cache_timeout'] : 180); #seconds
        $isUpdated = (isset($params['is_updated']) ? $params['is_updated'] : FALSE); #untuk data terbaru 2 jam terakhir
        $cekCount = (isset($params['cek_count']) ? $params['cek_count'] : FALSE); #untuk data terbaru 2 jam terakhir
        
        /*Read Cache*/
        if($enableCache){
            $pref = 'getSearchWithDetailAnime';
            $nameCache = '';
            if(!empty($keyword)){
                foreach($keyword as $key => $valueKey){
                    $nameCache .= $valueKey.'-'; 
                }
            }elseif(!empty($status)){
                $nameCache = $status;
            }else{
                $nameCache = $genre;
            }
            
            unset($params['enable_cache'], $params['cache_timeout']); //agar ada cachenya (di cache_timeout berbentuk datetimr jadi dinamis)

            $cacheName = 'getSearchWithDetailAnime:'.':'.$nameCache.':'.md5($pref.'_'.serialize($params));
            if(Cache::has($cacheName)){
                $data = Cache::get($cacheName);

                return $data;
            }
        }
        
        { /*Build Query*/
            { /*Query Utama*/
                $try['status'] = 200;
                $try['message'] = '';
                
                try {
                    $query = CollectionDetailAnimeModel::on($database_name)
                        ->timeout(9000);
                    if(!empty($idDetail)) $query = $query->where('id_detail_anime', '=', (int)$idDetail);
                    if(!empty($keyword)){
                        foreach($keyword as $key => $valueKey){
                            if(!empty($valueKey)) $query = $query->where('keyword', 'LIKE',"%".$valueKey."%");
                        }
                    }
                    if(!empty($genre)) $query = $query->where('genre', 'LIKE',"%".$genre."%");
                    if(!empty($status)) $query = $query->where('status', 'LIKE',"%".$status."%");
                    if($isUpdated){ #ambil data update atau terbaru
                        $query = $query->orderBy('cron_at', 'DESC');
                    }
                    $query = $query->offset($starIndex)
                            ->limit($limitRange);
                        
                } catch (\Exception $e) {

                    $query = [];
                    $try['status'] = 400;
                    $try['message'] = $e->getMessage();

                }
            } /*End Query Utama*/
            
            $collection = '';
            $data['collection_count'] = 0;
            if(!empty($query)){
                if($cekCount){
                    $collection = $query->count();    
                }else{
                    $collection = $query->get();
                }
                
                $data['collection_count'] = 1;
            }
        } /*End Build Query*/
        $data['collection'] = $collection;
        
        if($data['collection_count'] > 0){
            $cacheTimeout = Carbon::now()->addSeconds($cacheTimeout);
            /*Save Cache*/
            if($enableCache){
                Cache::add($cacheName, $data, $cacheTimeout);
            }
            /*End*/
        }
        return $data;

    }
    #================ End getSearchWithDetailAnime ==================================

    /**
     * @author [Prayugo]
     * @create date 2020-02-04 21:26:42
     * @desc [getDataLastUpdate]
     */
    #================ getDataLastUpdate ==================================
    static function getDataLastUpdate($params = [],$database_name = 'mongodb'){
        $limitRange = (isset($params['limit_range']) ? (int)$params['limit_range'] : 20);  #limit data related
        $starIndex = (isset($params['star_index']) ? (int)$params['star_index'] : 0);  #limit data related
        $enableCache = (isset($params['enable_cache']) ? $params['enable_cache'] : TRUE); #cache
        $cacheTimeout = (isset($params['cache_timeout']) ? $params['cache_timeout'] : 180); #seconds
        $isUpdated = (isset($params['is_updated']) ? $params['is_updated'] : FALSE); #untuk data terbaru 2 jam terakhir
        $cekCount = (isset($params['cek_count']) ? $params['cek_count'] : FALSE); #untuk data terbaru 2 jam terakhir
        $date = date('Y-m-d H');
        
        /*Read Cache*/
        if($enableCache){
            $pref = 'getDataLastUpdate';
            $nameCache = $date.'-'.$limitRange;
            unset($params['enable_cache'], $params['cache_timeout']); //agar ada cachenya (di cache_timeout berbentuk datetimr jadi dinamis)

            $cacheName = 'getDataLastUpdate:'.':'.$nameCache.':'.md5($pref.'_'.serialize($params));
            if(Cache::has($cacheName)){
                $data = Cache::get($cacheName);

                return $data;
            }
        }
        
        { /*Build Query*/
            { /*Query Utama*/
                $try['status'] = 200;
                $try['message'] = '';
                
                try {
                    $query = CollectionLastUpdateModel::on($database_name)
                        ->timeout(9000);
                    if($isUpdated){ #ambil data update atau terbaru
                        $query = $query->orderBy('cron_at', 'DESC');
                    }
                    $query = $query->offset($starIndex)
                            ->limit($limitRange);
                    
                } catch (\Exception $e) {

                    $query = [];
                    $try['status'] = 400;
                    $try['message'] = $e->getMessage();

                }
            } /*End Query Utama*/
            
            $collection = '';
            $data['collection_count'] = 0;
            if(!empty($query)){
                if($cekCount){
                    $collection = $query->count();    
                }else{
                    $collection = $query->get();
                }
                
                $data['collection_count'] = 1;
            }
        } /*End Build Query*/
        $data['collection'] = $collection;
        
        if($data['collection_count'] > 0){
            $cacheTimeout = Carbon::now()->addSeconds($cacheTimeout);
            /*Save Cache*/
            if($enableCache){
                Cache::add($cacheName, $data, $cacheTimeout);
            }
            /*End*/
        }
        return $data;

    }
    #================ End getDataLastUpdate ==================================

    /**
     * @author [Prayugo]
     * @create date 2020-02-04 21:26:42
     * @desc [getDataStream]
     */
    #================ getDataStream ==================================
    static function getDataStream($params = [],$database_name = 'mongodb'){
        
        $IDStream = (isset($params['ID_Stream']) ? $params['ID_Stream'] : ''); #id yang akan dicari
        $slugEps = (isset($params['slug_eps']) ? $params['slug_eps'] : ''); #id yang akan dicari
        $id_list_episode = (isset($params['id_list_episode']) ? $params['id_list_episode'] : ''); #id yang akan dicari
        
        $enableCache = (isset($params['enable_cache']) ? $params['enable_cache'] : TRUE); #cache
        $cacheTimeout = (isset($params['cache_timeout']) ? $params['cache_timeout'] : 180); #seconds
        
        /*Read Cache*/
        if($enableCache){
            $pref = 'getDataStream';
            $nameCache = '';
            if(empty($IDStream)){
                $nameCache = $slugEps;
            }else{
                $nameCache = $IDStream;
            }
            unset($params['enable_cache'], $params['cache_timeout']); //agar ada cachenya (di cache_timeout berbentuk datetimr jadi dinamis)

            $cacheName = 'getDataStream:'.':'.$nameCache.':'.md5($pref.'_'.serialize($params));
            if(Cache::has($cacheName)){
                $data = Cache::get($cacheName);

                return $data;
            }
        }
        { /*Build Query*/
            { /*Query Utama*/
                $try['status'] = 200;
                $try['message'] = '';
                
                try {
                    $query = CollectionStreamAnimeModel::on($database_name)
                        ->timeout(9000);
                    if(!empty($IDStream)) $query = $query->where('id_stream_anime', '=', (int)$IDStream);
                    if(!empty($slugEps)) $query = $query->where('slug', '=', $slugEps);
                    if(!empty($id_list_episode)) $query = $query->where('id_list_episode', '=', $id_list_episode);
                    
                    $query = $query->first();
                } catch (\Exception $e) {

                    $query = [];
                    $try['status'] = 400;
                    $try['message'] = $e->getMessage();

                }
            } /*End Query Utama*/
            
            $collection = '';
            $data['collection_count'] = 0;
            if(!empty($query)){
                $collection = $query->toArray();
                $data['collection_count'] = 1;
            }
        } /*End Build Query*/
        $data['collection'] = $collection;
        
        if($data['collection_count'] > 0){
            $cacheTimeout = Carbon::now()->addSeconds($cacheTimeout);
            /*Save Cache*/
            if($enableCache){
                Cache::add($cacheName, $data, $cacheTimeout);
            }
            /*End*/
        }
        return $data;

    }
    #================ End getDataStream ==================================

    /**
     * @author [Prayugo]
     * @create date 2020-02-04 21:26:42
     * @desc [getListGenreAnime]
     */
    #================ getListGenreAnime ==================================
    static function getListGenreAnime($params = [],$database_name = 'mongodb'){
        
        $nameIndex = (isset($params['name_index']) ? $params['name_index'] : ''); #id yang akan dicari
        $AllIndex = (isset($params['All_index']) ? (int)$params['All_index'] : 10);  #limit data related
        
        $enableCache = (isset($params['enable_cache']) ? $params['enable_cache'] : TRUE); #cache
        $cacheTimeout = (isset($params['cache_timeout']) ? $params['cache_timeout'] : 180); #seconds
        
        /*Read Cache*/
        if($AllIndex){
            $nameIndex = '';
        }
        if($enableCache){
            $pref = 'getListGenreAnime';
            $nameCache = '';
            if(!empty($nameIndex)){
                $nameCache = 'All';
            }else{
                $nameCache = $nameIndex;
            }
            unset($params['enable_cache'], $params['cache_timeout']); //agar ada cachenya (di cache_timeout berbentuk datetimr jadi dinamis)

            $cacheName = 'ListGenre:'.':'.$nameCache.':'.md5($pref.'_'.serialize($params));
            if(Cache::has($cacheName)){
                $data = Cache::get($cacheName);

                return $data;
            }
        }

        { /*Build Query*/
            
            { /*Query Utama*/
                $try['status'] = 200;
                $try['message'] = '';

                try {
                    $query = CollectionGenreListModel::on($database_name)
                        ->timeout(9000)
                        ->orderBy('name_index','desc');
                    if(!empty($nameIndex)) $query = $query->where('name_index', '=', $nameIndex);
                    
                } catch (\Exception $e) {

                    $query = [];
                    $try['status'] = 400;
                    $try['message'] = $e->getMessage();

                }
            } /*End Query Utama*/
            
            $collection = '';
            $data['collection_count'] = 0;
            if(!empty($query)){
                $collection = $query->get();
                $data['collection_count'] = 1;
            }
        } /*End Build Query*/
        $data['collection'] = $collection;
        
        if($data['collection_count'] > 0){
            $cacheTimeout = Carbon::now()->addSeconds($cacheTimeout);
            /*Save Cache*/
            if($enableCache){
                Cache::add($cacheName, $data, $cacheTimeout);
            }
            /*End*/
        }
        return $data;

    }
    #================ End getListGenreAnime ==================================

    /**
     * @author [Prayugo]
     * @create date 2020-07-08 11:50:45
     * @modify date 2020-07-08 11:50:45
     * @desc [getDataRecomendation]
     */
    #================ getDataRecomendation ==================================
    static function getDataRecomendation($params = [],$database_name = 'mongodb'){
        $limitRange = (isset($params['limit_range']) ? (int)$params['limit_range'] : 20);  #limit data related
        $starIndex = (isset($params['star_index']) ? (int)$params['star_index'] : 0);  #limit data related
        $enableCache = (isset($params['enable_cache']) ? $params['enable_cache'] : TRUE); #cache
        $cacheTimeout = (isset($params['cache_timeout']) ? $params['cache_timeout'] : 180); #seconds
        $isUpdated = (isset($params['is_updated']) ? $params['is_updated'] : FALSE); #untuk data terbaru 2 jam terakhir
        $cekCount = (isset($params['cek_count']) ? $params['cek_count'] : FALSE); #untuk data terbaru 2 jam terakhir
        $date = date('Y-m-d H');
        
        /*Read Cache*/
        if($enableCache){
            $pref = 'getDataRecomendation';
            $nameCache = $date.'-'.$limitRange;
            unset($params['enable_cache'], $params['cache_timeout']); //agar ada cachenya (di cache_timeout berbentuk datetimr jadi dinamis)

            $cacheName = 'getDataRecomendation:'.':'.$nameCache.':'.md5($pref.'_'.serialize($params));
            if(Cache::has($cacheName)){
                $data = Cache::get($cacheName);

                return $data;
            }
        }
        
        { /*Build Query*/
            { /*Query Utama*/
                $try['status'] = 200;
                $try['message'] = '';
                
                try {
                    $query = CollectionRecomendationAnimeModel::on($database_name)
                        ->timeout(9000);
                        
                    if($isUpdated){ #ambil data update atau terbaru
                        $query = $query->orderBy('cron_at', 'DESC');
                    }
                    $query = $query->offset($starIndex)
                            ->limit($limitRange);
                    
                } catch (\Exception $e) {

                    $query = [];
                    $try['status'] = 400;
                    $try['message'] = $e->getMessage();

                }
            } /*End Query Utama*/
            
            $collection = '';
            $data['collection_count'] = 0;
            if(!empty($query)){
                if($cekCount){
                    $collection = $query->count();    
                }else{
                    $collection = $query->get();
                }
                
                $data['collection_count'] = 1;
            }
        } /*End Build Query*/
        $data['collection'] = $collection;
        
        if($data['collection_count'] > 0){
            $cacheTimeout = Carbon::now()->addSeconds($cacheTimeout);
            /*Save Cache*/
            if($enableCache){
                Cache::add($cacheName, $data, $cacheTimeout);
            }
            /*End*/
        }
        return $data;

    }
    #================ End getDataRecomendation ==================================

    /**
     * @author [Prayugo]
     * @create date 2020-07-08 11:50:45
     * @modify date 2020-07-08 11:50:45
     * @desc [getDataTopAnime]
     */
    #================ getDataTopAnime ==================================
    static function getDataTopAnime($params = [],$database_name = 'mongodb'){
        $limitRange = (isset($params['limit_range']) ? (int)$params['limit_range'] : 20);  #limit data related
        $starIndex = (isset($params['star_index']) ? (int)$params['star_index'] : 0);  #limit data related
        $enableCache = (isset($params['enable_cache']) ? $params['enable_cache'] : TRUE); #cache
        $cacheTimeout = (isset($params['cache_timeout']) ? $params['cache_timeout'] : 180); #seconds
        $isUpdated = (isset($params['is_updated']) ? $params['is_updated'] : FALSE); #untuk data terbaru 2 jam terakhir
        $cekCount = (isset($params['cek_count']) ? $params['cek_count'] : FALSE); #untuk data terbaru 2 jam terakhir
        $date = date('Y-m-d H');
        
        /*Read Cache*/
        if($enableCache){
            $pref = 'getDataTopAnime';
            $nameCache = $date.'-'.$limitRange;
            unset($params['enable_cache'], $params['cache_timeout']); //agar ada cachenya (di cache_timeout berbentuk datetimr jadi dinamis)

            $cacheName = 'getDataTopAnime:'.':'.$nameCache.':'.md5($pref.'_'.serialize($params));
            if(Cache::has($cacheName)){
                $data = Cache::get($cacheName);

                return $data;
            }
        }
        
        { /*Build Query*/
            { /*Query Utama*/
                $try['status'] = 200;
                $try['message'] = '';
                
                try {
                    $query = CollectionTopAnimeModel::on($database_name)
                        ->timeout(9000);
                    if($isUpdated){ #ambil data update atau terbaru
                        $query = $query->orderBy('cron_at', 'DESC');
                    }
                    $query = $query->offset($starIndex)
                            ->limit($limitRange);
                    
                } catch (\Exception $e) {

                    $query = [];
                    $try['status'] = 400;
                    $try['message'] = $e->getMessage();

                }
            } /*End Query Utama*/
            
            $collection = '';
            $data['collection_count'] = 0;
            if(!empty($query)){
                if($cekCount){
                    $collection = $query->count();    
                }else{
                    $collection = $query->get();
                }
                
                $data['collection_count'] = 1;
            }
        } /*End Build Query*/
        $data['collection'] = $collection;
        
        if($data['collection_count'] > 0){
            $cacheTimeout = Carbon::now()->addSeconds($cacheTimeout);
            /*Save Cache*/
            if($enableCache){
                Cache::add($cacheName, $data, $cacheTimeout);
            }
            /*End*/
        }
        return $data;

    }
    #================ End getDataTopAnime ==================================

    /**
     * @author [prayugo]
     * @create date 2020-07-25 00:58:07
     * @modify date 2020-07-25 00:58:07
     * @desc [getDataSliderAnime]
     */
    #================ getDataSliderAnime ==================================
    static function getDataSliderAnime($params = [],$database_name = 'mongodb'){
        $limitRange = (isset($params['limit_range']) ? (int)$params['limit_range'] : 20);  #limit data related
        $starIndex = (isset($params['star_index']) ? (int)$params['star_index'] : 0);  #limit data related
        $enableCache = (isset($params['enable_cache']) ? $params['enable_cache'] : TRUE); #cache
        $cacheTimeout = (isset($params['cache_timeout']) ? $params['cache_timeout'] : 180); #seconds
        $isUpdated = (isset($params['is_updated']) ? $params['is_updated'] : FALSE); #untuk data terbaru 2 jam terakhir
        $cekCount = (isset($params['cek_count']) ? $params['cek_count'] : FALSE); #untuk data terbaru 2 jam terakhir
        $date = date('Y-m-d H');
        
        /*Read Cache*/
        if($enableCache){
            $pref = 'getDataSliderAnime';
            $nameCache = $date.'-'.$limitRange;
            unset($params['enable_cache'], $params['cache_timeout']); //agar ada cachenya (di cache_timeout berbentuk datetimr jadi dinamis)

            $cacheName = 'getDataSliderAnime:'.':'.$nameCache.':'.md5($pref.'_'.serialize($params));
            if(Cache::has($cacheName)){
                $data = Cache::get($cacheName);

                return $data;
            }
        }
        
        { /*Build Query*/
            { /*Query Utama*/
                $try['status'] = 200;
                $try['message'] = '';
                
                try {
                    $query = CollectionSliderAnimeModel::on($database_name)
                        ->timeout(9000);
                    if($isUpdated){ #ambil data update atau terbaru
                        $query = $query->orderBy('cron_at', 'DESC');
                    }
                    $query = $query->offset($starIndex)
                            ->limit($limitRange);
                    
                } catch (\Exception $e) {

                    $query = [];
                    $try['status'] = 400;
                    $try['message'] = $e->getMessage();

                }
            } /*End Query Utama*/
            
            $collection = '';
            $data['collection_count'] = 0;
            if(!empty($query)){
                if($cekCount){
                    $collection = $query->count();    
                }else{
                    $collection = $query->get();
                }
                
                $data['collection_count'] = 1;
            }
        } /*End Build Query*/
        $data['collection'] = $collection;
        
        if($data['collection_count'] > 0){
            $cacheTimeout = Carbon::now()->addSeconds($cacheTimeout);
            /*Save Cache*/
            if($enableCache){
                Cache::add($cacheName, $data, $cacheTimeout);
            }
            /*End*/
        }
        return $data;

    }
    #================ End getDataSliderAnime ==================================

}