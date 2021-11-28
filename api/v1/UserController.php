<?php
include_once getenv("DOCUMENT_ROOT") . "/trading-game/api/v1/Controller.php";
class UserController implements Controller {
    private $db;
    
    public function __construct($override=null) {
        // use the test database if passed, otherwise use default persistant sqlite one
        $this->db = ($override===null) 
            ? new DB() 
            : $override;
    }

    // delegate the requested $action 
    public function route($action) {
        switch ($action) {
            case "index":
                $this->index();
                break;
            case "show":
                $this->show();
                break;
            default:
                echo json_encode(array("message" => "Page not Found"));
                break;
        }
    }
    
    // handles displaying of all users
    private function index() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        include_once getenv("DOCUMENT_ROOT") . '/trading-game/api/models/User.php';
        $user = new User($this->db);
        
        $usersArr = array();
        $result = $user->getAll();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            extract($row); // helper that extracts assoc values from row into variables of same name
            
            if (!isset($usersArr[$id])) { // unique user we haven't added yet, skip if they exist already
                $user_obj = array(
                    "id"        => $id,
                    "username"  => $username,
                    "money"     => $money,
                    "inventory" => []
                );
                $usersArr[$id] = $user_obj;
            }

            // extra rows in search result reflect inventory belonging to user, handle them here
            if ($item_id != null) { // add inventory data to the object's inventory array if it exists
                $usersArr[$id]["inventory"][] = array(
                    "item_id"    => $item_id,
                    "item_name"  => $item_name,
                    "item_count" => $item_count
                );
            }
        }

        if (!empty($usersArr)) {
            // clean up json return value by stripping superfluous keys
            $users = array("users" => array_values($usersArr));
            echo json_encode($users);
        }
        else { // no users in database
            echo json_encode(array("message" => "No Users Found"));
        }
    }

    // handles showing specific users based on either their id or their username
    private function show() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        include_once getenv("DOCUMENT_ROOT") . '/trading-game/api/models/User.php';
        $user = new User($this->db);

        if (isset($_GET["user_id"])) { // a user_id was passed in
            $user->getByID($_GET["user_id"]);
        }
        else if (isset($_GET["username"])) { // a username was passed in
            $user->getByName($_GET["username"]);
        }
        else { // missing either user_id or username parameter
            echo json_encode(array("message" => "Error: Missing parameter for query."));
            return;
        }

        if ($user->id != null) { // a user was found!
            echo json_encode(get_object_vars($user));
        }
        else { // no user was found...
            echo json_encode(array("message" => "No User Found"));
        }
    }
}
?>