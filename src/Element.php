<?php
/**
 * HTML to Carbon JSON converter
 *
 * This class is heavily lifted from html-to-markdown, see below.
 *
 * @link https://github.com/thephpleague/html-to-markdown/ Latest version on GitHub.
 *
 * @author  Adam McCann (@AssembledAdam)
 * @author Colin O'Dell <colinodell@gmail.com>
 * @author Nick Cernis <nick@cern.is>
 * @license MIT (see LICENSE file)
 */
namespace Candybanana\HtmlToCarbonJson;

class Element
{
    /**
     * @var \DOMNode
     */
    protected $node;

    /**
     * @var ElementInterface|null
     */
    private $nextCached;

    public function __construct(\DOMNode $node)
    {
        $this->node = $node;
    }

    /**
     * @return bool
     */
    public function isBlock()
    {
        switch ($this->getTagName()) {
            case 'blockquote':
            case 'code':
            case 'div':
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
            case 'hr':
            case 'li':
            case 'p':
            case 'ol':
            case 'ul':
            case 'img':
                return true;
            default:
                return false;
        }
    }

    /**
     * Returns node
     *
     * @return \DOMNode
     */
    public function get()
    {
        return $this->node;
    }

    /**
     * Removes this node from the document
     */
    public function remove()
    {
        $this->node->parentNode->removeChild($this->node);
    }

    /**
     * @return bool
     */
    public function isText()
    {
        return $this->getTagName() === '#text';
    }

    /**
     * @return bool
     */
    public function isWhitespace()
    {
        return $this->getTagName() === '#text' && trim($this->getValue()) === '';
    }

    /**
     * @return string
     */
    public function getTagName()
    {
        return $this->node->nodeName;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->node->nodeValue;
    }

    /**
     * @return ElementInterface|null
     */
    public function getParent()
    {
        return new static($this->node->parentNode) ?: null;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return $this->node->hasChildNodes();
    }

    /**
     * @return ElementInterface[]
     */
    public function getChildren()
    {
        $ret = array();
        /** @var \DOMNode $node */
        foreach ($this->node->childNodes as $node) {
            $ret[] = new static($node);
        }

        return $ret;
    }

    /**
     * @return integer
     */
    public function numChildren()
    {
        if (! $this->hasChildren()) {
            return 0;
        }

        $num = 0;

        foreach ($this->getChildren() as $child) {
            $num++;
        }

        return $num;
    }

    /**
     * @return ElementInterface|null
     */
    public function getNext()
    {
        if ($this->nextCached === null) {
            $nextNode = $this->getNextNode($this->node);
            if ($nextNode !== null) {
                $this->nextCached = new static($nextNode);
            }
        }

        return $this->nextCached;
    }

    /**
     * @param \DomNode $node
     *
     * @return \DomNode|null
     */
    private function getNextNode($node, $checkChildren = true)
    {
        if ($checkChildren && $node->firstChild) {
            return $node->firstChild;
        } elseif ($node->nextSibling) {
            return $node->nextSibling;
        } elseif ($node->parentNode) {
            return $this->getNextNode($node->parentNode, false);
        }
    }

    /**
     * @param string[]|string $tagNames
     *
     * @return bool
     */
    public function isDescendantOf($tagNames)
    {
        if (!is_array($tagNames)) {
            $tagNames = array($tagNames);
        }

        for ($p = $this->node->parentNode; $p !== false; $p = $p->parentNode) {
            if (is_null($p)) {
                return false;
            }

            if (in_array($p->nodeName, $tagNames)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $markdown
     */
    public function setFinalMarkdown($markdown)
    {
        $markdown_node = $this->node->ownerDocument->createTextNode($markdown);
        $this->node->parentNode->replaceChild($markdown_node, $this->node);
    }

    /**
     * @return string
     */
    public function getChildrenAsString()
    {
        return $this->node->C14N();
    }

    /**
     * @return int
     */
    public function getSiblingPosition()
    {
        $position = 0;

        // Loop through all nodes and find the given $node
        foreach ($this->getParent()->getChildren() as $current_node) {
            if (!$current_node->isWhitespace()) {
                $position++;
            }

            // TODO: Need a less-buggy way of comparing these
            // Perhaps we can somehow ensure that we always have the exact same object and use === instead?
            if ($this->equals($current_node)) {
                break;
            }
        }

        return $position;
    }

    /**
     * @return int
     */
    public function getListItemLevel()
    {
        $level = 0;
        $parent = $this->getParent();

        while ($parent !== null && $parent->node->parentNode) {
            if ($parent->getTagName() === 'li') {
                $level++;
            }
            $parent = $parent->getParent();
        }

        return $level;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getAttribute($name)
    {
        if ($this->node instanceof \DOMElement) {
            return $this->node->getAttribute($name);
        }

        return '';
    }

    /**
     * @param ElementInterface $element
     *
     * @return bool
     */
    public function equals(ElementInterface $element)
    {
        if ($element instanceof self) {
            return $element->node === $this->node;
        }

        return $element === $this;
    }
}
