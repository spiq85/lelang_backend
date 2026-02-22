<?php

echo "=== Testing API Endpoints ===\n\n";

// Test 1: Banners
echo "1. Testing /api/banners\n";
$banners = json_decode(file_get_contents('http://localhost:8000/api/banners'), true);
echo "   Status: " . ($banners['success'] ? 'OK' : 'ERROR') . "\n";
echo "   Data count: " . count($banners['data'] ?? []) . "\n\n";

// Test 2: Auction Batches
echo "2. Testing /api/auction-batches\n";
try {
    $batches = json_decode(file_get_contents('http://localhost:8000/api/auction-batches'), true);
    echo "   Status: OK\n";
    echo "   Batches found: " . count($batches['data'] ?? []) . "\n";
    if (!empty($batches['data'])) {
        echo "   First batch: " . ($batches['data'][0]['title'] ?? 'N/A') . "\n";
    }
} catch (Exception $e) {
    echo "   Status: ERROR - " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Home Trending
echo "3. Testing /api/home/trending\n";
try {
    $trending = json_decode(file_get_contents('http://localhost:8000/api/home/trending'), true);
    echo "   Status: OK\n";
    echo "   Products: " . count($trending['data'] ?? []) . "\n";
} catch (Exception $e) {
    echo "   Status: ERROR - " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Home Closed
echo "4. Testing /api/home/closed\n";
try {
    $closed = json_decode(file_get_contents('http://localhost:8000/api/home/closed'), true);
    echo "   Status: OK\n";
    echo "   Products: " . count($closed['data'] ?? []) . "\n";
} catch (Exception $e) {
    echo "   Status: ERROR - " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== Test Complete ===\n";
