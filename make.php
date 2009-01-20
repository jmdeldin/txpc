<?php

/**
 * CLI usage:
 * $ php make.php source.php merged.php [../cache/compiled.txt]
 */
if (isset($argc))
{
    if ($argc > 2)
    {
        make($argv[1], $argv[2], $argv[3]);
    }
    else
    {
        exit("Invalid arguments provided.\n");
    }
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
    {
        exit("Unable to write to {$filename}.\n");
    }
}

// http://code.google.com/p/textpattern/source/browse/development/4.0-plugin-template/zem_tpl.php
function extract_section($lines, $section)
{
    $start_delim = "# --- BEGIN PLUGIN $section ---";
    $end_delim = "# --- END PLUGIN $section ---";

    $start = array_search($start_delim, $lines) + 1;
    $end = array_search($end_delim, $lines);

    $content = array_slice($lines, $start, $end-$start);

    return join("\n", $content);

}

function compile_plugin($file, $help)
{
    require $file;
    
    if (!isset($plugin['name']))
    {
        $plugin['name'] = basename($file, '.php');
    }

    // Read the contents of this file, and strip line ends
    $content = file($file);
    for ($i = 0; $i < count($content); $i++)
    {
        $content[$i] = rtrim($content[$i]);
    }
    $plugin['code'] = extract_section($content, 'CODE');
    $plugin['help_raw'] = $help;
    $plugin['md5'] = md5($plugin['code']);
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
