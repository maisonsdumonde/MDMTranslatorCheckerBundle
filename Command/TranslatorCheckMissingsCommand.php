<?php

namespace MDM\TranslatorCheckerBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\MessageCatalogue;

class TranslatorCheckMissingsCommand extends BaseCommand
{
    private $testsSuite = array();
    private $ignoreFile = 'translation.check-missing.ignored.php';
    private $currentLocale;

    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('translation:check-missings')
            ->setDescription('Check missings translations inserted in twig')
            ->setDefinition(array(
                new InputArgument('locale', InputArgument::REQUIRED, 'The locale'),
                new InputOption('show-unused', null, InputOption::VALUE_NONE, 'Show unused elements'),
                new InputOption('junit', null, InputOption::VALUE_REQUIRED, 'The junit file to export'),
            ))
            ->setHelp('
To check your translation with interact mode run :

$ <info>php app/console translation:check-missings [--show-unused]</info>

Or for a junit output

$ <info>php app/console translation:check-missings en -n --junit myjunit.xml</info>
$ <info>php app/console translation:check-missings fr,en -n --junit myjunit.xml</info>
');
    }

    /**
     * @{inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        foreach (explode(',', $input->getArgument('locale')) as $locale) {
            $this->writeSection($output, 'Check missings translations for '.$locale);
            $this->executeForLocale($locale, $input, $output);
        }
    }

    /**
     * Execute command for a given locale
     * @param string          $locale
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function executeForLocale($locale, InputInterface $input, OutputInterface $output)
    {
        $this->currentLocale = $locale;

        $catalogue = new MessageCatalogue($this->currentLocale);

        $extractor = $this->getContainer()->get("mdm.twig.translation.extractor");
        $extractor->extract('src', $catalogue);

        $this->getContainer()->get('translator')->trans('Warmup for mdm translator', array(), 'messages', $this->currentLocale);
        $translations = $this->getContainer()->get('mdm.translator')->getTranslations($this->currentLocale);

        // Apply detections
        $this->detectMissingTranslations($catalogue, $translations, $input, $output);
        $ignored = $this->detectNotTranslated($catalogue, $input, $output);
        $this->updateIgnoreList($ignored);
        $this->detectUnused($catalogue, $translations, $input, $output);

        if ($input->getOption('junit')) {
            $this->saveJunit($input->getOption('junit'));
        }
    }

    /**
     * @param MessageCatalogue $catalogue    extracted from twig
     * @param MessageCatalogue $translations from messages.xx.yml
     * @param InputInterface   $input
     * @param OutputInterface  $output
     */
    public function detectMissingTranslations(MessageCatalogue $catalogue, MessageCatalogue $translations, InputInterface $input, OutputInterface $output)
    {
        $this->testsSuite['Missing translation '.$this->currentLocale] = array('Check for missings translations' => array());

        foreach (array_diff(array_keys($catalogue->all('messages')), array_keys($translations->all('messages'))) as $key) {
            $output->writeln('<error>Missing translation</error> '.$key);

            $this->testsSuite['Missing translation '.$this->currentLocale]['Check for missings translations'][] = $key;
        }
    }

    /**
     * Detect string in twig without trans tag
     * @param  MessageCatalogue $catalogue extracted from twig
     * @param  InputInterface   $input
     * @param  OutputInterface  $output
     * @return array            ignore list
     */
    public function detectNotTranslated(MessageCatalogue $catalogue, InputInterface $input, OutputInterface $output)
    {
        // String not in trans tags
        $ignored = file_exists($this->ignoreFile) ? include($this->ignoreFile) : array();
        $dialog = $this->getHelperSet()->get('dialog');
        $this->testsSuite['Missing translation '.$this->currentLocale]['Check for not translated strings'] = array();

        foreach ($catalogue->all('not_translated') as $str) {
            if (isset($ignored[$str])) {
                continue;
            }

            $output->writeln('<error>No translation found for</error> '.$str);
            $this->testsSuite['Missing translation '.$this->currentLocale]['Check for not translated strings'][] = $str;

            if ($input->getOption('no-interaction')) {
                continue;
            }

            $ignored = $dialog->askAndValidate($output, 'Press enter to continue or <info>i</info> to add into ignore list ', function ($answer) use ($str, $ignored) {
                if ($answer == 'i') {
                    $ignored[$str] = true;
                }

                return $ignored;
            });
        }

        return $ignored;
    }

    /**
     * @param MessageCatalogue $catalogue    extracted from twig
     * @param MessageCatalogue $translations from messages.xx.yml
     * @param InputInterface   $input
     * @param OutputInterface  $output
     */
    protected function detectUnused(MessageCatalogue $catalogue, MessageCatalogue $translations, InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('show-unused')) {
            return ;
        }

        // Not usage found for
        $this->testsSuite['Unused translation '.$this->currentLocale]['Check for unused translations'] = array();

        foreach (array_diff(array_keys($translations->all('messages')), array_keys($catalogue->all('messages'))) as $key) {
            $output->writeln('Unused translation '.$key);

            $this->testsSuite['Unused translation '.$this->currentLocale]['Check for unused translations'][] = $key;
        }
    }

    /**
     * Update ignore file
     * @param  array $ignored Strings to ignore
     * @return bool
     */
    protected function updateIgnoreList(array $ignored)
    {
        if ($ignored == include($this->ignoreFile)) {
            return false;
        }

        $fp = fopen($this->ignoreFile, 'w');
        fputs($fp, '<?php
// List of ignored strings for translation:check-missings
// Generated at '.date('c').'

return '.var_export($ignored, true).';');

        fclose($fp);

        return true;
    }

    /**
     * Save as junit
     * @param string $filename
     */
    protected function saveJunit($filename)
    {
        $xml = new \SimpleXMLElement('<testsuites />');

        foreach ($this->testsSuite as $suiteName => $tests) {
            $suite = $xml->addChild('testsuite');
            $suite->addAttribute('name', $suiteName);

            foreach ($tests as $name => $failures) {
                $testcase = $suite->addChild('testcase');
                $testcase->addAttribute('name', $name);

                foreach ($failures as $failure) {
                    $fail = $testcase->addChild('failure');
                    $fail->addAttribute('message', $failure);
                    $fail->addAttribute('type', 'failed');
                }
            }
        }

        $xml->asXML($filename);
    }

    /**
     * @{inheritDoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $this->writeSection($output, 'Check missings translations');

        if (!$input->getArgument('locale')) {
            $input->setArgument('locale', $dialog->ask($output, 'Please specify a culture (default: en) ', 'en'));
        }
    }

}
