<?php

use App\Ai\Agents\InvoiceAnalysisAgent;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Client\RequestException;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$agent = new InvoiceAnalysisAgent;
try {
    $result = $agent->prompt('hello', model: 'nvidia/nemotron-nano-12b-v2-vl:free');
    echo "Success!\n";
} catch (RequestException $e) {
    echo $e->response->body();
} catch (Exception $e) {
    echo $e->getMessage();
}
