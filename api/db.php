<?php
header("Content-Type: application/json; charset=UTF-8");

$host = "localhost";
$usuario = "root";
$contrasena = "";
$base_datos = "el_hangar_api";

try {
    $conexion = new PDO(
        "mysql:host=$host;dbname=$base_datos;charset=utf8mb4",
        $usuario,
        $contrasena
    );

    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["mensaje" => "Error al conectar con la base de datos"]);
    exit;
}
?>