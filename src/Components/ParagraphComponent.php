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
class ParagraphComponent extends AbstractComponent implements ComponentInterface
{
    /**
     * {@inheritdoc}
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
        'ul',
        'div',
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $config = [])
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
            ],

            // callback that determines what the layout type should be for this component
            'layoutTypeCallback' => function ($element) {
                return 'layout-single-column';
            },
        ];

        parent::__construct($defaultConfig, $config);

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
        if (! parent::matches($element)) {
            return false;
        }

        // if this is a list, we need children to continue
        if ($this->element->getTagName() == 'ul' && ! $this->element->hasChildren()) {
            return false;
        }

        // only process divs that determine 'boxout's
        if ($this->element->getTagName() == 'div' && $this->element->getAttribute('data-boxout') !== 'true') {
            return false;
        }

        // @todo: detect if value = empty and the only node is an image - if so skip for Figure

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresNewLayout()
    {
        // boxouts require a new layout
        if ($this->element->getAttribute('data-boxout')) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getLayout()
    {
        $type = $this->element->getAttribute('data-boxout') === 'true' ?
            'boxout' : $this->config['layoutTypeCallback']($this->element);

        return [
            'name'      => Converter::carbonId(),
            'component' => 'Layout',
            'tagName'   => 'div',
            'type'      => $type,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        // list type
        if ($this->element->getTagName() == 'ul') {

            $json = [
                'name'       => Converter::carbonId(),
                'component'  => 'List',
                'tagName'    => 'ul',
                'components' => $this->listItems(),
            ];

        // ordinary type
        } else {

            $json = $this->makeParagraph();
        }

        return $json;
    }

    /**
     * Constructs list items for list type
     *
     * @return array
     */
    protected function listItems()
    {
        $listItems = [];

        foreach ($this->element->getChildren() as $item) {

            $listItems[] = $this->makeParagraph($item);
        }

        return $listItems;
    }

    /**
     * Construct paragraph section
     *
     * @param  \Candybanana\HtmlToCarbonJson\Element|null
     * @return array
     */
    protected function makeParagraph(Element $element = null)
    {
        $element = $element ?: $this->element;

        $json = [
            'name'            => Converter::carbonId(),
            'component'       => 'Paragraph',
            'text'            => $element->getValue(),
            'placeholderText' => null,
            'paragraphType'   => $this->paragraphType($element),
        ];

        if ($formats = $this->detectFormatting($element)) {
            $json['formats'] = $formats;
        }

        return $json;
    }

    /**
     * Determine paragraph type
     *
     * @param  \Candybanana\HtmlToCarbonJson\Element|null
     * @return string
     */
    protected function paragraphType(Element $element)
    {
        switch ($element->getTagName()) {

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

            // fine as it is!
            default:
                return $element->getTagName();
        }
    }

    /**
     * Detect valid formatting elements within a paragraph
     *
     * @param  \Candybanana\HtmlToCarbonJson\Element
     * @return string
     */
    protected function detectFormatting(Element $element)
    {
        if ($element->hasChildren()) {

            $formats = [];

            foreach ($element->getChildren() as $node) {

                if (! $node->isText() && in_array($node->getTagName(), $this->config['allowedFormattingTags'])) {

                    $type = $this->config['formattingTags'][$node->getTagName()];

                    if (empty($node->getValue())) {
                        continue;
                    }

                    list($from, $to) = $this->getTextPosition($element->getValue(), $node->getValue());

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
     * @param  string The parent text of the text to be formatted
     * @param  string The text to be formatted
     * @return string
     */
    protected function getTextPosition($context, $text)
    {
        $pos = strpos($context, $text);

        return [
            $pos,
            $pos + strlen($text)
        ];
    }
}
