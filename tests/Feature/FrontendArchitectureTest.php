<?php

namespace HexaPackageSmokeTests\LaravelHexaPackageTelegram;

use hexa_core\Support\PackageAssetRegistry;
use Tests\TestCase;

class FrontendArchitectureTest extends TestCase
{
    public function test_frontend_workflows_are_static_and_allowlisted(): void
    {
        $root = dirname(__DIR__, 2);
        $assets = app(PackageAssetRegistry::class)->assetsFor('telegram');

        foreach (['raw-tools.js', 'settings.js'] as $asset) {
            $this->assertArrayHasKey($asset, $assets);
            $this->assertFileExists($assets[$asset]);
            $content = (string) file_get_contents($assets[$asset]);
            $this->assertDoesNotMatchRegularExpression('/@json|\{\{|\}\}|@(?:if|foreach|php|route)\b/', $content);
        }
    }

    public function test_views_reference_external_workflows(): void
    {
        $root = dirname(__DIR__, 2);
        $raw = (string) file_get_contents($root . '/resources/views/raw/index.blade.php');
        $settings = (string) file_get_contents($root . '/resources/views/settings/index.blade.php');

        $this->assertStringContainsString('telegram::raw.scripts', $raw);
        $this->assertStringContainsString('telegram::settings.scripts', $settings);
        $this->assertStringNotContainsString('function telegramSend()', $raw);
        $this->assertStringNotContainsString('function telegramSettingsPage()', $settings);
    }
}
