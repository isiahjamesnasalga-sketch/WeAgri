<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/auth_helpers.php';

weagri_dashboard_headers();

function weagri_weather_code_label(?int $code): string
{
    return match (true) {
        $code === 0 => 'Clear',
        in_array($code, [1, 2, 3], true) => 'Partly cloudy',
        in_array($code, [45, 48], true) => 'Foggy',
        in_array($code, [51, 53, 55, 56, 57], true) => 'Drizzle',
        in_array($code, [61, 63, 65, 66, 67], true) => 'Rain',
        in_array($code, [80, 81, 82], true) => 'Rain showers',
        in_array($code, [95, 96, 99], true) => 'Thunderstorm',
        default => 'Forecast',
    };
}

function weagri_fetch_text_url(string $url, string $accept = '*/*'): ?string
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 6,
            'header' => "Accept: {$accept}\r\nUser-Agent: WeAgri/1.0\r\n",
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    if (($raw === false || $raw === '') && function_exists('curl_init')) {
        $curl = curl_init($url);
        if ($curl !== false) {
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_HTTPHEADER => [
                    "Accept: {$accept}",
                    'User-Agent: WeAgri/1.0',
                ],
            ]);
            $curlRaw = curl_exec($curl);
            curl_close($curl);
            $raw = is_string($curlRaw) ? $curlRaw : $raw;
        }
    }

    if ($raw === false || $raw === '') {
        return null;
    }

    return $raw;
}

