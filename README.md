# EcoDrive · Sistema backend de flota y alquiler de vehículos

Práctica 2 — Módulo backend en PHP para validar solicitudes de alquiler de vehículos eléctricos, procesar reservas de flotas corporativas y generar reportes de inventario seguros.

## Requisitos

- PHP 8.0 o superior (se usan `match` y tipos unión)
- Extensión `mbstring` activada

## Estructura

```
ecodrive/
├── procesador.php   # Validación GET (HTTP 400), función documentada, excepciones y tarifas
├── reporte.php      # Catálogo multibyte, isset vs array_key_exists, ordenación, búfer y salida segura
└── README.md
```

## Ejecución

```bash
php -S localhost:8000
```

- Reserva válida: http://localhost:8000/procesador.php?dias=5
- Error 400: http://localhost:8000/procesador.php?dias=abc o `?dias=-3`
- Reporte de flota: http://localhost:8000/reporte.php

## Funcionalidades por bloque

**Bloque 1 – Configuración y validación.** `declare(strict_types=1)`, `display_errors` y `error_reporting(E_ALL)`. El parámetro `dias` se valida con `filter_var(FILTER_VALIDATE_INT)` y `min_range = 1`; si falla se responde con `http_response_code(400)` y `exit`. Los datos capturados se muestran con `var_dump` dentro de `<pre>`.

**Bloque 2 – Facturación y excepciones.** `calcularTotalReserva()` está documentada con PHPDoc (`@param`, `@return`, `@throws`) y lanza `InvalidArgumentException` si la reserva está vacía. La excepción se captura con `try/catch` en el flujo principal. La categoría de descuento o suplemento se decide con `match (true)`.

**Bloque 3 – Texto multibyte y flota.** `mb_convert_case(MB_CASE_TITLE)`, `mb_strtoupper` y `mb_strlen` (comparado con `strlen` para ver la diferencia entre caracteres y bytes). Se compara `array_key_exists()` (la clave existe) con `isset()` (la clave existe y no es `null`). El catálogo se ordena de mayor a menor autonomía con `usort` y el operador `<=>`.

**Bloque 4 – Reporte y seguridad.** El reporte se genera dentro de `ob_start()` / `ob_get_clean()` antes de enviarse. Toda salida HTML pasa por `htmlspecialchars(ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`, y los datos para JavaScript se codifican con `json_encode` usando `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`. El catálogo incluye un nombre con `<script>` a propósito para comprobar que no se ejecuta.
