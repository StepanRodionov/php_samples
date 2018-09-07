<?php

namespace REB\Grain;

//  GLOBAL TODO - вынести все настройки и списочные данные в GrainSettings!!!

class GrainCalculator
{
   /**
    * Список констант для параметризации
    * Константами являются весьма условно, т.к могут меняться
    * Для получения использовать только методы - тк в дальнейшем все эти значения будут изменяться или переедут в базу
    */
   const STOCK_COMMISSION_IN_PERCENT = 0.0007;

   const CLEARING_COMMISSION_IN_PERCENT = 0.0007;

   const OUR_COMMISSION_IN_PERCENT = 0.005;

   const MAX_SWAP_TIME = 90;

   const ACCOUNT_PERIOD_LENGTH = 365;

   const STOCK_COEFF = 0.35;

   const PRICE_SWAP_IN_PERCENT = 11.75;

   const SWAP_PRICE_PERCENT = 10;

   const RISK_RATE_FIRST_PERCENT = 25;

   const RISK_RATE_SECOND_PERCENT = 25;

   const SWAPX = 1.1;

   const DURATION_FACTOR = 0.1;

   private $assetGrain;

   private $price;

   private $volume;

   private $time;

   private $basis;

   private $product_code;

   private $testData = array();

   //  Возвращаемые значения добываются не только в конце, но и в середине кое-где
   //  Поэтому мы их сразу сохраняем в $returnValues
   //  Заодно дополнительно проверяем при выдаче, все ли посчиталось
   private $returnValues = array(
       'RESULT' => false,
       'STORE_COEFF' => false,
       'COMMISSION_FOR_ACCOUNTING' => false,
       'COMMISSION_OF_STOCK_AND_CLEARING' => false,
       'COMMISSION_OF_BROKER_FOR_DEAL' => false,
       'SWAP_DIFF_WITH_STORING' => false
   );

   /**
    * Массив описывает лимит концентрации - ограничения на объем вводимые биржей
    * http://www.nkcbank.ru/viewCatalog.do?menuKey=459 - вот тут можно посмотреть
    * По словам бизнеса меняется редко или никогда
    *
    * WH - пшеница
    * BR - ячмень
    * CR - кукуруза
    * SB - соя
    * SG - сахар
    */
   private static $limits = array(
       'WH' => array(
           array(
               'limit' => 2000,
               'rate' => 0.25
           ),
           array(
               'limit' => 5500,
               'rate' => 0.4
           )
       ),
       'BR' => array(
           array(
               'limit' => 1000,
               'rate' => 0.4
           ),
           array(
               'limit' => 3500,
               'rate' => 0.7
           )
       ),
       'CR' => array(
           array(
               'limit' => 1000,
               'rate' => 0.25
           ),
           array(
               'limit' => 3500,
               'rate' => 0.7
           )
       ),
       'SB' => array(
           array(
               'limit' => 500,
               'rate' => 0.5
           ),
           array(
               'limit' => 1500,
               'rate' => 0.6
           )
       ),
       'SG' => array(
           //  No data
       )
   );

   /**
    * Тариф за учет: тариф учета товара на базисе, выраженный коэффициентом. Размещается отдельно на сайте клирингового центра
    */
   private static $tariffsForAccounting = array(
       'WH' => 0.29,
       'BR' => 0.21,
       'CR' => 0.21,
       'SG' => 1.13,
       'SB' => 0.77
   );

