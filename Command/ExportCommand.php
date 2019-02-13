<?php
/**
 * This class is inspired from https://github.com/lexik/LexikTranslationBundle.
 */

namespace Kilik\TranslationBundle\Command;

use Kilik\TranslationBundle\Services\LoadTranslationService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportCommand.
 */
class ExportCommand extends ContainerAwareCommand
{

    /**
     * Load translation service
     *
     * @var LoadTranslationService
     */
    private $loadService;

    /**
     * @param LoadTranslationService $service
     */
    public function setLoadService(LoadTranslationService $service)
    {
        $this->loadService = $service;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('kilik:translation:export')
            ->setDescription('Export translations from project bundles to CSV file')
            ->addArgument('locale', InputArgument::REQUIRED, 'Locale used as reference in application')
            ->addArgument('locales', InputArgument::REQUIRED, 'Locales to export missing translations')
            ->addArgument('bundles', InputArgument::REQUIRED, 'Bundles scope (app for symfony4 core application)')
            ->addArgument('csv', InputArgument::REQUIRED, 'Output CSV filename')
            ->addOption('domains', null, InputOption::VALUE_OPTIONAL, 'Domains', 'all')
            ->addOption('only-missing', null, InputOption::VALUE_NONE, 'Export only missing translations')
            ->addOption('separator', 'sep', InputOption::VALUE_REQUIRED, 'The character used as separator', "\t");
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundlesNames = explode(',', $input->getArgument('bundles'));

        $locale = $input->getArgument('locale');
        $locales = explode(',', $input->getArgument('locales'));
        $domains = explode(',', $input->getOption('domains'));

        $separator = $input->getOption('separator');

        // load all translations
        foreach ($bundlesNames as $bundleName) {
            // fix symfony 4 applications (use magic bundle name "app")
            if ('app' === $bundleName) {
                // locales to export
                $this->loadService->loadAppTranslationFiles($locales, $domains);
                // locale reference
                $this->loadService->loadAppTranslationFiles([$locale], $domains);
            } else {
                $bundle = $this->getApplication()->getKernel()->getBundle($bundleName);

                if (method_exists($bundle, 'getParent') && null !== $bundle->getParent()) {
                    $bundles = $this->getApplication()->getKernel()->getBundle($bundle->getParent(), false);
                    $bundle = $bundles[1];
                    $output->writeln('<info>Using: '.$bundle->getName().' as bundle to lookup translations files for.</info>');
                }

                // locales to export
                $this->loadService->loadBundleTranslationFiles($bundle, $locales, $domains);
                // locale reference
                $this->loadService->loadBundleTranslationFiles($bundle, [$locale], $domains);
            }
        }

        // and export data as CSV (tab separated values)
        $columns = ['Bundle', 'Domain', 'Key', $locale];
        foreach ($locales as $localeColumn) {
            $columns[] = $localeColumn;
        }

        $buffer = implode($separator, $columns).PHP_EOL;

        foreach ($this->loadService->getTranslations() as $bundleName => $domains) {
            foreach ($domains as $domain => $translations) {
                foreach ($translations as $trKey => $trLocales) {
                    $missing = false;

                    $data = [$bundleName, $domain, $trKey];
                    if (isset($trLocales[$locale])) {
                        $data[] = $this->fixMultiLine($trLocales[$locale]);
                    } else {
                        $data[] = '';
                        $missing = true;
                    }

                    foreach ($locales as $trLocale) {
                        if (isset($trLocales[$trLocale])) {
                            $data[] = $this->fixMultiLine($trLocales[$trLocale]);
                        } else {
                            $data[] = '';
                            $missing = true;
                        }
                    }

                    if (!$input->getOption('only-missing') || $missing) {
                        $buffer .= implode($separator, $data).PHP_EOL;
                    }
                }
            }
        }
        file_put_contents($input->getArgument('csv'), $buffer);
        $output->writeln('<info>Saving translations to : '.$input->getArgument('csv').' (CSV tab separated value).</info>');
    }

    /**
     * Makes sure translation files with multi line strings result in correct csv files.
     *
     * @param string $str
     *
     * @return string
     */
    protected function fixMultiLine($str)
    {
        $str = str_replace(PHP_EOL, "\\n", $str);
        if (substr($str, -2) === "\\n") {
            $str = substr($str, 0, -2);//Not doing this results in \n at the end of some strings after import.
        }

        return $str;
    }
}
