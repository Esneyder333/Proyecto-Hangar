
<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode(["error" => "Utiliza el método GET."]);
    exit;
}

try {
    $consultaResumen = $conexion->query(
        "SELECT
            COUNT(*) AS total_ventas,
            COALESCE(SUM(total), 0) AS dinero_vendido
         FROM ventas
         WHERE fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)
           AND fecha <= NOW()"
    );

    $resumen = $consultaResumen->fetch();

    $consultaDetalle = $conexion->query(
        "SELECT
            v.id,
            DATE_FORMAT(v.fecha, '%Y-%m-%d %H:%i') AS fecha,
            v.total,
            COALESCE(
                GROUP_CONCAT(
                    CONCAT(p.nombre, ' x ', d.cantidad)
                    ORDER BY p.nombre
                    SEPARATOR ', '
                ),
                'Sin detalles'
            ) AS productos
         FROM ventas v
         LEFT JOIN detalle_venta d ON d.venta_id = v.id
         LEFT JOIN productos p ON p.id = d.producto_id
         WHERE v.fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)
           AND v.fecha <= NOW()
         GROUP BY v.id, v.fecha, v.total
         ORDER BY v.fecha DESC"
    );

    echo json_encode([
        "total_ventas" => (int)$resumen["total_ventas"],
        "dinero_vendido" => (float)$resumen["dinero_vendido"],
        "ventas" => $consultaDetalle->fetchAll()
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "No fue posible generar el informe semanal."
    ]);
}
