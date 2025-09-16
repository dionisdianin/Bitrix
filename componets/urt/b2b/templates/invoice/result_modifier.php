<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */

if (!empty($arResult['FIELD_VALUES'])) {
    foreach ($arResult['FIELD_VALUES'] as $k => $arValues) {
        foreach ($arValues as $uf => $value) {
            if (in_array($uf, ['UF_INVOICE_GOODS', 'UF_GUID'])) {
                unset($arResult['FIELD_VALUES'][$k][$uf]);
            } elseif (in_array($uf, ['UF_INVOICE_MANAGER', 'UF_INVOICE_OPERATOR', 'UF_INVOICE_REGION'])) {
                $arResult['FIELD_VALUES'][$k][$uf] = str_replace(' ','</br>',  $value);
            } elseif ($uf == 'UF_INVOICE_EMAIL') {
                if (mb_strlen($value) > 12) $arResult['FIELD_VALUES'][$k][$uf] = mb_substr($value, 0, 12) . '...';
            } elseif ($uf == 'UF_INVOICE_SUM') {
                $arResult['FIELD_VALUES'][$k][$uf] = number_format((float)$value, 2, '.', ' ') . ' ₽';
            } elseif ($uf == 'UF_INVOICE_SHIPPING_DAYS' && !empty($value)) {
                $arResult['FIELD_VALUES'][$k][$uf] = $arResult['FIELD_VALUES'][$k][$uf] . ' дн.';
            } elseif ($uf == 'UF_INVOICE_ORDER_NUMBER' && is_array($value)) {
                $arResult['FIELD_VALUES'][$k][$uf] = implode('<br>', $value);
            } elseif ($uf == 'UF_INVOICE_FILE_BILL') {
                if ($value) {
                    $linkFile = CFile::GetPath($value);
                    $arResult['FIELD_VALUES'][$k][$uf] = "<i class='lni lni-download'></i> <a class='link-file-invoice' href='{$linkFile}' download>Скачать</a>";
                } else {
                    $arResult['FIELD_VALUES'][$k][$uf] = '';
                }
            } elseif ($value instanceof \Bitrix\Main\Type\Date) {
                $arResult['FIELD_VALUES'][$k][$uf] = $value->format('d.m.Y');
            }
        }
    }
}