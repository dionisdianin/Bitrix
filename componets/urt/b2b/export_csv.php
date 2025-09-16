<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable as HL;
use Bitrix\Main\UserFieldTable;
use Bitrix\Main\UserFieldLangTable;
use Bitrix\Main\Context;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Engine\CurrentUser;

Loader::includeModule('highloadblock');

$request = Context::getCurrent()->getRequest();
$arrRequest = $request->getPostList()->toArray();

$hl = $arrRequest['HL_ID'];
$entity = HL::compileEntity($hl);
$entityClass = $entity->getDataClass();

// Список необходимых полей, с исключениями
$fields = array_keys($entityClass::getEntity()->getFields());
foreach ($fields as $k => $field) {
    if (in_array($field, ['ID', 'UF_GUID', 'UF_INVOICE_FILE_BILL'])) {
        unset($fields[$k]);
    }
} // Удаляем лишнее из полей

$filter = getFilter($hl, $arrRequest);

$items = $entityClass::getList(
    [
        'select' => ['*'],
        'filter' => $filter
    ]
)->fetchAll();
//}

foreach ($items as $k => $row) {
    foreach ($row as $uf => $val) {
        if (in_array($uf, ['ID', 'UF_GUID', 'UF_INVOICE_FILE_BILL'])) {
            unset($items[$k][$uf]);
        } // Удаляем лишнее из значений
        switch ($uf) {
            case 'UF_ORDER_GOODS': // Товары - { Заказы }
            case 'UF_INVOICE_GOODS': // Товары - { Счета }
                $arGoods = json_decode($val, true);
                unset($arGoods['goods']);
                $strGoods = '';

                foreach ($arGoods as $kGood => $arGood) {
                    $rowParams = '';
                    foreach ($arGood as $nameParam => $valParam) {
                        //$rowParams .= "{$nameParam}={$valParam} ";
                        switch ($nameParam) {
                            case 'PRODUCT_NAME':
                                $rowParams .= "«{$valParam}» ";
                                break;
                            case 'PRICE':
                                $rowParams .= "{$valParam}руб. ";
                                break;
                            case 'QUANTITY':
                                $rowParams .= "{$valParam}шт. ";
                                break;
                        }
                    }
                    $strGoods .= "{$rowParams};\n";
                }
                $items[$k][$uf] = $strGoods;
                break;
            case 'UF_INVOICE_ORDER_NUMBER': // Номер заказа - { Счета }
                $items[$k][$uf] = implode("\n", $val);
                break;
        }
    }
}

function getConfirmINN()
{
    $items = [];
    $userId = CurrentUser::get()->getId();
    $entity = HL::compileEntity(5);
    $entityClass = $entity->getDataClass();

    $resItems = $entityClass::getList(
        [
            'select' => ['UF_B2B_INN'],
            'filter' => ['UF_B2B_USERS' => $userId, '=UF_B2B_CONFIRM' => 1]
        ]
    );

    while ($item = $resItems->fetch()) {
        $items[] = $item['UF_B2B_INN'];
    }
    return $items;
}

function getListStatusOrder(string $status = '')
{
    $arStatus = [
        'Новый' => [
            'Ваш заказ взят в работу',
            'Ваш заказ приостановлен для уточнения',
            'Ваш заказ частично готов к отгрузке на складе'
        ],
        'В работе' => [
            'Ваш заказ отгружен частично',
            'По вашему заказу требуется уточнение условий отгрузки',
            'Дата отгрузки по вашему заказу перенесена'
        ],
        'Отгружен' => [
            'Ваш заказ готов к отгрузке на складе',
            'Ваш заказ отгружен (самовывоз)',
            'Ваш заказ отгружен (доставка)'
        ]
    ];

    if (empty($status)) {
        return array_keys($arStatus);
    }

    $arSubStatus = array_key_exists($status, $arStatus) ? $arStatus[$status] : [];
    return $arSubStatus;
}

