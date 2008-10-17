<?php

/**
 * CLI usage:
 * $ php make.php source.php output.php
 */
if (isset($argc))
{
    if ($argc === 3)
    {
        make($argv[1], $argv[2]);
    }
    else
    {
        exit("Invalid arguments provided.\n");
    }
}

/**
 * Make, for TXP plugins.
 *
 * @param str $src Source file
 * @param str $output Destination file
 * @param bool $compile Compile to a text file.
 */
function make($src, $output, $compile=0)
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
        
        // write merged plugin code to $output
        if (is_writable($output))
        {
            $handle = fopen($output, 'w');
            fwrite($handle, $src);
            fclose($handle);
        }
    }
}

?>