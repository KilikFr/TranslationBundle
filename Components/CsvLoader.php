<?php

namespace Kilik\TranslationBundle\Components;

/**
 * Class CsvLoader
 */
class CsvLoader
{

    /**
     * Load CSV File.
     *
     * @param       $filepath
     * @param array $bundles bundles names to load
     * @param array $domains domains to load
     * @param array $locales locales to load
     *
     * @throws \Exception
     *
     * @return array
     */
    public static function load($filepath, $bundles, $domains, $locales)
    {
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $localesKeys = [];
        $columnsKeys = null;

        $translations = [];

        foreach ($lines as $lineId => $line) {
            $row = explode("\t", $line);
            // detect columns names
            if (is_null($columnsKeys)) {
                $columnsKeys = [];
                foreach ($row as $key => $columnName) {
                    $columnsKeys[$columnName] = $key;
                }

                // check mandatory columns
                foreach (['Bundle', 'Domain', 'Key'] as $mandatoryColumn) {
                    if (!in_array($mandatoryColumn, $row)) {
                        throw new \Exception('mandatory column '.$mandatoryColumn.' is missing');
                    }
                }
                // check wanted locales
                foreach ($locales as $locale) {
                    $localeKey = array_search($locale, $row);
                    if ($localeKey === false) {
                        throw new \Exception('locale column '.$locale.' is missing');
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
                                throw new \Exception('missing column value on line '.$lineId.', column '.$localesKeys[$locale]);

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
