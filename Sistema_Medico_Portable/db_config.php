<?php
// db_config.php
$base_dir = __DIR__;
$db_path = $base_dir . DIRECTORY_SEPARATOR . 'citas_medicas.db';

try {
    $pdo = new PDO("sqlite:" . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sprint 3: Creación de tablas corregidas
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario TEXT UNIQUE,
        password TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS citas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        paciente_nombre TEXT, -- Cambiado para coincidir con index.php
        especialidad TEXT,
        fecha DATE,
        hora TEXT,
        estado TEXT DEFAULT 'Registrada'
    )");

    // Crear usuario por defecto si no existe
    $pdo->exec("INSERT OR IGNORE INTO usuarios (usuario, password) VALUES ('GRUPO_H', 'GRUPOH')");

} catch (PDOException $e) {
    die("Error en el almacenamiento local: " . $e->getMessage());
}
?>