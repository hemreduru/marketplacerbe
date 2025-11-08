<?php

// Trendyol API Configuration
define('INTEGRATOR', 'Entegrasyoncum');
define('MERCHANT_ID', '342591');
define('API_KEY', 'PICixvzGypfjiBfTVz0z');
define('API_SECRET', '95HaBdU0zMsWoPxYMywQ');

class Trendyol
{
    public function getProducts($page = 0, $pageSize = 10)
    {
        $query = array(
            'page' => $page,
            'size' => $pageSize
        );
        $url = 'https://api.trendyol.com/sapigw/suppliers/' . MERCHANT_ID . '/products';
        $productList = $this->call($url, $query);
        return $productList;
    }

    public function getOrders($page = 0, $pageSize = 10)
    {
        $query = array(
            'page' => $page,
            'size' => $pageSize,
            'orderByField' => 'PackageLastModifiedDate',
            'orderByDirection' => 'DESC'
        );
        $url = 'https://api.trendyol.com/sapigw/suppliers/' . MERCHANT_ID . '/orders';
        $orderList = $this->call($url, $query);
        return $orderList;
    }

    public function call($url, $params)
    {
        $fullUrl = $url . ($params ? '?' . http_build_query($params) : null);

        echo "🔗 Full URL: {$fullUrl}\n";

        // Create authorization
        $authString = API_KEY . ':' . API_SECRET;
        $authBase64 = base64_encode($authString);

        echo "🔐 Auth String: {$authString}\n";
        echo "🔐 Auth Base64: {$authBase64}\n\n";

        $curl = curl_init($fullUrl);
        $header = array(
            'Authorization: Basic ' . $authBase64,
            'User-Agent: ' . MERCHANT_ID . ' - ' . INTEGRATOR,
            'Content-Type: application/json'
        );

        echo "📋 Headers:\n";
        foreach ($header as $h) {
            echo "   - {$h}\n";
        }
        echo "\n";

        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlerror = curl_error($curl);

        echo "📊 HTTP Code: {$httpcode}\n";
        echo "📄 Raw Response (first 500 chars):\n";
        echo substr($response, 0, 500) . "\n\n";

        curl_close($curl);

        if ($curlerror) {
            return ['error' => $curlerror, 'http_code' => $httpcode, 'raw_response' => $response];
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'error' => 'JSON decode error: ' . json_last_error_msg(),
                'http_code' => $httpcode,
                'raw_response' => $response
            ];
        }

        return $decoded;
    }
}

echo "🚀 Testing Trendyol Stage API with CURL\n";
echo "=========================================\n\n";

$ty = new Trendyol();

// Test 1: Get Products
echo "📦 Fetching Products...\n";
echo "------------------------\n";
$products = $ty->getProducts(0, 5);

if (isset($products['error'])) {
    echo "❌ ERROR: " . $products['error'] . "\n";
    echo "HTTP Code: " . $products['http_code'] . "\n\n";
} else {
    echo "✅ SUCCESS!\n\n";
    echo json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    // Save to file
    file_put_contents(__DIR__ . '/trendyol_products.json', json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "💾 Products saved to: trendyol_products.json\n\n";
}

// Test 2: Get Orders
echo "📋 Fetching Orders...\n";
echo "------------------------\n";
$orders = $ty->getOrders(0, 5);

if (isset($orders['error'])) {
    echo "❌ ERROR: " . $orders['error'] . "\n";
    echo "HTTP Code: " . $orders['http_code'] . "\n\n";
} else {
    echo "✅ SUCCESS!\n\n";
    echo json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    // Save to file
    file_put_contents(__DIR__ . '/trendyol_orders.json', json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "💾 Orders saved to: trendyol_orders.json\n\n";
}

echo "=========================================\n";
echo "✨ Test completed!\n";
