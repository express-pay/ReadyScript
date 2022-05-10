<h3>Данные для настройки уведомлений о статусе платежа, задаются в <a target="_blank" href='https://client.express-pay.by'>личном кабинете:</a></h3>
<p>URL для уведомлений: <b>{$router->getUrl('shop-front-onlinepay', [Act=>result, PaymentType=>$payment_type->getShortName()], true)}</b></p>
{$config = \RS\Config\Loader::byModule($payment_type)}
<p>Применять цифровую подпись: <b>{if $config['is_use_signature_for_notification'] == 1}Да{else}Нет{/if}</b></p>
<p>Секретное слово: <b>{$config['secret_word_for_notification']}</b> <a href='{$router->getAdminUrl('edit', ['mod' => $payment_type->getShortName()], 'modcontrol-control')}'>Изменить</a></p>

<script type="text/javascript">
    showCurrentSection();

    jQuery("input[name='data[is_test_mode]']").change(changeTestMode);
    jQuery("select[name='data[payment_type]']").change(showCurrentSection);

    function changeTestMode() {
        if (jQuery("input[name='data[is_test_mode]']").is(":checked")) {
            let selected_value = jQuery("select[name='data[payment_type]']").val();
            if(selected_value == 'card'){
                jQuery("input[name='data[token]']").val("a75b74cbcfe446509e8ee874f421bd68");
                jQuery("input[name='data[service_id]']").val("6");
                jQuery("input[name='data[secret_word]']").val("sandbox.expresspay.by");
            }
            else{
                jQuery("input[name='data[token]']").val("a75b74cbcfe446509e8ee874f421bd66");
                jQuery("input[name='data[service_id]']").val("4");
                jQuery("input[name='data[secret_word]']").val("sandbox.expresspay.by");
            }
        }
        else{
            jQuery("input[name='data[token]']").val("");
            jQuery("input[name='data[service_id]']").val("");
            jQuery("input[name='data[secret_word]']").val("");
        }
    }

    function showCurrentSection() {
        let selected_value = jQuery("select[name='data[payment_type]']").val();
        if (selected_value == 'erip'){
            jQuery("input[name='data[is_show_qr_code]']").parent().parent().show(400);
            jQuery("input[name='data[is_name_editable]']").parent().parent().show(400);
            jQuery("input[name='data[is_amount_editable]']").parent().parent().show(400);
            jQuery("input[name='data[is_address_editable]']").parent().parent().show(400);
            jQuery("input[name='data[path_in_erip]']").parent().parent().show(400);
            jQuery("input[name='data[service_provider_id]']").parent().parent().hide(400);
            jQuery("input[name='data[epos_service_id]']").parent().parent().hide(400);
        }
        else if (selected_value == 'epos'){
            jQuery("input[name='data[is_show_qr_code]']").parent().parent().show(400);
            jQuery("input[name='data[is_name_editable]']").parent().parent().show(400);
            jQuery("input[name='data[is_amount_editable]']").parent().parent().show(400);
            jQuery("input[name='data[is_address_editable]']").parent().parent().show(400);
            jQuery("input[name='data[path_in_erip]']").parent().parent().hide(400);
            jQuery("input[name='data[service_provider_id]']").parent().parent().show(400);
            jQuery("input[name='data[epos_service_id]']").parent().parent().show(400);
        }
        else{
            jQuery("input[name='data[is_show_qr_code]']").parent().parent().hide(400);
            jQuery("input[name='data[is_name_editable]']").parent().parent().hide(400);
            jQuery("input[name='data[is_amount_editable]']").parent().parent().hide(400);
            jQuery("input[name='data[is_address_editable]']").parent().parent().hide(400);
            jQuery("input[name='data[path_in_erip]']").parent().parent().hide(400);
            jQuery("input[name='data[service_provider_id]']").parent().parent().hide(400);
            jQuery("input[name='data[epos_service_id]']").parent().parent().hide(400);
        }
        changeTestMode();
    }
</script>