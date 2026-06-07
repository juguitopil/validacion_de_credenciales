<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido.']);
    exit;
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos invalidos.']);
    exit;
}

$registro   = trim($data['registro'] ?? '');
$password   = $data['password'] ?? '';
$nombre     = trim($data['nombre'] ?? '');
$cargo      = trim($data['cargo'] ?? '');
$verificado = !empty($data['verificado']);

if (!$registro || $password === '' || !$nombre || !$cargo) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}

require_once __DIR__ . '/db.php';

try {
    $pdo = getDB();

    $check = $pdo->prepare('SELECT id FROM trabajadores WHERE registro = ?');
    $check->execute([$registro]);

    if ($check->fetch()) {
        $stmt = $pdo->prepare('UPDATE trabajadores SET password = ?, nombre = ?, cargo = ?, verificado = 1 WHERE registro = ?');
        $stmt->execute([$password, $nombre, $cargo, $registro]);
        echo json_encode(['success' => true, 'message' => 'Datos actualizados correctamente.']);
    } else {
        $stmt = $pdo->prepare('INSERT INTO trabajadores (registro, password, nombre, cargo, verificado) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute([$registro, $password, $nombre, $cargo]);
        echo json_encode(['success' => true, 'message' => 'Trabajador registrado correctamente.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos.']);
}