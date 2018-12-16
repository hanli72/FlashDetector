<?php

/*
 * iTXTech FlashDetector
 *
 * Copyright (C) 2018 iTX Technologies
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace iTXTech\FlashDetector;

use iTXTech\FlashDetector\Decoder\Decoder;
use iTXTech\FlashDetector\Decoder\Intel;
use iTXTech\FlashDetector\Decoder\Micron;
use iTXTech\FlashDetector\Decoder\Samsung;
use iTXTech\FlashDetector\Decoder\SanDisk;
use iTXTech\FlashDetector\Decoder\SKHynix;
use iTXTech\FlashDetector\Decoder\SpecTek;
use iTXTech\FlashDetector\Decoder\Toshiba;

abstract class FlashDetector{
	/** @var Decoder[] */
	private static $decoders = [];
	private static $fdb = [];

	public static function init(){
		self::registerDecoder(Micron::class);
		self::registerDecoder(SKHynix::class);
		self::registerDecoder(Toshiba::class);
		self::registerDecoder(Samsung::class);
		self::registerDecoder(Intel::class);
		self::registerDecoder(SpecTek::class);
		self::registerDecoder(SanDisk::class);
		if(Loader::getInstance() !== null){
			$fdb = Loader::getInstance()->getResourceAsText("fdb.json");
			self::$fdb = json_decode($fdb, true);
		}
	}

	public static function registerDecoder(string $decoder) : bool {
		if(is_a($decoder, Decoder::class, true)){
			/** @var $decoder Decoder */
			self::$decoders[$decoder::getName()] = $decoder;
			return true;
		}
		return false;
	}

	public static function detect(string $partNumber, bool $combineFdb = false) : FlashInfo{
		$partNumber = strtoupper($partNumber);
		foreach(self::$decoders as $decoder){
			if($decoder::check($partNumber)){
				$info = $decoder::decode($partNumber);
				if($combineFdb and ($data = self::getFlashInfoFromFdb($info)) !== null){
					$info->setFlashId($data["id"]);
					if($data["l"] !== ""){
						$info->setLithography($data["l"]);
					}
				}
				return $info;
			}
		}
		return (new FlashInfo($partNumber))->setManufacturer("Unknown");
	}

	public static function getFlashInfoFromFdb(FlashInfo $info) : ?array {
		$m = strtolower($info->getManufacturer());
		if($m === "skhynix"){
			$m = "hynix";
		}
		return self::$fdb[$m][$info->getPartNumber()] ?? null;
	}
}