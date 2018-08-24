<?php

namespace Common;


class CSVManager{

   private $delimeter;

   private $coding;

   public function __construct($delimeter = ';', $coding = 'cp1251'){
       $this->delimeter = $delimeter;
       $this->coding = $coding;
   }

   private function fromMS($val){
       if($this->coding == 'utf8'){
           return $val;
       }
       return iconv($this->coding, 'utf8', $val);
   }

   private function toMS($val){
       if($this->coding == 'utf8'){
           return $val;
       }
       $val = str_replace('amp;', '&', $val);                        //   Множественные html сущности некорректно декодятся
       $val = html_entity_decode(iconv('utf8', 'cp1251', $val));
       return $val;
   }

   private function fromMSarray($array){
       if(!is_array($array)){
           return $this->fromMS($array);
       }
       foreach($array as $key => $value){
           if(is_array($value)){
               $array[$key] = $this->fromMSarray($value);
           }
           else{
               $array[$key] = $this->fromMS($value);
           }
       }
       return $array;
   }

   private function toMSarray($array){
       if(!is_array($array)){
           return $this->toMS($array);
       }
       foreach($array as $key => $value){
           if(is_array($value)){
               $array[$key] = $this->toMSarray($value);
           }
           else{
               $array[$key] = $this->toMS($value);
           }
       }
       return $array;
   }

   public static function CSVtoArray($path, $rel = false, $delimeter = ';', $coding = 'cp1251'){
       $el = new self($delimeter, $coding);
       if(!$rel){
           $path = $_SERVER['DOCUMENT_ROOT'].$path;
       }
       $arResult = array();
       if (($handle = fopen($path, "r")) !== FALSE) {
           while(($data = fgetcsv($handle, 1000, $delimeter)) !== FALSE){
               $arResult[] = $el->fromMSarray($data);
           }
           fclose($handle);
       }else{
           $arResult = ['result' => 'fail'];
       }
       return $arResult;
   }

   public static function CSVtoGenerator($path, $rel = false, $delimeter = ';', $coding = 'cp1251', $len = 1000){
       $el = new self($delimeter, $coding);
       if(!$rel){
           $path = $_SERVER['DOCUMENT_ROOT'].$path;
       }
       //$arResult = array();
       if (($handle = fopen($path, "r")) !== FALSE) {
           try {
               while(($data = fgetcsv($handle, $len, $delimeter)) !== FALSE){
                   yield $el->fromMSarray($data);
               }
           } finally {
               fclose($handle);
           }
       }else{
           yield false;
       }
   }

   public static function MakeCSV($data, $filename, $rel = false, $delimeter = ';', $coding = 'cp1251'){
       $el = new self($delimeter, $coding);
       if(!is_array($data)){
           return false;
       }
       if(!$rel){
           $filename = $_SERVER['DOCUMENT_ROOT'].$filename;
       }
       $data = $el->toMSarray($data);
       $fp = fopen($filename, 'w');
       foreach($data as $fields){
           fputcsv($fp, $fields, $delimeter, '"');
       }
       fclose($fp);
       return $filename;
   }

}
