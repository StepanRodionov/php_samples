<?php

class GeoCoder
{
   private static $url = 'https://geocode-maps.yandex.ru/1.x/?format=json&geocode=';
   
   private const TIMEOUT = 5;

   public function __construct(){
       // do nothing
   }

   public static function GetCoords($addr){
       if($coords = self::checkAddrCoords($addr)){
          return $coords;
       }
       $url = self::$url.$addr;
       $Connector = new Connector();
       $curlObj = $Connector->getOuterConnect($url)->getConnection();
       curl_setopt($curlObj, CURLOPT_TIMEOUT, self::TIMEOUT);
       $result = curl_exec($curlObj);
       $result = self::manageYandexResponce($result, $url);
       return $result;
   }

   private static function manageYandexResponce($resp, $url = false){
       $loc = json_decode($resp, true);
       $GeoPoint = $loc['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'];
       $GeoPoint = explode(' ', $GeoPoint);
       $arRet = array(
           'LAT' => $GeoPoint[1],
           'LON' => $GeoPoint[0]
       );
       if(!strlen($GeoPoint[1])){
           // Logger::write([$GeoPoint, $loc, $resp, $url]);    // it's todo feature :)
       }
       return $arRet;
   }
   
   private static function checkAddrCoords(){
      /*  TODO - try to get cached result from db - in case of indexing large list of common addresses
      
      $coords = weFoundCoordsInDB()
      if($coords){
          return $coords;
      }
      */
      return false;
   }

}
