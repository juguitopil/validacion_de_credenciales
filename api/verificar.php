<?php
// verificar.php
// Llama al robot Puppeteer en Render.
// Render ejecuta Chrome real y verifica las credenciales en el portal UAGRM.

define('RENDER_URL', 'https://un-ultimo-intento-por-valeri.onrender.com');

function verificarCredencialesUAGRM(string $registro, string $password, array &$debug = []): bool {

    $endpoint = RENDER_URL . '/api/verificar';
    $debug['render_endpoint'] = $endpoint;

    $payload = json_encode([
        'username' => $registro,
        'password' => $password,
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $endpoint,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $debug['render_http_code']  = $httpCode;
    $debug['render_curl_error'] = $curlError;
    $debug['render_response']   = $response;

    if ($curlError || $httpCode === 0) {
        $debug['resultado'] = 'ERROR: no se pudo conectar a Render: ' . $curlError;
        return false;
    }

    $data = json_decode($response, true);
    $debug['render_data'] = $data;

    if (!is_array($data)) {
        $debug['resultado'] = 'ERROR: respuesta invalida de Render';
        return false;
    }

    $valid = isset($data['valid']) && $data['valid'] === true;
    $debug['resultado'] = $valid
        ? 'VALIDO: ' . ($data['reason'] ?? 'robot confirmo login')
        : 'INVALIDO: ' . ($data['reason'] ?? $data['error'] ?? 'robot rechazo login');

    return $valid;
}