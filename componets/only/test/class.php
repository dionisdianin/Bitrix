<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Bitrix\Highloadblock\HighloadBlockTable as HL;
use \Bitrix\Iblock\Iblock;
use \Bitrix\Iblock\IblockTable;
use \Bitrix\Main\SystemException;
use \Bitrix\Main\Entity\Query;
use \Bitrix\Main\Entity\ReferenceField;
use \Bitrix\Main\Entity\Query\Join;
use \Bitrix\Main\Context;
use \Bitrix\Main\Engine\CurrentUser;
use \Bitrix\Main\Type\DateTime as BXDateTime;

Loc::loadMessages(__FILE__);
Loader::includeModule('iblock');
Loader::includeModule('highloadblock');

class TestOnly extends \CBitrixComponent
{
    private $err = [];

    public function onPrepareComponentParams($arParams)
    {
        $arParams['HL_COMPANY_CARS_NAME'] = $arParams['HL_COMPANY_CARS_NAME'] ?? 'CompanyCars';
        $arParams['HL_COMFORT_CATS_NAME'] = $arParams['HL_COMFORT_CATS_NAME'] ?? 'ComfortCats';
        $arParams['HL_DRIVERS_NAME'] = $arParams['HL_DRIVERS_NAME'] ?? 'Drivers';
        $arParams['IB_EMPL_CODE'] = $arParams['IB_EMPL_CODE'] ?? 'employees';
        $arParams['IB_POS_CODE'] = $arParams['IB_POS_CODE'] ?? 'positions';
        return $arParams;
    }

    private function getHlEntity($name): string|false
    {
        $hl = HL::getList(['filter' => ['NAME' => $name]])->fetch();

        if (!$hl) {
            $this->err[] = Loc::getMessage('ERR_HL', ['#HL_NAME#' => $name]);
            return false;
        }

        try {
            $entity = HL::compileEntity($hl);
            $entityClass = $entity->getDataClass();
            return $entityClass;
        } catch (SystemException $e) {
            $this->err[] = Loc::getMessage('ERR_HL_ENTITY', ['#HL_NAME#' => $name, '#ERR#' => $e->getMessage()]);
        }

        return false;
    }

    private function getIBEntity($code): string|false
    {
        $ib = IblockTable::getList([
            'select' => ['ID'],
            'filter' => ['CODE' => $code]
        ])->fetch();

        if (!$ib) {
            $this->err[] = Loc::getMessage('ERR_IB', ['#IB_CODE#' => $code]);
            return false;
        }

        $entityClass = Iblock::wakeUp($ib['ID'])->getEntityDataClass();
        return $entityClass;
    }

    public function getRequestParams(): array|false
    {
        $request = Context::getCurrent()->getRequest();
        $today = new \DateTime();

        if (!$request->get('TIME_FROM') || !$request->get('TIME_TO')) {
            $this->err[] = Loc::getMessage('ERR_REQUEST_TIME_IS_MISSING');
            return false;
        }

        try {
            $objTimeFrom = new BXDateTime($today->format("Y-m-d {$request->get('TIME_FROM')}"), 'Y-m-d H:i');
            $objTimeTo = new BXDateTime($today->format("Y-m-d {$request->get('TIME_TO')}"), 'Y-m-d H:i');
        } catch  (SystemException $e) {
            $this->err[] = Loc::getMessage('ERR_REQUEST_TIME_FORMAT', ['#ERR#' => $e->getMessage()]);
            return false;
        }

        $arrPrms = [
            'OBJ_TIME_FROM' => $objTimeFrom,
            'OBJ_TIME_TO' => $objTimeTo,
        ];

        return $arrPrms;
    }

