<h2 style="margin-top:25px;">{$heading_title}</h2>
<table style="width: 100%;text-align: left;">
    <tbody>
        <tr>
            <td valign="top" style="text-align:left;">
            {$content_body}
            </td>
            {if $show_qr_code == 1}
            <td style="text-align: center;padding: 40px 20px 0 0;vertical-align: middle">
                <p><img src="data:image/jpeg;base64,{$qr_code}"  width="150" height="150"/></p>
                <div style="padding:0 15px"><b>Отсканируйте QR-код для оплаты</b></div>
            </td>
            {/if}
        </tr>
    </tbody>
</table>