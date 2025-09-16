<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use \Bitrix\Main\Loader;
use \Bitrix\Highloadblock\HighloadBlockTable as HL;
use \Bitrix\Main\Engine\Contract\Controllerable;
use \Bitrix\Main\Context;
use \Bitrix\Main\UserTable;
use \Bitrix\Main\UserGroupTable;
use \Bitrix\Main\Entity\ReferenceField;
use \Bitrix\Main\Mail\Event;
use \Lib\ORM\CustomFeedbackTable;

Loader::includeModule('highloadblock');

class TestWeJET extends \CBitrixComponent implements Controllerable
{
    /**
     * Метод интерфейса Controllerable, для описания ajax-методов класса
     */
    public function configureActions()
    {
        return [
            'saveForm' => [
                'prefilters' => [
                    new Bitrix\Main\Engine\ActionFilter\HttpMethod(
                        [Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_POST]
                    ),
                    new Bitrix\Main\Engine\ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    public function onPrepareComponentParams($arParams)
    {
        $arParams['HL_NAME'] = $arParams['HL_NAME'] ?? 'CustomFeedbackHL';
        return $arParams;
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
     * Ajax-метод для сохранения данных формы
     */
    public function saveFormAction($name, $email = '', $message = '', $params)
    {
        $request = Context::getCurrent()->getRequest();
        $arrRequest = $request->getPostList()->toArray();

        $dataFields = [];

        // Для HL
        /*
        $dataFields['UF_NAME'] = $arrRequest['name'];
        $dataFields['UF_EMAIL'] = $arrRequest['email'];
        $dataFields['UF_MESSAGE'] = $arrRequest['message'];

        $rs = self::getHlEntity($this->arParams['HL_NAME'])::add($dataFields);
        */

        // Для таблицы
        $dataFields['NAME'] = $arrRequest['name'];
        $dataFields['EMAIL'] = $arrRequest['email'];
        $dataFields['MESSAGE'] = $arrRequest['message'];

        $rs = CustomFeedbackTable::add($dataFields);

        if ($rs->isSuccess()) {
            $this::sendMail($dataFields);
            return true;
        } else {
            $err = $rs->getErrorMessages();
            return 'Ошибка: ' . implode(', ', $err);
        }
    }

    /**
     * Письмо админам
     */
    private function sendMail(array $dataFields)
    {
        $rsAdmins = UserGroupTable::query()
            ->setFilter(['GROUP_ID' => 1])
            ->setSelect(['GROUP_ID', 'USER_ID', 'EMAIL' => 'USER_DATA.EMAIL'])
            ->registerRuntimeField(
                new ReferenceField(
                    'USER_DATA',
                    UserTable::class,
                    ['=this.USER_ID' => 'ref.ID']

                )
            )
            ->setCacheTtl(3600)
            ->cacheJoins(true)
            ->fetchAll();

        $emailAdmins = implode(',', array_column($rsAdmins, 'EMAIL'));

        Event::send(
            [
                "EVENT_NAME" => "IM_NEW_MESSAGE",
                "LID" => "s1",
                "C_FIELDS" => [
                    "EMAIL_TO" => $emailAdmins,
                    "MESSAGE" => implode('; ', $dataFields)
                ]
            ]
        );
        return false;
    }

    public function executeComponent()
    {
        $this->includeComponentTemplate();
    }
}