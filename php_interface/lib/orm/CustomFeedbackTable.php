<?php

namespace Lib\ORM;

use \Bitrix\Main\Entity;

class CustomFeedbackTable extends Entity\DataManager
{
    public static function getTableName(): string
    {
        return 'b_custom_feedback';
    }

    public static function getMap()
    {
        return [
            new Entity\IntegerField(
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                ]
            ),
            new Entity\StringField(
                'NAME',
                [
                    'required' => true,
                ]
            ),
            new Entity\StringField(
                'EMAIL',
                [
                    'required' => true,
                ]
            ),
            new Entity\TextField(
                'MESSAGE'
            ),
            new Entity\DatetimeField(
                'CREATED',
                [
                    'default_value' => new \Bitrix\Main\Type\DateTime(),
                ]
            ),
        ];
    }

    public static function dropTable()
    {
        $connection = \Bitrix\Main\Application::getConnection();
        $connection->dropTable(self::getTableName());
    }
}