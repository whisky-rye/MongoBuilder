<?php
/**
 * 简单封装MongoDB驱动
 */
namespace mysticzhong;

class mongobuilder{

    public  $bulk;
    public  $manager;
    public  $writeConcern;
    private $dbname;

    /*
     * 架构函数
     */
    public function __construct(){

        $config = [
            // 'type'     => '\think\mongo\Connection', // 数据库类型
            'hostname' => '127.0.0.1',  // 服务器地址
            'database' => 'xxxxx',      // 数据库名
            'username' => 'root',       // 数据库用户名
            'password' => 'xxxxx',      // 数据库密码
            'hostport' => '27017',      // 数据库连接端口
        ];

        $this->manager = new \MongoDB\Driver\Manager("mongodb://".$config['username'].":".$config['password']."@".$config['hostname'].":".$config['hostport']);

        $this->writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY,1000);

        $this->readPreference = new \MongoDB\Driver\ReadPreference(\MongoDB\Driver\ReadPreference::RP_PRIMARY);

        $this->dbname = $config['database'];  // 构造定义库名
    }

    // 返回一个bulk对象以供执行insert,update,delete
    private function newBulk(){
        return new \MongoDB\Driver\BulkWrite();
    }

    // 返回一个MongoDB内置对象id
    private function newObjectID(){
        return new \MongoDB\BSON\ObjectID;
    }

    // 返回一个MongoDB驱动Query对象
    private function newQuery($filter,$options){
        return new \MongoDB\Driver\Query($filter,$options);
    }

    // 返回一个MongoDB驱动Command对象
    private function newCommand($document){
        return new \MongoDB\Driver\Command($document);
    }

    // 执行插入一条记录到文档
    public function doInsertOne($table,$array){
         $array['_id'] = $this->newObjectID();
         $array['id'] = $this->getLastInsertID($table)+1;

         $this->bulk = $this->newBulk();
         $this->bulk->insert($array);
         $result = $this->manager->executeBulkWrite($this->dbname.'.'.$table,$this->bulk,$this->writeConcern);
         if($result->getInsertedCount() > 0){
             return $array['id'];
         }else{
             return false;
         }
    }

    /**
     * 批量执行插入记录到文档
     * @access public
     * @param  string   $table   执行操作的表名
     * @param  array    $array   要插入的数组
     * @param  boolean  $isback  执行失败是否回滚删除
     * @return int      最后插入成功的id,0代表执行失败
     */
    public function doInsertAll($table,$array,$isback=false){

         if(is_array($array)){
             $index = $this->getLastInsertID($table);
             $start_index = $index;

             foreach ($array as $v1){
                     $index++;
                     $v1['id'] = $index;
                     $v1['_id'] = $this->newObjectID();

                     $this->bulk = $this->newBulk();
                     $this->bulk->insert($v1);
                     $result = $this->manager->executeBulkWrite($this->dbname.'.'.$table,$this->bulk,$this->writeConcern);

                     if($result->getInsertedCount() == 1){
                         // pass
                     }else{
                         if($isback){
                            $this->doDelete($table,['id' => [
                                '$gt' => $start_index,
                                '$lte' => $v1['id'],
                            ]]);
                         }
                         return 0; break;
                     }
             }

             return $index;
         }else{
             return 0;
         }

    }


    // 过滤型查询方法 对doQuery进行优化 数组返回
    public function filterQuery($table,$filter=[],$options=[]){
//      $options['projection']['id'] = 1;   // 默认显示自增id
        $options['projection']['_id'] = 0;  // 默认取消显示object id

        $query = $this->newQuery($filter,$options);
        $cursor = $this->manager->executeQuery($this->dbname.'.'.$table,$query,$this->readPreference);

        if(empty($cursor)){
            return null;
        }else{
            $res = $cursor->toArray();
            foreach ($res as $k=>$v){
                $res[$k] = get_object_vars($v);
            }
            return $res;
        }
    }


    // 过滤型查询方法 返回符合结果集的行数
    public function filterCount($table,$filter=[]){
        $options['projection']['id'] = 1;   // 默认显示自增id

        $query = $this->newQuery($filter,$options);
        $cursor = $this->manager->executeQuery($this->dbname.'.'.$table,$query,$this->readPreference);

        if(empty($cursor)){
            return 0;
        }else{
            return count($cursor->toArray());
        }
    }


    /**
     * 过滤查询方法 分组
     * @see http://bighow.org/3796532-MongoDB_aggregate_query_using_PHP_driver.html
     */
    public function filterGroupBy($table,$pipeline){

        $command = $this->newCommand(['aggregate' => $table, 'pipeline' => $pipeline ]);
        $cursor = $this->manager->executeCommand($this->dbname, $command);

        $result = $cursor->toArray()[0]->result;
        foreach ($result as $k=>$v){
            $result[$k] = get_object_vars($v);
        }
        return $result;
    }


    // 执行查询该表总行数
    public function doCountAll($table){
        $options = [];
        $options['projection']['_id'] = 1;  // 默认显示object id

        $query = $this->newQuery([],$options);
        $cursor = $this->manager->executeQuery($this->dbname.'.'.$table,$query,$this->readPreference);

        return intval(count($cursor->toArray()));
    }


    // 返回该表最后一行记录的id值
    public function getLastInsertID($table){
        $options = [];
        $options['projection']['id'] = 1;
        $options['sort']['id'] = -1;

        $lastid = $this->doQueryOne($table,[],$options);
        if(empty($lastid)){
            return 1;
        }else{
            return $lastid['id'];
        }
    }


    // 执行查询只返回一个结果
    public function doQueryOne($table,$filter=[],$options=[]){
        // $options['projection']['id'] = 1;  // 默认显示自增id
        $options['projection']['_id'] = 0;    // 默认取消显示object id
        $options['limit'] = 1;                // 限制只出现一个结果集

        $query = $this->newQuery($filter,$options);
        $cursor = $this->manager->executeQuery($this->dbname.'.'.$table,$query,$this->readPreference);

        if(empty($cursor)){
             return null;
        }else{
            $res_arr = $cursor->toArray();
            if(count($res_arr) == 0){
                return null;
            }else{
                return get_object_vars($res_arr[0]);
            }
        }
    }


    // 执行删除
    public function doDelete($table,$filter=[],$limit=0){
        $this->bulk = $this->newBulk();

        // limit为0时删除所有匹配数据
        $this->bulk->delete($filter, ['limit' => $limit]);

        $result = $this->manager->executeBulkWrite($this->dbname.'.'.$table,$this->bulk,$this->writeConcern);
        return $result->getDeletedCount();

    }


    // 执行更新
    public function doUpdate($table,$where=[],$set=[],$multi=true,$upsert=false){
        // multi  默认是false,只更新找到的第一条记录 如果为true, 就全部更新
        // upsert 如果不存在update的记录 是否插入objNew,true为插入 默认是false 不插入
        $this->bulk = $this->newBulk();

        $this->bulk->update(
            $where,
            ['$set' => $set],
            ['multi' => $multi, 'upsert' => $upsert]
        );

        $result = $this->manager->executeBulkWrite($this->dbname.'.'.$table,$this->bulk,$this->writeConcern);
        return $result->getModifiedCount();

    }

    // 返回mongo正则对象
    public function newRegex($pattern,$flags='i'){
        return new \MongoDB\BSON\Regex($pattern,$flags);
    }


}