   /**
    *     Тариф за хранение: тариф базиса на котором хранится товар, выраженный в рублях на тонну.
    *  Размещается отдельно на сайте клирингового центра. Условное обозначение: С_д^х
    */
   private static $tariffsForStoring = array(
       "RAME" => 3.5,
       "VOLG" => 3.05,
       "DIVN" => 1.87,
       "BERD" => 2.59,
       "NVPL" => 2.31,
       "BLAG" => 1.88,
       "ALHB" => 2.99,
       "KPLI" => 4.2,
       "DAVL" => 3.1,
       "NAZV" => 2.86,
       "KAST" => 3.89,
       "SBTK" => 2.45,
       "PVHB" => 2.99,
       "SURO" => 2.45,
       "OMSK" => 2.42,
       "BUTR" => 3.15,
       "GLOB" => 2.59,
       "PALL" => 3.51,
       "BIOT" => 5.25,
       "NVSP" => 3.72,
       "KONK" => 2.84,
       "KLSH" => 3.68,
       "NKHP" => 3.36,
       "PRHB" => 2.45,
       "PGCH" => 3.66,
       "MELE" => 3.85,
       "GULK" => 2.84,
       "KAMN" => 3.72,
       "BUDN" => 3.15,
       "MILE" => 2.42,
       "TIMA" => 2.84,
       "ISTR" => 2.42,
       "ZARA" => 3.6,
       "SERG" => 3.68,
       "STAR" => 2.86,
       "CCGR" => 3.25,
       "BUZD" => 3.72,
       "ACHN" => 2.28,
       "KOVH" => 3.52,
       "RYAZ" => 4.1,
       "RAMN" => 2.45,
       "ORLV" => 3.83,
       "LGOV" => 3.65,
       "POSV" => 3.1,
       "UJUR" => 2.97,
       "BURE" => 1.75,
       "SHIG" => 3.94,
       "ROSH" => 3.72,
       "RYLS" => 3.94,
       "ALNS" => 3.15,
       "CHER" => 2.84,
       "USPN" => 1.24,
       "LEBD" => 1.05,
       "TBLS" => 1.26,
       "LNGR" => 1.05,
       "BLSH" => 1.05,
       "EKSZ" => 2.51,
       "TIHR" => 1.05,
       "TAMB" => 2.42,
       "KANV" => 2.1,
       "KRVC" => 5.57,
       "ZEMT" => 1.51
   );

   /**
    *     Комиссия за вывод со счета: комиссия за списание клиентом товара с его товарного счета.
    *  Размещается отдельно на сайте клирингового центра. Условное обозначение: С_т^с
    *
    *  Если число в массиве - значит считается за тонну
    */
   private static $commissionForWithdrawal = array(
       "RAME" => 525,
       "VOLG" => array(6.3),
       "BERD" => 525,
       "DIVN" => array(6.04),
       "NVPL" => array(5.25),
       "BLAG" => array(6.05),
       "ALHB" => 2100,
       "KPLI" => 1050,
       "DAVL" => 0,
       "NAZV" => 0,
       "KAST" => 0,
       "SBTK" => 0,
       "PVHB" => array(6.04),
       "SURO" => 0,
       "OMSK" => 0,
       "BUTR" => 0,
       "GLOB" => 1050,
       "PALL" => 1050,
       "BIOT" => 3675,
       "NVSP" => 371.7,
       "KONK" => 0,
       "KLSH" => 1260,
       "NKHP" => 0,
       "PRHB" => 1050,
       "PGCH" => 0,
       "MELE" => array(1.24),
       "GULK" => array(5.25),
       "KAMN" => 0,
       "BUDN" => 1050,
       "MILE" => 0,
       "TIMA" => 0,
       "ISTR" => 105,
       "ZARA" => 0,
       "SERG" => 315,
       "STAR" => array(7.43),
       "CCGR" => 6195,
       "BUZD" => 3717,
       "ACHN" => 1260,
       "KOVH" => 525,
       "RYAZ" => 0,
       "RAMN" => 5250,
       "ORLV" => 525,
       "LGOV" => 0,
       "POSV" => array(6.19),
       "UJUR" => 0,
       "BURE" => 0,
       "SHIG" => 0,
       "ROSH" => 123.9,
       "RYLS" => 0,
       "ALNS" => 5250,
       "CHER" => array(27.79),
       "USPN" => array(15.29),
       "LEBD" => array(15.29),
       "TBLS" => array(16.76),
       "LNGR" => array(15.29),
       "BLSH" => array(15.29),
       "EKSZ" => array(22.86),
       "TIHR" => array(15.29),
       "TAMB" => array(24.85),
       "KANV" => array(22.64),
       "KRVC" => array(46.9),
       "ZEMT" => array(16.91)
   );


   //
   private static $increasingRiskInterestRatioStock = array(
       10 => 3,
       5 => 2.5,
       0 => 2
   );

