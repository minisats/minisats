<?php
include("common.php");
if (!isset($_REQUEST['id_address'])) {
  die("eid_address not defined");
}
$id_address = $_REQUEST['id_address'];

if (!validate_address($bitcoin,$id_address)) {
  die("eAddress formatted incorrectly");
}

$query = "SELECT deposit_address,balance FROM `accounts` WHERE id_address = '".$id_address."'";
$result = mysql_query($query)or die(mysql_error());
if (!($assoc = mysql_fetch_assoc($result))) {//If there was no entry for this user...
  //generate it
  $deposit_address = add_account($bitcoin,$id_address);
  $balance = 0;
}
else {//otherwise
  //get it
  $deposit_address = $assoc['deposit_address'];
  $balance = $assoc['balance'];
}
echo "s".$deposit_address.",".$balance;
?>