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
     * Set the package's metadata.
     *
     * @param SimpleXMLElement $metadata
     *     (optional) Object with the package and release metadata.
     */
    public function setMetadata(SimpleXMLElement $metadata = null)
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
