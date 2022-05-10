<?php
/**
 * @package       ExpressPay Payment Module for ReadyScript
 * @author        ООО "ТриИнком" <info@express-pay.by>
 * @copyright     (c) 2022 Экспресс Платежи. Все права защищены.
 */
namespace ExpressPay\Config;

use \RS\Orm\Type;

/**
 * Конфигурационный файл модуля
 */
class File extends \RS\Orm\ConfigObject
{
    function _init()
    {
        parent::_init()->append([
            t('Основные'),
            'is_use_signature_for_notification' => new Type\Integer([
                'description' => t('Использовать цифровую подпись для уведомлений'),
                'checkboxView' => [1,0]
            ]),
            'secret_word_for_notification' => new Type\Varchar([
                'description' => t('Секретное слово для уведомлений'),
                'hint' => t('Секретное слово для формирования цифровой подписи для уведомлений')
            ]),
            'api_url' => new Type\Varchar([
                'description' => t('Адрес API'),
                'hint' => t('Адрес для работы с API')
            ]),
            'sandbox_url' => new Type\Varchar([
                'description' => t('Адрес тестового API'),
                'hint' => t('Адрес для работы с тестовым API')
            ])
        ]);
    }
}