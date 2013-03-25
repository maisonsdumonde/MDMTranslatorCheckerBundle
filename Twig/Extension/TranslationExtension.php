<?php

namespace MDM\TranslatorCheckerBundle\Twig\Extension;

use MDM\TranslatorCheckerBundle\Twig\NodeVisitor\TranslationNodeVisitor;

class TranslationExtension extends \Twig_Extension
{
    protected $translationNodeVisitor;

    public function __construct()
    {
        $this->translationNodeVisitor = new TranslationNodeVisitor();
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeVisitors()
    {
        return array($this->translationNodeVisitor);
    }

    public function getTranslationNodeVisitor()
    {
        return $this->translationNodeVisitor;
    }

    public function getName()
    {
        return 'mdm_translator';
    }

}
