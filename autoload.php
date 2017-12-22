<?php

$vendorPath = __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if(file_exists($vendorPath))
{
    require_once $vendorPath;
}
else
{
    spl_autoload_register(function($className){

        $namespace = 'Now';

        $className = trim($className, '\\');

        if(strpos($className, $namespace) === 0)
        {
            $path = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . str_replace('\\', '/', $className) . '.php';

            if(is_file($path))
            {
                require_once $path;
            }
        }

    });
}