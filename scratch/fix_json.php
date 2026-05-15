<?php
$f = __DIR__ . '/leads.json';
$j = file_get_contents($f);
// Remove invalid single quote escapes
$j = str_replace("\\'", "'", $j);
file_put_contents($f, $j);
echo "JSON purified successfully.";
