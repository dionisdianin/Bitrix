<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use \Bitrix\Main\Loader;
use \Bitrix\Highloadblock\HighloadBlockTable as HL;
use \Bitrix\Main\UserFieldTable;
use \Bitrix\Main\UserFieldLangTable;
use \Bitrix\Main\UI\PageNavigation;
use \Bitrix\Main\Engine\Contract\Controllerable;
use \Bitrix\Main\Engine\ActionFilter;
use \Bitrix\Main\Engine\Response;
use \Bitrix\Main\Context;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Engine\CurrentUser;

Loader::includeModule('highloadblock');

class B2BComponentTables extends CBitrixComponent implements Controllerable
{

    public function configureActions()
    {
        return [
            'addContentModalAction' => [
                'prefilters' => [],
            ],
            'checkUserINNAction' => [
                'prefilters' => [],
            ],
        ];
    }

    private function getHlEntity($name)
    {
        $hl = HL::getList(['filter' => ['name' => $name]])->fetch();

        if ($hl) {
            $entity = HL::compileEntity($hl);
            $entityClass = $entity->getDataClass();
            return $entityClass;
        }
        return null;
    }

    /**
     * Контент модального окна  ((Костыль))
     */
    public function addContentModalAction($id, $params)
    {
        $hlBlock = HL::getById($params['HLBLOCK'])->fetch();

        if (!$hlBlock) {
            return [];
        }

        $entity = HL::compileEntity($hlBlock);
        $entityClass = $entity->getDataClass();

        $rsData = $entityClass::getList(
            [
                'select' => ['*'], // $params['SELECTED_FIELDS'],
                'filter' => ['ID' => $id]
            ]
        )->fetch();

        array_walk(
            $rsData,
            function (&$val, $key) {
                if ($val instanceof \Bitrix\Main\Type\Date) {
                    $val = $val->format('d.m.Y');
                } elseif (stristr($key, '_GOODS')) {
                    $arGoods = json_decode($val, true);
                    unset($arGoods['goods']);
                    $val = array_map(
                        function ($arGood) {
                            return [
                                'Название товара' => $arGood['PRODUCT_NAME'],
                                //'Код' => $arGood['XML_ID'],
                                'Цена, ₽' => $arGood['PRICE'],
                                'Количество' => $arGood['QUANTITY'],
                                'Ед. измерения' => $arGood['MEASURE_NAME'],
                                //'Ответственный' => $arGood['ASSIGNED_BY_ID'],
                            ];
                        },
                        $arGoods
                    );
                    $val = json_encode($val, true);
                }
            }
        );

        return $rsData;
    }

    /**
     * Проверка ИНН юзера, для изменения данных по pull-подписке
     * @param string $inn
     * @param $params
     * @return bool
     */
    public function checkUserINNAction(string $inn, $params)
    {
        $userId = CurrentUser::get()->getId();
        $res = self::getHlEntity('B2BCompany')::getList(
            [
                'select' => ['UF_B2B_INN'],
                'filter' => ['=UF_B2B_USERS' => $userId, '=UF_B2B_INN' => $inn, '=UF_B2B_CONFIRM' => 1]
            ]
        )->fetch();

        return ($res) ? true : false;
    }

    public function onPrepareComponentParams($arParams)
    {
        $arParams['HLBLOCK'] = (int)($arParams['HLBLOCK'] ?? 0);
        $arParams['HL'] = (string)($arParams['HL'] ?? '');
        $arParams['SELECTED_FIELDS'] = (array)($arParams['SELECTED_FIELDS'] ?? []);
        $arParams['FILTER'] = (array)($arParams['FILTER'] ?? []);
        return $arParams;
    }

    /**
     * ИНН подтвержденных компаний, для фильтра по "ИНН покупателя"
     * @return mixed
     */
    private function getConfirmINN()
    {
        $items = [];
        $userId = CurrentUser::get()->getId();
        $rsItems = self::getHlEntity('B2BCompany')::getList(
            [
                'select' => ['UF_B2B_INN'],
                'filter' => ['=UF_B2B_USERS' => $userId, '=UF_B2B_CONFIRM' => 1]
            ]
        )->fetchAll();

        $items = array_map(fn($item) => $item['UF_B2B_INN'], $rsItems);
        return $items;
    }

