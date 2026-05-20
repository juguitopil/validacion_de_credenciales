<?php
// ============================================================
//  verificar.php — Robot de validación con cURL
//  Endpoint confirmado por DevTools:
//    URL AJAX: https://perfil.uagrm.edu.bo/estudiantes/verif_est.php
//    Campos:   username = registro
//              password = substr(md5($password), 0, 8)
// ============================================================

function verificarCredencialesUAGRM(string $registro, string $password, array &$debug = []): bool {

    $urlAjax = 'https://perfil.uagrm.edu.bo/estudiantes/verif_est.php';
    $urlBase = 'https://perfil.uagrm.edu.bo/estudiantes/default.php';

    $passwordHash = substr(md5($password), 0, 8);
    $debug['password_hash_generado'] = $passwordHash;

    $cookieFile = sys_get_temp_dir() . '/uagrm_' . md5($registro . microtime()) . '.txt';

    // ── Paso 1a: GET sin seguir redirecciones para capturar PHPSESSID inicial ──
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $urlBase,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,  // ← NO seguir redirecciones
        CURLOPT_HEADER         => true,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9',
            'Connection: keep-alive',
        ],
    ]);
    $resp1       = curl_exec($ch);
    $code1       = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hSize1      = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $debug['paso1a_http_code'] = $code1;
    $headers1 = substr($resp1, 0, $hSize1);

    // Extraer PHPSESSID del primer Set-Cookie
    $sessionId = '';
    if (preg_match('/Set-Cookie:\s*PHPSESSID=([^;]+)/i', $headers1, $sm)) {
        $sessionId = $sm[1];
    }
    $debug['paso1a_session_id'] = $sessionId ?: '(no encontrado en paso 1a)';

    // ── Paso 1b: Si hubo redirección, seguirla para que la sesión quede activa ──
    if (in_array($code1, [301, 302]) && $sessionId) {
        preg_match('/Location:\s*(.+)/i', $headers1, $loc);
        $nextUrl = trim($loc[1] ?? $urlBase);
        if (!str_starts_with($nextUrl, 'http')) {
            $nextUrl = 'https://perfil.uagrm.edu.bo' . $nextUrl;
        }

        $ch1b = curl_init();
        curl_setopt_array($ch1b, [
            CURLOPT_URL            => $nextUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER         => false,
            CURLOPT_COOKIEJAR      => $cookieFile,
            CURLOPT_COOKIEFILE     => $cookieFile,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Cookie: PHPSESSID=' . $sessionId,
                'Referer: ' . $urlBase,
            ],
        ]);
        curl_exec($ch1b);
        $debug['paso1b_http_code'] = curl_getinfo($ch1b, CURLINFO_HTTP_CODE);
        curl_close($ch1b);
    }

    // Si no obtuvimos sesión de la redirección, intentar GET directo con FOLLOWLOCATION
    if (!$sessionId) {
        $ch1c = curl_init();
        curl_setopt_array($ch1c, [
            CURLOPT_URL            => $urlBase,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER         => false,
            CURLOPT_COOKIEJAR      => $cookieFile,
            CURLOPT_COOKIEFILE     => $cookieFile,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36',
        ]);
        curl_exec($ch1c);
        curl_close($ch1c);
        $debug['paso1c_fallback'] = 'GET con FOLLOWLOCATION ejecutado';

        // Leer el PHPSESSID del archivo de cookies que cURL escribió
        if (file_exists($cookieFile)) {
            $cookieContent = file_get_contents($cookieFile);
            if (preg_match('/PHPSESSID\s+(\S+)/i', $cookieContent, $cm)) {
                $sessionId = $cm[1];
                $debug['paso1c_session_from_file'] = $sessionId;
            }
        }
    }

    $debug['session_id_final'] = $sessionId ?: '(sin sesión — el POST puede fallar)';

    // ── Paso 2: POST al endpoint AJAX ──
    $postFields = http_build_query([
        'username' => $registro,
        'password' => $passwordHash,
    ]);

    $headers2 = [
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With: XMLHttpRequest',
        'Accept: */*',
        'Accept-Language: es-ES,es;q=0.9',
        'Referer: ' . $urlBase,
        'Origin: https://perfil.uagrm.edu.bo',
        'Connection: keep-alive',
    ];
    if ($sessionId) {
        $headers2[] = 'Cookie: PHPSESSID=' . $sessionId;
    }

    $ch2 = curl_init();
    curl_setopt_array($ch2, [
        CURLOPT_URL            => $urlAjax,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER         => true,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => $headers2,
    ]);

    $resp2      = curl_exec($ch2);
    $code2      = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    $hSize2     = curl_getinfo($ch2, CURLINFO_HEADER_SIZE);
    $curlErr    = curl_error($ch2);
    curl_close($ch2);

    @unlink($cookieFile);

    $debug['paso2_http_code']  = $code2;
    $debug['paso2_curl_error'] = $curlErr;

    if ($resp2 === false) {
        $debug['resultado'] = 'ERROR: cURL falló en el POST';
        return false;
    }

    $headers2resp = substr($resp2, 0, $hSize2);
    $body         = trim(substr($resp2, $hSize2));
    $bodyLower    = strtolower($body);

    $debug['paso2_headers']      = $headers2resp;
    $debug['paso2_body_raw']     = $body;
    $debug['paso2_body_preview'] = substr($body, 0, 300);
    $debug['respuesta_tipo']     = 'texto';

    // ── Paso 3: Interpretar respuesta ──

    if ($code2 === 401) {
        $debug['resultado'] = 'ERROR 401: sesión aún no reconocida';
        return false;
    }

    // Redirección
    if (in_array($code2, [301, 302])) {
        preg_match('/Location:\s*(.+)/i', $headers2resp, $loc);
        $redir = trim($loc[1] ?? '');
        $debug['redirect_url'] = $redir;
        if (stripos($redir, 'default.php') !== false || stripos($redir, 'login') !== false) {
            $debug['resultado'] = 'INVÁLIDO: redirigió al login';
            return false;
        }
        $debug['resultado'] = 'VÁLIDO: redirigió a ' . $redir;
        return true;
    }

    // JSON
    $json = json_decode($body, true);
    if ($json !== null) {
        $debug['respuesta_tipo'] = 'JSON';
        if (!empty($json['success']) || !empty($json['ok']) || !empty($json['valid'])) {
            $debug['resultado'] = 'VÁLIDO: JSON éxito';
            return true;
        }
        $debug['resultado'] = 'INVÁLIDO: JSON con error';
        return false;
    }

    // Texto: el portal devuelve "Error: ..." para cualquier fallo
    if (stripos($body, 'Error:') !== false || stripos($body, 'bloqueada') !== false) {
        $debug['resultado'] = 'INVÁLIDO: "' . $body . '"';
        return false;
    }

    // HTTP 200 sin "Error:" → válido
    if ($code2 === 200 && strlen($body) > 0) {
        $debug['resultado'] = 'VÁLIDO: HTTP 200, body = "' . $body . '"';
        return true;
    }

    $debug['resultado'] = 'INDETERMINADO — ver paso2_body_raw';
    return false;
}