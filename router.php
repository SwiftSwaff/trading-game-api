<?php
/*
 * Entry point of api, redirected to by .htaccess file
 */
$urn = explode("/", $_GET["route"]);
$controller = $urn[0];
$action     = $urn[1] ?? "";

switch($controller) {
    case 'user':
        include_once 'api/v1/UserController.php';
        $controller = new UserController();
        $controller->route($action);
        break;
    case 'offer':
        include_once 'api/v1/OfferController.php';
        $controller = new OfferController();
        $controller->route($action);
        break;
    default:
        echo json_encode(array("message" => "Page not Found"));
        break;
}
?>