   //  Свойство выше - коэффициент, свойство ниже - процент. Внезапно, да?
   private static $increasingRiskInterestRatioBrokerInPercent = array(
       10 => 2,
       5 => 1.5,
       0 => 1
   );

   //  Строго говоря все это уже написано в error message
   //  И вообще кидать просто Exception не комильфо
   //  Но целостная система обработки ошибок в данном случае подождет лучших времен
   private static $errorCodes = array(
       11001 => 'Передано невалидное время свопа',
       11002 => 'Нет информации по лимитам на продукт',
       11003 => 'Объем выходит за границы лимита',
       11004 => 'Нет тарифа за учет по продукту',
   );

   private static $timeFrames = array(
       1,
       7,
       14,
       30,
       60,
       90
   );

   public function __construct(Grain $grain, $volume, $time)
   {
       $time = intval($time);
       $volume = intval($volume);
       if($time > self::GetMaxSwapTime() || $time <= 0){
           Throw new \Exception('Bad swap time', 11001);
       }
       $assParams = $grain->getAssetArray();
       //debmes_fa([$assParams, $grain]);
       $this->assetGrain = $assParams['asset'];
       $this->product_code = $assParams['product_code'];
       $this->basis = $assParams['basis'];
       $this->price = $grain->getPrice();
       $this->volume = $volume;
       $this->time = $time;
   }

   public static function GetStockCommission(){
       return self::STOCK_COMMISSION_IN_PERCENT / 100;
   }

   public static function GetClearingCommission(){
       return self::CLEARING_COMMISSION_IN_PERCENT / 100;
   }

   public static function GetOurCommission(){
       return self::OUR_COMMISSION_IN_PERCENT / 100;
   }

   public static function GetMaxSwapTime(){
       return self::MAX_SWAP_TIME;
   }

   public static function GetStockCoeff(){
       return self::STOCK_COEFF;
   }

   public static function GetSwapPrice(){
       return self::PRICE_SWAP_IN_PERCENT / 100;
   }

   public static function GetAccountPeriodLength(){
       //  А вдруг високосный год
       return self::ACCOUNT_PERIOD_LENGTH;
   }

   public static function GetSwapPricePercent(){
       return self::SWAP_PRICE_PERCENT / 100;
   }

   public static function GetRiskRatePercent($first = true){
       if($first){
           return self::RISK_RATE_FIRST_PERCENT / 100;
       }
       return self::RISK_RATE_SECOND_PERCENT / 100;
   }

   public static function GetSwapX(){
       return self::SWAPX;
   }

   public static function GetDurationFactor(){
       return self::DURATION_FACTOR;
   }

   private static function GetIncreasingInterestRatioStock($days){
       foreach(self::$increasingRiskInterestRatioStock as $k => $v){
           if($days > $k){
               return $v;
           }
       }
       return 1;
   }

   private static function GetIncreasingInterestRatioBroker($days){
       foreach(self::$increasingRiskInterestRatioBrokerInPercent as $k => $v){
           if($days > $k){
               return $v / 100;
           }
       }
       return 1;
   }

   private function getTimeFrameBorder($first = true){
       $days = $this->time;
       $arDays = self::$timeFrames;
       rsort($arDays);
       foreach($arDays as $k => $v){
           if($days > $v){
               return $first ? $v : $arDays[$k - 1];
           }
       }
   }

   public function getTestsData(){
       return $this->testData;
   }

   //  Пункт 17 в файле - там список
   private function getStoringCoeff(){
       $val = self::$tariffsForStoring[$this->basis];
       return $val;
   }

   //  Уже не помню какой :) Комиссия за вывод средств тут
   private function getWithdravalCommission(){
       $val = self::$commissionForWithdrawal[$this->basis];
       if(is_array($val)){
           $val = $val[0] * $this->volume;
       }
       return $val;
   }

   private function getAccountingTariff(){
       $asset = $this->assetGrain;
       $a = self::$tariffsForAccounting[$asset];
       if(!$a){
           throw new \Exception("No tariff for asset {$asset}", 11004);
       }
       return floatval($a);
   }

