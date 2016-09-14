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
 * Component
 */
class HTMLComponent extends AbstractComponent implements ComponentInterface
{
    /**
     * {@inheritdoc}
     */
    protected $tags = [
        'div',
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $config = [])
    {
        $defaultConfig = [

            // callback that determines what the layout type should be for this component
            'layoutTypeCallback' => function ($element) {
                return 'layout-single-column';
            },
        ];

        parent::__construct($defaultConfig, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function matches(Element $element)
    {
        if (! parent::matches($element)) {
            return false;
        }

        // ensure this div is for this component
        if ($this->element->getAttribute('data-special') !== 'html') {
            return false;
        }

        return $this;
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
     * {@inheritdoc}
     */
    public function render(Converter $converter = null)
    {
        $json = [
            'name'        => Converter::carbonId(),
            'component'   => 'HTMLComponent',
            'html'        => html_entity_decode($this->element->getValue()),
        ];

        // if ($provider['sizes']) {
        //     $json['sizes'] = $provider['sizes'];
        // }

        return $json;
    }
}
