<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $r = App\Models\Product::where('is_trending', true)
        ->where('status', 'published')
        ->withCount(['bidItems as bids_count'])
        ->with(['images', 'seller'])
        ->limit(2)
        ->get();
    echo "OK: " . count($r) . " products\n";
    foreach ($r as $p) {
        echo "  {$p->product_name} bids_count={$p->bids_count}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
