<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use \Bitrix\Main\Loader;
use \Bitrix\Main\Context;
use \Bitrix\Iblock\SectionTable;
use \Bitrix\Iblock\ElementTable;
use \Bitrix\Catalog\ProductTable;
use \Bitrix\Catalog\PriceTable;
use \Bitrix\Main\Entity\ReferenceField;
use \Bitrix\Main\UI\PageNavigation;

Loader::includeModule('iblock');

class B2BPrices extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams['IBLOCK_ID'] = (int)($arParams['IBLOCK_ID'] ?? 26);
        $arParams['PAGE_SIZE'] = (int)($arParams['PAGE_SIZE'] ?? 50);
        return $arParams;
    }

    /**
     * Получить все разделы 1-го уровня
     * @param int $iblockID
     * @return mixed|null
     */
    private function getListAllSections(int $iblockID)
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

    private function getRequestFilter(array $arAllSections)
    {
        $filter = [];
        $request = Context::getCurrent()->getRequest();
        $arRequest = $request->getQueryList()->toArray();

        if (!empty($arRequest['NAME'])) {
            $filter['NAME'] = $arRequest['NAME'];
        }
        if (is_array($arRequest['SECTIONS']) && !empty($arRequest['SECTIONS'][0])) {
            $filter['SECTIONS'] = array_filter(
                $arRequest['SECTIONS'],
                function ($idSection) use ($arAllSections) {
                    return array_key_exists($idSection, $arAllSections);
                }
            );
        }

        return $filter;
    }

    /**
     * Получить товары по выбранным разделам 1-го уровня, запрос по всем возможным вложенным уровням товара: от 3-го до 1-го
     */
    private function getListItems(int $iblockID, array $filter, PageNavigation $nav)
    {
        $query = ProductTable::getList(
            [
                'select' => [
                    'ID', 'QUANTITY', 'AVAILABLE',
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
                'offset' => $nav->getOffset(),
                'limit' => $nav->getLimit(),
                'order' => ['SECTION_ID' => 'ASC', 'PARENT_SECTION_ID' => 'ASC', 'PARENT_FIRST_SECTION_ID' => 'ASC'],
                'count_total' => 1,
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

        $totalCount = $query->getCount();
        $nav->setRecordCount($totalCount);
        $this->arResult['NAV'] = $nav;

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

    /**
     * Собираем компонент
     */
    public function executeComponent()
    {
        $nav = new PageNavigation('nav');
        $nav->setPageSize($this->arParams['PAGE_SIZE'])->initFromUri();

        $arAllSections = $this->getListAllSections($this->arParams['IBLOCK_ID']);

        $this->arResult['ALL_SECTIONS'] = $arAllSections;

        $requestFilter = $this->getRequestFilter($arAllSections);

        $filter['NAME'] = $requestFilter['NAME'] ?? [];
        $filter['SECTIONS'] = $requestFilter['SECTIONS'] ?? array_keys($arAllSections);

        $this->arResult['ITEMS'] = $this->getListItems(
            $this->arParams['IBLOCK_ID'],
            $filter,
            $nav
        );

        // Для шаблона
        $this->arResult['REQUEST_FILTER']['NAME'] = $requestFilter['NAME'] ?? '';
        $this->arResult['REQUEST_FILTER']['SECTIONS'] = $requestFilter['SECTIONS'] ?? [];

        $this->includeComponentTemplate();
    }

}