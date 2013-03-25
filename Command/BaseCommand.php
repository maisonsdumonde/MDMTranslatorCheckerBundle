<?php

namespace MDM\TranslatorCheckerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends ContainerAwareCommand
{
    /**
     * @param OutputInterface $output
     * @param string          $text
     * @param string          $style
     */
    public function writeSection(OutputInterface $output, $text, $style = 'bg=blue;fg=white')
    {
        $output->writeln(array(
            '',
            $this->getHelperSet()->get('formatter')->formatBlock($text, $style, true),
            '',
        ));
    }
}
