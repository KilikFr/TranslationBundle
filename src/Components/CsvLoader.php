<?php

namespace Kilik\TranslationBundle\Components;

use Kilik\TranslationBundle\Exception\DataException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Translation\Loader\FileLoader;

/**
 * Class CsvLoader
 */
class CsvLoader
{

    /**
     * Load CSV File.
     *
     * @param        $filepath
     * @param array  $bundles bundles names to load
     * @param array  $domains domains to load
     * @param array  $locales locales to load
     * @param string $separator
     *
     * @return array
     * @throws \Exception
     *
     */
    public static function load($filepath, $bundles, $domains, $locales, $separator = "\t"): array
    {
        if (!file_exists($filepath)) {
            throw new FileNotFoundException(sprintf('File "%s" not found.', $filepath));
        }

        $lines = @file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (false === $lines) {
            throw new IOException('Error loading "%s".', $filepath);
        }

        $localesKeys = [];
        $columnsKeys = null;

        $translations = [];

        foreach ($lines as $lineId => $line) {
            $row = explode($separator, $line);
            // detect columns names
            if (is_null($columnsKeys)) {
                $columnsKeys = [];
                foreach ($row as $key => $columnName) {
                    $columnsKeys[$columnName] = $key;
                }

                // check mandatory columns
                foreach (['Bundle', 'Domain', 'Key'] as $mandatoryColumn) {
                    if (!in_array($mandatoryColumn, $row)) {
                        throw new DataException('mandatory column '.$mandatoryColumn.' is missing');
                    }
                }
                // check wanted locales
                foreach ($locales as $locale) {
                    $localeKey = array_search($locale, $row);
                    if ($localeKey === false) {
                        throw new DataException('locale column '.$locale.' is missing');
                    }
                    // keep column id
                    $localesKeys[$locale] = $localeKey;
                }
            } // keep in memory translations
            else {
                $bundleName = $row[$columnsKeys['Bundle']];
                $domainName = $row[$columnsKeys['Domain']];
                if (in_array($bundleName, $bundles) || count($bundles) == 1 && $bundles[0] == 'all') {
                    if (in_array($domainName, $domains) || count($domains) == 1 && $domains[0] == 'all') {
                        foreach ($locales as $locale) {
                            // replace new line unescaped by reald newline (works good wy yaml dumper)
                            if (!isset($row[$localesKeys[$locale]])) {
                                throw new DataException('missing column value on line '.$lineId.', column '.$localesKeys[$locale]);
                            }
                            $value = str_replace('\n', "\n", $row[$localesKeys[$locale]]);
                            // keep only non blank translations
                            if ($value) {
                                // bundle / domain / key
                                $translations[$bundleName][$domainName][$row[$columnsKeys['Key']]][$locale] = $value;
                            }
                        }
                    }
                }
            }
        }

        return $translations;
    }
}
