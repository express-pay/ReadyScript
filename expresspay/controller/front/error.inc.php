<?php
/**
 * @package       ExpressPay Payment Module for ReadyScript
 * @author        ООО "ТриИнком" <info@express-pay.by>
 * @copyright     (c) 2022 Экспресс Платежи. Все права защищены.
 */
namespace ExpressPay\Controller\Front;

class error extends \RS\Controller\Front
{
    function actionIndex()
    {
        return $this->result->setTemplate('error.tpl');
    }
}