   //  Вся работа калькулятора для внешнего пользователя сводится к вызову ЭТОГО метода!
   public function processCalculator(){
       $limit = $this->getUnitedLimit();
       $volume = $this->getDealVolume($limit);
       $swp_wdur = $this->getSwapPriceWithDuration();
       $swapPrice = $this->getSwapPriceWithDurationAndStoring();
       $volAfterDiscount = $this->getVolumeAfterDiscount($volume, $swapPrice);
       $kzs = $this->getSwapListingInApplication($volAfterDiscount);
       $listing_vol = $this->getListingVolume($kzs);
       $rub_vol = $this->getListingVolumeInRub($listing_vol);
       $store_comm = $this->getCommissionForStoring($listing_vol);
       $stock_clearing_commission = $this->getStockAndClearingCommAll($listing_vol);
       $acc_commission = $this->getAccountingCommission($listing_vol);
       $br_commission = $this->getBrokerCommission($listing_vol);
       $sw_diff_str = $this->getSwapDiffWithStoring($swapPrice, $listing_vol);
       $vl = $this->getSwapDiffWoStoring($swp_wdur, $kzs);
       $first_risk_border = $this->getFirstRiskBorder();
       $second_risk_border = $this->getSecondRiskBorder();
       $upp_risk_border = $this->getUpperRiskBorderOnDeal($first_risk_border, $second_risk_border);
       $calculated_price = $this->getCalculatedPrice();
       $upper_border_rel = $this->getUpperBorderWithRelation($upp_risk_border, $calculated_price);
       $contract_duration_terms = $this->getContractDurationTerms();
       $diff = $this->getDiffBetweenValues($upp_risk_border, $contract_duration_terms);
       $corr_with_prc = $this->getCorrWithPercentRisk($contract_duration_terms);

       $finalResult = $this->oneMethodToCalcThemAll($rub_vol, $stock_clearing_commission, $br_commission, $corr_with_prc);
       //  we don't need $finalResult really because it already in $this->returnValues

       $returnValue = $this->getResult();
       return $returnValue;
   }

   //  Пункт 6 в файле
   private function getUnitedLimit(){
       $asset = $this->assetGrain;
       //debmes_fa($asset); die();
       $assetLimits = self::$limits[$asset];
       if(!$assetLimits){
           throw new \Exception('Нет актуальных данных по продукту на бирже', 11002);
       }
       $volume = $this->volume;
       $price = $this->price;
       $limit_1 = $assetLimits[0];
       $limit_2 = $assetLimits[1];

       if($volume <= $limit_1['limit']){
           $lim = $this->getSingleLimit($volume, $price, $limit_1['rate']);

       }elseif($volume > $limit_1['limit'] && $volume <= $limit_2['limit']){
           $lim = $this->getSingleLimit($limit_1['limit'], $price, $limit_1['rate']) +
               $this->getSingleLimit(($volume - $limit_1['limit']), $price, $limit_2['rate']);
       }else{
           throw new \Exception('Слишком большой объем', 11003);
       }
       $this->testData['un_limit'] = $lim;
       return $lim;
   }

   private function getSingleLimit($volume, $price, $rate){
       $rate = (1 - $rate);
       $lim = $volume * $price * $rate;
       return $lim;
   }

   //  Пункт 7 в файле
   private function getDealVolume($limit){
       $price = $this->price;
       $dealVolume = $limit / $price;
       $this->testData['deal_volume'] = $dealVolume;
       return $dealVolume;
   }

   //  Пункт 7 в промежуточных расчетах
   private function getDiscount1(){
       $tm = $this->time;
       $Rkl = ((self::GetStockCoeff() / self::GetAccountPeriodLength()) * $tm) * self::GetIncreasingInterestRatioStock($tm);
       $this->testData['Rkl'] = $Rkl;
       return $Rkl;
   }

   //  Пункт 8 в промежуточных расчетах (формулы)
   private function getSwapPriceWithDuration(){
       $swapPrice = self::GetSwapPrice();
       $value = ($swapPrice / self::GetAccountPeriodLength()) * $this->time;
       $this->testData['swap_price_with_duration'] = $value;
       return $value;
   }

