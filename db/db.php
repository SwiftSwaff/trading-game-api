<?php
/*
 * DB class extends default PDO class, allows it to serve as the database object
 *      preparedQuery ($sql, $param): establishes and executes a prepared statement
 */
class DB extends \PDO {
    public function __construct($override=null) {
        $dbpath = ($override===null) 
            ? "sqlite:" . __DIR__ . "/trading-game.sqlite3" 
            : $override;
        parent::__construct($dbpath);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    public function preparedQuery($sql, $params=[]) {
        $stmt = $this->prepare($sql);
        foreach ($params as $symbol => $param) {
            $stmt->bindParam($symbol, $param["value"], $param["type"]);
        }
        $stmt->execute();
        return $stmt;
    }
}
?>