function weagri_fetch_json_url(string $url): ?array
{
    $raw = weagri_fetch_text_url($url, 'application/json');

    if ($raw === null) {
        return null;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function weagri_absolute_url(string $url, string $baseUrl): string
{
    if (preg_match('/^https?:\/\//i', $url)) {
        return $url;
    }

    if (str_starts_with($url, '//')) {
        return 'https:' . $url;
    }

    return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
}

function weagri_demo_weather_calendar(): array
{
    $calendar = [];
    $conditions = ['Partly cloudy', 'Rain showers', 'Cloudy', 'Clear', 'Drizzle', 'Partly cloudy', 'Rain'];
    $rain = [24, 52, 38, 12, 41, 28, 60];

    for ($day = 0; $day < 7; $day++) {
        $timestamp = strtotime(sprintf('+%d days', $day));
        $calendar[] = [
            'date' => date('Y-m-d', $timestamp),
            'day_label' => $day === 0 ? 'Today' : date('D', $timestamp),
            'date_label' => date('M j', $timestamp),
            'temp_max' => 30.0 + ($day % 3),
            'temp_min' => 24.0 + ($day % 2),
            'rain_probability' => $rain[$day],
            'precipitation_sum' => round($rain[$day] / 18, 1),
            'weather_code' => null,
            'condition' => $conditions[$day],
        ];
    }

    return $calendar;
}

function weagri_demo_market_prices(?string $recordedAt = null): array
{
    $recordedAt = $recordedAt ?: date('Y-m-d H:i:s');

    return [
        ['id' => 0, 'crop_name' => 'Rice', 'price' => 52.4, 'trend' => 'up', 'updated_at' => $recordedAt],
        ['id' => 0, 'crop_name' => 'Corn', 'price' => 31.75, 'trend' => 'down', 'updated_at' => $recordedAt],
        ['id' => 0, 'crop_name' => 'Tomato', 'price' => 68.2, 'trend' => 'up', 'updated_at' => $recordedAt],
        ['id' => 0, 'crop_name' => 'Eggplant', 'price' => 58.0, 'trend' => 'stable', 'updated_at' => $recordedAt],
    ];
}

function weagri_resolve_weather_location(string $location): array
{
    $fallback = [
        'latitude' => 10.64,
        'longitude' => 122.24,
        'name' => 'Miagao, Iloilo',
    ];

    $location = trim($location);
    if ($location === '') {
        return $fallback;
    }

    $url = 'https://geocoding-api.open-meteo.com/v1/search?name=' . rawurlencode($location) . '&count=1&language=en&format=json';
    $payload = weagri_fetch_json_url($url);
    $result = $payload['results'][0] ?? null;

    if (!is_array($result) || !isset($result['latitude'], $result['longitude'])) {
        return [
            'latitude' => $fallback['latitude'],
            'longitude' => $fallback['longitude'],
            'name' => $location,
        ];
    }

    $parts = array_filter([
        $result['name'] ?? null,
        $result['admin1'] ?? null,
        $result['country'] ?? null,
    ]);

    return [
        'latitude' => (float) $result['latitude'],
        'longitude' => (float) $result['longitude'],
        'name' => implode(', ', $parts),
    ];
}

function weagri_fetch_open_meteo_forecast(string $location): ?array
{
    $resolved = weagri_resolve_weather_location($location);
    $url = sprintf(
        'https://api.open-meteo.com/v1/forecast?latitude=%F&longitude=%F&current=temperature_2m,precipitation,weather_code&hourly=soil_moisture_0_to_1cm&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,precipitation_sum&timezone=auto&forecast_days=7',
        $resolved['latitude'],
        $resolved['longitude']
    );
    $payload = weagri_fetch_json_url($url);

    if (!is_array($payload) || empty($payload['daily']['time'])) {
        return null;
    }

    $daily = $payload['daily'];
    $calendar = [];
    foreach ($daily['time'] as $index => $date) {
        $timestamp = strtotime((string) $date);
        $calendar[] = [
            'date' => (string) $date,
            'day_label' => $index === 0 ? 'Today' : date('D', $timestamp),
            'date_label' => date('M j', $timestamp),
            'temp_max' => (float) ($daily['temperature_2m_max'][$index] ?? 0),
            'temp_min' => (float) ($daily['temperature_2m_min'][$index] ?? 0),
            'rain_probability' => (float) ($daily['precipitation_probability_max'][$index] ?? 0),
            'precipitation_sum' => (float) ($daily['precipitation_sum'][$index] ?? 0),
            'weather_code' => isset($daily['weather_code'][$index]) ? (int) $daily['weather_code'][$index] : null,
            'condition' => weagri_weather_code_label(isset($daily['weather_code'][$index]) ? (int) $daily['weather_code'][$index] : null),
        ];
    }

    $soilMoisture = null;
    foreach (($payload['hourly']['soil_moisture_0_to_1cm'] ?? []) as $value) {
        if (is_numeric($value)) {
            $soilMoisture = round((float) $value * 100, 1);
            break;
        }
    }

    return [
        'location' => $resolved['name'],
        'calendar' => $calendar,
        'metrics' => [
            'temperature' => (float) ($payload['current']['temperature_2m'] ?? ($calendar[0]['temp_max'] ?? 0)),
            'soil_moisture' => $soilMoisture,
            'rain_probability' => (float) ($calendar[0]['rain_probability'] ?? 0),
            'timestamp' => (string) ($payload['current']['time'] ?? date('Y-m-d H:i:s')),
        ],
    ];
}

function weagri_demo_dashboard_payload(string $source, string $message, ?array $weatherPayload = null): array
{
    $recordedAt = date('Y-m-d H:i:s');
    $weatherCalendar = $weatherPayload['calendar'] ?? weagri_demo_weather_calendar();
    $weatherMetrics = $weatherPayload['metrics'] ?? [];
    $weather = [];
    $soilHealth = [];

    foreach ([50, 40, 30, 20, 10, 0] as $index => $minutesAgo) {
        $timestamp = date('Y-m-d H:i:s', strtotime(sprintf('-%d minutes', $minutesAgo)));
        $weather[] = [
            'label' => date('H:i', strtotime($timestamp)),
            'value' => [27.8, 28.1, 28.7, 29.2, 28.6, 28.4][$index],
            'recorded_at' => $timestamp,
        ];
        $soilHealth[] = [
            'label' => date('H:i', strtotime($timestamp)),
            'soil_moisture' => [67.2, 65.9, 64.1, 62.7, 63.8, 64.5][$index],
            'crop_health' => [92.0, 91.4, 90.8, 89.6, 90.2, 91.0][$index],
            'recorded_at' => $timestamp,
        ];
    }

    $marketPrices = weagri_demo_market_prices($recordedAt);

    return [
        'ok' => $source !== 'error',
        'source' => $source,
        'message' => $message,
        'metrics' => [
            'temperature' => (float) ($weatherMetrics['temperature'] ?? 28.4),
            'soil_moisture' => (float) ($weatherMetrics['soil_moisture'] ?? 64.5),
            'crop_health' => (float) ($weatherMetrics['rain_probability'] ?? 24),
            'rain_probability' => (float) ($weatherMetrics['rain_probability'] ?? 24),
            'open_queries' => 3,
            'timestamp' => (string) ($weatherMetrics['timestamp'] ?? $recordedAt),
        ],
        'weather_calendar' => $weatherCalendar,
        'weather_location' => (string) ($weatherPayload['location'] ?? 'Miagao, Iloilo'),
        'market_prices' => $marketPrices,
        'market_source' => [
            'label' => 'Fallback sample prices',
            'status' => 'fallback',
            'url' => null,
            'updated_at' => $recordedAt,
            'note' => 'Showing sample prices because live market data is unavailable.',
        ],
        'trends' => [
            'weather' => $weather,
            'soil_health' => $soilHealth,
            'market_prices' => array_map(
                static fn (array $row): array => [
                    'label' => $row['crop_name'],
                    'value' => $row['price'],
                    'trend' => $row['trend'],
                ],
                $marketPrices
            ),
        ],
        'insight' => 'Weather readings are steady. Check soil moisture before watering, avoid spraying before rain, and watch market prices before making large selling decisions.',
    ];
}

function fetch_latest_metrics_log(PDO $pdo): ?array
{
    try {
        $statement = $pdo->prepare(
            'SELECT id, sensor_node_id, temperature, soil_moisture, crop_health_index, recorded_at
             FROM agri_metrics_log
             ORDER BY recorded_at DESC, id DESC
             LIMIT 1'
        );
        $statement->execute();
        $row = $statement->fetch();

        return $row ?: null;
    } catch (Throwable $exception) {
        return null;
    }
}

function fetch_legacy_metrics(PDO $pdo): ?array
{
    try {
        $statement = $pdo->prepare(
            'SELECT id, temperature, soil_moisture, crop_health, timestamp
             FROM agri_metrics
             ORDER BY timestamp DESC, id DESC
             LIMIT 1'
        );
        $statement->execute();
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'sensor_node_id' => 'legacy-node',
            'temperature' => (float) $row['temperature'],
            'soil_moisture' => (float) $row['soil_moisture'],
            'crop_health_index' => (float) $row['crop_health'],
            'recorded_at' => (string) $row['timestamp'],
        ];
    } catch (Throwable $exception) {
        return null;
    }
}

function fetch_metrics_trends(PDO $pdo): array
{
    try {
        $statement = $pdo->prepare(
            'SELECT temperature, soil_moisture, crop_health_index, recorded_at
             FROM agri_metrics_log
             ORDER BY recorded_at DESC, id DESC
             LIMIT 12'
        );
        $statement->execute();
        $rows = array_reverse($statement->fetchAll());

        return array_map(
            static fn (array $row): array => [
                'label' => date('H:i', strtotime((string) $row['recorded_at'])),
                'temperature' => (float) $row['temperature'],
                'soil_moisture' => (float) $row['soil_moisture'],
                'crop_health' => (float) $row['crop_health_index'],
                'recorded_at' => (string) $row['recorded_at'],
            ],
            $rows
        );
    } catch (Throwable $exception) {
        return [];
    }
}

function fetch_legacy_metrics_trends(PDO $pdo): array
{
    try {
        $statement = $pdo->prepare(
            'SELECT temperature, soil_moisture, crop_health, timestamp
             FROM agri_metrics
             ORDER BY timestamp DESC, id DESC
             LIMIT 12'
        );
        $statement->execute();
        $rows = array_reverse($statement->fetchAll());

        return array_map(
            static fn (array $row): array => [
                'label' => date('H:i', strtotime((string) $row['timestamp'])),
                'temperature' => (float) $row['temperature'],
                'soil_moisture' => (float) $row['soil_moisture'],
                'crop_health' => (float) $row['crop_health'],
                'recorded_at' => (string) $row['timestamp'],
            ],
            $rows
        );
    } catch (Throwable $exception) {
        return [];
    }
}

function fetch_market_prices(PDO $pdo): array
{
    try {
        $statement = $pdo->prepare(
            'SELECT id, crop_name, price_per_kg, trend_direction, updated_at
             FROM market_hub_prices
             ORDER BY crop_name ASC'
        );
        $statement->execute();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'crop_name' => (string) $row['crop_name'],
                'price' => (float) $row['price_per_kg'],
                'trend' => (string) $row['trend_direction'],
                'updated_at' => (string) $row['updated_at'],
            ],
            $statement->fetchAll()
        );
    } catch (Throwable $exception) {
        return [];
    }
}

