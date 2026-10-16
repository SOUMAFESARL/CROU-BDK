<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class PublicAssetUrlsTest extends TestCase
{
    private bool $createdManifest = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (! is_file(public_path('build/manifest.json'))) {
            if (! is_dir(public_path('build'))) {
                mkdir(public_path('build'), 0777, true);
            }
            file_put_contents(public_path('build/manifest.json'), json_encode([
                'resources/js/admin.tsx' => [
                    'file' => 'assets/test-admin.js',
                    'isEntry' => true,
                    'css' => ['assets/test-admin.css'],
                ],
            ]));
            $this->createdManifest = true;
        }
    }

    protected function tearDown(): void
    {
        if ($this->createdManifest) {
            unlink(public_path('build/manifest.json'));
        }
        parent::tearDown();
    }

    private function configureAssets(string $documentRoot, ?string $assetOrigin = null): void
    {
        $request = Request::create('https://crou.example/admin/connexion', 'GET', [], [], [], [
            'DOCUMENT_ROOT' => $documentRoot,
        ]);
        $this->app->instance('request', $request);
        $this->app['url']->setRequest($request);
        config(['app.asset_url' => $assetOrigin]);
        $this->app['url']->useAssetOrigin($assetOrigin);
        (new AppServiceProvider($this->app))->boot();
    }

    public function test_project_root_hosting_uses_the_exposed_public_directory(): void
    {
        $this->configureAssets(base_path());
        $this->assertSame('https://crou.example/public/js/user-errors.js', asset('js/user-errors.js'));
        $this->assertSame('https://crou.example/public/build/assets/app.css', asset('build/assets/app.css'));
        $this->assertSame('https://crou.example/admin', url('admin'));
    }

    public function test_public_document_root_keeps_standard_asset_urls(): void
    {
        $this->configureAssets(public_path());
        $this->assertSame('https://crou.example/build/assets/app.css', asset('build/assets/app.css'));
    }

    public function test_explicit_asset_origin_is_preserved(): void
    {
        $this->configureAssets(base_path(), 'https://cdn.example');
        $this->assertSame('https://cdn.example/build/assets/app.css', asset('build/assets/app.css'));
    }

    public function test_vite_entrypoints_use_the_same_asset_origin(): void
    {
        $this->configureAssets(base_path());
        Vite::useHotFile(storage_path('framework/testing-no-vite-hot'));
        $html = (string) Vite::useBuildDirectory('build')(['resources/js/admin.tsx']);
        $this->assertStringContainsString('https://crou.example/public/build/assets/', $html);
        $this->assertStringNotContainsString('src="https://crou.example/build/', $html);
    }
}