    /**
     * Статусы заказа ((Костыль))
     */
    private function getListStatusOrder(string $status = '')
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

    /**
     * Параметры фильтра
     * @return array
     */
    private function getFilter(string $hl)
    {
        $filter = [];
        switch ($hl) {
            case 'B2BOrders':
                $filter['UF_ORDER_INN_BUYER'] = $this->getConfirmINN();
                break;
            case 'B2BInvoice':
                $filter['UF_INVOICE_INN_BUYER'] = $this->getConfirmINN();
                break;
            case 'B2BPayments':
                $filter['UF_PAYMENTS_INN_PAYER'] = $this->getConfirmINN();
                break;
            case 'B2BSettlements':
                $filter['UF_INN_DZ'] = $this->getConfirmINN();
                break;
        }

        $request = Context::getCurrent()->getRequest();
        $arRequest = $request->getQueryList()->toArray();
        if (!empty($arRequest)) {
            $this->arResult['REQUEST'] = $arRequest; // Для отображения значений в фильтре шаблона
            // Фильтр для Заказов
            if (!empty($arRequest['UF_ORDER_INN_BUYER'])) {
                $filter['=UF_ORDER_INN_BUYER'] = $arRequest['UF_ORDER_INN_BUYER'];
            }
            if (!empty($arRequest['UF_ORDER_SHIPPING_DATE_FROM'])) {
                $dateFrom = $arRequest['UF_ORDER_SHIPPING_DATE_FROM'];
                $ObjDateFrom = DateTime::createFromTimestamp(strtotime($dateFrom . ' 00:00:00'));
                $filter['>=UF_ORDER_SHIPPING_DATE'] = $ObjDateFrom; // Для шаблона: $ObjDateFrom->format("Y-m-d");
            }
            if (!empty($arRequest['UF_ORDER_SHIPPING_DATE_TO'])) {
                $dateTo = $arRequest['UF_ORDER_SHIPPING_DATE_TO'];
                $ObjDateTo = DateTime::createFromTimestamp(strtotime($dateTo . ' 23:59:59'));
                $filter['<=UF_ORDER_SHIPPING_DATE'] = $ObjDateTo;
            }
            if (!empty($arRequest['UF_ORDER_STATUS'])) {
                $arStatus = $this->getListStatusOrder($arRequest['UF_ORDER_STATUS']);
                $filter['UF_ORDER_STATUS'] = $arStatus;
            }
            // Фильтр для Счетов
            if (!empty($arRequest['UF_INVOICE_INN_BUYER'])) {
                $filter['=UF_INVOICE_INN_BUYER'] = $arRequest['UF_INVOICE_INN_BUYER'];
            }
            if (!empty($arRequest['UF_INVOICE_NUMBER'])) {
                $filter['%UF_INVOICE_NUMBER'] = $arRequest['UF_INVOICE_NUMBER'];
            }
            if (!empty($arRequest['UF_INVOICE_SUM_MIN'])) {
                $min = $arRequest['UF_INVOICE_SUM_MIN'];
                $filter['>=UF_INVOICE_SUM'] = $min;
            }
            if (!empty($arRequest['UF_INVOICE_SUM_MAX'])) {
                $max = $arRequest['UF_INVOICE_SUM_MAX'];
                $filter['<=UF_INVOICE_SUM'] = $max;
            }
            // Фильтр для Оплат
            if (!empty($arRequest['UF_PAYMENTS_NAME'])) {
                $filter['%UF_PAYMENTS_NAME'] = $arRequest['UF_PAYMENTS_NAME'];
            }
            if (!empty($arRequest['UF_PAYMENTS_SUM_MIN'])) {
                $min = $arRequest['UF_PAYMENTS_SUM_MIN'];
                $filter['>=UF_PAYMENTS_SUM'] = $min;
            }
            if (!empty($arRequest['UF_PAYMENTS_SUM_MAX'])) {
                $max = $arRequest['UF_PAYMENTS_SUM_MAX'];
                $filter['<=UF_PAYMENTS_SUM'] = $max;
            }
        }
        return $filter;
    }

