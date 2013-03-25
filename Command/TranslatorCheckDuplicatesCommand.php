<?php

namespace MDM\TranslatorCheckerBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class TranslatorCheckDuplicatesCommand extends BaseCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('translation:check-duplicates')
            ->setDescription('Check duplicates of value or keys in languages files')
            ->setDefinition(array(
                new InputArgument('locale', InputArgument::REQUIRED, 'The locale'),
            ));
    }

    /**
     * @{inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $duplicates = $this->getContainer()->get('mdm.translator_checker.duplicates_detector')->detect($input->getArgument('locale'));

        if (count($duplicates) == 0) {
            $output->writeln('<info>No duplicates found !</info>');
        }

        $output->writeln('<error>Duplicates found : </error>'.count($duplicates));

        foreach ($duplicates as $translation => $keys) {
            $output->writeln("Duplicate found for <info>$translation</info>");
            $output->writeln( implode(', ', $keys ));
            $output->writeln('');
        }
    }

    /**
     * @{inheritDoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $this->writeSection($output, 'Load translation');

        if (!$input->getArgument('locale')) {
            $input->setArgument('locale', $dialog->ask($output, 'Please specify a culture (default: en) ', 'en'));
        }
    }

}
