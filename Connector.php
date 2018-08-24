<?php

namespace Common\Utils;


use Bitrix\Main\DB\Exception;

class Connector
{
   private $connect = '';

   private static $proxy = 'Yml0cml4OldUNGg2ajNiQHByb3h5LnJvc2V1cm9iYW5rLnJ1OjMxMjg=';

   private $postData = array();

   private $token = '';

   private $headers = array();

   private $URL = '';

   private $POST = false;

   private $header = false;

   private $result = '';

   private $raw = false;

   private $error = false;

   public function __construct(){
       $this->connect = curl_init();
       $this->token = self::GetCSRF_TOKEN();
       curl_setopt($this->connect, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($this->connect, CURLOPT_FOLLOWLOCATION, 1);
       return $this;
   }

   public function getMdaConnection($url, $layer = false){
       $host = GetMdaHost($layer);
       $url = explode('?', $url)[0];
       $host .= $url;
       $this->headers['X-CSRF-Token'] = $this->token;
       $this->URL = $host;
       curl_setopt($this->connect, CURLOPT_URL, $host);
       return $this;
   }

   public function getOuterConnect($url){
       $this->URL = $url;
       curl_setopt($this->connect, CURLOPT_URL, $url);
       $proxy = self::GetProxyParams();
       curl_setopt($this->connect, CURLOPT_PROXY, $proxy);
       return $this;
   }

   public function addHeaders($addHeaders = array(), $flush = false){
       if(!is_array($addHeaders)){
           $addHeaders = array();
       }
       unset($addHeaders['X-CSRF-Token']);
       $this->headers = array_merge($this->headers, $addHeaders);
       if($flush){
           $cb = function($elem, $index){
               return $elem .": " . $index;
           };
           $arheads = array_map($cb, array_keys($this->headers), $this->headers);
           curl_setopt($this->connect, CURLOPT_HTTPHEADER, $arheads);
       }
       return $this;
   }

   public function post($set = true){
       $this->POST = $set;
       $set = intval($set);
       curl_setopt($this->connect, CURLOPT_POST, $set);
       return $this;
   }

   public function header($set = true){
       $this->header = $set;
       $set = intval($set);
       curl_setopt($this->connect, CURLOPT_HEADER, $set);
       return $this;
   }

   public function addData($addData = array(), $flush = false){
       if(!is_array($addData)){
           $addData = array();
       }
       unset($addData['CSRF_TOKEN']);
       $this->postData = array_merge($this->postData, $addData);
       if($flush){
           if($this->POST){
               curl_setopt($this->connect, CURLOPT_POSTFIELDS, $this->postData);
           }else{
               $req_data = http_build_query($this->postData);
               $newUrl = $this->URL.'?'.$req_data;
               curl_setopt($this->connect, CURLOPT_URL, $newUrl);
           }

       }
       return $this;
   }

   public function addRawData($data){
       $this->postData = $data;
       $this->post();
       $this->raw = true;
       curl_setopt($this->connect, CURLOPT_POSTFIELDS, $this->postData);
       return $this;
   }

   public function exec(){
       if(!$this->raw){
           $this->addData($this->postData, true)->addHeaders($this->headers, true);
       }
       $res = curl_exec($this->connect);
       $this->result = $res;
       if(curl_error($this->connect)){
           $this->error = curl_error($this->connect).'('.curl_errno($this->connect).')';
       }
       return $this;
   }

   public function getResult(){
       return $this->result;
   }

   public function getError(){
       return $this->error;
   }

   public function getConnection(){
       return $this->connect;
   }

   private static function GetCSRF_TOKEN(){
       // TODO
       return md5(NEWGUID());
   }

   private static function GetLayer(){
       return \COption::GetOptionString('#MODULE_CODE#', '#OPTION_NAME#');
   }

   private static function GetProxyParams(){
       $layer = self::GetLayer();
       // Определяем настройки прокси в зависимости от слоя
       // Инкапсулируем сложность, связанную с тем, что тест, дев и прод имеют их разные
       return $proxy;
   }
}
