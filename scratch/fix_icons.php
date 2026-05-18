<?php
$dir = new RecursiveDirectoryIterator('C:\xampp\htdocs\TRUMARK\resources\views');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.*\.blade\.php$/', RegexIterator::GET_MATCH);

foreach($files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    $pattern = "/@section\('page_icon'\)\s*<i class=\"fa (fa-[^\"]+)\"><\/i>\s*@endsection/s";
    if (preg_match($pattern, $content)) {
        $newContent = preg_replace($pattern, "@section('page_icon', '$1')", $content);
        file_put_contents($path, $newContent);
        echo "Fixed: $path\n";
    }
}
echo "Done.\n";
