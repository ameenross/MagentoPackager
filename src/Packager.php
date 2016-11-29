<?php namespace AmeenRoss\MagentoPackager;

use Archive_Tar as Tar;
use SimpleXMLElement;

class Packager
{
    /**
     * @var Tar $input
     *     The source tarball to be packaged.
     */
    protected $input;

    /**
     * @var SimpleXMLElement $metadata
     *     The metadata to store in the `package.xml`.
     */
    protected $metadata;

    /**
     * Create an extension packager.
     *
     * @param string $input
     *     The filename of the tar source file.
     * @param SimpleXMLElement $metadata
     *     (optional) Object with the package and release metadata.
     */
    public function __construct($input, SimpleXMLElement $metadata = null)
    {
        $this->input = new Tar($input);
        $this->setMetadata($metadata);
    }

    /**
     * Add package metadata.
     *
     * @param string $element
     *     The element to add to the metadata.
     * @param string $value
     *     (optional) The value of the element. If omitted, the function will
     *     add a self-closing element.
     * @param string[] $attributes
     *     (optional) Any XML attributes to add to the element, as key-value
     *     pairs.
     */
    public function addMetadata($element, $value = null, $attributes = [])
    {
        // Add the element.
        $child = $this->metadata->addChild($element, $value);

        // Add the element's attributes.
        foreach ($attributes as $attribute => $value) {
            $child->initAttribute($attribute, $value);
        }
    }

    /**
     * Initialize the package's metadata.
     *
     * @param SimpleXMLElement $metadata
     *     (optional) Object with the package and release metadata.
     */
    protected function initMetadata(SimpleXMLElement $metadata = null)
    {
        if (isset($metadata)) {
            // Store the given metadata on the object.
            $this->metadata = $metadata;
        } elseif (!isset($this->metadata)) {
            // Instantiate an empty metadata object if needed.
            $this->metadata = new SimpleXMLElement('<package/>');
        }
    }
}
