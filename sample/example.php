<?php
$plugin = array(
    'author' => 'Jon-Michael Deldin',
    'author_uri' => 'http://jmdeldin.com',
    'description' => 'A glorified "Hello, World".',
    'name' => 'jmd_example',
    'type' => 0,
    'version' => '0.1',
);
# --- BEGIN PLUGIN CODE ---

function jmd_example($atts)
{
    $js = <<<EOF
//inc <example.js>
EOF;
    
    return tag($js, 'script', ' type="text/javascript"');

}

# --- END PLUGIN CODE ---
?>
