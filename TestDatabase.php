<?php
class TestDatabase {

    function Query($sql, $params=array()){
        $result =  Database::execute($sql, $params);
        if (!$result){
            $msg = Database::error();
            file_put_contents( __DIR__ ."/db_errors.txt", $msg );
            throw new Exception( "DB ERROR: $msg" );
            }
        return $result;
    }    
  
    function getDbName(){
        return Database::getDbName();// $this->dbname;
    }
  
    function insert($table, $values){
        Database::insert($table, $values);
    }
  
    function update( $table, $data, $where, $params=array() ){
        Database::update($table, $data, $where, $params);
    }
  
    function delete( $table, $where, $params=array() ){
        Database::delete($table, $where, $params);
    }
  
    function insert_id(){
        return Database::getLastId();
    }
  
    function beginTransaction(){
        Database::beginTransaction();
    }
  
    function commit(){
        Database::commit();
    }
  
    function rollback(){
        Database::rollback();
    }
  
    function num_rows($result_set){
        return Database::num_rows($result_set);
    }
  
    public function formatQuery($sql, $params=array()){
        return Database::formatQuery($sql, $params);
    }
  
    function getIterator($query, $params=array()){
        return Database::getIterator($query, $params);
    }
  
    function error(){
        return Database::error();
    }
  
    function get_one($sql, $params=array()){
        return Database::get_one($sql, $params);
    }
	
    function auto_array($sql, $params = array(), $result_type=\Database::ASSOC) {
        return Database::auto_array($sql, $params, $result_type);
    }
	
    function getAll($query, $params=array() , $result_type=\Database::ASSOC ){
        return Database::getData($query, \Database::ALL, $result_type, $params);
    }
	
    function get_col($sql, $params=array()){
        return Database::get_col($sql, $params);
    }
	
    function fetch_row($result_set, $dorowset=false, $result_type=\Database::BOTH ) {
        return Database::fetch_row($result_set, $result_type );
    }
	
	//test tools
    function executeBlock($sql_block){
        $lines = explode(';', $sql_block);
        foreach($lines as $line){
            $line = trim($line);
            if (empty($line)){
                continue;
            }
            $this->Query($line);
            }
    }
    
    function disconnect(){
        return Database::disconnect();
    }
  
  
	
  
}