    private function getFieldLabels($hlBlockId, $selectedFields)
    {
        $fieldLabels = [];

        $userFields = UserFieldTable::getList(
            [
                'filter' => [
                    'ENTITY_ID' => 'HLBLOCK_' . $hlBlockId,
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

        return $fieldLabels;
    }

    private function getFieldsValues($hlBlockId, $select = ['*'], PageNavigation $nav, $filter = [])
    {
        $items = [];
        $hlBlock = HL::getById($hlBlockId)->fetch();

        if (!$hlBlock) {
            return [];
        }

        $entity = HL::compileEntity($hlBlock);
        $entityClass = $entity->getDataClass();

        // GetList для Оплат (HL=8)
        if ($hlBlockId == 8 && $this->arParams['HLBLOCK_INVOICE']) {
            $entityInvoice = HL::compileEntity($this->arParams['HLBLOCK_INVOICE']);
            $invoiceEntityClass = $entityInvoice->getDataClass();

            $items = $entityClass::getList(
                [
                    'select' => ['*', 'INVOICE_NUMBER' => 'INVOICE_DATA.UF_INVOICE_NUMBER'],
                    'filter' => $filter,
                    'offset' => $nav->getOffset(),
                    'limit' => $nav->getLimit(),
                    'order' => ['ID'],
                    'runtime' => [
                        new \Bitrix\Main\Entity\ReferenceField(
                            'INVOICE_DATA',
                            $invoiceEntityClass,
                            ['=this.UF_PAYMENTS_INVOICE' => 'ref.ID']
                        )
                    ],
                ]
            )->fetchAll();

            foreach ($items as $k => $item) {
                $items[$k]['UF_PAYMENTS_INVOICE'] = $item['INVOICE_NUMBER'];
                unset($items[$k]['INVOICE_NUMBER']);
            }
        } else {
            $items = $entityClass::getList(
                [
                    'select' => $select,
                    'filter' => $filter,
                    'offset' => $nav->getOffset(),
                    'limit' => $nav->getLimit(),
                    'order' => ['ID' => 'DESC'],
                    'cache' => [
                        'ttl' => 3600,
                        'cache_joins' => true
                    ],
                ]
            )->fetchAll();
        }

        $totalCount = $entityClass::getCount($filter);
        $nav->setRecordCount($totalCount);

        $this->arResult['TOTAL_COUNT'] = $totalCount;

        $this->arResult['NAV'] = $nav;

        return $items;
    }

    private function getAllFields($hlBlock)
    {
        $fields = [];
//        $fields = array_keys(self::getHlEntity($hl)->getFields());

        $entity = HL::compileEntity($hlBlock);
        $entityClass = $entity->getDataClass();
        $fields = array_keys($entityClass::getEntity()->getFields());

        return array_filter(
            $fields,
            function ($value) {
                //return $value !== 'ID';
                return !in_array(
                    $value,
                    ['UF_ORDER_GOODS', 'UF_INVOICE_GOODS', 'UF_GUID', 'UF_ID_B24', 'UF_ID_COMPANY_DZ']
                );
            }
        );
        return $fields;
    }

    public function executeComponent()
    {
        $hlBlock = HL::getById($this->arParams['HLBLOCK'])->fetch();
        $hl = $this->arParams['HL'];

        if (empty($this->arParams['SELECTED_FIELDS'])) {
            $this->arParams['SELECTED_FIELDS'] = $this->getAllFields($hlBlock);
        }

        $this->arResult['FIELD_LABELS'] = $this->getFieldLabels(
            $this->arParams['HLBLOCK'],
            $this->arParams['SELECTED_FIELDS']
        );

        $nav = new PageNavigation('nav');
        $nav->setPageSize($this->arParams['PAGE_SIZE'])->initFromUri();

        $this->arParams['FILTER'] = $this->getFilter($hl);

        $confirmINN = $this->getConfirmINN();
        $this->arResult['SELECTOR_INN'] = $confirmINN;

        $this->arResult['FIELD_VALUES'] = $this->getFieldsValues(
            $this->arParams['HLBLOCK'],
            ['*'],
            $nav,
            $this->arParams['FILTER']
        );

        // Подписываем юзера на pull-событие
        if (\CModule::IncludeModule('pull')) {
            \CPullWatch::Add(CurrentUser::get()->getId(), 'PULL_B2B_HL');
        }

        \Bitrix\Main\Diag\Debug::writeToFile(
            implode(', ', $confirmINN),
            date('d.m.Y H:i:s') . " {$hl}",
            "/local/log/hits_{$hl}"
        );
        $this->includeComponentTemplate();
    }
}