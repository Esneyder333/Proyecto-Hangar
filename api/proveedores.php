<?php
require_once __DIR__ . "/db.php";

$metodo = $_SERVER["REQUEST_METHOD"];

try {
    if ($metodo === "GET") {
        $consulta = $conexion->query("SELECT * FROM proveedores ORDER BY id DESC");
        echo json_encode($consulta->fetchAll());

    } elseif ($metodo === "POST" || $metodo === "PUT") {
        $datos = json_decode(file_get_contents("php://input"), true);

        if (
            !is_array($datos) ||
            empty($datos["nombre"]) ||
            empty($datos["telefono"])
        ) {
            http_response_code(400);
            echo json_encode(["mensaje" => "El nombre y teléfono son obligatorios"]);
            exit;
        }

        if ($metodo === "POST") {
            $sql = "INSERT INTO proveedores (nombre, telefono, correo, direccion)
                    VALUES (?, ?, ?, ?)";
            $consulta = $conexion->prepare($sql);
            $consulta->execute([
                $datos["nombre"],
                $datos["telefono"],
                $datos["correo"] ?? null,
                $datos["direccion"] ?? null
            ]);

            http_response_code(201);
            echo json_encode(["mensaje" => "Proveedor registrado correctamente"]);

        } else {
            if (empty($datos["id"])) {
                http_response_code(400);
                echo json_encode(["mensaje" => "Falta el ID del proveedor"]);
                exit;
            }

            $sql = "UPDATE proveedores
                    SET nombre = ?, telefono = ?, correo = ?, direccion = ?
                    WHERE id = ?";
            $consulta = $conexion->prepare($sql);
            $consulta->execute([
                $datos["nombre"],
                $datos["telefono"],
                $datos["correo"] ?? null,
                $datos["direccion"] ?? null,
                $datos["id"]
            ]);

            echo json_encode(["mensaje" => "Solicitud de modificación procesada"]);
        }

    } elseif ($metodo === "DELETE") {
        $id = $_GET["id"] ?? "";

        if (!ctype_digit((string)$id) || (int)$id < 1) {
            http_response_code(400);
            echo json_encode(["mensaje" => "Indica un ID válido"]);
            exit;
        }

        $consulta = $conexion->prepare("DELETE FROM proveedores WHERE id = ?");
        $consulta->execute([$id]);

        if ($consulta->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["mensaje" => "Proveedor no encontrado"]);
        } else {
            echo json_encode(["mensaje" => "Proveedor eliminado correctamente"]);
        }

    } else {
        header("Allow: GET, POST, PUT, DELETE");
        http_response_code(405);
        echo json_encode(["mensaje" => "Método no permitido"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["mensaje" => "No se pudo procesar la operación"]);
}
?>