<?php

namespace App\Helpers\V1;

use Carbon\Carbon;
use Illuminate\Support\Str;

class HelpersController
{
    public static function TotalSeachPage($limitRange, $seachTotal){
        $PageCount = floor($seachTotal / $limitRange);
        if($PageCount >= 1){
            $PageMod = (($seachTotal % $limitRange));
            if($PageMod != 0){
                $PageCount = $PageCount + 1;  
            }
        }else{
            $PageCount = 1;
        }
        return number_format($PageCount);
    }

    public static function PageSearch($starIndex, $limitRange){
        $PageSearch = floor($starIndex / $limitRange);
        if($PageSearch >= 1){
            $PageMod = (($starIndex % $limitRange));
            $PageSearch = $PageSearch + 1;  
            if($PageMod != 0){
                $PageSearch = $PageSearch + 1;  
            }
        }else{
            $PageSearch = 1;
        }
        return number_format($PageSearch);
    }

    public static function __rearrangeArrayIndex($NameIndex){
        sort($NameIndex);
        $NameIndex = (array_unique($NameIndex));
        ksort($NameIndex);
        $NameIndex = array_values($NameIndex);
        return $NameIndex;
    }
    public static function __rearrangeArrayIndexNotSortir($NameIndex){
        $NameIndex = (array_unique($NameIndex));
        ksort($NameIndex);
        $NameIndex = array_values($NameIndex);
        
        return $NameIndex;
    }
}