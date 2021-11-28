<?php
class Offer {
    private $connection;
    private $table = "trade_offers";

    public $id;
    public $user_id;
    public $item_id;
    public $price;

    public function __construct($db) {
        $this->connection = $db;
    }

    // returns all offers by default, with options for returning specific range of offers
    public function getAll($num=0, $offset=0) {
        $sql = "SELECT O.id, O.user_id, O.item_id, O.price 
                FROM {$this->table} AS O ";
        
        $stmt = null;
        if ($num > 0) {
            $sql.= "LIMIT :num OFFSET :offset";
            $params = array(
                ":num"    => array("type" => PDO::PARAM_INT, "value" => $num),
                ":offset" => array("type" => PDO::PARAM_INT, "value" => $offset)
            );
            $stmt = $this->connection->preparedQuery($sql, $params);
        }
        else {
            $stmt = $this->connection->preparedQuery($sql);
        }

        return $stmt;
    }

    // returns offer with $offer_id id
    public function getByID($offer_id) {
        $sql = "SELECT O.id, O.user_id, O.item_id, O.price 
                FROM {$this->table} AS O 
                WHERE O.id = :oid";
        $params = array(
            ":oid" => array("type" => PDO::PARAM_INT, "value" => $offer_id)
        );
        $stmt = $this->connection->preparedQuery($sql, $params);

        // populate this offer object's values
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->id      = $row["id"];
            $this->user_id = $row["user_id"];
            $this->item_id = $row["item_id"];
            $this->price   = $row["price"];
        }
    }

    // returns all offers that have $item_id up for sale
    public function getByItemID($item_id) {
        $sql = "SELECT O.id, O.user_id, O.item_id, O.price 
                FROM {$this->table} AS O 
                WHERE O.item_id = :iid";
        $params = array(
            ":iid" => array("type" => PDO::PARAM_INT, "value" => $item_id)
        );
        $stmt = $this->connection->preparedQuery($sql, $params);
        
        return $stmt;
    }

    // returns all offers belonging to $user_id
    public function getByUserID($user_id) {
        $sql = "SELECT O.id, O.user_id, O.item_id, O.price 
                FROM {$this->table} AS O 
                WHERE O.user_id = :uid";
        $params = array(
            ":uid" => array("type" => PDO::PARAM_INT, "value" => $user_id)
        );
        $stmt = $this->connection->preparedQuery($sql, $params);
        
        return $stmt;
    }

    // returns all offers belonging to every id in $user_ids
    public function getByUserIDs($user_ids) {
        $questionMarks = array();
        for ($i = 0; $i < count($user_ids); $i++) {
            $questionMarks[] = "?";
        }
        $questionMarks = implode(",", $questionMarks); // we can directly insert this into our query as it is server instantiated

        $sql = "SELECT O.id, O.user_id, O.item_id, O.price 
                FROM {$this->table} AS O 
                WHERE O.user_id IN ({$questionMarks})";
        $params = array();
        $count = 1;
        foreach ($user_ids as $uid) {
            $params[$count] = array("type" => PDO::PARAM_INT, "value" => $uid);
            $count++;
        }
        
        $stmt = $this->connection->preparedQuery($sql, $params);
        
        return $stmt;
    }

    // returns all offers belonging to $username
    public function getByUsername($username) {
        $sql = "SELECT O.id, O.user_id, O.item_id, O.price 
                FROM {$this->table} AS O 
                INNER JOIN users AS U ON U.id = O.user_id 
                WHERE U.username = :username";
        $params = array(
            ":username" => array("type" => PDO::PARAM_STR, "value" => $username)
        );
        $stmt = $this->connection->preparedQuery($sql, $params);
        
        return $stmt;
    }

    // returns all offers that fall into a price range $low to $high
    public function getByPriceRange($low, $high) {
        $sql = "SELECT O.id, O.user_id, O.item_id, O.price 
                FROM {$this->table} AS O 
                WHERE O.price >= :low AND O.price <= :high";
        $params = array(
            ":low"  => array("type" => PDO::PARAM_INT, "value" => $low),
            ":high" => array("type" => PDO::PARAM_INT, "value" => $high)
        );
        $stmt = $this->connection->preparedQuery($sql, $params);
        
        return $stmt;
    }

    // inserts this offer object into database
    public function insert() {
        $sql = "INSERT INTO {$this->table} (user_id, item_id, price) 
                VALUES (:uid, :iid, :price)";
        $params = array(
            ":uid"   => array("type" => PDO::PARAM_INT, "value" => $this->user_id),
            ":iid"   => array("type" => PDO::PARAM_INT, "value" => $this->item_id),
            ":price" => array("type" => PDO::PARAM_INT, "value" => $this->price)
        );
        $stmt = $this->connection->preparedQuery($sql, $params);
        $this->id = $this->connection->lastInsertId();

        return $stmt;
    }
    
    // removes this offer object from database
    public function delete() {
        $sql = "DELETE FROM {$this->table} WHERE id = :oid";
        $params = array(
            ":oid" => array("type" => PDO::PARAM_INT, "value" => $this->id)
        );
        $stmt = $this->connection->preparedQuery($sql, $params);

        return $stmt;
    }
}
?>