<?php
/**
 * HTML to Carbon JSON converter
 *
 * @author  Adam McCann (@AssembledAdam)
 * @license MIT (see LICENSE file)
 */
namespace Candybanana\HtmlToCarbonJson;

/**
 * Layout
 */
class Layout
{
    /**
     * Layout
     *
     * @var array
     */
    protected $layout;

    /**
     * Components that make up this layout
     *
     * @var array
     */
    protected $components = [];

    /**
     * Constructor
     */
    public function __construct(array $layout)
    {
        $this->layout = $layout;
    }

    /**
     * Add a component to this layout
     *
     * @param array
     */
    public function addComponent(array $component)
    {
        // is this component a numeric or associative array?
        if (Converter::isAssociativeArray($component)) {

            $this->components[] = $component;

        } else {

            $this->components = array_merge($this->components, $component);
        }
    }

    /**
     * Render layout with its components
     *
     * @return string
     */
    public function render()
    {
        $this->layout['components'] = $this->components;

        return $this->layout;
    }
}
