<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$DEVICE_URL = "https://188.247.84.45:6610/EcrWebInterface/EcrComInterface.svc";
$TID = "77777780";
$MID = "1111111140";
$MERCHANT_KEY = "0123456789ABCDEF0123456789ABCDEF";

$AMOUNT = $_GET['amount'] ?? 0;
$INVOICE_NUMBER = $_GET['invoice_no'] ?? 'INV_' . time();
$REFERENCE_NUMBER = $_GET['reference_no'] ?? 'REF_' . time();

$soapRequest = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:tem="http://tempuri.org/" 
                  xmlns:ns="http://schemas.datacontract.org/2004/07/">
   <soapenv:Header/>
   <soapenv:Body>
      <tem:Sale>
         <tem:webReq>
            <ns:Config>
               <ns:EcrCurrencyCode>400</ns:EcrCurrencyCode>
               <ns:MerchantSecureKey>' . $MERCHANT_KEY . '</ns:MerchantSecureKey>
               <ns:Mid>' . $MID . '</ns:Mid>
               <ns:Tid>' . $TID . '</ns:Tid>
               <ns:EcrTillerUserName>flan</ns:EcrTillerUserName>
               <ns:EcrTillerFullName>Flan Flany</ns:EcrTillerFullName>
            </ns:Config>
            <ns:EcrAmount>' . number_format($AMOUNT, 3, '.', '') . '</ns:EcrAmount>
            <ns:Printer>
               <ns:EnablePrintPosReceipt>1</ns:EnablePrintPosReceipt>
               <ns:EnablePrintReceiptNote>0</ns:EnablePrintReceiptNote>
               <ns:InvoiceNumber>' . $INVOICE_NUMBER . '</ns:InvoiceNumber>
               <ns:PrinterWidth>40</ns:PrinterWidth>
               <ns:ReferenceNumber>' . $REFERENCE_NUMBER . '</ns:ReferenceNumber>
            </ns:Printer>
            <ns:TransactionType>SALE</ns:TransactionType>
         </tem:webReq>
      </tem:Sale>
   </soapenv:Body>
</soapenv:Envelope>';

$ch = curl_init($DEVICE_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $soapRequest,
    CURLOPT_HTTPHEADER => [
        "Content-Type: text/xml; charset=utf-8",
        "SOAPAction: http://tempuri.org/IEcrComInterface/Sale",
        "Content-Length: " . strlen($soapRequest)
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 180, // انتظر حتى 3 دقائق
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_TCP_NODELAY => true,
    CURLOPT_ENCODING => 'gzip'
]);

$start = microtime(true);
$response = curl_exec($ch);
$duration = round(microtime(true) - $start, 2);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch);
    curl_close($ch);
    exit;
}
curl_close($ch);

echo "=== HTTP Status === $httpCode\n";
echo "⏱️ زمن الاستجابة: {$duration} ثانية\n";

$response = trim($response);

if (stripos($response, "Canceled by User") !== false) {
    echo "⚠️ تم إلغاء العملية من قبل المستخدم!";
} elseif (stripos($response, "SaleSuccess") !== false) {
    echo "✅ تمت العملية بنجاح!";
} else {
    echo "❌ حدث خطأ، الرد: $response";
}

file_put_contents('response_log.xml', $response);
?>
