<?php
/**
 * HTML to Carbon JSON converter
 *
 * @author  Adam McCann (@AssembledAdam)
 * @license MIT (see LICENSE file)
 */
namespace Candybanana\HtmlToCarbonJson;

use DOMDocument;

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
     * An array with html elements representing carbon 'Sections'
     *
     * @var array
     */
    protected $sectionsHtml = [];

    /**
     * An array of Carbon-format 'Sections'
     *
     * @var array
     */
    protected $sections = [];

    /**
     * Array of default components and their configurations, representing Carbon components
     *
     * @var array
     */
    protected $defaultComponents = [
        'ParagraphComponent',
        'EmbeddedComponent',
        'HTMLComponent',
        // 'Figure',
    ];

    /**
     * Array of instantiated components
     *
     * @var array
     */
    protected $components = [];

    /**
     * Constructor
     *
     * @param  array
     */
    public function __construct(array $config = [])
    {
        $defaults = array(
            'suppress_errors'  => true,  // Set to false to show warnings when loading malformed HTML
            // 'remove_nodes'     => '',    // space-separated list of dom nodes that should be removed. example: 'meta style script'
        );

        $this->config = array_merge($defaults, $config);

        // add default components
        foreach ($this->defaultComponents as $componentName => $config) {

            // do we have a config?
            if (! is_array($config)) {
                $componentName = $config;
                $config = [];
            }

            $component = '\\Candybanana\\HtmlToCarbonJson\\Components\\' . ucfirst($componentName);

            $this->addComponent($componentName, new $component($config));
        }
    }

    /**
     * Adds (or overrides) a component
     *
     * @param  string
     * @param  \Candybanana\HtmlToCarbonJson\Components\ComponentInterface
     * @return \Candybanana\HtmlToCarbonJson\Converter
     */
    public function addComponent($componentName, Components\ComponentInterface $component)
    {
        $this->components[$componentName] = $component;

        return $this;
    }

    /**
     * Convert HTML to Carbon JSON
     *
     * @param  string
     * @return string
     */
    public function convert($html)
    {
        if (trim($html) === '') {
            return '';
        }

        $document = $this->createDOMDocument($html);

        if (! ($root = $document->getElementsByTagName('body')->item(0))) {
            throw new \InvalidArgumentException('Invalid HTML was provided');
        }

        $this->extractSections(new Element($root));

        // DEBUG: reconstruct document
        // $temp = new \DOMDocument();
        // foreach ($this->sectionsHtml as $section) {
        //     $node = $temp->importNode($section->get(), true);
        //     $temp->appendChild($node);
        // }
        // dd($temp->saveHTML());

        // extract all the 'sections' from the document
        // while ($rootElement->hasChildren()) {

        //     // dbg($rootElement->numChildren());
        //     // dbg($rootElement->getValue());

        //     $this->extractSections($rootElement);

        //     $temp = new \DOMDocument();
        //     $node = $temp->importNode($rootElement);
        //     dd($temp->saveHTML();
        // }

        // convert each section's HTML into Carbon 'layouts'/'components'
        foreach ($this->sectionsHtml as $section) {

            if ($converted = $this->convertSection($section)) {
                $this->sections[] = $converted;
            }
        }

        return json_encode(['sections' => $this->sections]);
    }

    /**
     * Generates a unique Carbon element ID
     *
     * @return string
     */
    public static function carbonId()
    {
        return substr(str_shuffle(md5(microtime())), 0, 8);
    }

    /**
     * Create DOM document from given html
     *
     * @param  string
     * @return \DOMDocument
     */
    protected function createDOMDocument($html)
    {
        // clean up input html.
        // Note: If you need to actually fix & secure the html, I suggest ezyang/htmlpurifier
        $html = $this->sanitizeInput($html);

        // create document
        $document = new \DOMDocument();

        if ($this->config['suppress_errors']) {
            // Suppress conversion errors (from http://bit.ly/pCCRSX)
            libxml_use_internal_errors(true);
        }

        // hack to load utf-8 HTML (from http://bit.ly/pVDyCt)
        $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        $document->encoding = 'UTF-8';
        // $document->preserveWhiteSpace = false;

        if ($this->config['suppress_errors']) {
            libxml_clear_errors();
        }

        return $document;
    }

    /**
     * Get rid of whitespace
     *
     * @param  string
     * @return string
     */
    protected function sanitizeInput($html)
    {
        return preg_replace(
            array(
                '/ {2,}/',
                '/<!--.*?-->|\t|(?:\r?\n[ \t]*)+/s'
            ),
            array(
                ' ',
                ''
            ),
            $html
        );
    }

    /**
     * Extract the equivalent of 'Sections' in Carbon from the document
     *
     * @param  \Candybanana\HtmlToCarbonJson\Element
     * @return boolean
     */
    protected function extractSections(Element $element)
    {
        // recursively iterate until we get to the innermost child
        if ($element->hasChildren()) {

            foreach ($element->getChildren() as $child) {

                // we've found our section, return
                if ($this->extractSections($child)) {
                    return;
                }
            }
        }

        // is this a block element with children?
        if ($element->isBlock() && $element->hasChildren()) {

            // does it have a parent? If so that's our section
            if ($parent = $element->getParent()) {

                $this->sectionsHtml[] = $parent;

                // remove it from the DOM
                // @todo: check if this is the root element - if so error out

                $parent->remove();

                return true;
            }
        }

        // we're done with this node
        return false;
    }

    /**
     * Builds an object that will represent our JSON based on the sections we've extracted
     *
     * @param  \Candybanana\HtmlToCarbonJson\Element
     * @return string
     */
    protected function convertSection(Element $section)
    {
        $layouts = [];
        $layout = $prevComponentClass = null;

        foreach ($section->getChildren() as $sectionChild) {

            if (! ($component = $this->matchWithComponent($sectionChild))) {

                // is there's a value in this element, throw error, otherwise ignore it
                if (! trim($sectionChild->getValue())) {
                    continue;
                }

                // build the tag for easy debugging
                $tag = '<' . $sectionChild->getTagName();

                foreach ($sectionChild->getAttributes() as $attribute) {
                    $tag .= ' ' . $attribute->name . '="';
                    $tag .= $sectionChild->getAttribute($attribute->name) . '"';
                }

                $tag .= '>';

                dbg($section->getValue());
                dbg($tag);
                dbg($sectionChild->getValue());

                throw new Exceptions\InvalidStructureException("No component loaded to render '$tag' tags.");
            }

            // if empty, skip this child
            if ($component->isEmpty()) {
                continue;
            }

            $componentClass = get_class($component);

            // create a new layout if we're not in one, or the component requires a new one
            if ($component->requiresNewLayout() || $componentClass !== $prevComponentClass) {

                // save previous layout
                if ($layout) {
                    $layouts[] = $layout->render();
                }

                // create new
                $layout = new Layout($component->getLayout());
            }

            $layout->addComponent($component->render());

            $prevComponentClass = $componentClass;
        }

        // render the last layout
        if ($layout) {
            $layouts[] = $layout->render();
        }

        // create final section json
        if (! empty($layouts)) {

            $section = [
                'name'       => self::carbonId(),
                'component'  => 'Section',
                'components' => $layouts
            ];

            return $section;
        }

        // Strip nodes named in remove_nodes
        // $tags_to_remove = explode(' ', $this->getConfig()->getOption('remove_nodes'));
        // if (in_array($tag, $tags_to_remove)) {
        //     return false;
        // }
    }

    /**
     * Match a component to the given element
     *
     * @param  \Candybanana\HtmlToCarbonJson\Element
     * @return \Candybanana\HtmlToCarbonJson\Components\ComponentInterface
     */
    protected function matchWithComponent(Element $element)
    {
        foreach ($this->components as $component) {

            // return if we've found the component for this element
            if ($component->matches($element)) {

                return $component;
            }
        }

        // no component matches
        return null;
    }
}
