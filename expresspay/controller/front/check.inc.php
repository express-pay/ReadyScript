<?php
/**
 * @package       ExpressPay Payment Module for ReadyScript
 * @author        ООО "ТриИнком" <info@express-pay.by>
 * @copyright     (c) 2022 Экспресс Платежи. Все права защищены.
 */
namespace ExpressPay\Controller\Front;

use \Shop\Model\Orm\Transaction;
use \ExpressPay\Model\Log\LogExpressPay;
use \ExpressPay\Model\PaymentType\ExpressPay;

class check extends \RS\Controller\Front
{
    const
        ERIP_TITLE = 'Номер транзакции: ##transaction_id##',
        ERIP_CONTENT = 'Оплату необходимо произвести в любой системе, позволяющей оплачивать через ЕРИП '.
                        '(товары банковские услуги, банкоматы, платежные терминалы, системы интернет-банкинга, клиент-банкинг и др.).'.
                        '<br> 1. Для этого в списке услуг ЕРИП перейдите в раздел: ##erip_path##'.
                        '<br> 2. Далее введите номер транзакции <b>##transaction_id##</b> и нажмите "Продолжить"'.
                        '<br> 3. Проверьте правильность информации'.
                        '<br> 4. Произвести платеж',

        EPOS_TITLE = 'Номер транзакции: ##transaction_id##',
        EPOS_CONTENT = 'Оплату необходимо произвести в любой системе, позволяющей оплачивать через ЕРИП '.
                        '(товары банковские услуги, банкоматы, платежные терминалы, системы интернет-банкинга, клиент-банкинг и др.).'.
                        '<br> 1. Для этого в списке услуг ЕРИП перейдите в раздел: ##erip_path##'.
                        '<br> 2. В поле Код введите <b>##transaction_id##</b> и нажмите "Продолжить"'.
                        '<br> 3. Проверьте правильность информации'.
                        '<br> 4. Произвести платеж',
        EPOS_ERIP_PATH = 'Расчетная система (ЕРИП)->Услуга E-POS->E-POS->Оплата товаров и услуг';

    function actionIndex()
    {
        $log = LogExpressPay::getInstance();
        $transaction_id = $this->url->request('transaction_id', TYPE_INTEGER);
        $transaction = new \Shop\Model\Orm\Transaction((int)$transaction_id);
        if (!$transaction->id) {
            $this->$log->write(t('[Exception] expresspay-front-check::actionIndex($transaction->id = %0) Транзакция не найдена',[$transaction_id]), LogExpressPay::LEVEL_INVOICES);
            throw new ShopException(t("Транзакция с идентификатором %0 не найдена", [$transaction_id]), ShopException::ERR_TRANSACTION_NOT_FOUND);
        }

        if($transaction['status'] == \Shop\Model\Orm\Transaction::STATUS_SUCCESS){
            return $this->result->setSuccess(true)->setRedirect(ExpressPay::getSuccessUrl($transaction_id));
        }
        if($transaction['status'] == \Shop\Model\Orm\Transaction::STATUS_FAIL){
            return $this->result->setSuccess(false)->setRedirect(ExpressPay::getFailUrl($transaction_id));
        }
        
        $payment = $transaction->getPayment()->getTypeObject();
        $amount = round($transaction->cost, 2);

        if($payment->getOption('payment_type') == 'erip'){

            $content_body = str_replace('##erip_path##', $payment->getOption('path_in_erip'), self::ERIP_CONTENT);
            $content_body = str_replace('##transaction_id##', $transaction_id, $content_body);
            $heading_title = str_replace('##transaction_id##', $transaction_id, self::ERIP_TITLE);
            if($payment->getOption('is_show_qr_code') == 1){
                try {
                    $qrbase64 = $payment->getQrbase64($transaction_id, $amount);
                    $qrbase64 = json_decode($qrbase64);
                    $qr_code = $qrbase64->QrCodeBody;
                    $show_qr_code = 1;
                } catch (Exception $e) {
                    $log->write(t('[Exception] expresspay-front-check::actionIndex($transaction->id = %0) response = %1 exception %2',[$transaction_id, $qrbase64, $e]), LogExpressPay::LEVEL_INVOICES);
                }
            }
            $this->view->assign([
                'heading_title' => $heading_title,
                'content_body' => $content_body,
                'show_qr_code' => $show_qr_code,
                'qr_code' => $qr_code
            ]);
            $log->write(t('expresspay-front-check::actionIndex($transaction->id = %0) check erip content',[$transaction_id]), LogExpressPay::LEVEL_INVOICES);
            return $this->result->setTemplate('check.tpl');
        }
        else if($payment->getOption('payment_type') == 'epos'){
            
            $content_body = str_replace('##erip_path##', self::EPOS_ERIP_PATH, self::EPOS_CONTENT);
            $content_body = str_replace('##transaction_id##', $payment->getOption('service_provider_id').'-'.$payment->getOption('epos_service_id').'-'.$transaction_id, $content_body);
            $heading_title = str_replace('##transaction_id##', $payment->getOption('service_provider_id').'-'.$payment->getOption('epos_service_id').'-'.$transaction_id, self::EPOS_TITLE);
            if($payment->getOption('is_show_qr_code') == 1){
                try {
                    $qrbase64 = $payment->getQrbase64($transaction_id, $amount);
                    $qrbase64 = json_decode($qrbase64);
                    $qr_code = $qrbase64->QrCodeBody;
                    $show_qr_code = 1;
                } catch (Exception $e) {
                    $log->write(t('[Exception] expresspay-front-check::actionIndex($transaction->id = %0) response = %1 exception %2',[$transaction_id, $qrbase64, $e]), LogExpressPay::LEVEL_INVOICES);
                }
            }
            $this->view->assign([
                'heading_title' => $heading_title,
                'content_body' => $content_body,
                'show_qr_code' => $show_qr_code,
                'qr_code' => $qr_code
            ]);
            $log->write(t('expresspay-front-check::actionIndex($transaction->id = %0) check epos content',[$transaction_id]), LogExpressPay::LEVEL_INVOICES);
            return $this->result->setTemplate('check.tpl');
        }
        else if($payment->getOption('payment_type') == 'card'){
            $log->write(t('expresspay-front-check::actionIndex($transaction->id = %0) redirect to payurl',[$transaction_id]), LogExpressPay::LEVEL_INVOICES);
            return $this->result->setSuccess(true)->setRedirect($transaction->getPayUrl());
        }
    }
}