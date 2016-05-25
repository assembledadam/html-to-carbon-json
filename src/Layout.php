<?php
/**
 * HTML to Carbon JSON converter
 *
 * @author  Adam McCann (@AssembledAdam)
 * @license MIT (see LICENSE file)
 */
namespace Candybanana\HtmlToCarbonJson;

use Candybanana\HtmlToCarbonJson\Converter;
use Candybanana\HtmlToCarbonJson\Components\ComponentInterface;

/**
 * ComponentInterface
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
     * @param \Candybanana\HtmlToCarbonJson\Components\ComponentInterface
     */
    public function addComponent(array $component)
    {
        $this->components[] = $component;
    }

    public function render()
    {
        $this->layout['components'] = $this->components;

        return $this->layout;


        // Custom: 'Boxout' component, if the element is a div and has the class 'boxout'

        // EmbeddedComponent - single node with URL matching embed regex
        // @todo: set embed regex in config

        // List - tag type is UL. Find out how we recieve lists from the migration! May be able to modify input to set UL tags without <p>...

        // Figure (image) - not used in VG! - single node with tag IMG
    }
}
