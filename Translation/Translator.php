<?php

namespace MDM\TranslatorCheckerBundle\Translation;

use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;

class Translator extends BaseTranslator
{
    /**
     * Give all translations strings for a given local
     * @param  string                                         $locale
     * @return Symfony\Component\Translation\MessageCatalogue
     */
    public function getTranslations($locale)
    {
        if (!isset($this->catalogues[$locale])) {
            $this->loadCatalogue($locale);
        }

        return $this->catalogues[$locale];
    }
}
