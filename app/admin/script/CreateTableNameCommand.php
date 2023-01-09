<?php

namespace app\admin\script;

use app\admin\common\ApiUrlConfig;
use app\admin\model\BiRoomHideLogModel;
use app\admin\model\RoomHideModel;
use app\admin\service\ApiService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);

class CreateTableNameCommand extends Command
{

    protected function configure()
    {
        $this->setName('CreateTableNameCommand')->setDescription('CreateTableNameCommand');
    }

    /**
     *执行方法
     */
    protected function execute(Input $input, Output $output)
    {
        try{

            $tableSuffix = date('Ym',strtotime("+1months"));
            $tableNameList = [
                "bi_user_enter_room",
                "bi_user_room_chat",
                "bi_message_enter_room",
                "bi_message_room_chat",
                "bi_message_onmic"
            ];
            foreach($tableNameList as $tableName){
                $createTableName = "{$tableName}_{$tableSuffix}";
                Db::execute("create table if not exists  {$createTableName} like {$tableName}  ");
                Log::info("createtablename:".$createTableName);
            }

        }catch (\Throwable $e){
            Log::error("createtablenamecommand:error".$e->getMessage().$e->getFile().$e->getLine());
        }



    }



}
