<?php
class EedClient
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function searchArticles(string $query, int $limit = 18): array
    {
        $query = trim($query) ?: $this->config['default_query'];
        $limit = max(1, min(50, $limit));

        if (!empty($this->config['mock_only'])) {
            return $this->fallbackSearch($query, 'MOCK_ONLY is enabled.');
        }

        $params = [
            'art' => 'artikelsuche',
            'suchbg' => $query,
            'anzahl' => $limit,
            'seite' => 1,
            'bigPicture' => 1,
            'artikeldetails' => 1,
            'morepics' => 1,
        ];

        $response = $this->call($params);
        if (!$response['ok']) {
            return $this->fallbackSearch($query, $response['error'] ?? 'API request failed');
        }

        $items = $this->normalizeArticles($response['data']['treffer'] ?? []);
        return [
            'ok' => true,
            'source' => 'eed',
            'query' => $query,
            'total' => (int)($response['data']['gesamtanzahltreffer'] ?? count($items)),
            'items' => $items,
        ];
    }

    public function articleDetails(string $articleNo): array
    {
        $articleNo = trim($articleNo);
        if (!empty($this->config['mock_only'])) {
            foreach ($this->fallbackItems() as $item) {
                if ($item['id'] === $articleNo) {
                    return ['ok' => true, 'source' => 'mock', 'item' => $item];
                }
            }
        }
        if ($articleNo === '') {
            return ['ok' => false, 'error' => 'Missing article number.'];
        }

        $response = $this->call([
            'art' => 'artikeldetails',
            'artnr' => $articleNo,
            'bigPicture' => 1,
            'morepics' => 1,
        ]);

        if (!$response['ok']) {
            $mock = $this->fallbackItems();
            foreach ($mock as $item) {
                if ($item['id'] === $articleNo) {
                    return ['ok' => true, 'source' => 'mock', 'item' => $item, 'notice' => $response['error'] ?? 'API request failed'];
                }
            }
            return ['ok' => false, 'error' => $response['error'] ?? 'Article not found.'];
        }

        $data = $response['data'];
        $item = $this->normalizeArticle($data);
        return ['ok' => true, 'source' => 'eed', 'item' => $item];
    }

    private function call(array $params): array
    {
        $base = [
            'format' => 'json',
            'id' => $this->config['eed_id'],
            'sessionid' => $_SESSION['eed_sessionid'] ?? 'auto',
            'shopurl' => $this->currentShopUrl(),
            'customerip' => md5($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
        ];

        $url = $this->config['eed_base_url'] . '?' . http_build_query(array_merge($base, $params));
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['request_timeout'],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'ProductSearchSample/1.0',
        ]);

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false || $raw === '') {
            return ['ok' => false, 'error' => $curlError ?: 'Empty EED response.'];
        }
        if ($httpCode >= 400) {
            return ['ok' => false, 'error' => 'EED HTTP error ' . $httpCode . '.'];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'Invalid JSON returned by EED.'];
        }

        if (!empty($data['neuesessionid'])) {
            $_SESSION['eed_sessionid'] = $data['neuesessionid'];
        } elseif (!empty($data['sessionid'])) {
            $_SESSION['eed_sessionid'] = $data['sessionid'];
        }

        if (isset($data['fehlernummer']) && (string)$data['fehlernummer'] !== '0') {
            return ['ok' => false, 'error' => $data['fehlermeldung'] ?? 'EED returned error #' . $data['fehlernummer']];
        }

        return ['ok' => true, 'data' => $data];
    }

    private function currentShopUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? parse_url($this->config['shop_url'], PHP_URL_HOST) ?: 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $scheme . '://' . $host . $uri;
    }

    private function normalizeArticles(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $items[] = $this->normalizeArticle($row);
            }
        }
        return $items;
    }

    private function normalizeArticle(array $row): array
    {
        $id = (string)($row['artikelnummer'] ?? $row['artnr'] ?? $row['id'] ?? '');
        $name = (string)($row['artikelbezeichnung'] ?? $row['bezeichnung'] ?? $row['name'] ?? 'Unnamed product');
        $price = $row['vorgabepreisInklMwst'] ?? $row['ekpreis'] ?? $row['preis'] ?? null;
        $priceText = $price !== null && $price !== '' ? $this->formatPrice($price) : 'Price on request';
        $image = $row['thumbnailurl'] ?? $row['bigPicture'] ?? $row['tempurl'] ?? null;

        return [
            'id' => $id,
            'name' => $name,
            'price' => $priceText,
            'raw_price' => $price,
            'image' => $image ?: 'assets/img/product-placeholder.svg',
            'manufacturer' => $row['artikelhersteller'] ?? $row['hersteller'] ?? 'Not specified',
            'delivery' => $row['lieferzeit'] ?? 'Not specified',
            'orderable' => (($row['bestellbar'] ?? 'N') === 'J'),
            'has_image' => (($row['bild'] ?? 'N') === 'J'),
            'ean' => $row['EAN'] ?? 'N/A',
            'description' => strip_tags((string)($row['artikeltext'] ?? $row['artikelbeschreibung'] ?? $name)),
            'features' => $this->extractFeatures($row),
        ];
    }

    private function extractFeatures(array $row): array
    {
        $features = [];
        foreach (['artikelmerkmal', 'artikeldaten'] as $key) {
            if (!empty($row[$key]) && is_array($row[$key])) {
                foreach ($row[$key] as $feature) {
                    if (is_array($feature)) {
                        $features[] = implode(': ', array_filter(array_map('strval', $feature)));
                    } else {
                        $features[] = (string)$feature;
                    }
                }
            }
        }
        return array_values(array_filter(array_unique($features)));
    }

    private function formatPrice($value): string
    {
        $normalized = str_replace(',', '.', preg_replace('/[^0-9,\.]/', '', (string)$value));
        if ($normalized === '') {
            return 'Price on request';
        }
        return $this->config['currency'] . number_format((float)$normalized, 2);
    }

    private function fallbackSearch(string $query, string $error): array
    {
        if (!$this->config['use_mock_on_api_failure']) {
            return ['ok' => false, 'error' => $error, 'items' => []];
        }

        $queryLower = strtolower($query);
        $items = array_values(array_filter($this->fallbackItems(), function ($item) use ($queryLower) {
            return str_contains(strtolower($item['name'] . ' ' . $item['manufacturer'] . ' ' . $item['description']), $queryLower)
                || $queryLower === strtolower($this->config['default_query']);
        }));

        return [
            'ok' => true,
            'source' => 'mock',
            'query' => $query,
            'total' => count($items),
            'notice' => 'Showing local sample data because the EED API could not be reached: ' . $error,
            'items' => $items,
        ];
    }

    private function fallbackItems(): array
    {
        return [
            ['id'=>'1000100','name'=>'Universal Remote Control','price'=>'€19.90','raw_price'=>19.90,'image'=>'assets/img/product-placeholder.svg','manufacturer'=>'Sample Parts','delivery'=>'2-4 working days','orderable'=>true,'has_image'=>false,'ean'=>'4010001000100','description'=>'Replacement remote control compatible with many TV models.','features'=>['Infrared remote','Pre-programmed','Batteries not included']],
            ['id'=>'1000200','name'=>'Washing Machine Drain Pump','price'=>'€42.50','raw_price'=>42.50,'image'=>'assets/img/product-placeholder.svg','manufacturer'=>'Sample Parts','delivery'=>'3-5 working days','orderable'=>true,'has_image'=>false,'ean'=>'4010001000200','description'=>'Drain pump spare part for selected washing machines.','features'=>['230V motor','Includes filter housing','Appliance fit should be checked']],
            ['id'=>'1000300','name'=>'Refrigerator Door Seal','price'=>'€28.75','raw_price'=>28.75,'image'=>'assets/img/product-placeholder.svg','manufacturer'=>'Sample Parts','delivery'=>'5-7 working days','orderable'=>true,'has_image'=>false,'ean'=>'4010001000300','description'=>'Flexible magnetic door gasket for refrigerator repairs.','features'=>['Magnetic seal','Cut-resistant edge','Model compatibility required']],
            ['id'=>'1000400','name'=>'Dishwasher Spray Arm','price'=>'€16.20','raw_price'=>16.20,'image'=>'assets/img/product-placeholder.svg','manufacturer'=>'Sample Parts','delivery'=>'1-3 working days','orderable'=>true,'has_image'=>false,'ean'=>'4010001000400','description'=>'Upper spray arm replacement for dishwasher water distribution.','features'=>['Snap-fit mount','Heat-resistant plastic','Easy installation']],
            ['id'=>'1000500','name'=>'Vacuum Cleaner HEPA Filter','price'=>'€12.95','raw_price'=>12.95,'image'=>'assets/img/product-placeholder.svg','manufacturer'=>'Sample Parts','delivery'=>'2-4 working days','orderable'=>true,'has_image'=>false,'ean'=>'4010001000500','description'=>'Fine dust HEPA filter for vacuum cleaner maintenance.','features'=>['Washable filter','High dust retention','Replace regularly']],
            ['id'=>'1000600','name'=>'Oven Heating Element','price'=>'€35.40','raw_price'=>35.40,'image'=>'assets/img/product-placeholder.svg','manufacturer'=>'Sample Parts','delivery'=>'4-6 working days','orderable'=>false,'has_image'=>false,'ean'=>'4010001000600','description'=>'Circular fan oven heating element for appliance repair.','features'=>['High temperature rated','Rear mount','Technician installation recommended']],
        ];
    }
}