   //  Пункт 9 в промежуточных расчетах (формулы)
   private function getSwapPriceWithDurationAndStoring(){
       $store_coeff = $this->getStoringCoeff();
       $acc_tariff = $this->getAccountingTariff();
       $pr = $this->price; $p_len = self::GetAccountPeriodLength();
       $value = ($store_coeff * $p_len / $pr) + ($acc_tariff * $p_len / $pr) + self::GetSwapPrice();
       $this->testData['swap_price_with_duration_and_storing'] = $value;
       return $value;
   }

   //  Пункт 8 в файле
   private function getVolumeAfterDiscount($volume, $swapPrice){
       $tm = $this->time;
       $disc = $this->getDiscount1();
       $value = $volume * (1 - $disc - self::GetIncreasingInterestRatioBroker($tm) - $swapPrice * $tm / self::GetAccountPeriodLength());
       $this->testData['volume_after_discount'] = $value;
       return $value;
   }

   //  Пункт 9 в файле + 11 в промежуточных расчетах (форумы)
   private function getSwapListingInApplication($volumeAfterDiscount){
       $vv = $volumeAfterDiscount * $this->price;
       $value = $this->floorAsExcel($vv, -3);
       $this->testData['swap_in_application'] = $value;
       return $value;
   }

   //  Пункт 12 в промежуточных расчетах (форумы)
   private function getListingVolume($kzs){
       $vv = $kzs / $this->price;
       $value = $this->floorAsExcel($vv, 2);
       $this->testData['listing_volume'] = $value;
       return $value;
   }

   //  Пункт 13 в промежуточных расчетах (форумы)
   private function getListingVolumeInRub($vol){
       $price = $this->price;
       $value = $vol * $price;
       $this->testData['listing_volume_rub'] = $value;
       return $value;
   }

   //  Пункт 14 в промежуточных расчетах (форумы)
   private function getCommissionForStoring($vol){
       $storing_coeff = $this->getStoringCoeff();
       $value = $storing_coeff * $vol * $this->time;
       $this->testData['commission_for_storing'] = $value;
       $this->returnValues['STORE_COEFF'] = $value;
       return $value;
   }

   //  Пункт 22 в файле + 16 в промежуточных расчетах (форумы)
   private function getStockAndClearingCommDay($listing_vol){
       $prc = $this->price;
       $stock_comm = $prc * $listing_vol * self::GetStockCommission();
       $clearing_comm = $prc * $listing_vol * self::GetClearingCommission();
       $value = $stock_comm + $clearing_comm;
       $this->testData['daily_st_cl_comm'] = $value;
       return $value;
   }

   //  Пункт 17 в промежуточных расчетах (формулы)
   private function getStockAndClearingCommAll($listing_vol){
       $comm = $this->getStockAndClearingCommDay($listing_vol);
       $value = $comm * $this->time;
       $this->testData['all_st_cl_comm'] = $value;
       $this->returnValues['COMMISSION_OF_STOCK_AND_CLEARING'] = $value;
       return $value;
   }

   //  Пункт 18 в промежуточных расчетах (формулы)
   private function getAccountingCommission($listing_vol){
       $accTariff = $this->getAccountingTariff();
       $value = $accTariff * $listing_vol * $this->time;
       $this->testData['acc_commission'] = $value;
       $this->returnValues['COMMISSION_FOR_ACCOUNTING'] = $value;
       return $value;
   }

   //  Пункт 25 в файле + 19 в промежуточных расчетах (формулы)
   private function getBrokerCommission($listing_vol){
       $bcomm = self::GetOurCommission();
       $value = $bcomm * $this->price * $listing_vol * $this->time;
       $this->testData['broker_commission'] = $value;
       $this->returnValues['COMMISSION_OF_BROKER_FOR_DEAL'] = $value;
       return $value;
   }

   //  Пункт 26 в файле + 20 в промежуточных расчетах (формулы)
   private function getSwapDiffWithStoring($swapPrice, $listing_vol){
       $value = $swapPrice * $this->price * $listing_vol * ($this->time / self::GetAccountPeriodLength());
       $this->testData['swap_diff_with_storing'] = $value;
       $this->returnValues['SWAP_DIFF_WITH_STORING'] = $value;
       return $value;
   }

