<?php

$gridcontextmenus['backup']['code']="
        m.push({
            className : 'backup', 
            text: 'Run Backup',
            handler: 'this.runBackup'
        });						
";
$gridcontextmenus['backup']['handler'] = 'this.runBackup';

$gridfunctions['this.runBackup'] = "
runBackup: function(btn,e) {
    var _this=this;
    Ext.Msg.confirm(_('warning') || '','Run Backup',function(e) {
        if (e == 'yes') {    
            MODx.Ajax.request({
                url: _this.config.url
                ,params: {
                    action: 'mgr/migxdb/process'
                    ,processaction: 'backup'                     
                    ,object_id: _this.menu.record.id
	     			,configs: _this.config.configs
                    ,resource_id: _this.config.resource_id
                    ,co_id: '[[+config.connected_object_id]]'
                    ,reqConfigs: '[[+config.req_configs]]'  
                }
                ,listeners: {
                    'success': {fn:function(r) {
                        _this.refresh();
                    },scope:_this}
                }
            });
        }
    }),this;           
    return true;
}
";