function fetch_legacy_market_prices(PDO $pdo): array
{
    try {
        $statement = $pdo->prepare(
            'SELECT id, crop_name, price, trend
             FROM market_prices
             ORDER BY crop_name ASC'
        );
        $statement->execute();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'crop_name' => (string) $row['crop_name'],
                'price' => (float) $row['price'],
                'trend' => (string) $row['trend'],
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            $statement->fetchAll()
        );
    } catch (Throwable $exception) {
        return [];
    }
}

function fetch_da_market_monitor(array $previousPrices = []): array
{
    $pageUrl = 'https://www.da.gov.ph/price-monitoring/';
    $source = [
        'label' => 'Department of Agriculture Price Monitoring',
        'status' => 'unavailable',
        'url' => $pageUrl,
        'updated_at' => date('Y-m-d H:i:s'),
        'note' => 'Official market monitor could not be reached. Showing the local market table.',
    ];

    $html = weagri_fetch_text_url($pageUrl, 'text/html');
    if ($html === null) {
        return [
            'prices' => [],
            'source' => $source,
        ];
    }

    $links = [];
    if (preg_match_all('/href=["\']([^"\']*Daily-Price-Index[^"\']*\.pdf)["\']/i', $html, $matches)) {
        $links = $matches[1];
    }

    if (!$links) {
        $source['status'] = 'source-page';
        $source['note'] = 'Official price monitoring page loaded, but no Daily Price Index PDF was found. Showing the local market table.';

        return [
            'prices' => [],
            'source' => $source,
        ];
    }

    $pdfUrl = weagri_absolute_url((string) $links[0], 'https://www.da.gov.ph');
    $source['url'] = $pdfUrl;
    $source['status'] = 'official-source';
    $source['note'] = 'Official DA Daily Price Index was found. Showing extracted prices when readable, otherwise the local market table.';

    $pdfRaw = weagri_fetch_text_url($pdfUrl, 'application/pdf');
    if ($pdfRaw === null) {
        return [
            'prices' => [],
            'source' => $source,
        ];
    }

    $prices = extract_da_market_prices($pdfRaw, $previousPrices);
    if ($prices) {
        $source['status'] = 'live';
        $source['note'] = 'Prices were extracted from the latest official DA Daily Price Index.';
    }

    return [
        'prices' => $prices,
        'source' => $source,
    ];
}

