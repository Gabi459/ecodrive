<?php

// Activamos el modo estricto de tipos
// Tiene que ser lo primero del archivo
declare(strict_types=1);


// Hacemos que se muestren todos los errores mientras programamos
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);


// Seguridad en HTML (Bloque 4)
// Esta función protege los datos antes de mostrarlos en la página
// Cambia los símbolos como < > " ' por un código que el navegador
// enseña como texto normal, así nadie puede meter código en la página
function e(string|int $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}


// ============================================================
// BLOQUE 3: Tratamiento de texto multibyte y análisis de flota
// ============================================================


// Catálogo de vehículos con tildes, ñ y otros caracteres especiales
// Algunos tienen descuento o extras, otros los tienen vacíos (null)
// y otros no los tienen, para ver la diferencia entre isset y array_key_exists
$catalogo = [

    [
        'modelo'    => 'zoé évolution',
        'categoria' => 'utilitario eléctrico',
        'autonomia' => 395,
        'unidades'  => 12,
        'descuento' => 10,
        'extras'    => ['Cargador portátil'],
    ],

    [
        'modelo'    => 'ñandú e-city',
        'categoria' => 'MICRO URBANO',
        'autonomia' => 180,
        'unidades'  => 25,

        // La clave existe, pero su valor es null
        'descuento' => null,
        'extras'    => null,
    ],

    [
        'modelo'    => 'ión trópico suv',
        'categoria' => 'todoterreno compacto',
        'autonomia' => 450,
        'unidades'  => 6,

        // En este vehículo no existen las claves descuento ni extras
    ],

    [
        'modelo'    => 'mégane "côte" d\'azur',
        'categoria' => 'compacto de ciudad',
        'autonomia' => 420,
        'unidades'  => 9,
        'descuento' => 5,
    ],

    [
        // Este nombre contiene HTML a propósito
        // para comprobar que después se muestra de forma segura
        'modelo'    => '<script>alert("XSS")</script> cargo',
        'categoria' => 'furgoneta de reparto',
        'autonomia' => 280,
        'unidades'  => 14,
        'extras'    => null,
    ],
];


// ------------------------------------------------------------
// Normalización de textos de flota
// ------------------------------------------------------------

// Usamos las funciones que empiezan por mb_ porque son las que
// funcionan bien con las tildes y la ñ

// Recorremos todos los vehículos del catálogo
foreach ($catalogo as $i => $vehiculo) {

    // Ponemos la primera letra de cada palabra de la categoría en mayúscula
    // Ejemplo: "MICRO URBANO" -> "Micro Urbano"
    $catalogo[$i]['categoria'] = mb_convert_case($vehiculo['categoria'], MB_CASE_TITLE, 'UTF-8');

    // Pasamos el nombre del modelo a mayúsculas, también las letras con tilde
    // Ejemplo: "zoé évolution" -> "ZOÉ ÉVOLUTION"
    $catalogo[$i]['modelo'] = mb_strtoupper($vehiculo['modelo'], 'UTF-8');

    // Contamos cuántas letras tiene el nombre del modelo
    // Con strlen, "zoé" daría 4 porque la é cuenta doble; con mb_strlen da 3
    $catalogo[$i]['longitud'] = mb_strlen($vehiculo['modelo'], 'UTF-8');
}


// ------------------------------------------------------------
// Ordenación del catálogo
// ------------------------------------------------------------

// Ordenamos los vehículos de mayor a menor autonomía
// El operador <=> compara dos valores y dice si el primero es menor, igual o mayor
// Ponemos $b antes que $a para que el orden sea de mayor a menor
usort($catalogo, function (array $a, array $b): int {
    return $b['autonomia'] <=> $a['autonomia'];
});


// ------------------------------------------------------------
// Seguridad en código cliente (Bloque 4)
// ------------------------------------------------------------

// Convertimos el catálogo a JSON para poder usarlo en JavaScript
// Las opciones JSON_HEX cambian los símbolos < > & ' " por códigos seguros
// Así ningún dato puede romper el código JavaScript ni meter código malicioso
$jsonFlota = json_encode(
    $catalogo,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);


// ============================================================
// BLOQUE 4: Reportes, seguridad multinivel y control de versiones
// ============================================================


// ------------------------------------------------------------
// Captura en memoria
// ------------------------------------------------------------

// A partir de aquí, todo lo que se genere se guarda en memoria
// en lugar de enviarse directamente al navegador
ob_start();

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <!-- Indicamos que usamos UTF-8 para tildes, ñ y otros caracteres -->
    <meta charset="UTF-8">

    <!-- Título de la página -->
    <title>EcoDrive - Reporte de flota</title>

</head>

<body>

    <h1>EcoDrive · Reporte de inventario de flota</h1>


    <!-- Tabla de vehículos ordenados por autonomía -->
    <h2>Flota ordenada por autonomía (de mayor a menor)</h2>

    <table border="1">

        <tr>
            <th>Modelo</th>
            <th>Categoría</th>
            <th>Longitud (caracteres)</th>
            <th>Autonomía</th>
            <th>Unidades</th>
        </tr>

        <?php foreach ($catalogo as $v): ?>

            <!-- Mostramos todos los datos con e() para que sea seguro -->
            <tr>
                <td><?= e($v['modelo']) ?></td>
                <td><?= e($v['categoria']) ?></td>
                <td><?= e($v['longitud']) ?></td>
                <td><?= e($v['autonomia']) ?> km</td>
                <td><?= e($v['unidades']) ?></td>
            </tr>

        <?php endforeach; ?>

    </table>


    <!-- Diferenciación de existencia de propiedades (Bloque 3) -->
    <!-- Comprobamos si cada vehículo tiene descuento y extras -->
    <h2>Atributos opcionales: array_key_exists() frente a isset()</h2>

    <table border="1">

        <tr>
            <th>Modelo</th>
            <th>Atributo</th>
            <th>array_key_exists()</th>
            <th>isset()</th>
        </tr>

        <?php foreach ($catalogo as $v): ?>
            <?php foreach (['descuento', 'extras'] as $clave): ?>

                <tr>
                    <td><?= e($v['modelo']) ?></td>
                    <td><?= e($clave) ?></td>

                    <!-- array_key_exists solo mira si la clave existe, aunque esté vacía (null) -->
                    <td><?= array_key_exists($clave, $v) ? 'true' : 'false' ?></td>

                    <!-- isset mira si la clave existe y además tiene un valor (no es null) -->
                    <!-- Por eso, si descuento es null, array_key_exists da true e isset da false -->
                    <td><?= isset($v[$clave]) ? 'true' : 'false' ?></td>
                </tr>

            <?php endforeach; ?>
        <?php endforeach; ?>

    </table>


    <!-- Lista que rellenamos con JavaScript -->
    <h2>Datos transferidos a JavaScript</h2>

    <ul id="lista"></ul>

    <script>

        // Recibimos en JavaScript los datos que hemos preparado en PHP
        const flota = <?= $jsonFlota ?>;

        // Recorremos los vehículos y creamos un li para cada uno
        // Usamos textContent para que se muestre como texto normal y no se ejecute nada
        flota.forEach((v) => {
            const li = document.createElement('li');
            li.textContent = `${v.modelo} · ${v.autonomia} km`;
            document.getElementById('lista').appendChild(li);
        });

    </script>

</body>

</html>
<?php

// Recogemos todo lo que habíamos guardado en memoria
// y lo metemos en una variable con el reporte completo
$reporte = ob_get_clean();

// Ahora sí enviamos el reporte al navegador
echo $reporte;