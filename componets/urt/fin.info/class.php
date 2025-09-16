<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable as HL;
use Bitrix\Main\UserFieldTable;
use Bitrix\Main\UserFieldLangTable;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;

Loader::includeModule('highloadblock');

class FinInfo extends CBitrixComponent implements Controllerable
{
    public function configureActions()
    {
        return [
            'applyFilterAction' => [
                'prefilters' => [],
            ],
        ];
    }

    /**
     * Фильтр по ИНН, ajax
     * @param $filterJSON
     * @param $params
     * @return array
     */
    public function applyFilterAction($filterJSON, $params)
    {
        $data = [];
        $userId = CurrentUser::get()->getId();
        $filterDefault = ['UF_B2B_USERS' => $userId, '=UF_B2B_CONFIRM' => 1];
        $filter = json_decode($filterJSON, true);

        if ($filter['UF_B2B_INN'] == '*') {
            $filter = $filterDefault;
        } else {
            $filter += $filterDefault;
        }

        $data = $this->getInfo(
            $params['HL_ID'],
            $filter
        );

        return $data;
    }

    public function onPrepareComponentParams($arParams)
    {
        $arParams['HL_ID'] = (int)($arParams['HL_ID'] ?? 5);
        return $arParams;
    }

    private function getEntityClass($HLID)
    {
        $hlBlock = HL::getById($HLID)->fetch();
        if (!$hlBlock) {
            return null;
        }

        $entity = HL::compileEntity($hlBlock);
        $entityClass = $entity->getDataClass();

        return $entityClass;
    }

    /**
     * Основной метод получения данных: расчет сумм, выборка ИНН для фильтра
     * @param $HLID
     * @param array $filter
     * @return array
     */
    private function getInfo($HLID, $filter = [])
    {
        $data = [];
        $arrINN = [];
        $sum = [];

        $entityClass = $this->getEntityClass($HLID);
        if (!$entityClass) {
            return [];
        }

        $res = $entityClass::getList(
            [
                'select' => ['*'],
                'filter' => $filter
            ]
        )->fetchAll();

        if (!empty($res)) {
            foreach ($res as $k => $val) {
                $arrINN[] = $val['UF_B2B_INN'];

                $sumKL += (float)$val['UF_B2B_KL'];
                $sumDZ += (float)$val['UF_B2B_DZ'];
                $sumPDZ += (float)$val['UF_B2B_PDZ'];
            }
        }
        $sum['KL'] = number_format($sumKL, 2, '.', ' ') ?? (float)0.00;
        $sum['DZ'] = number_format($sumDZ, 2, '.', ' ') ?? (float)0.00;
        $sum['PDZ'] = number_format($sumPDZ, 2, '.', ' ') ?? (float)0.00;

        $data['SELECTOR_INN'] = $arrINN;
        $data['SUM'] = $sum;

        return $data;
    }

    public function executeComponent()
    {
        $userId = CurrentUser::get()->getId();

        $this->arResult = $this->getInfo(
            $this->arParams['HL_ID'],
            ['UF_B2B_USERS' => $userId, '=UF_B2B_CONFIRM' => 1]
        );

        $this->includeComponentTemplate();
    }

}