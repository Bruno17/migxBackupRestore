<?php
$master_domain = $modx->getOption('migxbackuprestore.masterdomain');
$loginData = $modx->getOption('migxbackuprestore.masterlogindata');
$loginUrl = $modx->getOption('migxbackuprestore.masterloginurl');
$downloadUrl = $modx->getOption('migxbackuprestore.masterdownloadurl');

$ip = get_client_ip();
$v = $modx->getVersionData();
$modx_version = 'revo' . $modx->getOption('full_version', $v, '');

$tpl = '
<h3>[[+title]]</h3>

[[+form]]

<p>&nbsp;</p>
';

$placeholders['title'] = 'Upgrade from Master';
$placeholders['form'] = '<br/><br/>
        <form method="post" action="">
           <input class="x-btn x-btn-small x-btn-icon-small-left primary-button x-btn-noicon"
                    type="submit" name="UpgradePortal" value="Run Upgrader">
        </form>';

function get_client_ip()
{
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else
        if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else
            if (getenv('HTTP_X_FORWARDED'))
                $ipaddress = getenv('HTTP_X_FORWARDED');
            else
                if (getenv('HTTP_FORWARDED_FOR'))
                    $ipaddress = getenv('HTTP_FORWARDED_FOR');
                else
                    if (getenv('HTTP_FORWARDED'))
                        $ipaddress = getenv('HTTP_FORWARDED');
                    else
                        if (getenv('REMOTE_ADDR'))
                            $ipaddress = getenv('REMOTE_ADDR');
                        else
                            $ipaddress = 'UNKNOWN';

    return $ipaddress;
}

function cURLcheckBasicFunctions()
{
    if (!function_exists("curl_init") && !function_exists("curl_setopt") && !function_exists("curl_exec") && !function_exists("curl_close"))
        return false;
    else
        return true;
}
/*
* Returns string status information.
* Can be changed to int or bool return types.
*/
function cURLdownload($url, $file, $loginUrl = '', $loginData = '')
{
    if (!cURLcheckBasicFunctions())
        return "UNAVAILABLE: cURL Basic Functions";
    $ch = curl_init();
    if ($ch) {

        if (!empty($loginUrl)) {
            //Set the URL to work with
            curl_setopt($ch, CURLOPT_URL, $loginUrl);

            // ENABLE HTTP POST
            curl_setopt($ch, CURLOPT_POST, 1);

            //Set the post parameters
            curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);

            //Handle cookies for the login
            curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');

            //Setting CURLOPT_RETURNTRANSFER variable to 1 will force cURL
            //not to print out the results of its query.
            //Instead, it will return the results as a string return value
            //from curl_exec() instead of the usual true/false.
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            //execute the request (the login)
            $store = curl_exec($ch);
        }

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

if (isset($_POST['UpgradePortal'])) {
    $sep = strstr($downloadUrl, '?') ? '&' : '?';
    if ($res = cURLdownload($downloadUrl . $sep . "mode=upgrade&ip=" . $ip . '&modxversion=' . $modx_version, MODX_BASE_PATH . "install.php", $loginUrl, $loginData)) {


        /* Log out all users before launching the form */
        $sessionTable = $modx->getTableName('modSession');
        if ($modx->query("TRUNCATE TABLE {$sessionTable}") == false) {
            $flushed = false;
        } else {
            $modx->user->endSession();
        }
        $modx->sendRedirect(MODX_BASE_URL . 'install.php');

    } else {
        return 'Datei ' . MODX_BASE_PATH . 'install.php' . ' konnte nicht erstellt werden';
    }
}


$chunk = $modx->newObject('modChunk');
$chunk->setCacheable(false);
$chunk->setContent($tpl);

$output = $chunk->process($placeholders);

return $output;