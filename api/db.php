<?php
// ============================================================
//  db.php — Configuración de conexión a MySQL (InfinityFree)
//  Cambia estos valores con los datos de tu panel InfinityFree
// ============================================================

define('DB_HOST', 'sql100.infinityfree.com');   // Ej: sql212.infinityfree.com
define('DB_USER', 'tif0_41710876');   // Ej: if0_12345678
define('DB_PASS', '6harBakzZ8a4');
define('DB_NAME', 'if0_41710876_fire'); // Ej: if0_12345678_carnetizacion

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            // No exponer detalles en producción
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']));
        }
    }
    return $pdo;
}

// ============================================================
//  SQL para crear la tabla (ejecuta esto UNA VEZ en phpMyAdmin)
// ============================================================
/*
CREATE TABLE IF NOT EXISTS trabajadores (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    registro    VARCHAR(20)  NOT NULL UNIQUE,
    nombre      VARCHAR(150) NOT NULL,
    cargo       VARCHAR(100) NOT NULL,
    verificado  TINYINT(1)   NOT NULL DEFAULT 1,
    fecha_reg   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/