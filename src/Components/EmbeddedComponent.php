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
class EmbeddedComponent extends AbstractComponent implements ComponentInterface
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

            /*
             * callback that should return an array with the following keys:
             * [
             *     'provider'      the provider that is resolving the oembed for us (e.g. embedly)
             *     'type':         type of embed, one of rich|video|link|image
             *     'serviceName':  provider of the embed, e.g. YouTube
             *     'sizes':        an array of available embed sizes with width and height
             * ]
             */
            'providerCallback' => function ($url) {
                return [
                    'provider'    => null,
                    'type'        => null,
                    'serviceName' => null,
                    'sizes'       => [

                    ]
                ];
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
        if ($this->element->getAttribute('data-special') !== 'embed') {
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
        $provider = $this->config['providerCallback']($this->element->getValue());

        $json = [
            'name'        => Converter::carbonId(),
            'component'   => 'EmbeddedComponent',
            'url'         => $this->element->getValue(),
            'caption'     => '',
            'provider'    => $provider['provider'],
            'type'        => $provider['type'],
            'serviceName' => $provider['serviceName'],
        ];

        if ($provider['sizes']) {
            $json['sizes'] = $provider['sizes'];
        }

        return $json;
    }
}
