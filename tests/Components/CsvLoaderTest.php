<?php

declare(strict_types=1);

namespace Kilik\TranslationBundle\Tests;

use Kilik\TranslationBundle\Exception\DataException;
use Kilik\TranslationBundle\Components\CsvLoader;
use Kilik\TranslationBundle\Services\LoadTranslationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class CsvLoaderTest extends TestCase
{
    /**
     * @dataProvider loadProvider
     */
    public function testLoad(array $wanted, string $filepath, array $bundles, array $domains, array $locales, string $separator)
    {
        $data = CsvLoader::load($filepath, $bundles, $domains, $locales, $separator);
        $this->assertEquals($wanted, $data);
    }

    public function loadProvider()
    {
        $basePath = __DIR__.'/../Resources/Components/CsvLoader';

        return [
            // load no bundles
            [[], $basePath.'/test1.csv', [], [], [], ';'],

            // load no domains
            [[], $basePath.'/test1.csv', ['TestBundle'], [], [], ';'],

            // load no locales
            [[], $basePath.'/test1.csv', ['TestBundle'], ['messages'], [], ';'],

            // load TestBundle fr messages
            [
                ['TestBundle' => ['messages' => ['key1' => ['fr' => 'clé 1'], 'key2' => ['fr' => 'clé 2']]]],
                $basePath.'/test1.csv',
                ['TestBundle'],
                ['messages'],
                ['fr'],
                ';',
            ],

            // load TestBundle en messages
            [
                ['TestBundle' => ['messages' => ['key1' => ['en' => 'key 1'], 'key2' => ['en' => 'key 2']]]],
                $basePath.'/test1.csv',
                ['TestBundle'],
                ['messages'],
                ['en'],
                ';',
            ],

            // load single bundle from multiple bundles
            [
                [
                    'TestBundle' => ['messages' => ['key1' => ['en' => 'key 1']]],
                ],
                $basePath.'/test2.csv',
                ['TestBundle'],
                ['messages'],
                ['en'],
                ';',
            ],

            // load multiple bundles
            [
                [
                    'TestBundle' => ['messages' => ['key1' => ['en' => 'key 1']]],
                    'TestBundle2' => ['messages' => ['key1' => ['en' => 'key 1']]],
                ],
                $basePath.'/test2.csv',
                ['TestBundle', 'TestBundle2'],
                ['messages'],
                ['en'],
                ';',
            ],

            // load single domain from multiple domains
            [
                [
                    'TestBundle' => ['messages' => ['key1' => ['en' => 'key 1']]],
                ],
                $basePath.'/test3.csv',
                ['TestBundle'],
                ['messages'],
                ['en'],
                ';',
            ],

            // load multiple domains
            [
                [
                    'TestBundle' => [
                        'messages' => ['key1' => ['en' => 'key 1']],
                        'others' => ['key1' => ['en' => 'key 1']],
                    ],
                ],
                $basePath.'/test3.csv',
                ['TestBundle'],
                ['messages', 'others'],
                ['en'],
                ';',
            ],
        ];
    }

    /**
     * @dataProvider loadFailureProvider
     */
    public function testLoadFailure(string $wantedException, string $filepath, array $bundles, array $domains, array $locales, string $separator)
    {
        $this->expectException($wantedException);

        CsvLoader::load($filepath, $bundles, $domains, $locales, $separator);
    }

    public function loadFailureProvider(): array
    {
        $basePath = __DIR__.'/../Resources/Components/CsvLoader';

        return [
            [FileNotFoundException::class, $basePath.'/missing-file.csv', [], [], [], ';'],
            [DataException::class, $basePath.'/fail1.csv', [], [], [], ';'],
            [DataException::class, $basePath.'/fail2.csv', [], [], [], ';'],
            [DataException::class, $basePath.'/fail3.csv', [], [], [], ';'],
            [DataException::class, $basePath.'/fail4.csv', [], [], [], ';'],
        ];
    }

}
