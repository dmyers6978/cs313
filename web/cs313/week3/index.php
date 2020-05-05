<?php
if(!$_SESSION){
    session_start();
}
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
if(!$action){
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
}

switch($action){

    case 'viewCart':
        include './views/cart.php';
        exit;
    break;

    case 'checkout':
        include './views/checkout.php';
        exit;
    break;

    case 'confirmation':
        include './views/confirmation.php';
        exit;
    break;

    case 'addToCart':
    $itemTitle = filter_input(INPUT_GET, 'itemTitle', FILTER_SANITIZE_STRING);
    $itemPrice = filter_input(INPUT_GET, 'itemPrice', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    array_push($_SESSION['cart'], array('itemTitle'=>$itemTitle,'itemPrice'=>$itemPrice));
    header('location: ./index.php');
    exit;
    break;

    case 'remItem':
        $itemId = filter_input(INPUT_GET, 'itemId', FILTER_VALIDATE_INT);
        unset($_SESSION['cart'][intval($itemId)]);
        header('location: ./index.php?action=viewCart');
        exit;
    default:
    include './views/viewItems.php';
    exit;
}