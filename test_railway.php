<?php
$ch = curl_init('https://unultimointentoporvaleri-production-5064.up.railway.app/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$r = curl_exec($ch);
echo "Code: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "<br>";
echo "Error: " . curl_error($ch) . "<br>";
echo "Body: " . $r;
curl_close($ch);