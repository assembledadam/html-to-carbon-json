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
class Paragraph implements ComponentInterface
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
     * Tags serviced by this element
     *
     * @var array
     */
    protected $tags = [
        'p',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'blockquote',
        'pre',
        'code',

        // TEMP
        'div',
        'ul',
    ];

    /**
     * Component constructor
     *
     * @param array|null
     */
    public function __construct(array $config = null)
    {
        $defaultConfig = [

            // minimum heading to allow - useful for if your toolbar only contains h1 or h1 and h2
            'minimumHeading' => 'h3',

            // allowed inline formatting tags and their Carbon value
            'formattingTags' => [
                'a'      => 'a',
                'strong' => 'strong',
                'em'     => 'em',
                's'      => 's',
                'u'      => 'u',
                'b'      => 'strong',
                'i'      => 'em',
                'ins'    => 'u',
                'del'    => 's',
            ]
        ];

        $this->config = array_merge($defaultConfig, $config);

        // arrange allowed formatting tags
        foreach ($this->config['formattingTags'] as $varient => $tag) {
            $this->config['allowedFormattingTags'][] = $varient;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function matches(Element $element)
    {
        if (! in_array($element->getTagName(), $this->tags)) {
            return false;
        }

        // @todo: detect if value = empty and the only node is an image - if so skip for Figure

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
        return false;
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
            'type'      => 'layout-single-column',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        //  -> be sure to parse any iframe/html here

        $json = [
            'name'            => Converter::carbonId(),
            'component'       => 'Paragraph',
            'text'            => $this->element->getValue(),
            'placeholderText' => null,
            'paragraphType'   => $this->paragraphType(),
        ];

        if ($formats = $this->detectFormatting()) {
            $json['formats'] = $formats;
        }

        return $json;
    }

    /**
     * Determine paragraph type
     *
     * @return string
     */
    protected function paragraphType()
    {
        switch ($this->element->getTagName()) {

            // heading
            case 'h2':
                return $this->config['minimumHeading'] == 'h1' ? 'h1' : 'h2';

            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                if ($this->config['minimumHeading'] !== 'h3') {
                    return $this->config['minimumHeading'];
                }

                return 'h3';

            case 'code':
                return 'blockquote';

            // find as it is!
            default:
                return $this->element->getTagName();
        }
    }

    /**
     * Detect valid formatting elements within a paragraph
     *
     * @return string
     */
    protected function detectFormatting()
    {
        if ($this->element->hasChildren()) {

            $formats = [];

            foreach ($this->element->getChildren() as $node) {

                if (! $node->isText() && in_array($node->getTagName(), $this->config['allowedFormattingTags'])) {

                    $type = $this->config['formattingTags'][$node->getTagName()];

                    list($from, $to) = $this->getTextPosition($node->getValue());

                    $format = [
                        'type'  => $type,
                        'from'  => $from,
                        'to'    => $to
                    ];

                    // if <a> tag, add attributes
                    if ($type == 'a') {
                        $format['attrs'] = [
                            'href' => $node->getAttribute('href')
                        ];
                    }

                    $formats[] = $format;
                }
            }

            return ! empty($formats) ? $formats : null;
        }
    }

    /**
     * Return first and last position of text
     *
     * @param  string
     * @return string
     */
    protected function getTextPosition($text)
    {
        $pos = strpos($this->element->getValue(), $text);

        return [
            $pos,
            $pos + strlen($text)
        ];
    }
}
