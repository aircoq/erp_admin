<?php
// This autoload is here just in case you didn't run composer install.
// Running composer install would be a better way to autoload the classes.

// We search in the ../../packages/ folder
$packageFolder = __DIR__ . '/../../packages';
spl_autoload_register(function ($className) use ($packageFolder) {
    $tryFolders = array();
    $splits = explode('\\', $className);

    $c = count($splits);
    if ($c > 0 && $splits[0] === 'BarcodeBakery') {
        if ($c > 1) {
            if ($splits[1] === 'Common') {
                $tryFolders = array('barcode-common');
            } else {
                // Try all the other folders
                $tryFolders = array_filter(scandir($packageFolder), function ($f) {
                    if ($f !== '.' && $f !== '..' && $f !== 'barcode-common') {
                        return true;
                    }
                    
                    return false;
                });
            }
        }
    }
    
    if (count($tryFolders) > 0) {
        $file = implode('/', array_slice($splits, 2)) . '.php';
        foreach ($tryFolders as $folder) {
            $fullpath = $packageFolder . '/' . $folder . '/src/' . $file;
            
            if (file_exists($fullpath)) {
                include $fullpath;
                break;
            }
        }
    }
});
