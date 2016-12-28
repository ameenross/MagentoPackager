<?php namespace AmeenRoss\MagentoPackager;

use Archive_Tar as Tar;
use DateTime;
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
     * Set the package's release date metadata.
     *
     * @param DateTime $date
     *     The release date.
     */
    public function setReleaseDate(DateTime $date)
    {
        $this->metadata->date = $date->format('Y-m-d');
        $this->metadata->time = $date->format('H:i:s');
    }

    /**
     * Add package metadata.
     *
     * @param string $element
     *     The element to add to the metadata.
     * @param string $value
     *     (optional) The value of the element.
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
        $files = [];

        // Iterate over the content in the input tarball.
        foreach ($this->input->listContent() as $node) {
            $fileContent = $this->input->extractInString($node['filename']);

            // Add the files/folders to the output file.
            $output->addString(
                $node['filename'],
                $fileContent,
                false,
                $node
            );

            // Keep track of all files and their hashes.
            if ($node['typeflag'] == 0) {
                $files[] = $this->getTarget($node['filename']) + ['hash' => md5($fileContent)];
            }
        }

        // Add the actual file metadata.
        $this->processFiles($files);

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

    /**
     * Get target name for a file.
     *
     * @param string $filename
     *
     * @return array
     *     An array with keys:
     *     - target: the name of the target directory.
     *     - path: the path relative to the target directory.
     */
    protected function getTarget($filename)
    {
        $map = [
            'app/code/community/' => 'magecommunity',
            'app/code/core/' => 'magecore',
            'app/code/local/' => 'magelocal',
            'app/design/' => 'magedesign',
            'app/etc/' => 'mageetc',
            'app/locale/' => 'magelocale',
            'lib/' => 'magelib',
            'media/' => 'magemedia',
            'skin/' => 'mageskin',
            'Test/' => 'magetest',
        ];

        // Iterate over the map to find a match.
        foreach ($map as $targetPath => $targetName) {
            // If the target path is found at the start of the filename, it's a
            // match.
            if (strpos($filename, $targetPath) === 0) {
                return [
                    'target' => $targetName,
                    'path' => substr($filenam, strlen($targetPath)),
                ];
            }

            // If no match was found, use the "catch-all" target.
            return [
                'target' => 'mage',
                'path' => $filename,
            ];
        }
    }

    /**
     * Add files to the package's metadata.
     *
     * @param array[] $files
     *     A list of files that are part of the package. A file is described as
     *     an array with these keys:
     *     - target: the name of the target directory.
     *     - path: the path relative to the target directory.
     *     - hash: the md5 hash of the file.
     */
    protected function processFiles(array $files)
    {
        // Recursive closure to get the direct parent element of the file.
        // Creates the parent elements if they don't exist.
        $getDirectParentElement = function ($element, $parents) use (&$getDirectParentElement) {
            // If there are no more parents, the current element is the direct
            // parent.
            if (!$parents) {
                return $element;
            }

            // Process the first parent on the stack.
            $parent = array_shift($parents);

            // Check if the parent exists.
            if (!$parentElement = @$element->xpath("dir[@name='{$parent}']")[0]) {
                // Create the parent.
                $parentElement = $element->addChild('dir');
                $parentElement->addAttribute('name', $parent);
            }

            // Recurse.
            return $getDirectParentElement($parentElement, $parents);
        };

        // Iterate over the files, keeping track of the target elements added.
        $targets = [];
        foreach ($files as $file) {
            // If this target is new, add the target element for it.
            if (!array_key_exists($file['target'], $targets)) {
                $targets[$file['target']] = $this->metadata->contents->addChild('target');
                $targets[$file['target']]->addAttribute('name', $file['target']);
            }

            // Break up the path into an array of parent directories and the
            // filename. Make sure files in root have an empty array of parents
            // instead of an array with a dot.
            $dirname = pathinfo($file['path'], PATHINFO_DIRNAME);
            $parents = ($dirname == '.') ? [] : explode('/', $dirname);
            $filename = pathinfo($file['path'], PATHINFO_BASENAME);

            // Get the direct parent.
            $directParent = $getDirectParentElement($targets[$file['target']], $parents);

            // Add the file metadata.
            $fileElement = $directParent->addChild('file');
            $fileElement->addAttribute('name', $filename);
            $fileElement->addAttribute('hash', $file['hash']);
        }
    }
}
