<?php
/**
 * @package       ExpressPay Payment Module for ReadyScript
 * @author        ООО "ТриИнком" <info@express-pay.by>
 * @copyright     (c) 2022 Экспресс Платежи. Все права защищены.
 */
namespace ExpressPay\Config;

/**
 * Класс предназначен для объявления событий, которые будет прослушивать данный модуль и обработчиков этих событий.
 */
class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
			->bind('getlogs')
            ->bind('getroute')
            ->bind('payment.gettypes');
    }
    
    /**
     * 
     * Регистрирует в системе классы логирования
     *
     * @param AbstractLog[] $list - список классов логирования
     * 
     * @return AbstractLog[]
     * 
     */
    public static function getLogs($list)
    {
        $list[] = \ExpressPay\Model\Log\LogExpressPay::getInstance();
        return $list;
    }

    /**
     * 
     * Добавляем новый вид оплаты - ExpressPay
     * 
     * @param array $list - массив уже существующих типов оплаты
     * 
     * @return array
     * 
     */
    public static function paymentGetTypes($list)
    {
        $list[] = new \ExpressPay\Model\PaymentType\ExpressPay();
        return $list;
    }
	
    public static function getRoute(array $routes)
    {        
        $routes[] = new \RS\Router\Route('expresspay-front-check', [
            '/expresspay/check/{transaction_id}/'
        ], null, t('Просмотр счёта'));
        $routes[] = new \RS\Router\Route('expresspay-front-error', [
            '/expresspay/error/{transaction_id}/'
        ], null, t('Ошибка инициализации'));
		
        return $routes;
    }
}