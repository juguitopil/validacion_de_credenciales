<?php
// ============================================================
//  test_robot.php — Prueba con debug completo
//  ELIMINAR después de confirmar que funciona
// ============================================================
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/api/verificar.php';

$resultado = null;
$registro  = '';
$password  = '';
$tiempoMs  = null;
$debug     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registro = trim($_POST['registro'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($registro && $password) {
        session_start();
        $inicio    = microtime(true);
        $resultado = verificarCredencialesUAGRM($registro, $password, $debug);
        $tiempoMs  = round((microtime(true) - $inicio) * 1000);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Test Robot UAGRM</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0f1117; color: #e2e8f0; min-height: 100vh; padding: 32px 16px; }
    .wrap { max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }
    .card { background: #1a1f2e; border: 1px solid #2d3548; border-radius: 14px; padding: 28px; }
    .badge { display: inline-block; background: #c0392b; color: #fff; font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; padding: 3px 10px; border-radius: 100px; margin-bottom: 16px; }
    h1 { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 4px; }
    .sub { font-size: 13px; color: #64748b; margin-bottom: 22px; }
    label { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .8px; color: #64748b; margin-bottom: 5px; }
    input { width: 100%; padding: 10px 13px; background: #0f1117; border: 1.5px solid #2d3548; border-radius: 8px; color: #e2e8f0; font-size: 14px; font-family: inherit; outline: none; margin-bottom: 14px; transition: border-color .2s; }
    input:focus { border-color: #3b82f6; }
    button { width: 100%; padding: 12px; background: #3b82f6; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; margin-top: 4px; }
    button:hover { background: #2563eb; }
    /* Resultado */
    .result { border-radius: 10px; padding: 18px 20px; }
    .result.ok   { background: #052e16; border: 1.5px solid #166534; color: #4ade80; }
    .result.fail { background: #2d0a0a; border: 1.5px solid #991b1b; color: #f87171; }
    .result .icon { font-size: 32px; margin-bottom: 8px; }
    .result .rtitle { font-size: 17px; font-weight: 700; margin-bottom: 6px; }
    .result .rmeta  { font-size: 12px; opacity: .7; margin-top: 8px; }
    /* Debug */
    .debug-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 12px; }
    .debug-row { display: flex; gap: 12px; margin-bottom: 10px; align-items: flex-start; }
    .debug-key { flex-shrink: 0; width: 220px; font-size: 12px; color: #7dd3fc; font-weight: 600; }
    .debug-val { font-size: 12px; color: #94a3b8; line-height: 1.6; word-break: break-all; }
    .debug-val code { background: #0f1117; border: 1px solid #2d3548; padding: 2px 6px; border-radius: 4px; font-size: 11px; }
    .http-200 { color: #fbbf24; }
    .http-302 { color: #4ade80; }
    .http-err  { color: #f87171; }
    .preview-box { background: #0f1117; border: 1px solid #2d3548; border-radius: 8px; padding: 12px; font-size: 11px; color: #94a3b8; line-height: 1.7; white-space: pre-wrap; word-break: break-word; max-height: 200px; overflow-y: auto; margin-top: 4px; }
    .warn { background: #1c1400; border: 1px solid #854d0e; border-radius: 8px; padding: 14px 16px; font-size: 12px; color: #fbbf24; line-height: 1.6; }
    .warn strong { display: block; margin-bottom: 4px; font-size: 13px; }
    hr { border: none; border-top: 1px solid #2d3548; margin: 16px 0; }
  </style>
</head>
<body>
<div class="wrap">

  <div class="card">
    <div class="badge">⚠ Solo para pruebas — Eliminar después</div>
    <h1>Test Robot UAGRM</h1>
    <p class="sub">Prueba el verificador de credenciales sin tocar la base de datos. Muestra debug completo de la comunicación con el portal.</p>

    <form method="POST">
      <label>Registro Universitario</label>
      <input type="text" name="registro" placeholder="Ej: 216012345" value="<?= htmlspecialchars($registro) ?>" autocomplete="off" required/>
      <label>Contraseña del portal UAGRM</label>
      <input type="password" name="password" placeholder="Tu contraseña institucional" autocomplete="off" required/>
      <button type="submit">▶ Ejecutar prueba</button>
    </form>
  </div>

  <?php if ($resultado !== null): ?>

  <!-- RESULTADO PRINCIPAL -->
  <div class="card result <?= $resultado ? 'ok' : 'fail' ?>">
    <div class="icon"><?= $resultado ? '✅' : '❌' ?></div>
    <div class="rtitle"><?= $resultado ? 'Credenciales VÁLIDAS' : 'Credenciales INVÁLIDAS o portal no respondió' ?></div>
    <?= $resultado
      ? 'El robot detectó login exitoso. En producción estos datos SE GUARDARÍAN en la BD.'
      : 'Login fallido o sin respuesta. En producción NO se guardaría nada.' ?>
    <div class="rmeta">⏱ Tiempo: <?= $tiempoMs ?> ms &nbsp;|&nbsp; Resultado interno: <code><?= $debug['resultado'] ?? '—' ?></code></div>
  </div>

  <!-- DEBUG DETALLADO -->
  <div class="card">
    <div class="debug-title">🔍 Debug — Paso 1: GET al portal (obtener cookies)</div>

    <div class="debug-row">
      <div class="debug-key">HTTP Code GET</div>
      <div class="debug-val">
        <code class="<?= ($debug['paso1_http_code'] ?? 0) == 200 ? 'http-200' : 'http-err' ?>">
          <?= $debug['paso1_http_code'] ?? 'N/A' ?>
        </code>
        <?php if (($debug['paso1_http_code'] ?? 0) == 200): ?>✔ Portal respondió<?php else: ?>⚠ Revisa la URL o si InfinityFree bloquea cURL<?php endif; ?>
      </div>
    </div>

    <?php if (!empty($debug['paso1_curl_error'])): ?>
    <div class="debug-row">
      <div class="debug-key">Error cURL</div>
      <div class="debug-val" style="color:#f87171"><?= htmlspecialchars($debug['paso1_curl_error']) ?></div>
    </div>
    <?php endif; ?>

    <div class="debug-row">
      <div class="debug-key">Campos hidden detectados</div>
      <div class="debug-val">
        <?php if (empty($debug['campos_hidden'])): ?>
          <code>Ninguno</code> — El portal no usa tokens CSRF visibles
        <?php else: ?>
          <?php foreach ($debug['campos_hidden'] as $k => $v): ?>
            <code><?= htmlspecialchars($k) ?></code> = <code><?= htmlspecialchars(substr($v,0,40)) ?></code><br>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="debug-row">
      <div class="debug-key">Preview del HTML recibido</div>
      <div class="debug-val" style="width:100%">
        <div class="preview-box"><?= htmlspecialchars($debug['paso1_body_preview'] ?? '') ?></div>
      </div>
    </div>

    <hr>
    <div class="debug-title">🔍 Debug — Paso 2: POST con credenciales</div>

    <div class="debug-row">
      <div class="debug-key">Campo password detectado</div>
      <div class="debug-val"><code><?= htmlspecialchars($debug['campo_password_detectado'] ?? 'password') ?></code></div>
    </div>

    <div class="debug-row">
      <div class="debug-key">Campos enviados (POST)</div>
      <div class="debug-val">
        <?php foreach (($debug['post_fields_enviados'] ?? []) as $f): ?>
          <code><?= htmlspecialchars($f) ?></code>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="debug-row">
      <div class="debug-key">HTTP Code POST</div>
      <div class="debug-val">
        <?php $code = $debug['paso2_http_code'] ?? 0; ?>
        <code class="<?= $code == 302 ? 'http-302' : ($code == 200 ? 'http-200' : 'http-err') ?>">
          <?= $code ?>
        </code>
        <?php if ($code == 302): ?>✔ Redirigió (normal en login)
        <?php elseif ($code == 200): ?>⚠ Respondió 200 — revisar body
        <?php else: ?>⚠ Código inesperado<?php endif; ?>
      </div>
    </div>

    <?php if (!empty($debug['redirect_url'])): ?>
    <div class="debug-row">
      <div class="debug-key">URL de redirección</div>
      <div class="debug-val"><code><?= htmlspecialchars($debug['redirect_url']) ?></code></div>
    </div>
    <?php endif; ?>

    <?php if (!empty($debug['paso2_curl_error'])): ?>
    <div class="debug-row">
      <div class="debug-key">Error cURL POST</div>
      <div class="debug-val" style="color:#f87171"><?= htmlspecialchars($debug['paso2_curl_error']) ?></div>
    </div>
    <?php endif; ?>

    <div class="debug-row">
      <div class="debug-key">Headers de respuesta POST</div>
      <div class="debug-val" style="width:100%">
        <div class="preview-box"><?= htmlspecialchars($debug['paso2_headers'] ?? '') ?></div>
      </div>
    </div>

    <div class="debug-row">
      <div class="debug-key">PHPSESSID capturado<br><small style="opacity:.6;font-weight:400">(del GET a default.php)</small></div>
      <div class="debug-val">
        <code style="color:<?= isset($debug['paso1_session_id']) && $debug['paso1_session_id'] !== '(no encontrado — revisar cookies)' ? '#4ade80' : '#f87171' ?>">
          <?= htmlspecialchars($debug['paso1_session_id'] ?? '—') ?>
        </code>
      </div>
    </div>

    <div class="debug-row">
      <div class="debug-key">Hash MD5 generado<br><small style="opacity:.6;font-weight:400">(primeros 8 chars de md5)</small></div>
      <div class="debug-val">
        <code style="color:#fbbf24;font-size:14px"><?= htmlspecialchars($debug['password_hash_generado'] ?? '—') ?></code>
        <span style="font-size:11px;color:#64748b;margin-left:8px">← compara esto con el hash en DevTools</span>
      </div>
    </div>

    <div class="debug-row">
      <div class="debug-key">Tipo de respuesta AJAX</div>
      <div class="debug-val"><code><?= htmlspecialchars($debug['respuesta_tipo'] ?? '—') ?></code></div>
    </div>

    <div class="debug-row">
      <div class="debug-key">Body RAW completo<br><small style="opacity:.6;font-weight:400">(respuesta exacta de verif_est.php)</small></div>
      <div class="debug-val" style="width:100%">
        <div class="preview-box" style="color:#fde68a;font-size:13px;min-height:48px"><?= htmlspecialchars($debug['paso2_body_raw'] ?? '(vacío)') ?></div>
      </div>
    </div>

  </div>

  <?php endif; ?>

  <div class="warn">
    <strong>🔒 Recuerda eliminar este archivo</strong>
    Una vez confirmado que el robot funciona, borra <code>test_robot.php</code> del servidor.
    Dejarlo expuesto permite que cualquiera pruebe credenciales contra el portal.
  </div>

</div>
</body>
</html>