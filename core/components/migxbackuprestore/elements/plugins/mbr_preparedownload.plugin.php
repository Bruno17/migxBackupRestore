<?php
$zip_resource = $modx->getOption('migxbackuprestore.zip_resource_id',null,0);

$packageName = 'migxbackuprestore';

$packagepath = $modx->getOption('core_path') . 'components/' . $packageName . '/';
$modelpath = $packagepath . 'model/';
$modx->addPackage($packageName, $modelpath);

switch ($modx->event->name) {
    case 'OnLoadWebDocument':
        $id = $modx->resource->get('id');
        
        if ($id == $zip_resource){
            $download_id = $modx->getOption('download_id', $_REQUEST, '');
            $classname = 'mbrSetting';
            if (!empty($download_id)){
                if ($download = $modx->getObject($classname,$download_id)){
                    $file = $download->get('latestfile');
                    if (!empty($file) && file_exists($file)) {
                        //$modx->resource->set('contentType', $content_type);
                        $modx->resource->set('content', $file);
                        //$modx->resource->set('uri', $modx->resource->cleanAlias($filepath . $filename));                         
                    }
                   
                }
            }
        }

        break;
}

return;