<?php
session_start();
require_once "../config/db.php";

if(!isset($_SESSION['role']) || $_SESSION['role']!=='provider'){
    exit(json_encode(['status'=>'error','msg'=>'Unauthorized']));
}

if(isset($_POST['booking_id'], $_POST['new_status'])){
    $stmt = $pdo->prepare("UPDATE bookings SET status=:status WHERE id=:id");
    if($stmt->execute(['status'=>$_POST['new_status'],'id'=>$_POST['booking_id']])){
        echo json_encode(['status'=>'success']);
    } else {
        echo json_encode(['status'=>'error']);
    }
}
?>
