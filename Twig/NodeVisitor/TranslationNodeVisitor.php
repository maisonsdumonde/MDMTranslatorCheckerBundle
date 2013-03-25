<?php

namespace MDM\TranslatorCheckerBundle\Twig\NodeVisitor;

use Symfony\Bridge\Twig\Node\TransNode;

class TranslationNodeVisitor implements \Twig_NodeVisitorInterface
{
    protected $inNodeTrans = false;

    const UNDEFINED_DOMAIN = '_undefined';

    private $enabled = false;
    private $messages = array();

    public function enable()
    {
        $this->enabled = true;
        $this->messages = array();
    }

    public function disable()
    {
        $this->enabled = false;
        $this->messages = array();
    }

    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * {@inheritdoc}
     */
    public function enterNode(\Twig_NodeInterface $node, \Twig_Environment $env)
    {
        if (!$this->enabled) {
            return $node;
        }

        if (
            $node instanceof \Twig_Node_Expression_Filter &&
            'trans' === $node->getNode('filter')->getAttribute('value') &&
            $node->getNode('node') instanceof \Twig_Node_Expression_Constant
        ) {
            // extract constant nodes with a trans filter
            $this->messages[] = array(
                $node->getNode('node')->getAttribute('value'),
                $this->getReadDomainFromArguments($node->getNode('arguments'), 1),
            );
        } elseif (
            $node instanceof \Twig_Node_Expression_Filter &&
            'transchoice' === $node->getNode('filter')->getAttribute('value') &&
            $node->getNode('node') instanceof \Twig_Node_Expression_Constant
        ) {
            // extract constant nodes with a trans filter
            $this->messages[] = array(
                $node->getNode('node')->getAttribute('value'),
                $this->getReadDomainFromArguments($node->getNode('arguments'), 2),
            );
        } elseif ($node instanceof TransNode) {
            // extract trans nodes
            $this->messages[] = array(
                $node->getNode('body')->getAttribute('data'),
                $this->getReadDomainFromNode($node->getNode('domain')),
            );
            $this->inNodeTrans = true;
        } elseif ($node instanceof \Twig_Node_Text) { // Linear text
            if ($this->inNodeTrans == true) {
                $this->inNodeTrans = false;

                return $node;
            }

            $text = preg_replace("/\\n/", '', $node->getAttribute('data'));
            $text = preg_replace("/\\t/", '', $text);
            $text = str_replace('&nbsp;', ' ', $text);
            $text = trim(strip_tags($text));

            if ($text != '') {
                $this->messages[] = array(
                    $text,
                    'not_translated',
                );
            }
        }

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function leaveNode(\Twig_NodeInterface $node, \Twig_Environment $env)
    {
        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 0;
    }

    /**
     * @param \Twig_Node $arguments
     * @param int        $index
     *
     * @return string|null
     */
    private function getReadDomainFromArguments(\Twig_Node $arguments, $index)
    {
        if ($arguments->hasNode('domain')) {
            $argument = $arguments->getNode('domain');
        } elseif ($arguments->hasNode($index)) {
            $argument = $arguments->getNode($index);
        } else {
            return null;
        }

        return $this->getReadDomainFromNode($argument);
    }

    /**
     * @param \Twig_Node $node
     *
     * @return string|null
     */
    private function getReadDomainFromNode(\Twig_Node $node = null)
    {
        if (null === $node) {
            return null;
        }

        if ($node instanceof \Twig_Node_Expression_Constant) {
            return $node->getAttribute('value');
        }

        return self::UNDEFINED_DOMAIN;
    }
}
