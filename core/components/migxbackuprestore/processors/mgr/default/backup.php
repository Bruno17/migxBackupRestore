<?php

set_time_limit(0);

if (empty($scriptProperties['object_id'])) {
    $updateerror = true;
    $errormsg = $modx->lexicon('quip.thread_err_ns');
    return;
}
$errormsg = '';
$config = $modx->migx->customconfigs;

$prefix = isset($config['prefix']) && !empty($config['prefix']) ? $config['prefix'] : null;
if (isset($config['use_custom_prefix']) && !empty($config['use_custom_prefix'])) {
    $prefix = isset($config['prefix']) ? $config['prefix'] : '';
}

if (!empty($config['packageName'])) {
    $packageNames = explode(',', $config['packageName']);

    if (count($packageNames) == '1') {
        //for now connecting also to foreign databases, only with one package by default possible
        $xpdo = $modx->migx->getXpdoInstanceAndAddPackage($config);
    } else {
        //all packages must have the same prefix for now!
        foreach ($packageNames as $packageName) {
            $packagepath = $modx->getOption('core_path') . 'components/' . $packageName . '/';
            $modelpath = $packagepath . 'model/';
            if (is_dir($modelpath)) {
                $modx->addPackage($packageName, $modelpath, $prefix);
            }

        }
        $xpdo = &$modx;
    }
} else {
    $xpdo = &$modx;
}

$classname = $config['classname'];

if ($settings_o = $modx->getObject($classname, $scriptProperties['object_id'])) {
    $backupdirs = $modx->fromJson($settings_o->get('backupdirs'));
    $dbbackup = $settings_o->get('dbbackup');
    $name = $settings_o->get('name');
    $use_drop = $settings_o->get('use_drop')==1 ? true : false;
    $includeTables = $settings_o->get('backuptables');
    $includeTables = str_replace('||',',',$includeTables);
    $custom_autoinc = $settings_o->get('custom_autoinc');

    $componentsfolder = dirname(dirname(dirname(dirname(__file__))));
    $backupfolder = $componentsfolder . '/backups/';
    $exportfolder = $backupfolder . date("Ymd-His") . '-export-db/';
    $zipfolder = $backupfolder . 'export-files/';

    include ($componentsfolder . '/includes/ziproot_funcs.php');

    // Create dump
    //$exportfolder = './export-db/';
    $tmpfolder = $exportfolder . 'tmp/';
    if (!file_exists($zipfolder)) {
        mkdir($zipfolder, 0777, true);
    }
    if (!file_exists($backupfolder)) {
        mkdir($backupfolder, 0777, true);
    }
    if (!file_exists($exportfolder)) {
        mkdir($exportfolder, 0777, true);
    }
    if (!file_exists($tmpfolder)) {
        mkdir($tmpfolder, 0777, true);
    }
    // Protect folders
    file_put_contents($backupfolder . ".htaccess", "order deny,allow" . PHP_EOL . "deny from all" . PHP_EOL);
    file_put_contents($exportfolder . ".htaccess", "order deny,allow" . PHP_EOL . "deny from all" . PHP_EOL);

    $destination = $zipfolder . date("Ymd-His") . "-" . $name . "-" . strtolower($_SERVER['HTTP_HOST']) . ".zip";
    $dirconstants = array();
    $dirconstants['MODX_CORE_PATH'] = 'core/';
    $dirconstants['MODX_ASSETS_PATH'] = 'assets/';
    $dirconstants['MODX_PROCESSORS_PATH'] = 'core/model/modx/processors/';
    $dirconstants['MODX_CONNECTORS_PATH'] = 'connectors/';
    $dirconstants['MODX_MANAGER_PATH'] = 'manager/';
    $dirconstants['MODX_BASE_PATH'] = '';

    $replace = array();
    $search = array();
    $specialdirs = array();
    foreach ($dirconstants as $constant => $defaultdir) {
        $search[] = $constant;
        $replace[] = constant($constant);
        $specialdirs[$constant] = constant($constant); 
    }
    $search[] = '//';
    $replace[] = '/';

    if (!extension_loaded('zip')) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $excludeFiles = array();
    $excludeFiles[] = $backupfolder;

    if (is_array($backupdirs)) {
        //collect excludes
        foreach ($backupdirs as $backupdir) {
            $mode = $modx->getOption('mode', $backupdir, '');
            $dir = $modx->getOption('dir', $backupdir, '');
            $dir = str_replace($search, $replace, $dir . '/');
            if ($mode == 'exclude') {
                $excludeFiles[] = $dir;
            }
        }

        foreach ($backupdirs as $backupdir) {
            $mode = $modx->getOption('mode', $backupdir, '');
            $dir = $modx->getOption('dir', $backupdir, '');
            $zipdir = $modx->getOption('zipdir', $backupdir, '');
            $dir = str_replace($search, $replace, $dir . '/');
            if ($mode == 'include') {
                addFolderToZip($dir, $zip, $zipdir, $excludeFiles, $dirconstants, $specialdirs);
            }
        }
    }

    if (!empty($dbbackup)) {
        // run sql dump
        $modx->runSnippet('mbrBackup', array(
            'dataFolder' => $exportfolder,
            'tempFolder' => $tmpfolder,
            'createDatabase' => false,
            'writeTableFiles' => false,
            'writeFile' => true,
            'useDrop' => $use_drop,
            'includeTables' => $includeTables,
            'custom_autoinc' => $custom_autoinc
            ));

        delTree($tmpfolder);
        addFolderToZip($exportfolder, $zip, 'export-db/', $excludeFiles);
    }

    $settings_o->set('latestfile',$destination);
    $settings_o->save();
   
    return $modx->error->success('done');

}
