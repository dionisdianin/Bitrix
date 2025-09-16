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
            if (in_array($uf, ['UF_ORDER_GOODS', 'UF_GUID'])) {
                unset($arResult['FIELD_VALUES'][$k][$uf]);
            } elseif (in_array($uf, ['UF_ORDER_SHIPPING_WAREHOUSE', 'UF_ORDER_ASSIGNED_MANAGER'])) {
                $value = str_replace('Склад ', '', $value);
                $arResult['FIELD_VALUES'][$k][$uf] = str_replace(' ', '</br>', $value);
            } elseif ($uf == 'UF_ORDER_DELIVERY_ADDRESS') {
                if (mb_strlen($value) > 50) {
                    $arResult['FIELD_VALUES'][$k]['FULL_TEXT_DELIVERY_ADDRESS'] = $value;
                    $arResult['FIELD_VALUES'][$k][$uf] = mb_substr($value, 0, 50) . '...';
                }
            } elseif ($uf == 'UF_ORDER_SUM') {
                $arResult['FIELD_VALUES'][$k][$uf] = number_format((float)$value, 2, '.', ' ') . ' ₽';
            } elseif ($value instanceof \Bitrix\Main\Type\Date) {
                //$datePay = date_create($value);
                //$arResult['FIELD_VALUES'][$k][$uf] = date_format($datePay, 'd.m.Y');
                $arResult['FIELD_VALUES'][$k][$uf] = $value->format('d.m.Y');
            }
        }
    }
}