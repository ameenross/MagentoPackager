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
     * @var string $outputDirectory
     *     The directory to output the resulting package file to.
     */
    protected $outputDirectory;

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
     * @param string $outputDirectory
     *     The directory to output the resulting package file to.
     * @param SimpleXMLElement $metadata
     *     (optional) Object with the package and release metadata.
     */
    public function __construct($input, $outputDirectory = '.', SimpleXMLElement $metadata = null)
    {
        $this->input = new Tar($input);
        $this->outputDirectory = $outputDirectory;
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
     * Set the package's release version.
     *
     * @param string $version
     *     The version identifier.
     */
    public function setVersion($version)
    {
        $this->metadata->version = $version;
    }

    /**
     * Saves the package.
     */
    public function save()
    {
        // Make sure the metadata is complete.
        $this->validateMetadata();

        // Output file with the correct naming convention:
        // "<directory>/Name-1.0.0.tgz".
        $output = new Tar("{$this->outputDirectory}/{$this->metadata->name}-{$this->metadata->version}.tgz");
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

    /**
     * Validate the metadata.
     *
     * @todo Still a stub.
     */
    protected function validateMetadata()
    {
    }
}
