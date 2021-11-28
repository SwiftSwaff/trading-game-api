<?php
include_once getenv("DOCUMENT_ROOT") . '/trading-game/db/db.php';
$db = new DB("sqlite::memory:");

$db->query(
    "CREATE TABLE items (
        id          INTEGER PRIMARY KEY ASC AUTOINCREMENT,
        name        VARCHAR (64),
        description VARCHAR (255) 
    );"
);

$db->query(
    "CREATE TABLE users (
        id       INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR (32),
        password VARCHAR (255),
        money    INTEGER DEFAULT (0) 
    );"
);

$db->query(
    "CREATE TABLE trade_offers (
        id      INTEGER PRIMARY KEY ASC AUTOINCREMENT,
        user_id INTEGER REFERENCES users (id) ON DELETE CASCADE
                                                ON UPDATE CASCADE,
        item_id INTEGER REFERENCES items (id) ON DELETE NO ACTION
                                                ON UPDATE CASCADE,
        price   INTEGER DEFAULT (0) 
    );"
);

$db->query(
    "CREATE TABLE user_item (
        id      INTEGER PRIMARY KEY ASC AUTOINCREMENT,
        user_id INTEGER REFERENCES users (id) ON DELETE CASCADE
                                                ON UPDATE CASCADE,
        item_id INTEGER REFERENCES items (id) ON DELETE NO ACTION
                                                ON UPDATE CASCADE,
        count   INTEGER DEFAULT (1) 
    );"
);

// populate users
$db->query("INSERT INTO users (username, password, money) VALUES ('jackblack', 'password1', 10000)");
$db->query("INSERT INTO users (username, password, money) VALUES ('jillvalentine', 'password2', 15000)");
$db->query("INSERT INTO users (username, password, money) VALUES ('ronaldmcdonald', 'password3', 20000)");

// populate items
$db->query("INSERT INTO items (name, description) VALUES ('bronze sword', 'A sword made of bronze.')");
$db->query("INSERT INTO items (name, description) VALUES ('iron sword', 'A sword made of iron.')");
$db->query("INSERT INTO items (name, description) VALUES ('steel sword', 'A sword made of steel.')");
$db->query("INSERT INTO items (name, description) VALUES ('mithril sword', 'A sword made of mithril.')");
$db->query("INSERT INTO items (name, description) VALUES ('adamant sword', 'A sword made of adamant.')");

// give user id 1 a bunch of steel swords for testing offers with
$db->query("INSERT INTO user_item (user_id, item_id, count) VALUES (1, 3, 10)");


/*
 * user controller test
 *  supports: 
 *      get all users
 *      get one user based on their id
 *      get one user based on their username
 */
include_once '../api/v1/UserController.php';
$userController = new UserController($db);

// show all users
echo "api/user/index\n";
$userController->route("index");
echo "\n\n";

// show user based on id
echo "api/user/show?user_id=1\n";
$_GET["user_id"] = 1;
$userController->route("show");
unset($_GET["user_id"]);
echo "\n\n";

// show user based on username
echo "api/user/show?username=jillvalentine\n";
$_GET["username"] = "jillvalentine";
$userController->route("show");
unset($_GET["username"]);
echo "\n\n";


/*
 * offer controller test
 *  supports: 
 *      get all offers
 *      get all offers from N random users ***REQUIRED***
 *      get one offer based on its id
 *      get all offers from a user based on their id
 *      get all offers from a user based on their username
 *      get all offers with a specific item based on item id
 *      get all offers that fall between a low and high price
 *      create an offer using a user id, item id, and price ***REQUIRED***
 *      remove an offer using an offer id
 *      buy an offer using offer id and a user id for the buyer ***REQUIRED***
 */
include_once '../api/v1/OfferController.php';
$offerController = new OfferController($db);

// create an offer using a user id, item id, and price
echo "api/offer/create\n";
$_POST["user_id"] = 1;
$_POST["item_id"] = 3;
$_POST["price"] = 600;
$offerController->route("create");
unset($_POST["user_id"]);
unset($_POST["item_id"]);
unset($_POST["price"]);
echo "\n\n";

// get all offers from N random users
echo "api/offer/random?num_users=2\n";
$_GET["num_users"] = 2;
$offerController->route("random");
unset($_GET["num_users"]);
echo "\n\n";

// buy an offer using offer id and a user id for the buyer
echo "api/offer/buy\n";
$_POST["offer_id"] = 1;
$_POST["buyer_id"] = 2;
$offerController->route("buy");
unset($_POST["offer_id"]);
unset($_POST["buyer_id"]);
echo "\n\n";

// quickly demonstrating that user id 2 has a steel sword in their inventory now
echo "api/user/show?user_id=2\n";
$_GET["user_id"] = 2;
$userController->route("show");
unset($_GET["user_id"]);
echo "\n\n";

// also demonstrating that user id 1 has one less steel sword, and some extra money
echo "api/user/show?user_id=2\n";
$_GET["user_id"] = 1;
$userController->route("show");
unset($_GET["user_id"]);
echo "\n\n";



?>