<?php
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');

function ForceDownload($filename)
{ 
    if (ini_get('zlib.output_compression'))
    {
        ini_set('zlib.output_compression', 'Off'); 
    }
    
    $path = dirname(__FILE__) . '/templates/' . $filename . '.mtpl';

    $file_extension = strtolower(substr(strrchr($filename, '.'), 1)); 

    if ($filename == '')  
    { 
        echo "download file NOT SPECIFIED."; 
        exit; 
    }
    elseif ( ! file_exists( $path ) )  
    { 
        echo "File not found."; 
        exit; 
    };
    
    switch  ($file_extension) 
    { 
        case "pdf": $ctype="application/pdf"; break; 
        case "mp3": $ctype="audio/x-mp3"; break; 
        case "zip": $ctype="application/zip"; break; 
        case "rar": $ctype="application/zip"; break; 
        case "tar": $ctype="application/zip"; break; 
        case "sit": $ctype="application/zip"; break; 
        case "doc": $ctype="application/msword"; break; 
        case "xls": $ctype="application/vnd.ms-excel"; break; 
        case "ppt": $ctype="application/vnd.ms-powerpoint"; break; 
        case "gif": $ctype="image/gif"; break; 
        case "png": $ctype="image/png"; break; 
        case "jpeg": 
        case "jpg": $ctype="image/jpg"; break; 
        default: $ctype="application/force-download"; 
    }
    
    header("Pragma: public");
    header("Expires: 0"); 
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
    header("Cache-Control: private",false);
    header("Content-Type: $ctype"); 
    header("Content-Disposition: attachment; filename=\"" . $filename . "_" . time() . ".mtpl\";" ); 
    header("Content-Transfer-Encoding: binary"); 
    header("Content-Length: " . filesize($path)); 
    readfile("$path"); 
    exit(); 
}

$key = Tools::getValue('key', false);
$file = Tools::getValue('template', false);

if ( ! $key)
{
    die('Invalid token.');
}
elseif ( ! Validate::isMd5($key) || ! $key == md5(_COOKIE_KEY_))
{
    die('Hack attempt.');
}
elseif ( ! $file)
{
    die('Please specify file');
}
elseif ( ! Validate::isFileName($file))
{
    die('This isn\'t a valid file');
}
elseif ( ! file_exists(dirname(__FILE__) . '/templates/' . $file . '.mtpl'))
{
    die('File does not exist.');
}
else
{
    ForceDownload($file);
}
?>