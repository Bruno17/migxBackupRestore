<?php

/**
 *
 * Usage: see readme.txt
 *
 * Add your ip to the $whitelist_addr list below
 */

// functions

/**
 * ZIP
 */

function my_zip($destination, $excludeFiles) {

    $excludeFiles = array_merge($excludeFiles, array($destination));

    if (!extension_loaded('zip')) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    addFolderToZip("./", $zip, '', $excludeFiles, $dirconstants, $specialdirs);
}

// Function to recursively add a directory,
// sub-directories and files to a zip archive
function addFolderToZip($dir, &$zipArchive, $zipdir = '', $excludeFiles = array(), $dirconstants=array(), $specialdirs=array(), $recursive=true) {
    if (is_dir($dir) && $dh = opendir($dir)) {

        if (in_array($dir, $specialdirs)) {
            $key = array_search($dir, $specialdirs);
            if (isset($dirconstants[$key])){
               $zipdir = $dirconstants[$key];
            }
        }

        //Add the directory
        if (!empty($zipdir))
            $zipArchive->addEmptyDir($zipdir);

        // Loop through all the files
        while (($file = readdir($dh)) !== false) {
            // Skip parent and root directories
            if ($file == ".") {
                continue;
            }
            if ($file == "..") {
                continue;
            }
            if (in_array($dir . $file, $excludeFiles)) {
                continue;
            }
            if (in_array($dir . $file . '/', $excludeFiles)) {
                continue;
            }

            //If it's a folder, run the function again!
            if (is_dir($dir . $file)) {
                if ($recursive){
                    addFolderToZip($dir . $file . "/", $zipArchive, $zipdir . $file . "/", $excludeFiles, $dirconstants, $specialdirs);                    
                }
            } else {
                // Add the files (if not excluded)
                $zipArchive->addFile($dir . $file, $zipdir . $file);
            }
        }
    }
}


/**
 * Unzip
 */

function my_unzip($source, $destination) {
    $zip = new ZipArchive();
    if ($zip->open($source) && $zip->extractTo($destination)) {
        $zip->close();
        return true;
    } else {
        return false;
    }
}


function delTree($dir) {
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

function directoryToArray($directory, $recursive = true, $listDirs = false, $listFiles = true, $exclude = '') {
    $arrayItems = array();
    $skipByExclude = false;
    $handle = opendir($directory);
    if ($handle) {
        while (false !== ($file = readdir($handle))) {
            preg_match("/(^(([\.]){1,2})$|(\.(svn|git|md))|(Thumbs\.db|\.DS_STORE))$/iu", $file, $skip);
            if ($exclude) {
                preg_match($exclude, $file, $skipByExclude);
            }
            if (!$skip && !$skipByExclude) {
                if (is_dir($directory . DIRECTORY_SEPARATOR . $file)) {
                    if ($recursive) {
                        $arrayItems = array_merge($arrayItems, directoryToArray($directory . DIRECTORY_SEPARATOR . $file, $recursive, $listDirs, $listFiles, $exclude));
                    }
                    if ($listDirs) {
                        $file = $directory . DIRECTORY_SEPARATOR . $file;
                        $arrayItems[] = $file;
                    }
                } else {
                    if ($listFiles) {
                        $file = $directory . DIRECTORY_SEPARATOR . $file;
                        $arrayItems[] = $file;
                    }
                }
            }
        }
        closedir($handle);
    }
    return $arrayItems;
}

function cURLcheckBasicFunctions() {
    if (!function_exists("curl_init") && !function_exists("curl_setopt") && !function_exists("curl_exec") && !function_exists("curl_close"))
        return false;
    else
        return true;
}

/*
* Returns string status information.
* Can be changed to int or bool return types.
*/
function cURLdownload($url, $file) {
    if (!cURLcheckBasicFunctions())
        return "UNAVAILABLE: cURL Basic Functions";
    $ch = curl_init();
    if ($ch) {
        $fp = fopen($file, "w");
        if ($fp) {
            if (!curl_setopt($ch, CURLOPT_URL, $url)) {
                fclose($fp); // to match fopen()
                curl_close($ch); // to match curl_init()
                return "FAIL: curl_setopt(CURLOPT_URL)";
            }
            if (!curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true))
                return "FAIL: curl_setopt(CURLOPT_FOLLOWLOCATION)";
            if (!curl_setopt($ch, CURLOPT_FILE, $fp))
                return "FAIL: curl_setopt(CURLOPT_FILE)";
            if (!curl_setopt($ch, CURLOPT_HEADER, 0))
                return "FAIL: curl_setopt(CURLOPT_HEADER)";
            if (!curl_exec($ch)) {
                if ($errno = curl_errno($ch)) {
                    $error_message = curl_error($errno);
                    return "FAIL: curl_exec(); cURL error ({$errno}):\n {$error_message}";
                }
                return "FAIL: curl_exec()";
            }
            curl_close($ch);
            fclose($fp);
            return true;
        } else
            return "FAIL: fopen()";
    } else
        return "FAIL: curl_init()";
}

function addMessage($class, $text) {
    if (!array_key_exists("messages", $_SESSION) || !is_array($_SESSION['messages']))
        $_SESSION['messages'] = array();
    $_SESSION['messages'][] = array($class, $text);
}

function flushMessages() {
    if (!array_key_exists('messages', $_SESSION))
        return;
    foreach ($_SESSION['messages'] as $message) {
        list($class, $text) = $message;

        echo '<div class="alert alert-' . $class . '">' . $text . '</div>';
    }
    echo "<hr />";
    unset($_SESSION['messages']);
}

function redirectWithQuery($url, $query = array(), $permanent = false) {
    return redirect($url . "?" . http_build_query($query), $permanent);
}

function redirect($url, $permanent = false) {
    if ($permanent) {
        header('HTTP/1.1 301 Moved Permanently');
    }
    header('Location: ' . $url);
    exit();
}

function sQuote($input) {
    return "'$input'";
}
