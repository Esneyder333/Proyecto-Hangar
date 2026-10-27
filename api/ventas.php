
<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Utiliza el método POST."]);
    exit;
}

$datos = json_decode(file_get_contents("php://input"), true);

if (
    !is_array($datos) ||
    !isset($datos["productos"]) ||
    !is_array($datos["productos"]) ||
    count($datos["productos"]) === 0
) {
    http_response_code(400);
    echo json_encode(["error" => "El pedido está vacío."]);
    exit;
}

try {
    $conexion->beginTransaction();

    $productosPedido = [];
    $total = 0;

    foreach ($datos["productos"] as $item) {
        $id = filter_var(
            $item["producto_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $cantidad = filter_var(
            $item["cantidad"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$id || !$cantidad || $cantidad < 1) {
            throw new Exception("Producto o cantidad no válidos.");
        }

        if (isset($productosPedido[$id])) {
            throw new Exception("No repitas productos en el pedido.");
        }

        $consulta = $conexion->prepare(
            "SELECT id, nombre, precio, stock
             FROM productos
             WHERE id = ?
             FOR UPDATE"
        );
        $consulta->execute([$id]);
        $producto = $consulta->fetch();

        if (!$producto) {
            throw new Exception("El producto no existe.");
        }

        if ($cantidad > (int)$producto["stock"]) {
            throw new Exception(
                "Stock insuficiente para " . $producto["nombre"] .
                ". Disponibles: " . $producto["stock"]
            );
        }

        $producto["cantidad"] = $cantidad;
        $productosPedido[$id] = $producto;

        $total += (float)$producto["precio"] * $cantidad;
    }

    $total = round($total, 2);

    $guardarVenta = $conexion->prepare(
        "INSERT INTO ventas (total) VALUES (?)"
    );
    $guardarVenta->execute([$total]);

    $ventaId = $conexion->lastInsertId();

    $guardarDetalle = $conexion->prepare(
        "INSERT INTO detalle_venta
         (venta_id, producto_id, cantidad, precio)
         VALUES (?, ?, ?, ?)"
    );

    $actualizarStock = $conexion->prepare(
        "UPDATE productos
         SET stock = stock - ?
         WHERE id = ? AND stock >= ?"
    );

    foreach ($productosPedido as $id => $producto) {
        $cantidad = $producto["cantidad"];

        $actualizarStock->execute([$cantidad, $id, $cantidad]);

        if ($actualizarStock->rowCount() !== 1) {
            throw new Exception("No se pudo actualizar el inventario.");
        }

        $guardarDetalle->execute([
            $ventaId,
            $id,
            $cantidad,
            $producto["precio"]
        ]);
    }

    $conexion->commit();

    echo json_encode([
        "mensaje" => "Venta registrada correctamente.",
        "venta_id" => $ventaId,
        "total" => $total
    ]);

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    http_response_code(400);

    echo json_encode([
        "error" => $e instanceof PDOException
            ? "Error al guardar la venta. Revisa la base de datos."
            : $e->getMessage()
    ]);
}
