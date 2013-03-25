<?php

namespace MDM\TranslatorCheckerBundle\Duplicates;

use MDM\TranslatorCheckerBundle\Translation\Translator;
use Symfony\Component\Translation\MessageCatalogue;

class Detector
{
    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Detect duplication in translation
     * @param  string $locale
     * @param  string $catalog
     * @return array
     */
    public function detect($locale, $catalog = 'messages')
    {
        $translations = $this->translator->getTranslations($locale);
        $strs = array_count_values($translations->all($catalog));

        $duplicates = array();

        foreach ($strs as $str => $nbTime) {
            if ($nbTime > 1) {
                $duplicates[$str] = $this->array_search_all($str, $translations);
            }
        }

        return $duplicates;
    }

    /**
     * Recursive array_search
     * @param  string           $needle
     * @param  MessageCatalogue $catalog
     * @return array
     */
    private function array_search_all($needle, MessageCatalogue $catalog)
    {
        $array = array();

        foreach ($catalog->all() as $haystack) {
            foreach ($haystack as $k => $v) {
                if ($haystack[$k] == $needle) {
                   $array[] = $k;
                }
            }
        }

        return $array;
    }
}