function extract_da_market_prices(string $rawDocument, array $previousPrices = []): array
{
    $normalizedText = preg_replace('/\s+/', ' ', str_replace(["\0", "\r", "\n"], ' ', $rawDocument));
    if (!is_string($normalizedText) || $normalizedText === '') {
        return [];
    }

    $targets = [
        'Rice' => ['Regular Milled Rice', 'Well Milled Rice', 'Rice'],
        'Corn' => ['Yellow Corn', 'White Corn', 'Corn'],
        'Tomato' => ['Tomato'],
        'Eggplant' => ['Eggplant', 'Talong'],
    ];

    $previousByCrop = [];
    foreach ($previousPrices as $row) {
        $previousByCrop[mb_strtolower((string) ($row['crop_name'] ?? ''))] = (float) ($row['price'] ?? 0);
    }

    $prices = [];
    foreach ($targets as $cropName => $labels) {
        $price = null;
        foreach ($labels as $label) {
            $pattern = '/' . preg_quote($label, '/') . '.{0,220}?(\d{1,3}(?:,\d{3})*(?:\.\d{1,2})?)/i';
            if (preg_match($pattern, $normalizedText, $match)) {
                $candidate = (float) str_replace(',', '', $match[1]);
                if ($candidate > 0 && $candidate < 1000) {
                    $price = $candidate;
                    break;
                }
            }
        }

        if ($price === null) {
            continue;
        }

        $previousPrice = $previousByCrop[mb_strtolower($cropName)] ?? 0.0;
        $trend = 'stable';
        if ($previousPrice > 0 && abs($price - $previousPrice) >= 0.01) {
            $trend = $price > $previousPrice ? 'up' : 'down';
        }

        $prices[] = [
            'id' => 0,
            'crop_name' => $cropName,
            'price' => $price,
            'trend' => $trend,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    return $prices;
}

function build_dashboard_insight(array $metrics, array $marketPrices): string
{
    $soil = (float) $metrics['soil_moisture'];
    $rain = (float) ($metrics['rain_probability'] ?? $metrics['crop_health'] ?? 0);
    $temperature = (float) $metrics['temperature'];
    $risingMarkets = count(array_filter($marketPrices, static fn (array $row): bool => $row['trend'] === 'up'));

    if ($rain >= 60.0) {
        return 'Rain is likely today. Avoid spraying if possible, check drainage, and keep seedlings protected from waterlogging.';
    }

    if ($soil < 45.0) {
        return 'Soil moisture is trending low. Check the field surface and root zone before irrigating, then water deeply rather than lightly if the crop is stressed.';
    }

    if ($temperature >= 33.0) {
        return 'Temperature is high. Avoid midday spraying, monitor wilting, and prioritize early morning irrigation checks.';
    }

    return $risingMarkets > 1
        ? 'Field conditions look steady and several market prices are rising. Keep monitoring moisture and consider timing harvest or sales carefully.'
        : 'Weather readings are steady. Keep scouting weekly, maintain even moisture, and watch market changes before making large selling decisions.';
}

function fetch_open_queries(PDO $pdo, ?int $userId = null): int
{
    try {
        if ($userId !== null && $userId > 0) {
            $statement = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM messages
                 WHERE receiver_id = :user_id
                   AND is_read = 0'
            );
            $statement->execute(['user_id' => $userId]);
            return (int) $statement->fetchColumn();
        }

        $statement = $pdo->query('SELECT COUNT(*) FROM messages');
        return (int) $statement->fetchColumn();
    } catch (Throwable $exception) {
        return 0;
    }
}

try {
    $pdo = weagri_dashboard_pdo();
    $currentUser = weagri_auth_current_user($pdo);
    $requestedLocation = trim((string) ($_GET['location'] ?? ''));
    $weatherPayload = weagri_fetch_open_meteo_forecast($requestedLocation);
    $latest = fetch_latest_metrics_log($pdo) ?? fetch_legacy_metrics($pdo);
    $trendRows = fetch_metrics_trends($pdo);

    if (!$trendRows) {
        $trendRows = fetch_legacy_metrics_trends($pdo);
    }

    $marketPrices = fetch_market_prices($pdo);

    if (!$marketPrices) {
        $marketPrices = fetch_legacy_market_prices($pdo);
    }

    $daMarket = fetch_da_market_monitor($marketPrices);
    $marketSource = $daMarket['source'];
    if (!empty($daMarket['prices'])) {
        $marketPrices = $daMarket['prices'];
    } elseif ($marketPrices) {
        $marketSource = array_merge($marketSource, [
            'label' => 'Local market table',
            'status' => 'database',
            'updated_at' => (string) ($marketPrices[0]['updated_at'] ?? date('Y-m-d H:i:s')),
            'note' => 'Showing prices stored in MySQL. The official DA monitor is linked for verification when live extraction is not available.',
        ]);
    } else {
        $marketPrices = weagri_demo_market_prices();
        $marketSource = array_merge($marketSource, [
            'label' => 'Fallback sample prices',
            'status' => 'fallback',
            'updated_at' => date('Y-m-d H:i:s'),
            'note' => 'Showing sample prices because no market rows were found and live extraction was unavailable.',
        ]);
    }

    if (!$latest && !$weatherPayload) {
        echo json_encode(
            array_replace(
                weagri_demo_dashboard_payload('empty', 'No dashboard sensor readings have been recorded yet.'),
                ['market_source' => $marketSource]
            ),
            JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    $weatherMetrics = $weatherPayload['metrics'] ?? [];

    $metrics = [
        'id' => (int) ($latest['id'] ?? 0),
        'sensor_node_id' => (string) ($latest['sensor_node_id'] ?? 'open-meteo'),
        'temperature' => (float) ($weatherMetrics['temperature'] ?? ($latest['temperature'] ?? 28.4)),
        'soil_moisture' => (float) ($weatherMetrics['soil_moisture'] ?? ($latest['soil_moisture'] ?? 64.5)),
        'crop_health' => (float) ($weatherMetrics['rain_probability'] ?? 24),
        'rain_probability' => (float) ($weatherMetrics['rain_probability'] ?? 24),
        'open_queries' => fetch_open_queries($pdo, $currentUser ? (int) $currentUser['id'] : null),
        'timestamp' => (string) ($weatherMetrics['timestamp'] ?? ($latest['recorded_at'] ?? date('Y-m-d H:i:s'))),
    ];

    echo json_encode([
        'ok' => true,
        'source' => $weatherPayload ? 'open-meteo' : 'mysql',
        'message' => $weatherPayload ? 'Live weather forecast loaded.' : 'Live dashboard metrics loaded.',
        'metrics' => $metrics,
        'weather_calendar' => $weatherPayload['calendar'] ?? weagri_demo_weather_calendar(),
        'weather_location' => (string) ($weatherPayload['location'] ?? ($requestedLocation !== '' ? $requestedLocation : 'Miagao, Iloilo')),
        'market_prices' => $marketPrices,
        'market_source' => $marketSource,
        'trends' => [
            'weather' => array_map(
                static fn (array $row): array => [
                    'label' => $row['label'],
                    'value' => (float) $row['temperature'],
                    'recorded_at' => $row['recorded_at'],
                ],
                $trendRows
            ),
            'soil_health' => array_map(
                static fn (array $row): array => [
                    'label' => $row['label'],
                    'soil_moisture' => (float) $row['soil_moisture'],
                    'crop_health' => (float) $row['crop_health'],
                    'recorded_at' => $row['recorded_at'],
                ],
                $trendRows
            ),
            'market_prices' => array_map(
                static fn (array $row): array => [
                    'label' => $row['crop_name'],
                    'value' => (float) $row['price'],
                    'trend' => $row['trend'],
                ],
                $marketPrices
            ),
        ],
        'insight' => build_dashboard_insight($metrics, $marketPrices),
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(200);
    $requestedLocation = trim((string) ($_GET['location'] ?? ''));
    $weatherPayload = weagri_fetch_open_meteo_forecast($requestedLocation);
    $fallbackPayload = weagri_demo_dashboard_payload(
        'error',
        'Dashboard database is unavailable. Import database/dashboard_schema.sql in phpMyAdmin to enable live MySQL data.',
        $weatherPayload
    );
    $daMarket = fetch_da_market_monitor($fallbackPayload['market_prices']);

    if (!empty($daMarket['prices'])) {
        $fallbackPayload['market_prices'] = $daMarket['prices'];
        $fallbackPayload['market_source'] = $daMarket['source'];
        $fallbackPayload['trends']['market_prices'] = array_map(
            static fn (array $row): array => [
                'label' => $row['crop_name'],
                'value' => (float) $row['price'],
                'trend' => $row['trend'],
            ],
            $daMarket['prices']
        );
    } elseif (($daMarket['source']['status'] ?? 'unavailable') !== 'unavailable') {
        $fallbackPayload['market_source'] = array_merge($daMarket['source'], [
            'label' => 'Fallback sample prices',
            'status' => 'fallback',
            'note' => 'Official DA price monitoring was found, but readable prices could not be extracted. Showing sample prices.',
        ]);
    }

    echo json_encode($fallbackPayload, JSON_UNESCAPED_SLASHES);
}
