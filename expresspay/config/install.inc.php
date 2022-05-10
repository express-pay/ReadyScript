<?php
/**
 * @package       ExpressPay Payment Module for ReadyScript
 * @author        ООО "ТриИнком" <info@express-pay.by>
 * @copyright     (c) 2022 Экспресс Платежи. Все права защищены.
 */
namespace ExpressPay\Config;

class Install extends \RS\Module\AbstractInstall
{
    /**
     * 
     * Выполняет установку модуля
     * 
     * @return bool
     * 
     */
    function install()
    {
        if ($result = parent::install()) {
            $config = \RS\Config\Loader::byModule($this);
            $config['secret_word_for_notification'] = \RS\Helper\Tools::generatePassword(16, array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9')));
            $config->update();
        }
        return $result;
    }
}
