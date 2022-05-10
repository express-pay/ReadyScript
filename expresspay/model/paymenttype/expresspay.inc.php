<?php
/**
 * @package       ExpressPay Payment Module for ReadyScript
 * @author        ООО "ТриИнком" <info@express-pay.by>
 * @copyright     (c) 2022 Экспресс Платежи. Все права защищены.
 */
namespace ExpressPay\Model\PaymentType;

use RS\Orm\FormObject;
use \RS\Orm\Type;
use \Shop\Model\ChangeTransaction;
use \Shop\Model\Orm\Transaction;
use \ExpressPay\Model\Log\LogExpressPay;

/**
 * 
 * Способ оплаты - ExpressPay
 * 
 */
class ExpressPay extends \Shop\Model\PaymentType\AbstractType
{
    const
        API_URL = "https://api.express-pay.by/v1/",  
        SANDBOX_URL = "https://sandbox-api.express-pay.by/v1/";

    private $log;

    public function __construct()
    {
        //parent::__construct();
        $this->$log = LogExpressPay::getInstance();
    }

    /**
     * 
     * Возвращает название расчетного модуля (типа доставки)
     * 
     * @return string
     * 
     */
    public function getTitle()
    {
        return t('Экспресс платежи');
    }
    
    /**
     * 
     * Возвращает описание типа оплаты. Возможен HTML
     * 
     * @return string
     * 
     */
    public function getDescription()
    {
        $currency = \Catalog\Model\CurrencyApi::getCurrecyCode();
        if ($currency != 'BYN') //Проверим валюту
            return t('Сервис <a href="https://express-pay.by/">«Экспресс Платежи»</a> принимает платежи только <b>BYN (белорусский рубль)</b>.</BR>
            Для корректной работы модуля необходимо изменить валюту по умолчанию на BYN. Текущая валюта по умолчанию %0.', [$currency]);
        else return t('Приём платежей через сервис <a href="https://express-pay.by/">«Экспресс Платежи»</a>');
    }
    
    /**
     * 
     * Возвращает идентификатор данного типа оплаты. (только англ. буквы)
     * 
     * @return string
     * 
     */
    public function getShortName()
    {
        return 'expresspay';
    }
    
    /**
     * 
     * Возвращает true, если данный тип поддерживает проведение платежа через интернет
     * 
     * @return bool
     * 
     */
    public function canOnlinePay()
    {
        return true;
    }

    /**
     * 
     * Отправка данных с помощью POST?
     * 
     */
    public function isPostQuery()
    {
        return true;
    }
    
    /**
     * 
     * Возвращает ORM объект для генерации формы или null
     * 
     * @return \RS\Orm\FormObject | null
     * 
     */
    public function getFormObject()
    {
        $properties = new \RS\Orm\PropertyIterator([
            'is_test_mode' => new Type\Integer([
                'description' => t('Использовать тестовый режим?'),
                'maxLength' => 1,
                'default' => 0,
                'CheckboxView' => [1,0]
            ]),
            'token' => new Type\Varchar([
                'description' => t('Токен'),
                'hint' => t('API-ключ производителя услуг')
                
            ]),
            'service_id' => new Type\Varchar([
                'description' => t('Номер услуги'),
                'hint' => t('Номер услуги в системе express-pay.by')
                
            ]),
            'secret_word' => new Type\Varchar([
                'description' => t('Секретное слово для подписи счетов'),
                'hint' => t('Секретное слово для формирования цифровой подписи для подписи счетов')
            ]),
            'payment_type' => new Type\Varchar(array(
                'description' => t('Тип метода оплаты'),
                'listFromArray' => array(array(
                    'erip' => t('ЕРИП'),
                    'epos' => t('E-POS'),
                    'card' => t('Интернет-эквайринг'),
                ))
            )),

            // ЕРИП и E-POS
            'is_show_qr_code' => new Type\Integer([
                'description' => t('Показывать QR код для оплаты'),
                'maxLength' => 1,
                'default' => 0,
                'CheckboxView' => [1,0]
            ]),
            'is_name_editable' => new Type\Integer([
                'description' => t('Разрешено изменять ФИО'),
                'maxLength' => 1,
                'default' => 0,
                'CheckboxView' => [1,0]
            ]),
            'is_amount_editable' => new Type\Integer([
                'description' => t('Разрешено изменять сумму'),
                'maxLength' => 1,
                'default' => 0,
                'CheckboxView' => [1,0]
            ]),
            'is_address_editable' => new Type\Integer([
                'description' => t('Разрешено изменять адрес'),
                'maxLength' => 1,
                'default' => 0,
                'CheckboxView' => [1,0]
            ]),
            //--
            
            // ЕРИП
            'path_in_erip' => new Type\Varchar([
                'description' => t('Путь к ЕРИП'),
                'hint' => t('Путь по веткам ЕРИП для осуществления оплаты')
            ]),
            //--

            // E-POS
            'service_provider_id' => new Type\Varchar([
                'description' => t('Код производителя услуг'),
                'hint' => t('Код производителя услуг в системе express-pay.by')
            ]),
            'epos_service_id' => new Type\Varchar([
                'description' => t('Код услуги E-POS'),
                'hint' => t('Код услуги E-POS в системе express-pay.by')
            ]),
            //--

            '__help__' => new Type\MixedType([
                'description' => t(''),
                'visible' => true,  
                'template' => '%expresspay%/form/payment/expresspay/help.tpl'
            ]),
        ]);
        
        $form_object = new FormObject($properties);
        $form_object->setParentObject($this);
        $form_object->setParentParamMethod('Form');
        return $form_object;
    }
    
    /**
     * 
     * Возвращает URL для перехода на сайт сервиса оплаты
     * 
     * @param Transaction $transaction - ORM объект транзакции
     * 
     * @return string
     * 
     */	
    public function getPayUrl(\Shop\Model\Orm\Transaction $transaction)
    {
        try
        {
            $currency = \Catalog\Model\CurrencyApi::getCurrecyCode();
            if ($currency != 'BYN'){ //Проверим валюту
                $this->$log->write(t('[ERROR] ExpressPay::getPayUrl (Сервис «Экспресс Платежи» принимает платежи только BYN (белорусский рубль).
                Для корректной работы модуля необходимо изменить валюту по умолчанию на BYN. Текущая валюта по умолчанию %0)',[$currency]), LogExpressPay::LEVEL_INVOICES);
                return \RS\Router\Manager::obj()->getUrl('expresspay-front-error', ['transaction_id' => $transaction->id]);
            }

            //Получим текущий заказ
            $order = $transaction->getOrder();
            if (isset($order['order_num']) && $order['order_num']){
                $orderId = $order['order_num']; 
            }else{
                $orderId = $transaction->id; 
            }

            $amount = round($transaction->cost, 2); //Сумма заказа

            $signatureParams['Token'] = $this->getOption('token');
            $signatureParams['ServiceId'] = $this->getOption('service_id');
            $signatureParams['AccountNo'] = $transaction->id;
            $signatureParams['Amount'] = $amount;
            $signatureParams['Currency'] = '933';
            $signatureParams['Info'] = $transaction->reason;
            $signatureParams['ReturnType'] = 'redirect';
            $signatureParams['FailUrl'] = $this->getFailUrl($transaction->id);
            //$signatureParams["ReturnInvoiceUrl"] = "1";

            if($this->getOption('payment_type') == 'erip')
            {
                $user = $transaction->getUser(); //Получим текущего пользователя
                $signatureParams['Surname'] = $user['surname'];
                $signatureParams['Firstname'] = $user['name'];
                $signatureParams['Patronymic'] = $user['midname'];
                $signatureParams['EmailNotification'] = $user['e_mail'];
                $signatureParams['SmsPhone'] = $user['phone'];
                
                $signatureParams['IsNameEditable'] = $this->getOption('is_name_editable');
                $signatureParams['IsAddressEditable'] = $this->getOption('is_amount_editable');
                $signatureParams['IsAmountEditable'] = $this->getOption('is_address_editable');

                $signatureParams['ReturnUrl'] = \RS\Router\Manager::obj()->getUrl('expresspay-front-check', ['transaction_id' => $transaction->id], true);
                $signatureParams['Signature'] = self::computeSignature($signatureParams, $this->getOption('secret_word'), 'add-web-invoice');
                $link = $this->generateLink('/web_invoices');
            }
            else if($this->getOption('payment_type') == 'epos')
            {
                $user = $transaction->getUser(); //Получим текущего пользователя
                $signatureParams['Surname'] = $user['surname'];
                $signatureParams['Firstname'] = $user['name'];
                $signatureParams['Patronymic'] = $user['midname'];
                $signatureParams['Email'] = $user['e_mail'];
                $signatureParams['SmsPhone'] = $user['phone'];
                
                $signatureParams['IsNameEditable'] = $this->getOption('is_name_editable');
                $signatureParams['IsAddressEditable'] = $this->getOption('is_amount_editable');
                $signatureParams['IsAmountEditable'] = $this->getOption('is_address_editable');

                $signatureParams['ReturnUrl'] = \RS\Router\Manager::obj()->getUrl('expresspay-front-check', ['transaction_id' => $transaction->id], true);
                $signatureParams['Signature'] = self::computeSignature($signatureParams, $this->getOption('secret_word'), 'add-web-invoice');
                $link = $this->generateLink('/web_invoices');
            }
            else if($this->getOption('payment_type') == 'card')
            {
                $signatureParams['ReturnUrl'] = $this->getSuccessUrl($transaction->id);
                $signatureParams['Signature'] = self::computeSignature($signatureParams, $this->getOption('secret_word'), 'add-webcard-invoice');
                $link = $this->generateLink('/web_invoices');
            }
            unset($signatureParams['Token']);

            $this->addPostParams($signatureParams);
            $this->$log->write(t('ExpressPay::getPayUrl($transaction->id = %0) return: %1',[$transaction->id, $link]), LogExpressPay::LEVEL_INVOICES);
            $this->$log->write(t('ExpressPay::getPayUrl($transaction->id = %0) PostParams: %1',[$transaction->id, json_encode($signatureParams, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]), LogExpressPay::LEVEL_INVOICES);
            return $link;
        } catch (Exception $e) {
            $this->$log->write(t('[Exception] ExpressPay::getPayUrl($transaction->id = %0)',[$transaction->id]), LogExpressPay::LEVEL_INVOICES);
            throw $e;
        }
    }

    /**
     * 
     * Возвращает ID заказа исходя из REQUEST-параметров
     * 
     * @param \RS\Http\Request $request
     * 
     * @return bool
     * 
     */
    public function getTransactionIdFromRequest(\RS\Http\Request $request)
    {
        $this->$log->write(t('ExpressPay::getTransactionIdFromRequest (Act = %0)', [$request->request('Act', TYPE_STRING)]), LogExpressPay::LEVEL_NOTIFICATIONS);
        if($request->request('Act', TYPE_STRING) == 'result'){
            if ($request->getMethod() === 'POST') {
                $dataJSON = htmlspecialchars_decode($request->post('Data', TYPE_STRING, ''));
                $signature = $request->post('Signature', TYPE_STRING, '');
    
                $config = \RS\Config\Loader::byModule($this);
                $useSignatureForNotification = $config['is_use_signature_for_notification'] ? true : false;
                $secretWordForNotification = $config['secret_word_for_notification'];
    
                if ($useSignatureForNotification || self::computeSignature(array("data" => $dataJSON), $secretWordForNotification, 'notification') == $signature) {
                    try {
                        $data = json_decode($dataJSON, true); 
                    }
                    catch(Exception $e) {
                        $exception = new \Shop\Model\PaymentType\ResultException(t('FAILED | Failed to decode data'));
                        $exception->setResponse('FAILED | Failed to decode data');
                        $this->$log->write(t('[Exception] ExpressPay::getTransactionIdFromRequest: Failed to decode data (dataJSON = %0)', [$dataJSON]), LogExpressPay::LEVEL_NOTIFICATIONS);
                        throw $exception;
                    }
                    $accountNo = $data['AccountNo'];
                    $this->$log->write(t('ExpressPay::getTransactionIdFromRequest result (AccountNo = %0)', [$accountNo]), LogExpressPay::LEVEL_NOTIFICATIONS);
                    return $accountNo;
                }
                else { 
                    $exception = new \Shop\Model\PaymentType\ResultException(t('FAILED | Access is denied'));
                    $exception->setResponse('FAILED | Access is denied');
                    $this->$log->write(t('ExpressPay::getTransactionIdFromRequest: Access is denied (RequestSignature = %0) != (Signature = %1)', 
                                        [$signature, self::computeSignature(array("data" => $dataJSON), $secretWordForNotification, 'notification')]), LogExpressPay::LEVEL_NOTIFICATIONS);
                    throw $exception;
                }
            }
            else{
                $exception = new \Shop\Model\PaymentType\ResultException(t('FAILED | request method not supported'));
                $exception->setResponse('FAILED | request method not supported');
                throw $exception;
            }
        }
        return $request->request('TransactionId', TYPE_STRING);
    }

    /**
     * 
     * Обработка запросов от ExpressPay
     * 
     * @param \Shop\Model\Orm\Transaction $transaction - объект транзакции
     * @param \RS\Http\Request $request - объект запросов
     * 
     * @return string
     */
    public function onResult(\Shop\Model\Orm\Transaction $transaction, \RS\Http\Request $request)
    {
        $dataJSON = htmlspecialchars_decode($request->post('Data', TYPE_STRING, ''));
        
        // Преобразование из json в array
        $data = array();
        try {
            $data = json_decode($dataJSON, true); 
        }
        catch(Exception $e) {
            $exception = new \Shop\Model\PaymentType\ResultException(t('FAILED | Failed to decode data'));
            $exception->setResponse('FAILED | Failed to decode data');
            $exception->setUpdateTransaction(false);
            $this->$log->write(t('[Exception] ExpressPay::onResult: Failed to decode data (dataJSON = %0)', [$dataJSON]), LogExpressPay::LEVEL_NOTIFICATIONS);
            throw $exception;
        }

        $accountNo = $data['AccountNo'];
        if(isset($accountNo)){
            $cmdtype    = $data['CmdType'];
            $status     = $data['Status'];
            $amount     = $data['Amount'];

            $this->$log->write(t('ExpressPay::onResult($transaction->id = %0) PostParams: %1',[$transaction->id, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]), LogExpressPay::LEVEL_NOTIFICATIONS);
            switch ($cmdtype) {
                case 1:
                    $change = new ChangeTransaction($transaction);
                    $change->setResponse(t('OK | the notice is processed'))
                        ->setChangelog(t('Поступил новый платеж'));
                    return $change;
                case 2:
                    $change = new ChangeTransaction($transaction);
                    $change->setNewStatus($transaction::STATUS_FAIL)
                        ->setResponse(t('OK | the notice is processed'))
                        ->setChangelog(t('Платёж отменён'));
                    return $change;
                case 3:
                    if(isset($status)){
                        switch($status){
                            case 1: // Ожидает оплату
                                if( $transaction['status'] != \Shop\Model\Orm\Transaction::STATUS_SUCCESS){
                                    $change = new ChangeTransaction($transaction);
                                    $change->setNewStatus($transaction::STATUS_NEW)
                                        ->setResponse(t('OK | the notice is processed'))
                                        ->setChangelog(t('Платёж создан в системе'));
                                    return $change;
                                }
                                break;
                            case 2: // Просрочен
                                $change = new ChangeTransaction($transaction);
                                $change->setNewStatus($transaction::STATUS_FAIL)
                                    ->setResponse(t('OK | the notice is processed'))
                                    ->setChangelog(t('Платёж просрочен'));
                                return $change;
                            case 3: // Оплачен
                            case 6: // Оплачен с помощью банковской карты
                                return 'OK | the notice is processed';
                            case 4: // Оплачен частично
                                $change = new ChangeTransaction($transaction);
                                $change->setResponse(t('OK | the notice is processed'))
                                    ->setChangelog(t('Платёж оплачен частично'));
                                return $change;
                            case 5: // Отменён
                                $change = new ChangeTransaction($transaction);
                                $change->setNewStatus($transaction::STATUS_FAIL)
                                    ->setResponse(t('OK | the notice is processed'))
                                    ->setChangelog(t('Платёж отменён'));
                                return $change;
                            case 7: // Платеж возращен
                                $change = new ChangeTransaction($transaction);
                                $change->setNewStatus($transaction::STATUS_FAIL)
                                    ->setResponse(t('OK | the notice is processed'))
                                    ->setChangelog(t('Платёж возращен'));
                                return $change;
                            default:
                                $exception = new \Shop\Model\PaymentType\ResultException(t('FAILED | invalid status'));
                                $exception->setResponse('FAILED | invalid status');
                                $exception->setUpdateTransaction(false);
                                throw $exception;
                        }
                    }
                    break;
                default:
                    $exception = new \Shop\Model\PaymentType\ResultException(t('FAILED | invalid cmdtype'));
                    $exception->setResponse('FAILED | invalid cmdtype');
                    $exception->setUpdateTransaction(false);
                    throw $exception;
                }
        }
        $exception = new \Shop\Model\PaymentType\ResultException(t('FAILED | The notice is not processed'));
        $exception->setResponse('FAILED | The notice is not processed');
        $exception->setUpdateTransaction(false);
        throw $exception;
    }

    /**
     * 
     * Вызывается при переходе на страницу успеха, после совершения платежа
     *
     * @param Transaction $transaction
     * @param HttpRequest $request
     * 
     * @return void
     * @throws RSException
     * 
     */
    public function onSuccess(\Shop\Model\Orm\Transaction $transaction, \RS\Http\Request $request)
    {
        try{
            $change = new ChangeTransaction($transaction);
            $change->setChangelog(t('Клиент вернулся на страницу успешной оплаты'));
            $change->applyChanges();
        }
        catch(Exception $e) {
            $this->$log->write(t('[Exception] ExpressPay::onSuccess: Failed to decode data (Exception = %0)', [$e->getMessage()]), LogExpressPay::LEVEL_NOTIFICATIONS);
            throw $exception;
        }
    }

    /**
     * 
     * Вызывается при открытии страницы неуспешного проведения платежа 
     * Используется только для Online-платежей
     * 
     * @param \Shop\Model\Orm\Transaction $transaction
     * @param \RS\Http\Request $request
     *     
     * @return void
     * 
     */
    public function onFail(\Shop\Model\Orm\Transaction $transaction, \RS\Http\Request $request)
    {
        try{
            $change = new ChangeTransaction($transaction);
            $change->setNewStatus($transaction::STATUS_FAIL)
                ->setChangelog(t('Клиент вернулся на страницу с ошибкой'));
            $change->applyChanges();
        }
        catch(Exception $e) {
            $this->$log->write(t('[Exception] ExpressPay::onFail: Failed to decode data (Exception = %0)', [$e->getMessage()]), LogExpressPay::LEVEL_NOTIFICATIONS);
            throw $exception;
        }
    }

    /**
     * 
     * Генерация QR-кода для лицевого счета
     * 
     * @param int       $accountNumber Номер лицевого счёта
     * @param float     $amount Сумма платежа
     * 
     * @return string $json (QrCodeBody) Возвращает изображение в формате base64
     * 
     */
    public function getQrbase64($accountNumber, $amount)
    {
        $signatureParams = array(
            "Token" => $this->getOption('Token'),
            "Amount" => $amount,
            "AccountNumber" => $accountNumber,
            "ViewType" => "base64",
            "ImageWidth" => "",
            "ImageHeight" => ""
        );
        $signatureParams['Signature'] = self::computeSignature($signatureParams, $this->getOption('secret_word'), 'get-qr-code-by-accountnumber');
        unset($signatureParams['Token']);
        return self::sendRequest(self::generateLink('/qrcode/getqrcodebyaccountnumber') . http_build_query($signatureParams));
    }

    /**
     * 
     * Возвращает URL неуспешно проведённого платежа
     * 
     * @param int       $transactionId Номер транзакции
     * 
     */
    public function getFailUrl($transactionId)
    {
        return \RS\Router\Manager::obj()->getUrl('shop-front-onlinepay', [Act=>fail, PaymentType=>self::getShortName()], true)."?TransactionId=".$transactionId;
    }
    
    /**
     * 
     * Возвращает URL успешной оплаты
     * 
     * @param int       $transactionId Номер транзакции
     * 
     */
    public function getSuccessUrl($transactionId)
    {
        return \RS\Router\Manager::obj()->getUrl('shop-front-onlinepay', [Act=>success, PaymentType=>self::getShortName()], true)."?TransactionId=".$transactionId;
    }

    //////////////////////////////////////////////////////////////////////
    //----------------------[ Private method ]----------------------------

    /**
	 *
	 * Возвращает составленную ссылку
	 *
	 */
	private function generateLink($operation, $params = [])
	{
        $config = \RS\Config\Loader::byModule($this);
        if(intval($this->getOption('is_test_mode')) == 1){
            $url = trim($config['sandbox_url']) ? $config['sandbox_url'] : self::SANDBOX_URL;
        }
        else{
            $url = trim($config['api_url']) ? $config['api_url'] : self::API_URL;
        }
        
		return $url."/".$operation."?". http_build_query($params);
	}

    /**
	 *
	 * Выполняет GET запрос
	 *
     * @param string    $url        Удаленный адрес
     * 
     * @return string   $response   Результат выполнения GET запроса
     * 
	 */
    private function sendRequest($url) 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * 
     * Формирование цифровой подписи
     * 
     * @param array  $signatureParams Список передаваемых параметров
     * @param string $secretWord      Секретное слово
     * @param string $method          Метод формирования цифровой подписи
     * 
     * @return string $hash           Сформированная цифровая подпись
     * 
     */
    private static function computeSignature($signatureParams, $secretWord, $method)
    {
        $normalizedParams = array_change_key_case($signatureParams, CASE_LOWER);
        $mapping = array(
            "get-qr-code"          => array(
                "token",
                "invoiceid",
                "viewtype",
                "imagewidth",
                "imageheight"
            ),
            "get-qr-code-by-accountnumber"  => array(
                "token",
                "accountnumber",
                "amount",
                "viewtype",
                "imagewidth",
                "imageheight"
            ),
            "add-web-invoice"      => array(
                "token",
                "serviceid",
                "accountno",
                "amount",
                "currency",
                "expiration",
                "info",
                "surname",
                "firstname",
                "patronymic",
                "city",
                "street",
                "house",
                "building",
                "apartment",
                "isnameeditable",
                "isaddresseditable",
                "isamounteditable",
                "emailnotification",
                "smsphone",
                "returntype",
                "returnurl",
                "failurl",
                "returninvoiceurl"
            ),
            "add-webcard-invoice" => array(
                "token",
                "serviceid",
                "accountno",
                "expiration",
                "amount",
                "currency",
                "info",
                "returnurl",
                "failurl",
                "language",
                "sessiontimeoutsecs",
                "expirationdate",
                "returntype",
                "returninvoiceurl"
            ),
            "notification"         => array(
                "data"
            )
        );
        $apiMethod = $mapping[$method];
        $result = "";
        foreach ($apiMethod as $item) {
            $result .= (isset($normalizedParams[$item])) ? $normalizedParams[$item] : '';
        }
        $hash = strtoupper(hash_hmac('sha1', $result, $secretWord));
        return $hash;
    }
}