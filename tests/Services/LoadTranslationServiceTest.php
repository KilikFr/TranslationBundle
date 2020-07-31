<?php

declare(strict_types=1);

namespace Kilik\TranslationBundle\Tests\Services;

use Kilik\TranslationBundle\Services\LoadTranslationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FakeBundle extends Bundle
{
    protected $path = __DIR__.'/../Resources/FakeBundle';
}

class LoadTranslationServiceTest extends TestCase
{
    public function testConstruct()
    {
        $service = new LoadTranslationService("root", "translation");
        $this->assertEquals("translation", $service->getAppTranslationsPath());
    }

    public function testLoadBundleTranslationFiles()
    {
        $service = new LoadTranslationService(__DIR__.'/..', __DIR__.'/..');
        $fakeBundle = new FakeBundle();
        $service->loadBundleTranslationFiles($fakeBundle, ['fr', 'en'], ['messages']);

        $wanted = [
            'FakeBundle' => [
                'messages' => [
                    'key1' => [
                        'fr' => 'clé 1',
                        'en' => 'key 1',
                    ],
                    'key2' => [
                        'fr' => 'clé 2',
                        'en' => 'key 2',
                    ],
                    'key3.key1' => [
                        'fr' => 'clé 3.1',
                        'en' => 'key 3.1',
                    ],
                    'key3.key2' => [
                        'fr' => 'clé 3.2',
                        'en' => 'key 3.2',
                    ],
                ],
            ],
        ];

        $this->assertEquals($wanted, $service->getTranslations());
    }
}
