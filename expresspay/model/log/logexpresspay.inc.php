<?php
/**
 * @package       ExpressPay Payment Module for ReadyScript
 * @author        ООО "ТриИнком" <info@express-pay.by>
 * @copyright     (c) 2022 Экспресс Платежи. Все права защищены.
 */
namespace ExpressPay\Model\Log;

use RS\Log\AbstractLog;

/**
 * Класс логирования 
 */
class LogExpressPay extends AbstractLog
{
    const LEVEL_INVOICES = 'invoices';
    const LEVEL_NOTIFICATIONS = 'notifications';

    /**
     * Возвращает идентификатор класса логирования
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'expresspay';
    }

    /**
     * Возвращает название класса логирования
     *
     * @return string
     */
    public function getTitle(): string
    {
        return t('Экспресс платежи');
    }

    /**
     * Возвращает список допустимых уровней лог-записей
     *
     * @return string[]
     */
    protected function selfLogLevelList(): array
    {
        return [
            self::LEVEL_INVOICES => t('Создание счёта'),
            self::LEVEL_NOTIFICATIONS => t('Обработка уведомлений'),
        ];
    }
}
