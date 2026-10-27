<?php
require_once __DIR__ . "/db.php";

$metodo = $_SERVER["REQUEST_METHOD"];

try {
    if ($metodo === "GET") {
        $consulta = $conexion->query("SELECT * FROM productos ORDER BY id DESC");
        echo json_encode($consulta->fetchAll());

    } elseif ($metodo === "POST") {
        $datos = json_decode(file_get_contents("php://input"), true);

        if (
            !is_array($datos) ||
            empty($datos["nombre"]) ||
            empty($datos["categoria"]) ||
            !isset($datos["precio"], $datos["stock"]) ||
            !is_numeric($datos["precio"]) ||
            !is_numeric($datos["stock"]) ||
            $datos["precio"] < 0 ||
            $datos["stock"] < 0
        ) {
            http_response_code(400);
            echo json_encode(["mensaje" => "Completa correctamente los datos"]);
            exit;
        }

        $sql = "INSERT INTO productos
                (nombre, categoria, precio, stock, imagen)
                VALUES (?, ?, ?, ?, ?)";

        $consulta = $conexion->prepare($sql);
        $consulta->execute([
            $datos["nombre"],
            $datos["categoria"],
            $datos["precio"],
            $datos["stock"],
            $datos["imagen"] ?? null
        ]);

        http_response_code(201);
        echo json_encode(["mensaje" => "Producto registrado correctamente"]);

    } elseif ($metodo === "PUT") {
        $datos = json_decode(file_get_contents("php://input"), true);

        if (
            !is_array($datos) ||
            empty($datos["id"]) ||
            empty($datos["nombre"]) ||
            empty($datos["categoria"]) ||
            !isset($datos["precio"], $datos["stock"]) ||
            !is_numeric($datos["precio"]) ||
            !is_numeric($datos["stock"]) ||
            $datos["precio"] < 0 ||
            $datos["stock"] < 0
        ) {
            http_response_code(400);
            echo json_encode(["mensaje" => "Datos de modificación incorrectos"]);
            exit;
        }

        $sql = "UPDATE productos
                SET nombre = ?, categoria = ?, precio = ?, stock = ?, imagen = ?
                WHERE id = ?";

        $consulta = $conexion->prepare($sql);
        $consulta->execute([
            $datos["nombre"],
            $datos["categoria"],
            $datos["precio"],
            $datos["stock"],
            $datos["imagen"] ?? null,
            $datos["id"]
        ]);

        echo json_encode([
            "mensaje" => $consulta->rowCount() > 0
                ? "Producto modificado correctamente"
                : "No hubo cambios o el producto no existe"
        ]);

    } elseif ($metodo === "DELETE") {
        $id = $_GET["id"] ?? "";

        if (!ctype_digit((string)$id) || (int)$id < 1) {
            http_response_code(400);
            echo json_encode(["mensaje" => "Indica un ID válido"]);
            exit;
        }

        $consulta = $conexion->prepare("DELETE FROM productos WHERE id = ?");
        $consulta->execute([$id]);

        if ($consulta->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["mensaje" => "Producto no encontrado"]);
        } else {
            echo json_encode(["mensaje" => "Producto eliminado correctamente"]);
        }

    } else {
        header("Allow: GET, POST, PUT, DELETE");
        http_response_code(405);
        echo json_encode(["mensaje" => "Método no permitido"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "mensaje" => "No se pudo procesar la operación. Verifica si el producto está relacionado con una venta."
    ]);
}
?>