   //  Пункт 27 в файле + 21 в промежуточных расчетах (формулы)
   private function getSwapDiffWoStoring($swp_wdur, $kzs){
       $value = $kzs * $swp_wdur * ($this->time / self::GetAccountPeriodLength());
       $this->testData['swap_diff_wo_storing'] = $value;
       return $value;
   }

   //  Пункт 35 в файле + 22 в промежуточных расчетах (формулы)
   private function getFirstRiskBorder(){
       $priceSwapPerc = self::GetSwapPricePercent();
       $percRiskRate = self::GetRiskRatePercent();
       $timeBorder = $this->getTimeFrameBorder();
       $value = ($priceSwapPerc + $percRiskRate) * $timeBorder * $this->price / self::GetAccountPeriodLength();
       $this->testData['first_risk_border'] = $value;
       return $value;
   }

   //  Пункт 35 в файле + 23 в промежуточных расчетах (формулы)
   private function getSecondRiskBorder(){
       $priceSwapPerc = self::GetSwapPricePercent();
       $percRiskRate = self::GetRiskRatePercent(false);
       $timeBorder = $this->getTimeFrameBorder(false);
       $value = ($priceSwapPerc + $percRiskRate) * $timeBorder * $this->price / self::GetAccountPeriodLength();
       $this->testData['second_risk_border'] = $value;
       return $value;
   }

   //  Пункт 36 в файле + 24 в промежуточных расчетах (формулы)
   private function getUpperRiskBorderOnDeal($firstBorder, $secondBorder){
       $tm = $this->time;
       $ftm = $this->getTimeFrameBorder();
       $stm = $this->getTimeFrameBorder(false);
       $value = $firstBorder + ($secondBorder - $firstBorder) * ($tm - $ftm) / ($stm - $ftm);
       $this->testData['upp_risk_border'] = $value;
       return $value;
   }

   //  Пункт 37 в файле + 25 в промежуточных расчетах (формулы)
   private function getCalculatedPrice(){
       $priceSwapPerc = self::GetSwapPricePercent();
       $value = $priceSwapPerc * $this->price * $this->time / self::GetAccountPeriodLength();
       $this->testData['calculated_price'] = $value;
       return $value;
   }

   //  Пункт 39 в файле + 26 в промежуточных расчетах (формулы)
   private function getUpperBorderWithRelation($upperRisk, $calculatedPrice){
       $value = ($upperRisk - $calculatedPrice) / self::GetSwapX();
       $this->testData['upper_border_rel'] = $value;
       return $value;
   }

   //  Пункт 40 в файле + 27 в промежуточных расчетах (формулы)
   private function getContractDurationTerms(){
       $value = self::GetDurationFactor() * $this->price * $this->time / self::GetAccountPeriodLength();
       $this->testData['cont_duration_terms'] = $value;
       return $value;
   }

   //  Пункт 41 в файле + 28 в промежуточных расчетах (формулы)
   private function getDiffBetweenValues($upp_bord, $dur_terms){
       $value = $upp_bord - $dur_terms;
       $this->testData['diff_btw_vals'] = $value;
       return $value;
   }

   //  Пункт 42 в файле + 29 в промежуточных расчетах (формулы)
   private function getCorrWithPercentRisk($term_d){
       $Rkl = $this->getDiscount1();
       $value = $this->price * $term_d * $Rkl;
       $this->testData['corr_with_prc'] = $value;
       return $value;
   }

   //  Финальный результат!
   private function oneMethodToCalcThemAll(
       $listing_vol,
       $stock_clearing_commission,
       $broker_commission,
       $corr_with_percent_risk
   ){
       $value = $listing_vol - $stock_clearing_commission - $broker_commission - $corr_with_percent_risk;
       $this->testData['result'] = $value;
       $this->returnValues['RESULT'] = $value;
       return $value;
   }

   private function getResult(){
       $res = $this->returnValues;
       //  проверяем, все ли посчиталось
       foreach($res as $k => $v){
           if($v === false){
               return false;
           }
       }
       return $res;
   }

   private function floorAsExcel($val, $prec){
       $divider = pow(10, abs($prec));
       if($prec < 0){
           return floor($val / $divider) * $divider;
       }
       return floor($val * $divider) / $divider;
   }

}

