<?php
/**
 * EcoDrive - Procesador de alquileres
 *
 * Valida la petición GET, calcula el importe de la reserva,
 * gestiona excepciones y categoriza la tarifa final.
 *
 * Uso: procesador.php?dias=5
 */

// ============================================================
// BLOQUE 1: Configuración del servidor, control de entrada y diagnóstico
// ============================================================

// Tipado estricto: debe ser la primera sentencia del archivo
declare(strict_types=1);

// Diagnóstico de errores (solo para desarrollo)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/html; charset=UTF-8');

/**
 * Escapa un valor para imprimirlo de forma segura en HTML.
 *
 * @param string|int|float|null $valor Valor a escapar.
 * @return string Valor escapado.
 */
function e(string|int|float|null $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

// --- Validación de la reserva ---
$diasRecibidos = $_GET['dias'] ?? null;

$dias = filter_var(
    $diasRecibidos,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($dias === false) {
    http_response_code(400);
    $valorMostrado = is_string($diasRecibidos) ? $diasRecibidos : '(vacío o formato no válido)';
    echo '<h1>400 - Solicitud incorrecta</h1>';
    echo '<p>El parámetro <code>dias</code> debe ser un número entero positivo. ';
    echo 'Valor recibido: <strong>' . e($valorMostrado) . '</strong></p>';
    echo '<p>Ejemplo correcto: <code>procesador.php?dias=5</code></p>';
    exit;
}

// ============================================================
// BLOQUE 2: Procesador de alquileres, excepciones y tarifas
// ============================================================

/**
 * Calcula el importe total de una reserva de vehículos.
 *
 * Recorre las líneas de la reserva y suma, para cada una,
 * precio por día × unidades × días de alquiler.
 *
 * @param array<int, array{modelo: string, precio_dia: float, unidades: int}> $lineas
 *        Líneas de la reserva (un elemento por modelo de vehículo).
 * @param int $dias Número de días de alquiler (entero positivo).
 *
 * @return float Importe total de la reserva en euros, sin descuentos.
 *
 * @throws InvalidArgumentException Si la reserva no contiene ninguna línea
 *                                  o si los días no son positivos.
 */
function calcularTotalReserva(array $lineas, int $dias): float
{
    // Salida temprana: no se puede facturar una reserva vacía
    if (count($lineas) === 0) {
        throw new InvalidArgumentException('La reserva no contiene ningún vehículo.');
    }

    if ($dias <= 0) {
        throw new InvalidArgumentException('Los días de alquiler deben ser positivos.');
    }

    $total = 0.0;
    foreach ($lineas as $linea) {
        $total += $linea['precio_dia'] * $linea['unidades'] * $dias;
    }

    return $total;
}

/**
 * Determina la categoría de descuento o suplemento según el importe total.
 *
 * @param float $total Importe total de la reserva en euros.
 *
 * @return array{categoria: string, ajuste: float} Nombre de la categoría y
 *         porcentaje de ajuste (negativo = descuento, positivo = suplemento).
 */
function categorizarReserva(float $total): array
{
    // Expresión condicional basada en evaluaciones directas
    return match (true) {
        $total >= 5000 => ['categoria' => 'Flota corporativa', 'ajuste' => -15.0],
        $total >= 1500 => ['categoria' => 'Cliente preferente', 'ajuste' => -10.0],
        $total >= 500  => ['categoria' => 'Descuento básico',   'ajuste' => -5.0],
        default        => ['categoria' => 'Reserva pequeña (suplemento de gestión)', 'ajuste' => 8.0],
    };
}

// --- Datos de ejemplo: reservas recibidas ---
$reservas = [
    'Reserva corporativa GreenLogistics' => [
        ['modelo' => 'Zoé Évolution',   'precio_dia' => 39.90, 'unidades' => 10],
        ['modelo' => 'Ión Trópico SUV', 'precio_dia' => 64.50, 'unidades' => 4],
    ],
    'Reserva particular' => [
        ['modelo' => 'Ñandú e-City', 'precio_dia' => 24.00, 'unidades' => 1],
    ],
    // Reserva vacía: provoca la excepción de forma intencionada
    'Reserva sin vehículos' => [],
];

// Resultados que se mostrarán después
$resultados = [];

foreach ($reservas as $nombre => $lineas) {
    try {
        $total = calcularTotalReserva($lineas, $dias);
        $categoria = categorizarReserva($total);
        $totalFinal = $total * (1 + $categoria['ajuste'] / 100);

        $resultados[] = [
            'nombre'     => $nombre,
            'ok'         => true,
            'total'      => $total,
            'categoria'  => $categoria['categoria'],
            'ajuste'     => $categoria['ajuste'],
            'totalFinal' => $totalFinal,
        ];
    } catch (InvalidArgumentException $ex) {
        $resultados[] = [
            'nombre' => $nombre,
            'ok'     => false,
            'error'  => $ex->getMessage(),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>EcoDrive - Procesador de alquileres</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem; color: #1d2b36; }
        table { border-collapse: collapse; margin-bottom: 1.5rem; }
        th, td { border: 1px solid #c7d3dc; padding: .4rem .8rem; text-align: left; }
        th { background: #e8f3ec; }
        pre { background: #f4f6f8; padding: 1rem; border-radius: 6px; overflow-x: auto; }
        .error { color: #b3261e; font-weight: bold; }
    </style>
</head>
<body>
    <h1>EcoDrive · Procesador de alquileres</h1>

    <h2>Trazabilidad de datos (inspección técnica)</h2>
    <?php
    // Se captura var_dump y se escapa para no imprimir datos del usuario sin filtrar
    ob_start();
    var_dump($diasRecibidos, $dias);
    $inspeccion = (string) ob_get_clean();
    ?>
    <pre><?= e($inspeccion) ?></pre>

    <h2>Resultado de las reservas (<?= e($dias) ?> días)</h2>
    <table>
        <tr>
            <th>Reserva</th>
            <th>Importe base</th>
            <th>Categoría</th>
            <th>Ajuste</th>
            <th>Importe final</th>
        </tr>
        <?php foreach ($resultados as $r): ?>
            <tr>
                <td><?= e($r['nombre']) ?></td>
                <?php if ($r['ok']): ?>
                    <td><?= e(number_format($r['total'], 2, ',', '.')) ?> €</td>
                    <td><?= e($r['categoria']) ?></td>
                    <td><?= e(sprintf('%+.0f %%', $r['ajuste'])) ?></td>
                    <td><strong><?= e(number_format($r['totalFinal'], 2, ',', '.')) ?> €</strong></td>
                <?php else: ?>
                    <td colspan="4" class="error">Excepción capturada: <?= e($r['error']) ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </table>

    <p><a href="reporte.php">Ver reporte de flota →</a></p>
</body>
</html>
