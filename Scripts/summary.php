<?php

/*
 * iTXTech FlashDetector
 *
 * Copyright (C) 2018-2020 iTX Technologies
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

//Originally customized for Fangli Technology Ltd.

require_once "env.php";

use iTXTech\FlashDetector\Constants;
use iTXTech\FlashDetector\FlashDetector;
use iTXTech\FlashDetector\Property\Classification;

$fdb = FlashDetector::getFdb();

$csv = "Part Number,Vendor,Density,Cell Level,Process Node,Package,CE Pins,Flash IDs" . PHP_EOL;

foreach ($fdb->getVendors() as $vendor) {
    foreach ($vendor->getPartNumbers() as $pn) {
        $i = FlashDetector::detect($pn->getPartNumber(), true);
        $ids = $i->getFlashId();
        if ($ids != null and $i->getDensity() > 0) {
            $cell = $i->getCellLevel();
            $ce = ($i->getClassification() ?? new Classification())->getCe();
            $ce = ($ce < 0) ? "Unknown" : $ce;
            if (($i->getExtraInfo()[Constants::ENTERPRISE] ?? false) === true) {
                $cell = "e" . $cell;
            }
            $csv .= $pn->getPartNumber() . "," . strtoupper($i->getVendor()) . "," .
                FlashDetector::getHumanReadableDensity($i->getDensity(), true) . "," .
                $cell . "," . $i->getProcessNode() . "," . str_replace(",", " ", $i->getPackage()) .
                "," . $ce . "," . implode(" ", $ids) . PHP_EOL;
        }
    }
}

file_put_contents("summary.csv", $csv);

$csv = "Micron FBGA Code,Part Number,Density,Cell Level,Process Node,Package,CE Pins,Flash IDs" . PHP_EOL;

$mdb = FlashDetector::getMdb();

foreach ($mdb as $vendor) {
    foreach ($vendor as $code => $pns) {
        if (!is_array($pns)) {
            $pns = [$pns];
        }
        $info = FlashDetector::detect($pns[0]);
        $ce = ($info->getClassification() ?? new Classification())->getCe();
        $ids = $info->getFlashId() ?? [];
        $d = $info->getDensity() ?? 0;
        $cell = $info->getCellLevel();
        if (($info->getExtraInfo()[Constants::ENTERPRISE] ?? false) === true) {
            $cell = "e" . $cell;
        }
        $csv .= $code . "," . implode("  ", $pns) . "," .
            FlashDetector::getHumanReadableDensity($d, true) . "," . $cell .
            "," . $info->getProcessNode() . "," . str_replace(",", " ", $info->getPackage()) .
            "," . $ce . "," . implode("  ", $ids) . PHP_EOL;
    }
}

file_put_contents("micron_summary.csv", $csv);

$csv = "Flash IDs,Part Numbers,Controllers" . PHP_EOL;

$iddb = $fdb->getIddb();

foreach ($iddb->getFlashIds() as $flashId) {
    $pns = $flashId->getPartNumbers();
    $npn = [];
    foreach ($pns as $pn) {
        $npn[] = explode(" ", $pn)[1];
    }

    $csv .= $flashId->getFlashId() . "," . implode(" ", $npn) . "," . implode(" ", $flashId->getControllers()) . PHP_EOL;
}

file_put_contents("id_summary.csv", $csv);
