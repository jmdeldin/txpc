<?php
/**
 * CLI usage:
 *   $ php txpc.php <source> [cache_dir] [release_dir]
 */
if (isset($argc))
{
    if ($argc > 1)
        $plugin = new TXPC($argv[1], $argv[2], $argv[3]);
    else
        exit("Invalid arguments provided.\n");
}

/**
 * TXPC, a Textpattern plugin compiler.
 *
 * @author Jon-Michael Deldin <dev@jmdeldin.com>
 */
class TXPC
{
    /**
     * Help file
     */
    const HELP_FNAME = 'README.textile';

    /**
     * Help file contents
     */
    protected $help = '';

    /**
     * The source-plugin file.
     */
    protected $src;

    /**
     * The source directory.
     */
    protected $srcDir;

    /**
     * Plugin cache directory.
     */
    protected $cacheDir;

    /**
     * Directory for compiled plugins (base64 text file).
     */
    protected $releaseDir;

    /**
     * Plugin metadata.
     */
    protected $meta = array();

    /**
     * Metadata labels (prefixed with "@") to look for.
     */
    protected $metaLabels = array('name', 'description', 'author',
                                  'author_uri', 'version', 'type', 'order');

    public function __construct($src, $cacheDir, $releaseDir)
    {
        // check for src file
        if (!file_exists($src))
            exit("$src does not exist.\n");
        $this->src = $src;

        // get directories
        $this->cacheDir = (isset($cacheDir)) ?
                          rtrim($cacheDir, DIRECTORY_SEPARATOR) : getcwd();
        $this->releaseDir = (isset($releaseDir)) ?
                            rtrim($releaseDir, DIRECTORY_SEPARATOR) : getcwd();
        $this->srcDir = dirname($this->src);

        // check for help file
        $help = "{$this->srcDir}/" . self::HELP_FNAME;
        if (file_exists($help))
            $this->help = file_get_contents($help);

        // link and compile
        $this->link();

        // get metadata
        $this->extractMeta();

        // write cached file
        $this->writeFile("{$this->cacheDir}/{$this->meta['name']}.php",
                         $this->src);

        // release filename: release.d/plugin_name-version.txt
        $release = "{$this->releaseDir}/{$this->meta['name']}-" .
                   "{$this->meta['version']}.txt";
        // write $release
        $this->writeFile($release, $this->compile());
    }

    /**
     * Plugin linker.
     *
     * Replaces <code>//inc <file></code> statements in <var>$this->src</var>
     * with the contents of <var><file></var>.
     */
    protected function link()
    {
        $this->src = file_get_contents($this->src);
        if (preg_match_all(',//inc\s+<(.*?)>,', $this->src, $matches))
        {
            for ($i = 0; $i < count($matches[1]); $i++)
            {
                $filename = $matches[1][$i];
                $contents = file_get_contents("$this->srcDir/$filename");
                $this->src = str_replace($matches[0][$i], $contents, $this->src);
            }
        }
    }

    /**
     * Extracts plugin metadata.
     */
    protected function extractMeta()
    {
        foreach ($this->metaLabels as $field)
        {
            $this->meta[$field] = $this->getField($field);
        }
    }

    /**
     * Returns a field's value.
     *
     * Captures anything following <code>@<$field> </code>.
     *
     * @param  string $field Metadatum
     * @return string Anything following <var>$field</var>.
     */
    protected function getField($field)
    {
        preg_match("/@{$field}\s+(.*)/", $this->src, $field);
        return $field[1];
    }

    /**
     * Assembles header and base64 output.
     *
     * Mostly from <code>compile_plugin()</code> of TXP's plugin compiler.
     *
     * @link http://textpattern.googlecode.com/svn/development/4.0-plugin-template/zem_tpl.php
     * @return string Compiled plugin.
     */
    protected function compile()
    {
        // merge metadata array
        $plugin = $this->meta;
        // pull code, minus opening and closing tags
        $plugin['code'] = $this->extractCode($this->src);
        $plugin['md5'] = md5($plugin['code']);
        $plugin['help_raw'] = $this->help;
        $header = <<<EOF
# {$plugin['name']} v{$plugin['version']}
# {$plugin['description']}
# {$plugin['author']}
# {$plugin['author_uri']}

# ......................................................................
# This is a plugin for Textpattern - http://textpattern.com/
# To install: textpattern > admin > plugins
# Paste the following text into the 'Install plugin' box:
# ......................................................................
EOF;

        return $header . "\n\n" . trim(chunk_split(base64_encode(gzencode(serialize($plugin))), 72));
    }

    /**
     * Removes the opening and closing PHP tags.
     *
     * Because the <code># --- BEGIN|END PLUGIN CODE ---</code> delimiters
     * are no longer needed, the PHP tags need to be stripped for TXP.
     *
     * @param string $plugin
     */
    protected function extractCode($plugin)
    {
        return str_replace('<?php', '', str_replace('?>', '', $plugin));
    }

    /**
     * Writes contents to a file.
     *
     * @param string $fname    Target filename
     * @param string $contents Content to write.
     */
    protected function writeFile($filename, $contents)
    {
        $mode = (file_exists($filename) ? 'w' : 'x');
        if ($handle = fopen($filename, $mode))
        {
            fwrite($handle, $contents);
            fclose($handle);
        }
        else
            exit("Unable to write to {$filename}.\n");
    }
}

