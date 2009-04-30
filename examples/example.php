<?php
/**
 * @name            jmd_example
 * @description     A glorified "Hello, World".
 * @author          Jon-Michael Deldin
 * @author_uri      http://jmdeldin.com
 * @version         0.1
 * @type            0
 * @order           5
 */

function jmd_example($attrs, $thing)
{
    $js = <<<EOF
//inc <example.js>
EOF;

    return tag($js, 'script', ' type="text/javascript"');
}

