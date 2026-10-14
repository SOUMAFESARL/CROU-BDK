<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$service = new App\Services\HousingReportingService;
foreach (App\Services\HousingReportingService::MODULES as $module => $title) {
    try {
        $data = $service->reports($module, now()->startOfYear(), now()->endOfDay(), null);
        echo $module.': OK ('.count($data[$module]['sections'])." sections)\n";
    } catch (Throwable $e) { echo $module.': ERROR '.$e->getMessage()."\n"; }
}

$cite = App\Models\Cite::query()->value('id_cite');
try {
    $data = $service->reports('synthese', now()->startOfYear(), now()->endOfDay(), $cite);
    echo 'Scoped overview: '.count($data)." modules OK\n";
    view()->share('errors', new Illuminate\Support\ViewErrorBag);
    $response = app(App\Http\Controllers\admin\immobilier\ReportingHebergementController::class)->index(Illuminate\Http\Request::create('/admin/gestion-hebergement/reporting'), $service);
    echo 'HTML: '.$response->getStatusCode().' / '.strlen($response->getContent())." bytes\n";
} catch (Throwable $e) { echo 'CHECK ERROR: '.$e->getMessage()."\n"; exit(1); }