    /**
     * Получаем должность текущего сотрудника
     */
    private function getEmplPosition(int $userId): array|false
    {
        $posEntityClass = self::getIBEntity($this->arParams['IB_POS_CODE']);
        $emplEntityClass = self::getIBEntity($this->arParams['IB_EMPL_CODE']);

        if (!$posEntityClass || !$emplEntityClass) {
            return false;
        }

        $query = new Query($emplEntityClass);

        $query->setSelect(['ID', 'NAME']);
        $query->addSelect('EMPLOYEE.VALUE', 'EMPLOYEE_USER_ID');
        $query->addSelect('POSITION.VALUE', 'POSITION_ID');
        $query->addSelect('POSITION_DATA.NAME', 'POSITION_NAME');
        $query->where('EMPLOYEE.VALUE', $userId);
        $query->registerRuntimeField(
            new ReferenceField(
                'POSITION_DATA', $posEntityClass,
                Join::on('this.POSITION.VALUE', 'ref.ID')
            )
        );
        $query->setCacheTtl(3600);
        $query->cacheJoins(true);
        $rs = $query->fetchAll();

        return current($rs);
    }

    private function getFreeCars(int $posId, BXDateTime $objTimeFrom, BXDateTime $objTimeTo) :array|false
    {
        $carsEntityClass = self::getHlEntity($this->arParams['HL_COMPANY_CARS_NAME']);
        $comfEntityClass = self::getHlEntity($this->arParams['HL_COMFORT_CATS_NAME']);
        $driversEntityClass = self::getHlEntity($this->arParams['HL_DRIVERS_NAME']);

        if (!$comfEntityClass || !$carsEntityClass || !$driversEntityClass) {
            return false;
        }

        $query = new Query($carsEntityClass);

        $query->setSelect(['ID', 'MODEL' => 'UF_MODEL']);
        $query->addSelect('COMFORTCAT_DATA.UF_CAT_NAME', 'COMFORTCAT_NAME');
        $query->addSelect('COMFORTCAT_DATA.UF_POSITIONS', 'COMFORTCAT_POSITIONS');
        $query->addSelect('DRIVER_DATA.UF_NAME', 'DRIVER');
        $query->where(Query::filter()
            ->logic('OR')
            ->whereNull('UF_BUSY_TIME_FROM')
            ->where(
                Query::filter()
                    ->whereNotBetween('UF_BUSY_TIME_FROM', $objTimeFrom, $objTimeTo)
                    ->whereNotBetween('UF_BUSY_TIME_TO', $objTimeFrom, $objTimeTo)
            )
        );
        /*
            Join к HL категорий комфорта, для фильтра:
            Выбор категорий, где в привязанных должностях (мн. поле) содержится должность сотрудника:
        */
        $query->where('COMFORTCAT_DATA.UF_POSITIONS_SINGLE', $posId);
        $query->registerRuntimeField(
            new ReferenceField('COMFORTCAT_DATA', $comfEntityClass,
                Join::on('this.UF_COMFORT_CAT', 'ref.ID')
            )
        );
        // Имя водителя
        $query->registerRuntimeField(
            new ReferenceField('DRIVER_DATA', $driversEntityClass,
                Join::on('this.UF_DRIVER', 'ref.ID')
            )
        );
        $query->setOrder(['ID' => 'ASC']);
        $query->setCacheTtl(3600);
        $query->cacheJoins(true);
        $rs =  $query->fetchAll();

        return $rs;
    }

    public function executeComponent()
    {
        $userId = (int)CurrentUser::get()->getId();
        $reqPrms = $this->getRequestParams();

        if ($userId > 0) {
            $position = $this->getEmplPosition($userId);
        }

        if ($position && $reqPrms) {
            $this->arResult['FREE_CARS'] = $this->getFreeCars(
                $position['POSITION_ID'],
                $reqPrms['OBJ_TIME_FROM'],
                $reqPrms['OBJ_TIME_TO']
            );
        }

        echo '<pre>';
        if (!empty($this->err)) {
            echo $this->err[0]; ;
        } else {
            var_dump($this->arResult);
        }
        echo '</pre>';
    }
}