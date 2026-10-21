<?php

/**
 * STUB DE LA API DE TARIFAS DE LA TRANSPORTADORA
 * ==============================================
 * El enunciado apunta a POST https://tarifas.transportadora.test/v1/cotizar,
 * pero ese dominio es ficticio (`.test` es un TLD reservado para pruebas) y
 * la credencial "se entrega por solicitud".
 *
 * En su lugar, esta aplicación habla por HTTP contra una URL configurable
 * (TARIFAS_BASE_URL) con una credencial configurable (TARIFAS_API_KEY).
 * En este proyecto esa URL apunta a este stub, que reproduce el mismo
 * contrato de la API: misma ruta, mismo verbo, misma autenticación por
 * `Authorization: Bearer ...` y una respuesta con `costo_envio`.
 *
 * Para conectarlo al proveedor real solo hay que cambiar TARIFAS_BASE_URL
 * y TARIFAS_API_KEY en el .env; el código de la aplicación no cambia.
 */

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

header('Content-Type: application/json; charset=utf-8');

if ($ruta === '/healthz') {
    echo json_encode(['ok' => true, 'servicio' => 'tarifas-stub']);
    exit;
}

if ($ruta !== '/v1/cotizar') {
    http_response_code(404);
    echo json_encode(['error' => 'no_encontrado', 'mensaje' => 'El recurso solicitado no existe.']);
    exit;
}

if ($metodo !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['error' => 'metodo_no_permitido', 'mensaje' => 'Use POST /v1/cotizar.']);
    exit;
}

// --- Autenticación: la credencial se entrega por solicitud -------------
$credencial = getenv('TARIFAS_STUB_KEY') ?: 'dev-key-transportadora';
$autorizacion = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if ($autorizacion !== 'Bearer '.$credencial) {
    http_response_code(401);
    echo json_encode(['error' => 'credencial_invalida', 'mensaje' => 'La credencial de acceso no es válida o no fue enviada.']);
    exit;
}

$entrada = json_decode(file_get_contents('php://input'), true);

if (! is_array($entrada)) {
    http_response_code(400);
    echo json_encode(['error' => 'json_invalido', 'mensaje' => 'El cuerpo de la petición no es JSON válido.']);
    exit;
}

$destino = trim(mb_strtolower($entrada['destino'] ?? ''));
$pesoKg = max(0.1, (float) ($entrada['peso_kg'] ?? 1));
$subtotal = max(0, (float) ($entrada['subtotal'] ?? 0));

if ($destino === '') {
    http_response_code(402);
    echo json_encode(['error' => 'destino_requerido', 'mensaje' => 'Debe indicar la ciudad de destino.']);
    exit;
}

// Tarifas por ciudad (COP): tarifa base + tarifa por kilo + seguro sobre el valor.
$tarifasBase = [
    'bogota' => 12000,
    'ibague' => 14500,
    'villavicencio' => 13800,
    'medellin' => 15000,
    'neiva' => 15500,
    'cali' => 16500,
    'manizales' => 17000,
    'armenia' => 16800,
    'pereira' => 17200,
    'bucaramanga' => 17500,
    'sincelejo' => 18000,
    'popayan' => 18500,
    'barranquilla' => 19000,
    'monteria' => 19800,
    'valledupar' => 20000,
    'cartagena' => 21000,
    'santa marta' => 20500,
    'pasto' => 22000,
    'flores' => 23000,
];

$tarifaBase = $tarifasBase[$destino] ?? 25000; // ciudades no listadas
$tarifaPorKilo = 2500;
$seguro = round($subtotal * 0.002);            // 0.2 % del valor declarado
$costoEnvio = (int) round($tarifaBase + ($pesoKg * $tarifaPorKilo) + $seguro);

$etaDias = match (true) {
    in_array($destino, ['bogota', 'ibague', 'villavicencio'], true) => 1,
    in_array($destino, ['medellin', 'cali', 'neiva', 'bucaramanga'], true) => 2,
    in_array($destino, ['pasto', 'cartagena', 'santa marta', 'monteria'], true) => 3,
    default => 2,
};

echo json_encode([
    'cotizacion_id' => strtoupper(substr(md5($destino.$pesoKg.$subtotal.uniqid('', true)), 0, 12)),
    'transportadora' => 'Transportadora Nacional S.A.S.',
    'origen' => 'Bogotá',
    'destino' => $entrada['destino'],
    'peso_kg' => $pesoKg,
    'tarifa_base' => $tarifaBase,
    'tarifa_por_kilo' => $tarifaPorKilo,
    'seguro' => $seguro,
    'costo_envio' => $costoEnvio,
    'moneda' => 'COP',
    'eta_dias' => $etaDias,
    'vigencia_horas' => 24,
    'emitida_en' => date(DATE_ATOM),
], JSON_UNESCAPED_UNICODE);
