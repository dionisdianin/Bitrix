<?php

namespace KDM\Model;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;
use Sibirix\Main\Model\User;

class Favorites
{
    private $userModel = '';
    private $user = '';
    private $entityDataClass = '';

    public function __construct()
    {
        $this->userModel = new User();
        $this->user =  $this->userModel->getCurrent();

        if (Loader::includeModule("highloadblock")) {
            $hlbl_id = HL_FAVORITES;
            $hlblock = HL\HighloadBlockTable::getById($hlbl_id)->fetch();
            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $this->entityDataClass = $entity->getDataClass();
        }
    }

    /**
     * Получить список id избранных товаров юзера
     * @return array|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getList()
    {
        $rsData = $this->entityDataClass::getList([
            'select' => ['UF_USER_FAVORITES'],
            'filter' => ['UF_USER_ID' => (int)$this->user->ID]
        ])->Fetch();

        //return $rsData['UF_USER_FAVORITES'];
        return !empty($rsData['UF_USER_FAVORITES']) ? $rsData['UF_USER_FAVORITES'] : [];
    }

    /**
     * Создаем куку со списком id избранных товаров юзера
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function setCookieListItems()
    {
        setcookie('favoritesList', json_encode($this->getList()));
    }

    /**
     * Получаем куку со списком id избранных товаров юзера
     * @return mixed
     */
    public static function getCookieListItems()
    {
        $cookie = json_decode($_COOKIE['favoritesList']);
        return $cookie;
    }

    /**
     * Добавить в избранное
     * @param string $itemID
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function addFav(string $itemID)
    {
        $rsData = $this->entityDataClass::getList([
            'select' => ['ID', 'UF_USER_ID', 'UF_USER_FAVORITES'],
            'filter' => ['UF_USER_ID' => (int)$this->user->ID],
            'limit' => 1
        ])->Fetch();

        $arFavorites = $rsData['UF_USER_FAVORITES'];

        if ( isset($rsData['UF_USER_ID']) ) {
            if (!in_array($itemID, $arFavorites)) array_push($arFavorites, $itemID);
            $dataUpdate = [
                'UF_USER_FAVORITES' => $arFavorites
            ];
            if ($rsAction = $this->entityDataClass::update($rsData['ID'], $dataUpdate)) {
                $this->setCookieListItems();
                return true;
            }
        } else {
            $dataAdd = [
                'UF_USER_ID' => (int)$this->user->ID,
                'UF_USER_NAME' => $this->user->NAME,
                'UF_USER_FAVORITES' => [$itemID]
            ];
            if ($rsAction = $this->entityDataClass::add($dataAdd)) {
                $this->setCookieListItems();
                return true;
            }
        }
        return false;
    }

    /**
     * Удалить из избранного
     * @param string $itemID
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function deleteFav(string $itemID)
    {
        $rsData = $this->entityDataClass::getList([
            'select' => ['ID', 'UF_USER_ID', 'UF_USER_FAVORITES'],
            'filter' => ['UF_USER_ID' => (int)$this->user->ID],
            'limit' => 1
        ])->Fetch();

        $arFavorites = $rsData['UF_USER_FAVORITES'];

        if ( isset($rsData['UF_USER_ID']) && count($arFavorites) > 0 ) {
            if (in_array($itemID, $arFavorites)) {
                $arFavorites = array_diff($arFavorites, [$itemID]);
            };
            $dataUpdate = [
                'UF_USER_FAVORITES' => $arFavorites
            ];
            if ($rsAction = $this->entityDataClass::update($rsData['ID'], $dataUpdate)) {
                $this->setCookieListItems();
                return true;
            }
        }
        return false;
    }

}