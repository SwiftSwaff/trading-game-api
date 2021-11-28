<?php
include_once getenv("DOCUMENT_ROOT") . "/trading-game/api/v1/Controller.php";
class OfferController implements Controller {
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
            case "random": 
                $this->indexRandom();
                break;
            case "show":
                $this->show();
                break;
            case "create":
                $this->create();
                break;
            case "remove":
                $this->remove();
                break;
            case "buy": 
                $this->buy();
                break;
            default:
                echo json_encode(array("message" => "Page not Found"));
                break;
        }
    }

    // handles displaying of all offers
    private function index() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        include_once getenv("DOCUMENT_ROOT") . '/trading-game/api/models/Offer.php';
        $offer = new Offer($this->db);
        
        $offersArr = array();
        $result = $offer->getAll();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            extract($row); // helper that extracts assoc values from row into variables of same name
            
            $offer_obj = array(
                "id"      => $id,
                "user_id" => $user_id,
                "item_id" => $item_id,
                "price"   => $price
            );
    
            $offersArr["offers"][] = $offer_obj;
        }

        if (!empty($offersArr)) {
            echo json_encode($offersArr);
        }
        else {
            echo json_encode(array("message" => "No Offers Found"));
        }
    }

    // variant of index that instead returns a random set of users
    private function indexRandom() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        include_once getenv("DOCUMENT_ROOT") . '/trading-game/api/models/Offer.php';
        $offer = new Offer($this->db);

        include_once getenv("DOCUMENT_ROOT") . '/trading-game/api/models/User.php';
        $user = new User($this->db);

        if (!isset($_GET["num_users"])) {
            echo json_encode(array("message" => "Please set a number of users to randomly fetch."));
            return;
        }

        // step 1: fetch the random user's ids and store them in the offers array as keys
        $offers = array();
        $result = $user->getRandomUserIDs($_GET["num_users"]);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $offers[$row["id"]] = [];
        }
        unset($result); // reset our result variable

        // step 2: fetch all the offers belonging to our random users
        $result = $offer->getByUserIDs(array_keys($offers));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $offers[$row["user_id"]] = array(
                "user_id" => $row["user_id"],
                "item_id" => $row["item_id"],
                "price"   => $row["price"]
            );
        }
        
        if (!empty($offers)) {
            echo json_encode($offers);
        }
        else {
            echo json_encode(array("message" => "No Users Found"));
        }
    }

    // handles showing specific offers based on what is found in the get request
    private function show() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        include_once getenv("DOCUMENT_ROOT") . '/trading-game/api/models/Offer.php';
        $offer = new Offer($this->db);

        $result = null;
        if (isset($_GET["offer_id"])) { // get the offer object using offer_id
            $offer->getByID($_GET["offer_id"]);
        }
        else {
            if (isset($_GET["item_id"])) { // get all offers that have item_id
                $result = $offer->getByitem_id($_GET["item_id"]);
            }
            else if (isset($_GET["user_id"])) { // get all offers from user_id
                $result = $offer->getByUserID($_GET["user_id"]);
            }
            else if (isset($_GET["username"])) { // get all offers from username
                $result = $offer->getByUsername($_GET["username"]);
            }
            else if (isset($_GET["lowprice"]) && isset($_GET["highprice"])) { // get all offers that fall within price range
                $result = $offer->getByPriceRange($_GET["lowprice"], $_GET["highprice"]);
            }
            else { // no valid parameter was given...
                echo json_encode(array("message" => "Error: Missing parameter for query."));
                return;
            }

            $offersArr = array();
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                extract($row); // helper that extracts assoc values from row into variables of same name
                
                $offer_obj = array(
                    "id"      => $id,
                    "user_id" => $user_id,
                    "item_id" => $item_id,
                    "price"   => $price
                );
        
                $offersArr["offers"][] = $offer_obj;
            }
        }

        if ($offer->id != null) { // return the offer object
            echo json_encode(get_object_vars($offer));
        }
        else {
            if (!empty($offersArr)) { // return the queried objects
                echo json_encode($offersArr);
            }
            else { // we found nothing
                echo json_encode(array("message" => "No Offers Found"));
            }
        }
    }

    // creates a new offer 
    private function create() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, X-Requested-With');

        include_once getenv("DOCUMENT_ROOT") . '/trading-game/api/models/Offer.php';
        $offer = new Offer($this->db);

        $data = json_decode(file_get_contents("php://input"));
        if ($data === null && empty($_POST)) { // check for both raw input and post, test cases use post
            echo json_encode(array("message" => "Incorrect Request Type"));
            return;
        }
        $offer->user_id = $data->user_id ?? $_POST["user_id"];
        $offer->item_id = $data->item_id ?? $_POST["item_id"];
        $offer->price   = $data->price ?? $_POST["price"];

        include_once getenv("DOCUMENT_ROOT") . '/trading-game/api/models/User.php';
        $user = new User($this->db);
        $user->getByID($offer->user_id);

        // validate the transaction before committing it
        if ($this->db->beginTransaction()) {
            try {
                // remove the item from the user's inventory before creating the offer
                if (!$user->removeInventory($offer->item_id)) { // couldn't remove item from inventory, means the user didn't have it in the first place
                    echo json_encode(array("message" => "Error: You can't sell an item you don't have!"));
                    return;
                }
                $offer->insert();
                $this->db->commit();
                echo json_encode(array("message" => "Offer Created!"));
            }
            catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                echo json_encode(array("message" => "Error: " . $e->getMessage()));
            }
        }
    }

    // removes an existing offer
    private function remove() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        include_once getenv("DOCUMENT_ROOT") . '/trading-game/api/models/Offer.php';
        $offer = new Offer($this->db);

        if (isset($_GET["offer_id"])) { // get the offer object from the passed offer_id
            $offer->getByID($_GET["offer_id"]);
        }
        else { // no offer_id in the get request
            echo json_encode(array("message" => "Offer ID Missing"));
            return;
        }

        // validate the transaction before committing it
        if ($this->db->beginTransaction()) {
            try {
                if ($offer->id != null) { // offer exists, so it can be removed
                    include_once getenv("DOCUMENT_ROOT") . '/trading-game/api/models/User.php';
                    $user = new User($this->db);
                    $user->getByID($offer->user_id);
                    
                    if ($offer->delete()) { // offer was successfully removed
                        $user->addInventory($offer->item_id); // give user back the item they put up
                        $this->db->commit();
                        echo json_encode(array("message" => "Offer Removed!"));
                    }
                    else { // something went wrong, take 
                        echo json_encode(array("message" => "Error: Something went wrong, please try again later."));
                    }
                }
                else {
                    echo json_encode(array("message" => "Error: Offer Does Not Exist..."));
                }
            }
            catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                } 
                echo json_encode(array("message" => "Error: " . $e->getMessage()));
            }
        }
    }

    // facilitates purchase of an item from an offer
    private function buy() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, X-Requested-With');

        include_once getenv("DOCUMENT_ROOT") . '/trading-game/api/models/Offer.php';
        $offer = new Offer($this->db);

        include_once getenv("DOCUMENT_ROOT") . '/trading-game/api/models/User.php';
        $buyer = new User($this->db);
        $seller = new User($this->db);

        $data = json_decode(file_get_contents("php://input"));
        if ($data === null && empty($_POST)) { // check for both raw input and post, test cases use post
            echo json_encode(array("message" => "Incorrect Method"));
            return;
        }
        $offer->getByID($data->offer_id ?? $_POST["offer_id"]);
        $buyer->getByID($data->buyer_id ?? $_POST["buyer_id"]);
        $seller->getByID($offer->user_id);

        // validate the transaction before committing it
        if ($offer->id === null) { // offer didn't exist in the offers table
            echo json_encode(array("message" => "Offer Does Not Exist..."));
            return;
        }
        else if ($buyer->id === null) { // the buyer doesn't exist in the users table
            echo json_encode(array("message" => "Buyer Does Not Exist..."));
            return;
        }
        else if ($buyer->id === $seller->id) { // the buyer is the seller...haha
            echo json_encode(array("message" => "Buyer is the Seller..."));
            return;
        }
        else if ($buyer->money < $offer->price) { // the buyer doesn't have enough money for the purchase
            echo json_encode(array("message" => "Buyer Does Not Have Enough Money..."));
            return;
        }
        
        // try to perform the set of operations necessary to faciliate a transaction
        if ($this->db->beginTransaction()) { //start transaction in the event that something goes wrong
            try {
                $offer->delete();
                $buyer->deductMoney($offer->price);
                $buyer->addInventory($offer->item_id);
                $seller->addMoney($offer->price);
                $this->db->commit();
                echo json_encode(array("message" => "Transaction Complete!"));
            }
            catch (PDOException $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                echo json_encode(array("message" => "Error: " . $e->getMessage()));
            }
        }
    }
}
?>