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
     * @var resource $tempFile
     *     The file handle of the temporary file containing the tarball, if the
     *     tar comes from STDIN.
     */
    protected $tempFile;

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
     *     (optional) The directory to output the resulting package file to.
     *     Defaults to current working directory.
     * @param SimpleXMLElement $metadata
     *     (optional) Object with the package and release metadata.
     */
    public function __construct($input, $outputDirectory = '.', SimpleXMLElement $metadata = null)
    {
        $this->input = new Tar($this->getInputFilename($input));
        $this->outputDirectory = rtrim($outputDirectory, '/');
        $this->initMetadata($metadata);
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
        $this->metadata->{$element} = $value;

        // Add the element's attributes.
        foreach ($attributes as $attribute => $value) {
            $this->metadata->{$element}->setAttribute($attribute, $value);
        }
    }

    /**
     * Save the package.
     *
     * @todo Process files of input tar.
     * @todo Add package.xml file.
     */
    public function save()
    {
        // Make sure the metadata is complete.
        $this->validateMetadata();

        // Output file with the correct naming convention:
        // "<directory>/Name-1.0.0.tgz".
        $filename = "{$this->outputDirectory}/{$this->metadata->name}-{$this->metadata->version}.tgz";

        // Remove file if it already exists.
        if (is_file($filename)) {
            unlink($filename);
        }

        $output = new Tar($filename);

        // Iterate over the content in the input tarball.
        foreach ($this->input->listContent() as $node) {
            // Add the files/folders to the output file.
            $output->addString(
                $node['filename'],
                $this->input->extractInString($node['filename']),
                false,
                $node
            );
        }

        // Add the package.xml to the output file.
        $output->addString('package.xml', $this->metadata->asXML(), false, [
            'mode' => 0664,
            'uid' => getmyuid(),
            'gid' => getmygid(),
        ]);
    }

    /**
     * Get the input filename.
     *
     * Makes sure a file from STDIN is handled properly, as that cannot be read
     * from repeatedly.
     *
     * @param string $input
     *     The filename of the tar source file.
     */
    protected function getInputFilename($input)
    {
        // If not STDIN, just return the filename as given.
        if ($input != 'php://stdin') {
            return $input;
        }

        // Create a temporary file with the content read from STDIN.
        $this->tempFile = tmpfile();
        $stdin = fopen($input, 'rb');
        while (!feof($stdin)) {
            // Read/write 1024 bytes per iteration to conserve memory.
            fwrite($this->tempFile, fread($stdin, 1024));
        }

        // Return the filename of the temporary file.
        return stream_get_meta_data($this->tempFile)['uri'];
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
