<?php
class User {
    private $connection;
    private $table = "users";

    public $id;
    public $username;
    public $money;
    public $inventory;

    public function __construct($db) {
        $this->connection = $db;
    }

    // returns all users 
    public function getAll() {
        $sql = "SELECT U.id, U.username, U.money, Inv.item_id, I.name AS item_name, Inv.count AS item_count 
                FROM {$this->table} AS U 
                LEFT JOIN user_item AS Inv ON Inv.user_id = U.id 
                LEFT JOIN items AS I ON I.id = Inv.item_id ";
        $stmt = $this->connection->preparedQuery($sql);
        
        return $stmt;
    }

    // returns $num random users
    public function getRandomUserIDs($num) {
        $sql = "SELECT U.id, U.username, U.money
                FROM {$this->table} AS U 
                ORDER BY RANDOM() 
                LIMIT :num";
        $params = array(
            ":num" => array("type" => PDO::PARAM_INT, "value" => $num)
        );
        $stmt = $this->connection->preparedQuery($sql, $params);
        
        return $stmt;
    }

    // returns user with $user_id id
    public function getByID($user_id) {
        $sql = "SELECT U.id, U.username, U.money, Inv.item_id, I.name AS item_name, Inv.count AS item_count  
                FROM {$this->table} AS U 
                LEFT JOIN user_item AS Inv ON Inv.user_id = U.id 
                LEFT JOIN items AS I ON I.id = Inv.item_id 
                WHERE U.id = :uid";
        $params = array(
            ":uid" => array("type" => PDO::PARAM_INT, "value" => $user_id)
        );
        $stmt = $this->connection->preparedQuery($sql, $params);

        // populate this object's values, and insert items into inventory array if necessary
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($this->id == null) {
                $this->id        = $row["id"];
                $this->username  = $row["username"];
                $this->money     = $row["money"];
                $this->inventory = [];
            }
            if ($row["item_id"] != null) {
                $this->inventory[] = array(
                    "item_id"    => $row["item_id"],
                    "item_name"  => $row["item_name"],
                    "item_count" => $row["item_count"]
                );
            }
        }
    }

    // returns user with $username username
    public function getByName($username) {
        $sql = "SELECT U.id, U.username, U.money, Inv.item_id, I.name AS item_name, Inv.count AS item_count 
                FROM {$this->table} AS U 
                LEFT JOIN user_item AS Inv ON Inv.user_id = U.id 
                LEFT JOIN items AS I ON I.id = Inv.item_id 
                WHERE U.username = :uname";
        $params = array(
            ":uname" => array("type" => PDO::PARAM_STR, "value" => $username)
        );
        $stmt = $this->connection->preparedQuery($sql, $params);

        // populate this object's values, and insert items into inventory array if necessary
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($this->id == null) {
                $this->id        = $row["id"];
                $this->username  = $row["username"];
                $this->money     = $row["money"];
                $this->inventory = [];
            }
            if ($row["item_id"] != null) {
                $this->inventory[] = array(
                    "item_id"    => $row["item_id"],
                    "item_name"  => $row["item_name"],
                    "item_count" => $row["item_count"]
                );
            }
        }
    }

    // adds $item_id item to this user object's inventory
    public function addInventory($item_id) {
        // check if index exists to determine whether or not to insert a new inventory value, or update existing one
        $index = array_search($item_id, array_column($this->inventory, "item_id"));

        $sql = "";
        if ($index === false) { // insert new value
            $sql = "INSERT INTO user_item (user_id, item_id, count) 
                    VALUES (:uid, :iid, 1)";
        }
        else { //update existing value
            $sql = "UPDATE user_item 
                    SET count = count + 1 
                    WHERE user_id = :uid AND item_id = :iid";
        }
        $params = array(
            ":uid" => array("type" => PDO::PARAM_INT, "value" => $this->id),
            ":iid" => array("type" => PDO::PARAM_INT, "value" => $item_id),
        );
        $stmt = $this->connection->preparedQuery($sql, $params);

        if ($index === false) { // we need to add the item to the model's inventory
            $sql = "SELECT name FROM items WHERE id = :iid";
            $params = array(
                ":iid" => array("type" => PDO::PARAM_INT, "value" => $item_id),
            );
            $stmt = $this->connection->preparedQuery($sql, $params);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->inventory[] = array(
                    "item_id"    => $item_id,
                    "item_name"  => $row["name"],
                    "item_count" => 1
                );
            }
        }
        else { // item already existed in inventory, just increment the count
            $this->inventory[$index]["item_count"]++;
        }

        return $stmt;
    }

    // removes $item_id item from this user object's inventory (if it exists)
    public function removeInventory($item_id) {
        // make sure the user has the item in their inventory before attempting removal
        $index = array_search($item_id, array_column($this->inventory, "item_id"));
        if ($index === false) {
            return false; // they don't have this item...
        }

        $sql = "";
        if ($this->inventory[$index]["item_count"] > 1) { // update the record as it will linger
            $sql = "UPDATE user_item 
                    SET count = count - 1 
                    WHERE user_id = :uid AND item_id = :iid";
        }
        else { // delete the record as they now have zero of the item
            $sql = "DELETE FROM user_item WHERE user_id = :uid AND item_id = :iid";
        }
        $params = array(
            ":uid" => array("type" => PDO::PARAM_INT, "value" => $this->id),
            ":iid" => array("type" => PDO::PARAM_INT, "value" => $this->inventory[$index]["item_id"])
        );
        $stmt = $this->connection->preparedQuery($sql, $params);
        
        // decrement the count in object's inventory, and remove it from the inventor array if it now has zero
        $this->inventory[$index]["item_count"]--;
        if ($this->inventory[$index]["item_count"] == 0) {
            unset($this->inventory[$index]);
        }
        
        return $stmt;
    }

    // add $amount money to this user
    public function addMoney($amount) {
        $sql = "UPDATE {$this->table} 
                SET money = money + :amount
                WHERE id = :uid";
        $params = array(
            ":amount" => array("type" => PDO::PARAM_INT, "value" => $amount),
            ":uid"    => array("type" => PDO::PARAM_INT, "value" => $this->id),
        );
        $stmt = $this->connection->preparedQuery($sql, $params);

        $this->money+= $amount;
    }

    // remove $amount money from this user (assumes controller checked that $amount is less than the sum user has)
    public function deductMoney($amount) {
        $sql = "UPDATE {$this->table} 
                SET money = money - :amount
                WHERE id = :uid";
        $params = array(
            ":amount" => array("type" => PDO::PARAM_INT, "value" => $amount),
            ":uid"    => array("type" => PDO::PARAM_INT, "value" => $this->id),
        );
        $stmt = $this->connection->preparedQuery($sql, $params);

        $this->money-= $amount;
    }
}
?>