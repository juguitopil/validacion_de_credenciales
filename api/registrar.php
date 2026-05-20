<?php
// ============================================================
//  registrar.php — Backend principal de registro
//  Recibe las credenciales, las verifica y guarda en BD si son válidas
// ============================================================

// ── CORS y encabezados ──
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');          // ajusta al dominio de tu frontend si quieres más seguridad
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responder a preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// ── Leer el body JSON ──
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

// ── Extraer y sanitizar campos ──
$registro = trim($data['registro'] ?? '');
$password = $data['password'] ?? '';
$nombre   = trim($data['nombre'] ?? '');
$cargo    = trim($data['cargo'] ?? '');

// ── Validación básica ──
if (!$registro || !$password || !$nombre || !$cargo) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
    exit;
}

if (strlen($registro) < 5 || strlen($registro) > 20) {
    echo json_encode(['success' => false, 'message' => 'El registro universitario no tiene un formato válido.']);
    exit;
}

// ── Iniciar sesión para cookies únicas por request ──
session_start();

// ── Cargar dependencias ──
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/verificar.php';

// ── PASO 1: Verificar credenciales contra el portal UAGRM ──
$esValido = verificarCredencialesUAGRM($registro, $password);

if (!$esValido) {
    echo json_encode([
        'success' => false,
        'message' => 'Las credenciales institucionales son incorrectas. Verifica tu Registro Universitario y contraseña del portal UAGRM.'
    ]);
    exit;
}

// ── PASO 2: Credenciales válidas → guardar en BD ──
try {
    $pdo = getDB();

    // Verificar si ya existe el registro
    $check = $pdo->prepare('SELECT id FROM trabajadores WHERE registro = ?');
    $check->execute([$registro]);

    if ($check->fetch()) {
        // Ya existe → actualizar nombre y cargo
        $stmt = $pdo->prepare('UPDATE trabajadores SET nombre = ?, cargo = ?, verificado = 1 WHERE registro = ?');
        $stmt->execute([$nombre, $cargo, $registro]);
        $mensaje = 'Credenciales verificadas. El trabajador ya estaba registrado y sus datos fueron actualizados.';
    } else {
        // Nuevo registro
        // IMPORTANTE: No guardamos la contraseña. Solo guardamos que fue verificado.
        $stmt = $pdo->prepare('INSERT INTO trabajadores (registro, nombre, cargo, verificado) VALUES (?, ?, ?, 1)');
        $stmt->execute([$registro, $nombre, $cargo]);
        $mensaje = 'Registro exitoso. Las credenciales fueron verificadas y el trabajador fue registrado correctamente.';
    }

    echo json_encode(['success' => true, 'message' => $mensaje]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos.']);
}