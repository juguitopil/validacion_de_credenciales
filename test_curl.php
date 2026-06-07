<?php
$ch = curl_init('https://perfil.uagrm.edu.bo/estudiantes/default.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$resp = curl_exec($ch);
echo "HTTP Code: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "<br>";
echo "Error: " . curl_error($ch) . "<br>";
echo "Respuesta (500 chars): " . substr($resp, 0, 500);
curl_close($ch);