function getFilter(int $hl, $arRequest = [])
{
    $filter = [];

    switch ((int)$hl) {
        case 6:
            $filter['UF_ORDER_INN_BUYER'] = getConfirmINN();
            break;
        case 7:
            $filter['UF_INVOICE_INN_BUYER'] = getConfirmINN();
            break;
        case 8:
            $filter['UF_PAYMENTS_INN_PAYER'] = getConfirmINN();
            break;
    }

    if (empty($arRequest)) {
        return [];
    }
    // Фильтр для Заказов
    if ($arRequest['UF_ORDER_INN_BUYER']) {
        $filter['=UF_ORDER_INN_BUYER'] = $arRequest['UF_ORDER_INN_BUYER'];
    }
    if ($arRequest['UF_ORDER_SHIPPING_DATE_FROM']) {
        $dateFrom = $arRequest['UF_ORDER_SHIPPING_DATE_FROM'];
        $ObjDateFrom = DateTime::createFromTimestamp(strtotime($dateFrom . ' 00:00:00'));
        $filter['>=UF_ORDER_SHIPPING_DATE'] = $ObjDateFrom;
    }
    if ($arRequest['UF_ORDER_SHIPPING_DATE_TO']) {
        $dateTo = $arRequest['UF_ORDER_SHIPPING_DATE_TO'];
        $ObjDateTo = DateTime::createFromTimestamp(strtotime($dateTo . ' 23:59:59'));
        $filter['<=UF_ORDER_SHIPPING_DATE'] = $ObjDateTo;
    }
    if (!empty($arRequest['ORDER_STATUS'])) {
        $arStatus = getListStatusOrder($arRequest['ORDER_STATUS']);
        $filter['UF_ORDER_STATUS'] = $arStatus;
    }
    // Фильтр для Счетов
    if ($arRequest['UF_INVOICE_INN_BUYER']) {
        $filter['=UF_INVOICE_INN_BUYER'] = $arRequest['UF_INVOICE_INN_BUYER'];
    }
    if ($arRequest['UF_INVOICE_NUMBER']) {
        $filter['%UF_INVOICE_NUMBER'] = $arRequest['UF_INVOICE_NUMBER'];
    }
    if ($arRequest['UF_INVOICE_SUM_MIN']) {
        $min = $arRequest['UF_INVOICE_SUM_MIN'];
        $filter['>=UF_INVOICE_SUM'] = $min;
    }
    if ($arRequest['UF_INVOICE_SUM_MAX']) {
        $max = $arRequest['UF_INVOICE_SUM_MAX'];
        $filter['<=UF_INVOICE_SUM'] = $max;
    }
    // Фильтр для Оплат
    if ($arRequest['UF_PAYMENTS_NAME']) {
        $filter['%UF_PAYMENTS_NAME'] = $arRequest['UF_PAYMENTS_NAME'];
    }
    if ($arRequest['UF_PAYMENTS_SUM_MIN']) {
        $min = $arRequest['UF_PAYMENTS_SUM_MIN'];
        $filter['>=UF_PAYMENTS_SUM'] = $min;
    }
    if ($arRequest['UF_PAYMENTS_SUM_MAX']) {
        $max = $arRequest['UF_PAYMENTS_SUM_MAX'];
        $filter['<=UF_PAYMENTS_SUM'] = $max;
    }

    return $filter;
}

function getFieldLabels($hl, $selectedFields)
{
    $fieldLabels = [];

    $userFields = UserFieldTable::getList(
        [
            'filter' => [
                'ENTITY_ID' => 'HLBLOCK_' . $hl,
                'FIELD_NAME' => $selectedFields,
            ],
            'order' => ['sort']
        ]
    );

    while ($userField = $userFields->fetch()) {
        $langMessages = UserFieldLangTable::getList(
            [
                'filter' => [
                    'USER_FIELD_ID' => $userField['ID'],
                    'LANGUAGE_ID' => 'ru'
                ],
            ]
        )->fetch();

        if ($langMessages) {
            $fieldLabels[$userField['FIELD_NAME']] = $langMessages['EDIT_FORM_LABEL'];
        } else {
            $fieldLabels[$userField['FIELD_NAME']] = $userField['FIELD_NAME'];
        }
    }

    return array_values($fieldLabels);
}

function genFileCSV($hl, array $fields, array $items)
{
    if (count($items) == 0) {
        return null;
    }
    ob_start();
    $df = fopen("php://output", 'w');
    fputs($df, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

    $fieldLabels = getFieldLabels($hl, $fields);
    fputcsv($df, $fieldLabels, ';');

    foreach ($items as $row) {
        fputcsv($df, $row, ';');
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

$fileName = 'file';
switch ((int)$hl) {
    case 6:
        $fileName = 'orders';
        break;
    case 7:
        $fileName = 'invoice';
        break;
    case 8:
        $fileName = 'payments';
        break;
};

downloadFile("b2b_" . $fileName . "_" . date("Y-m-d#H_i_s") . ".csv");
echo genFileCSV($hl, $fields, $items);
die();