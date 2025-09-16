<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use \Bitrix\Main\Loader;
use \Bitrix\Main\Context;
use \Bitrix\Iblock\SectionTable;
use \Bitrix\Catalog\ProductTable;
use \Bitrix\Catalog\PriceTable;
use \Bitrix\Main\Entity\ReferenceField;

Loader::includeModule('iblock');

$iblockID = 26;

function getListAllSections(int $iblockID)
{
    $rsSections = SectionTable::getList(
        [
            'select' => ['ID', 'NAME'],
            'filter' => ['IBLOCK_ID' => $iblockID, 'DEPTH_LEVEL' => 1, 'ACTIVE' => 'Y'],
        ]
    )->fetchAll();

    $rsData = array_reduce(
        $rsSections,
        function ($acc, $arSection) {
            $acc[$arSection['ID']] = $arSection;
            return $acc;
        },
        []
    );

    return $rsData;
}

function getRequestFilter(array $arAllSections)
{
    $filter = [];
    $request = Context::getCurrent()->getRequest();
    $arRequest = $request->getPostList()->toArray();
    $arSections = json_decode($arRequest['SECTIONS']);

    if (!empty($arRequest['NAME'])) {
        $filter['NAME'] = $arRequest['NAME'];
    }
    if (is_array($arSections) && !empty($arSections[0])) {
        $filter['SECTIONS'] = array_filter(
            $arSections,
            function ($idSection) use ($arAllSections) {
                return array_key_exists($idSection, $arAllSections);
            }
        );
    }

    return $filter;
}

function getListItems(int $iblockID, array $filter)
{
    $query = ProductTable::getList(
        [
            'select' => [
                'ID',
                'QUANTITY',
                'AVAILABLE',
                'NAME' => 'IBLOCK_ELEMENT.NAME',
                'PRICE_BASE' => 'PRICE_DATA.PRICE',

                'SECTION_ID' => 'SECTION_DATA.ID',
                'SECTION_NAME' => 'SECTION_DATA.NAME',
                'SECTION_DEPTH_LEVEL' => 'SECTION_DATA.DEPTH_LEVEL',

                'PARENT_SECTION_ID' => 'PARENT_SECTION_DATA.ID',
                'PARENT_SECTION_NAME' => 'PARENT_SECTION_DATA.NAME',
                'PARENT_SECTION_DEPTH_LEVEL' => 'PARENT_SECTION_DATA.DEPTH_LEVEL',

                'PARENT_FIRST_SECTION_ID' => 'PARENT_FIRST_SECTION_DATA.ID',
                'PARENT_FIRST_SECTION_NAME' => 'PARENT_FIRST_SECTION_DATA.NAME',
                'PARENT_FIRST_SECTION_DEPTH_LEVEL' => 'PARENT_FIRST_SECTION_DATA.DEPTH_LEVEL',
            ],
            'filter' =>
                [
                    '=IBLOCK_ELEMENT.IBLOCK_ID' => $iblockID,
                    '%IBLOCK_ELEMENT.NAME' => $filter['NAME'],
                    'IBLOCK_ELEMENT.ACTIVE' => 'Y',
                    [
                        'LOGIC' => 'OR',
                        ['PARENT_FIRST_SECTION_ID' => $filter['SECTIONS']],
                        ['PARENT_SECTION_ID' => $filter['SECTIONS']],
                        ['SECTION_ID' => $filter['SECTIONS']],
                    ]
                ],
            //'limit'     => 5000,
            'order' => ['SECTION_ID' => 'ASC', 'PARENT_SECTION_ID' => 'ASC', 'PARENT_FIRST_SECTION_ID' => 'ASC'],
            'runtime' => [
                new ReferenceField(
                    'PRICE_DATA',
                    PriceTable::class,
                    ['=this.ID' => 'ref.PRODUCT_ID']
                ),
                // Раздел
                new ReferenceField(
                    'SECTION_DATA',
                    SectionTable::class,
                    ['=this.IBLOCK_ELEMENT.IBLOCK_SECTION_ID' => 'ref.ID']
                ),
                // Родительский раздел
                new ReferenceField(
                    'PARENT_SECTION_DATA',
                    SectionTable::class,
                    ['=this.SECTION_DATA.IBLOCK_SECTION_ID' => 'ref.ID']
                ),
                // Родитель родителя, если имеется
                new ReferenceField(
                    'PARENT_FIRST_SECTION_DATA',
                    SectionTable::class,
                    ['=this.PARENT_SECTION_DATA.IBLOCK_SECTION_ID' => 'ref.ID']
                )
            ],
            'cache' => [
                'ttl' => 3600,
                'cache_joins' => true
            ],
        ]
    );

    $rsItems = $query->fetchAll();

    $rsData = array_reduce(
        $rsItems,
        function ($acc, $arProduct) {
            $arMeasure = ProductTable::getCurrentRatioWithMeasure($arProduct['ID']);
            $arProduct['MEASURE_SYMBOL_RUS'] = $arMeasure[$arProduct['ID']]['MEASURE']['SYMBOL_RUS']; // ед. измерения остатков

            $idSection = $arProduct['PARENT_FIRST_SECTION_ID'] ?? $arProduct['PARENT_SECTION_ID'] ?? $arProduct['SECTION_ID']; // id корневого раздела
            $acc[$idSection][] = $arProduct;
            return $acc;
        },
        []
    );

    return $rsData;
}


function genFileCSV(array $items, array $allSections)
{
    if (count($items) == 0) {
        return null;
    }
    ob_start();
    $df = fopen("php://output", 'w');
    fputs($df, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

    fputcsv($df, ['Название товара', 'Цена, с ндс', 'Остаток'], ';');

    foreach ($items as $sectionId => $arSectionItems) {
        fputcsv($df, [$allSections[$sectionId]['NAME'], '', ''], ';');
        foreach ($arSectionItems as $arItem) {
            $row[0] = $arItem['NAME'];
            $row[1] = ((int)$arItem['PRICE_BASE'] > 0) ? "{$arItem['PRICE_BASE']} ₽/{$arItem['MEASURE_SYMBOL_RUS']}" : 'по запросу';
            $row[2] = ((int)$arItem['QUANTITY'] > 0) ? "{$arItem['QUANTITY']} {$arItem['MEASURE_SYMBOL_RUS']}" : 'под заказ';
            fputcsv($df, $row, ';');
        }
    }
    fclose($df);
    return ob_get_clean();
}

function downloadFile($filename)
{
    // disable caching
    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 20017 12:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");
    // force download
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    //header("Content-type: text/csv; charset=windows-1251");
    // disposition / encoding on response body
    header("Content-Disposition: attachment; filename={$filename}");
    header("Content-Transfer-Encoding: binary");
}

downloadFile("b2b_prices_" . date("Y-m-d#H_i_s") . ".csv");

$arAllSections = getListAllSections($iblockID);
$requestFilter = getRequestFilter($arAllSections);

$filter['NAME'] = $requestFilter['NAME'] ?? [];
$filter['SECTIONS'] = $requestFilter['SECTIONS'] ?? array_keys($arAllSections);

$items = getListItems($iblockID, $filter);

echo genFileCSV($items, $arAllSections);
die();

//$request = Context::getCurrent()->getRequest();
//$arRequest = $request->getPostList()->toArray();
//print_r(json_decode($arRequest));