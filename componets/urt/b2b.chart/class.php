<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use \Bitrix\Main\Loader;
use \Bitrix\Highloadblock\HighloadBlockTable as HL;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Engine\CurrentUser;

Loader::includeModule('highloadblock');

class B2BChart extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams['HL'] = (string)($arParams['HL'] ?? '');
        $arParams['FILTER'] = (array)($arParams['FILTER'] ?? []);
        return $arParams;
    }

    private function getHlEntity($name)
    {
        //$hl = HL::getById($id)->fetch();
        $hl = HL::getList(['filter' => ['name' => $name]])->fetch();

        if ($hl) {
            $entity = HL::compileEntity($hl);
            $entityClass = $entity->getDataClass();
            return $entityClass;
        }
        return null;
    }

    private function getConfirmINN($userId)
    {
        $hlCompanyEntity = $this->getHlEntity('B2BCompany');;

        $rsItems = $hlCompanyEntity::getList(
            [
                'select' => ['UF_B2B_INN'],
                'filter' => ['UF_B2B_USERS' => $userId, '=UF_B2B_CONFIRM' => 1]
            ]
        )->fetchAll();

        $items = array_map(fn($item) => $item['UF_B2B_INN'], $rsItems);
        return $items;
    }

    private function getStartPeriod()
    {
//        $today = new \DateTime();
//        $thisMonth = (clone $today)->format('m');
//        $objFirstDayYear = (clone $today)->modify('first day of next month')->modify("-{$thisMonth} month");
//        return $objFirstDayYear;

        $startPeriod = (new DateTime())->format('Y-01-01 00:00:00');
        $objStartPeriod = DateTime::createFromTimestamp(strtotime($startPeriod));
        return $objStartPeriod;
    }

    private function getSelect(string $hlName)
    {
        $select = [];
        $ufNameChangeDate = '';
        $ufNameSum = '';
        switch ($hlName) {
            case 'B2BOrders':
                $ufNameSum = 'UF_ORDER_SUM';
                $ufNameChangeDate = 'UF_ORDER_CHANGE_DATE';
                break;
            case 'B2BInvoice':
                $ufNameSum = 'UF_INVOICE_SUM';
                $ufNameChangeDate = 'UF_INVOICE_CHANGE_DATE';
                break;
            case 'B2BPayments':
                $ufNameSum = 'UF_PAYMENTS_SUM';
                $ufNameChangeDate = 'UF_PAYMENTS_CHANGE_DATE';
                break;
        }

        $select = ['SUM' => $ufNameSum, 'DATE_CHANGE' => $ufNameChangeDate];
        return $select;
    }

    /**
     * Считаем процент
     */
    private function getPercentChange(array $arDataSum)
    {
        $thisMonthSum = (float)array_pop($arDataSum); // Сумма по текущему месяцу
        $lastMonthSum = (float)array_pop($arDataSum); // Сумма последнего месяца
        $percentChange = 0;

        if ($thisMonthSum > 0 && $lastMonthSum > 0) {
            $percentChange = ((100 * $thisMonthSum) / $lastMonthSum) - 100;
            $percentChange = round($percentChange, 2);
        }
        return $percentChange;
    }

    /**
     * Фильтр
     */
    private function getFilter(string $hlName, $userId)
    {
        $filter = [];
        $ufNameChangeDate = '';
        $ufNameINN = '';
        switch ($hlName) {
            case 'B2BOrders':
                $ufNameINN = 'UF_ORDER_INN_BUYER';
                $ufNameChangeDate = 'UF_ORDER_CHANGE_DATE';
                break;
            case 'B2BInvoice':
                $ufNameINN = 'UF_INVOICE_INN_BUYER';
                $ufNameChangeDate = 'UF_INVOICE_CHANGE_DATE';
                break;
            case 'B2BPayments':
                $ufNameINN = 'UF_PAYMENTS_INN_PAYER';
                $ufNameChangeDate = 'UF_PAYMENTS_CHANGE_DATE';
                break;
        }

        $filter[">={$ufNameChangeDate}"] = $this->getStartPeriod();
        $filter[$ufNameINN] = $this->getConfirmINN($userId);
        return $filter;
    }

    /**
     * Собираем дату сумм по месяцам, от начала года до текущего месяца. {HARD}
     */
    private function getData(string $hl, array $select = ['*'], array $filter = [])
    {
        $hlEntity = $this->getHlEntity($hl);

        $rsItems = $hlEntity::getList(
            [
                'select' => $select,
                'filter' => $filter
            ]
        )->fetchAll();

        $thisMonth = (int)(new DateTime())->format('m');
        $arMonth = [];
        for ($i = (int)$this->getStartPeriod()->format('m'); $i <= $thisMonth ; ++$i) {
            $arMonth[$i] = 0;
        }

        $sumData = array_reduce(
            $rsItems,
            function ($acc, $item) {
                $month = (int)$item['DATE_CHANGE']->format('m');
                //$monthName = $item['DATE_CHANGE']->format('F');
                $acc[$month][] = (float)$item['SUM'];
                ksort($acc, SORT_NUMERIC);
                return $acc;
            },
            []
        );

        $sumData = array_map(fn($item) => array_sum($item), $sumData);
        $rsData = array_values(array_replace($arMonth, $sumData));
        return $rsData;
    }

    public function executeComponent()
    {
        $userId = CurrentUser::get()->getId();

        $arDataSum = $this->getData(
            $this->arParams['HL'],
            $this->getSelect($this->arParams['HL']),
            $this->getFilter($this->arParams['HL'], $userId)
        );

        $this->arResult['DATA_SUM'] = $arDataSum;
        $this->arResult['TOTAL_SUM'] = array_sum($arDataSum);
        $this->arResult['PERCENT_CHANGE'] = $this->getPercentChange($arDataSum ?? []);
        $this->arResult['START_PERIOD'] = $this->getStartPeriod()->format('d.m.Y');

        $this->includeComponentTemplate();
    }
}