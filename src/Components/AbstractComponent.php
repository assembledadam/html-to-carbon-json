<?php
/**
 * HTML to Carbon JSON converter
 *
 * @author  Adam McCann (@AssembledAdam)
 * @license MIT (see LICENSE file)
 */
namespace Candybanana\HtmlToCarbonJson\Components;

use Candybanana\HtmlToCarbonJson\Converter;
use Candybanana\HtmlToCarbonJson\Element;
use stdClass;

/**
 * Converter
 */
abstract class AbstractComponent implements ComponentInterface
{
    /**
     * Custom component configuration
     *
     * @var array
     */
    protected $config;

    /**
     * The currently matched element
     *
     * @var \Candybanana\HtmlToCarbonJson\Element
     */
    protected $element;

    /**
     * HTML Tags serviced by this element
     *
     * @var array
     */
    protected $tags = [];

    /**
     * Component constructor
     *
     * @param array
     * @param array
     */
    public function __construct(array $defaultConfig, array $config = [])
    {
        $this->config = array_merge($defaultConfig, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function matches(Element $element)
    {
        if (! in_array($element->getTagName(), $this->tags)) {
            return false;
        }

        $this->element = $element;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        if ($this->element->getValue()) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresNewLayout()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getLayout()
    {
        return [
            'name'      => Converter::carbonId(),
            'component' => 'Layout',
            'tagName'   => 'div',
            'type'      => $this->config['layoutTypeCallback']($this->element),
        ];
    }

    /**
     * Sets the current element on this component
     *
     * @param Element
     */
    public function setElement(Element $element)
    {
        $this->elemenet = $element;
    }
}
