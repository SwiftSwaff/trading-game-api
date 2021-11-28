<?php
/*
 * Controller interface for use on other controllers
 *      route ($action): given an action keyword, return appropriate method
 */
include_once getenv("DOCUMENT_ROOT") . '/trading-game/db/db.php';
interface Controller {
    public function route($action); // delegate the requested $action 
}
?>