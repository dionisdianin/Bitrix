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
            if (in_array($uf, ['UF_GUID'])) {
                unset($arResult['FIELD_VALUES'][$k][$uf]);
            } elseif ($uf == 'UF_PAYMENTS_NAME') {
                //if (mb_strlen($value) > 100) $arResult['FIELD_VALUES'][$k][$uf] = mb_substr($value, 0, 100) . '...';
            } elseif ($uf == 'UF_PAYMENTS_SUM') {
                $arResult['FIELD_VALUES'][$k][$uf] = number_format($value, 2, '.', ' ') . ' ₽';
            } elseif ($value instanceof \Bitrix\Main\Type\Date) {
                $arResult['FIELD_VALUES'][$k][$uf] = $value->format('d.m.Y');
            }
        }
    }
}