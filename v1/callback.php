<?php
    
    $url = 'https://'.$_ENV['XENDIT_URL'].'/ewallets';
    $apiKey = 'xnd_development_O46JfOtygef9kMNsK+ZPGT+ZZ9b3ooF4w3Dn+R1k+2fT/7GlCAN3jg==:';
    $headers = [];
    $headers[] = 'Content-Type: application/json';

    $externalId = 'ovo-ewallet';
    $ewalletType = 'OVO';

    $urlWithParams = $url . '?external_id=' . $externalId . '&ewallet_type=' . $ewalletType;

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_USERPWD, $apiKey.":");
    curl_setopt($curl, CURLOPT_URL, $urlWithParams);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($curl);
    echo $result;