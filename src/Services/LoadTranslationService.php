<?php

namespace Kilik\TranslationBundle\Services;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class LoadTranslationService.
 *
 * load translations from common yaml symfony resources files
 */
class LoadTranslationService
{

    /**
     * translations (bundle name/domain/key=>value).
     *
     * @var array
     */
    private $translations;

    /**
     * Root Dir.
     *
     * @var string
     */
    private $rootDir = null;

    /**
     * Translation Path.
     *
     * @var string
     */
    private $translationPath;

    /**
     * LoadTranslationService constructor.
     *
     * @param string $rootDir
     */
    public function __construct($rootDir, $translationPath)
    {
        $this->rootDir = $rootDir;
        $this->translations = [];
        $this->translationPath = $translationPath;
    }

    /**
     * Get Symfony 4 app translations path
     *
     * @return string
     */
    public function getAppTranslationsPath(): string
    {
        return $this->translationPath;
    }

    /**
     * Load app translation file (for symfony 4+ applications)
     *
     * @param array $locales
     * @param array $domains
     */
    public function loadAppTranslationFiles(array $locales, array $domains)
    {
        $path = $this->getAppTranslationsPath();
        $finder = $this->findTranslationsFiles($path, $locales, $domains, false);
        $this->loadTranslationFiles('app', $finder);
    }

    /**
     * Imports translation files form bundles.
     *
     * @param array $bundles
     * @param array $locales
     * @param array $domains
     */
    public function loadBundlesTranslationFiles(array $bundles, array $locales, array $domains)
    {
        if (isset($bundles['app'])) {
            $this->loadAppTranslationFiles($locales, $domains);
        }

        foreach ($bundles as $bundleName => $bundle) {
            if ('app' !== $bundleName) {
                $this->loadBundleTranslationFiles($bundle, $locales, $domains);
            }
        }
    }

    /**
     * Imports translation files form the specific bundles.
     *
     * @param BundleInterface $bundle
     * @param array           $locales
     * @param array           $domains
     */
    public function loadBundleTranslationFiles(BundleInterface $bundle, array $locales, array $domains)
    {
        $path = $bundle->getPath();
        $finder = $this->findTranslationsFiles($path, $locales, $domains);
        $this->loadTranslationFiles($bundle->getName(), $finder);
    }

    /**
     * Return a Finder object if $path has a Resources/translations folder.
     *
     * @param string $path
     * @param array  $locales
     * @param array  $domains
     * @param bool   $autocompletePath
     *
     * @return \Symfony\Component\Finder\Finder
     */
    protected function findTranslationsFiles(string $path, array $locales, array $domains, bool $autocompletePath = true)
    {
        $finder = null;
        if (preg_match('#^win#i', PHP_OS)) {
            $path = preg_replace('#'.preg_quote(DIRECTORY_SEPARATOR, '#').'#', '/', $path);
        }
        if (true === $autocompletePath) {
            $dir = (0 === strpos($path, $this->rootDir.'/Resources')) ? $path : $path.'/Resources/translations';
        } else {
            $dir = $path;
        }
        exec('ls -l '.$dir);
        if (is_dir($dir)) {
            $finder = new Finder();
            $finder->files()
                ->name($this->getFileNamePattern($locales, $domains))
                ->in($dir);
        }

        return (null !== $finder && $finder->count() > 0) ? $finder : null;
    }

    /**
     * Imports some translations files.
     *
     * @param string $bundleName
     * @param Finder $finder
     */
    protected function loadTranslationFiles(string $bundleName, Finder $finder = null)
    {
        if (!$finder instanceof Finder) {
            return;
        }
        foreach ($finder as $file) {
            list($domain, $locale, $extension) = explode('.', $file->getFilename());

            $this->loadTranslationFile($file, $bundleName, $domain, $locale);
        }
    }

    /**
     * Load translation file.
     *
     * @param SplFileInfo $file
     * @param string      $bundleName
     * @param string      $domain
     * @param string      $locale
     */
    protected function loadTranslationFile(SplFileInfo $file, string $bundleName, string $domain, string $locale)
    {
        $lines = Yaml::parse(file_get_contents($file->getPathname()));
        if (is_array($lines)) {
            $this->loadTranslationFromArray($lines, $bundleName, $domain, $locale);
        }
    }

    /**
     * Load translation file.
     *
     * @param array  $lines
     * @param string $bundleName
     * @param string $domain
     * @param string $locale
     * @param string $prefix
     */
    protected function loadTranslationFromArray(array $lines, string $bundleName, string $domain, string $locale, string $prefix = '')
    {
        foreach ($lines as $key => $value) {
            $fullKey = ($prefix != '' ? $prefix.'.' : '').$key;
            if (is_array($value)) {
                $this->loadTranslationFromArray($value, $bundleName, $domain, $locale, $fullKey);
            } else {
                $this->translations[$bundleName][$domain][$fullKey][$locale] = $value;
            }
        }
    }

    /**
     * @param array $locales
     * @param array $domains
     *
     * @return string
     */
    protected function getFileNamePattern(array $locales, array $domains)
    {
        if (count($domains) > 1) {
            $regex = sprintf('/((%s)\.(%s)\.(%s))/', implode('|', $domains), implode('|', $locales), implode('|', ['yml']));
        } elseif ($domains[0] == 'all') {
            $regex = sprintf('/(.*\.(%s)\.(%s))/', implode('|', $locales), implode('|', ['yml']));
        } else {
            $regex = sprintf('/'.$domains[0].'(.*\.(%s)\.(%s))/', implode('|', $locales), implode('|', ['yml']));
        }

        return $regex;
    }

    /**
     * Get translations array.
     *
     * @return array
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }
}
