<?php
/**
 * HTML to Carbon JSON converter
 *
 * @author  Adam McCann (@AssembledAdam)
 * @license MIT (see LICENSE file)
 */
namespace Candybanana\HtmlToCarbonJson;

use DOMDocument;
use DOMElement;

/**
 * Converter
 */
class Converter
{
    /**
     * Converter configuration
     *
     * @var array
     */
    protected $config;

    /**
     * An object representing the JSON to convert
     *
     * @var string
     */
    // protected $json;

    /**
     * Array of default components and their configurations, representing Carbon components
     *
     * @var array
     */
    // protected $defaultComponents = [
    //     'Section',
    //     'Layout',
    //     'Paragraph',
    //     'Figure',
    // ];

    /**
     * Array of instantiated components
     *
     * @var array
     */
    // protected $components = [];

    /**
     * Constructor
     *
     * @param  array
     * @return string
     */
    public function __construct(array $config)
    {
        $defaults = array(
            'suppress_errors' => true,  // Set to false to show warnings when loading malformed HTML
            'remove_nodes'    => '',    // space-separated list of dom nodes that should be removed. example: 'meta style script'
        );

        $this->config = array_merge($defaults, $config);

        // // add default components
        // foreach ($this->defaultComponents as $componentName => $config) {

        //     // do we have a config?
        //     if (! is_array($config)) {
        //         $componentName = $config;
        //         $config = [];
        //     }

        //     $component = '\\Candybanana\\CarbonJsonToHtml\\Components\\' . ucfirst($componentName);

        //     $this->addComponent($componentName, new $component($config));
        // }
    }

    /**
     * Adds a component parser
     *
     * @param  string
     * @param  \Candybanana\HtmlToCarbonJson\Components\ComponentInterface
     * @return \Candybanana\HtmlToCarbonJson\Converter
     */
    // public function addComponent($componentName, Components\ComponentInterface $component)
    // {
    //     $this->components[$componentName] = $component;

    //     return $this;
    // }

    /**
     * Convert
     *
     * Loads HTML and passes to getMarkdown()
     *
     * @param $html
     *
     * @return string The Markdown version of the html
     */
    public function convert($html)
    {
        if (trim($html) === '') {
            return '';
        }

        $document = $this->createDOMDocument($html);

        dd($document);

        // // Work on the entire DOM tree (including head and body)
        // if (!($root = $document->getElementsByTagName('html')->item(0))) {
        //     throw new \InvalidArgumentException('Invalid HTML was provided');
        // }

        // $rootElement = new Element($root);
        // $this->convertChildren($rootElement);

        // // Store the now-modified DOMDocument as a string
        // $markdown = $document->saveHTML();

        // $markdown = $this->sanitize($markdown);

        // return $markdown;
    }

    /**
     * @param string $html
     *
     * @return \DOMDocument
     */
    private function createDOMDocument($html)
    {
        $document = new \DOMDocument();

        if ($this->config['suppress_errors']) {
            // Suppress conversion errors (from http://bit.ly/pCCRSX)
            libxml_use_internal_errors(true);
        }

        // Hack to load utf-8 HTML (from http://bit.ly/pVDyCt)
        $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        $document->encoding = 'UTF-8';

        if ($this->config['suppress_errors']) {
            libxml_clear_errors();
        }

        return $document;
    }

    /**
     * Convert Children
     *
     * Recursive function to drill into the DOM and convert each node into Markdown from the inside out.
     *
     * Finds children of each node and convert those to #text nodes containing their Markdown equivalent,
     * starting with the innermost element and working up to the outermost element.
     *
     * @param ElementInterface $element
     */
    private function convertChildren(ElementInterface $element)
    {
        // Don't convert HTML code inside <code> and <pre> blocks to Markdown - that should stay as HTML
        if ($element->isDescendantOf(array('pre', 'code'))) {
            return;
        }

        // If the node has children, convert those to Markdown first
        if ($element->hasChildren()) {
            foreach ($element->getChildren() as $child) {
                $this->convertChildren($child);
            }
        }

        // Now that child nodes have been converted, convert the original node
        $markdown = $this->convertToMarkdown($element);

        // Create a DOM text node containing the Markdown equivalent of the original node

        // Replace the old $node e.g. '<h3>Title</h3>' with the new $markdown_node e.g. '### Title'
        $element->setFinalMarkdown($markdown);
    }

    /**
     * Convert to Markdown
     *
     * Converts an individual node into a #text node containing a string of its Markdown equivalent.
     *
     * Example: An <h3> node with text content of 'Title' becomes a text node with content of '### Title'
     *
     * @param ElementInterface $element
     *
     * @return string The converted HTML as Markdown
     */
    protected function convertToMarkdown(ElementInterface $element)
    {
        $tag = $element->getTagName();

        // Strip nodes named in remove_nodes
        $tags_to_remove = explode(' ', $this->getConfig()->getOption('remove_nodes'));
        if (in_array($tag, $tags_to_remove)) {
            return false;
        }

        $converter = $this->environment->getConverterByTag($tag);

        return $converter->convert($element);
    }

    /**
     * @param string $markdown
     *
     * @return string
     */
    protected function sanitize($markdown)
    {
        $markdown = html_entity_decode($markdown, ENT_QUOTES, 'UTF-8');
        $markdown = preg_replace('/<!DOCTYPE [^>]+>/', '', $markdown); // Strip doctype declaration
        $unwanted = array('<html>', '</html>', '<body>', '</body>', '<head>', '</head>', '<?xml encoding="UTF-8">', '&#xD;');
        $markdown = str_replace($unwanted, '', $markdown); // Strip unwanted tags
        $markdown = trim($markdown, "\n\r\0\x0B");

        return $markdown;
    }





    /**
     * Perform the conversion
     *
     * @return string
     */
    // public function convert($json)
    // {
    //     if (($this->json = json_decode($json)) === null) {

    //         throw new Exception\NotTraversableException(
    //             'The JSON provided is not valid'
    //         );
    //     }

    //     // sections is *always* our first node
    //     if (! isset($this->json->sections)) {

    //         throw new Exception\InvalidStructureException(
    //             'The JSON provided is not in a Carbon Editor format.'
    //         );
    //     }

    //     $this->convertRecursive($this->json->sections);

    //     return $this->dom->saveHTML();
    // }

    /**
     * Recursively walk the object and build the HTML
     *
     * @param  array
     */
    // protected function convertRecursive(array $json, DOMElement $parentElement = null)
    // {
    //     foreach ($json as $jsonNode) {

    //         $component = ucfirst($jsonNode->component);

    //         if (empty($this->components[$component])) {

    //             throw new Exception\InvalidStructureException(
    //                 "The JSON contains the component '$component', but that isn't loaded."
    //             );
    //         }

    //         $element = $this->components[$component]->parse($jsonNode, $this->dom, $parentElement);

    //         if (isset($jsonNode->components)) {
    //             $this->convertRecursive($jsonNode->components, $element);
    //         }
    //     }
    // }
}
