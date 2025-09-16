<?php

namespace KDM\Model;

use \Bitrix\Main\Type\DateTime;

class YookassaPay
{
    // тестовые
    private static $shop_id = 'xxxxx';
    private static $secret_key = 'test_xxxxx';

    public static function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    public static function goPayForm(int $bx_order_id, float $price)
    {
        $uuid = self::gen_uuid();

        $data = array(
            'amount' => array(
                'value' => $price,
                'currency' => 'RUB',
            ),
            'capture' => true,
            'confirmation' => array(
                'type' => 'redirect',
                'return_url' => 'https://' . $_SERVER['SERVER_NAME'] . '/',
            ),
            'description' => 'Заказ №' . $bx_order_id,
            'metadata' => array(
                'order_id' => $bx_order_id,
            )
        );

        $data = json_encode($data, JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://api.yookassa.ru/v3/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERPWD, self::$shop_id . ':' . self::$secret_key);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Idempotence-Key: ' . $uuid));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($res, true);

        if ($res['type'] == 'error') {

            echo '<div class="pay__support">Произошла ошибка! Попробуйте снова или свяжитесь с техподдержкой.</div>';

        } elseif ($res['confirmation']['confirmation_url']) {

            $cookie = $bx_order_id . ':' . $res['id'];

            if (setcookie('yookassa_order_info', $cookie, (time() + 3600), '/')) {
                // Редирект пользователя на страницу оплату, адрес которой придёт в ответе
                header('Location: ' . $res['confirmation']['confirmation_url'], true, 301);
                exit();
            }
        }
    }

    public static function getStatusPay(string $payment_id)
    {
        $uuid = self::gen_uuid();

        $ch = curl_init('https://api.yookassa.ru/v3/payments/' . $payment_id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERPWD, self::$shop_id . ':' . self::$secret_key);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Idempotence-Key: ' . $uuid));
        $res = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($res, true);

        if ($res['status']) {
            return $res['status'];
        } else {
            return false;
        }
    }

    /**
     * Смена статуса заказа в битриксе на "Оплачено"
     * @param int $bx_order_id
     * @param string $payment_id
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public static function setStatusOrder(int $bx_order_id, string $payment_id)
    {
        $statusPay = self::getStatusPay($payment_id);

        if ($statusPay == 'succeeded') {
            $order = \Bitrix\Sale\Order::load($bx_order_id);

            $paymentCollection = $order->getPaymentCollection();
            $onePayment = $paymentCollection[0];

            $oDateTime = new DateTime();
            $oDateTime->add('- 2 minutes');
            $onePayment->setField('DATE_BILL', $oDateTime); // Дата создания документа оплаты

            $onePayment->setPaid('Y');  // Статус оплаты

            $order->setField('STATUS_ID', 'P'); // Статус заказа

            if ($order->save()) {
                //setcookie('yookassa_order_info', '', (time() - 3600), '/');
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}