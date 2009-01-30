<?php

/**
 * CLI usage:
 * $ php make.php source.php merged.php [../cache/compiled.txt]
 */
if (isset($argc))
{
    if ($argc > 2)
        make($argv[1], $argv[2], $argv[3]);
    else
        exit("Invalid arguments provided.\n");
}

/**
 * Make, for TXP plugins.
 *
 * Replaces <code>//inc <filename></code> statements in <var>$src</var> with
 * the contents of <samp>filename</samp> and outputs <var>$combined</var>.
 *
 * @author Jon-Michael Deldin <dev@jmdeldin.com>
 * @param string $src Source file
 * @param string $combined Source file with includes' contents
 * @param string $compiled Compiled file
 */
function make($src, $combined, $compiled='')
{
    if (file_exists($src))
    {
        $src = file_get_contents($src);
        $pattern = '/\/\/inc\s+<(.*?)>/';
        preg_match_all($pattern, $src, $matches);
        for ($i = 0; $i < count($matches[1]); $i++)
        {
            $filename = $matches[1][$i];
            $contents = file_get_contents($filename);
            $src = str_replace($matches[0][$i], $contents, $src);
        }

        // write merged plugin code
        writeFile($combined, $src);

        // compile and write plugin code
        if ($compiled)
        {
            if (file_exists('README.textile'))
                $help = file_get_contents('README.textile');
            writeFile($compiled, compile_plugin($combined, (isset($help) ? $help : '')));
        }
    }
}

/**
 * Write contents to a file.
 *
 * @param string $filename
 * @param string $contents
 */
function writeFile($filename, $contents)
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

/**
 * Extract plugin metadata.
 *
 * @param string $meta
 */
function extractMeta($meta)
{
    foreach (array('name', 'description', 'author', 'author_uri', 'version',
        'type', 'order') as $field)
    {
        $out[$field] = getField($field, $meta);
    }

    return $out;
}

/**
 * Returns a field's value.
 *
 * @param string $field
 * @param string $meta
 */
function getField($field, &$meta)
{
    preg_match("/@{$field}\s+(.*)/", $meta, $field);

    return ($field[1]) ? $field[1] : '';
}

/**
 * Removes the opening and closing PHP tags.
 *
 * Because the <code># --- BEGIN|END PLUGIN CODE ---</code> delimiters
 * are no longer needed, the PHP tags need to be stripped for TXP.
 *
 * @param string $plugin
 */
function extractCode($plugin)
{
    $plugin = str_replace('<?php', '', $plugin);
    $plugin = str_replace('?>', '', $plugin);

    return $plugin;
}

/**
 * Compiles plugin code into base64 format.
 *
 * @param string $file
 * @param string $help
 */
function compile_plugin($file, $help)
{
    $content = file_get_contents($file);
    $plugin = extractMeta($content);
    $plugin['code'] = extractCode($content);
    $plugin['md5'] = md5($plugin['code']);
    $plugin['help_raw'] = $help;

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

    $body = trim(chunk_split(base64_encode(gzencode(serialize($plugin))), 72));

    return $header."\n\n".$body;
}

?>

