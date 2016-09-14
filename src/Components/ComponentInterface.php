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

/**
 * ComponentInterface
 */
interface ComponentInterface
{
    /**
     * Determine if this component handles this element
     *
     * @param  \Candybanana\HtmlToCarbonJson\Element
     * @return boolean
     */
    public function matches(Element $element);

    /**
     * Determine if the element is empty
     *
     * @return boolean
     */
    public function isEmpty();

    /**
     * Whether this component requires the formation of a new layout
     *
     * @return boolean
     */
    public function requiresNewLayout();

    /**
     * Return a new layout for this element
     *
     * @return string
     */
    public function getLayout();

    /**
     * Render the component's element
     *
     * @param  \Candybanana\HtmlToCarbonJson\Converter
     * @return string
     */
    public function render(Converter $converter = null);
}
