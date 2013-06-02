<?php
function satoshis_to_nsats($satoshi) {
  return $satoshi*1000000000;
}
function nsats_to_satoshis($nsat) {
  return $nsat/1000000000;
}
function nsats_floored_to_satoshi($nsats) {
  return bcsub($nsats,bcmod($nsats,"1000000000"));
}
function btc_to_nsats($btc) {
  return $btc*100000000000000000;
}
function nsats_to_btc($nsats) {
  return $nsats/100000000000000000;
}
function add_account($bitcoin,$id_address) {
  $deposit_address = $bitcoin->getnewaddress($id_address);//Not sure if this is somehow a bad idea, but i'm assigning the id_address as the "account" that bitcoind associates the deposit_address with. In the current version, this is not used, but maybe this association will be useful later on somehow?
  //insert new pair into accounts
  $query = "INSERT INTO `accounts` (id_address,deposit_address) VALUES ('".$id_address."', '".$deposit_address."')";
  mysql_query($query)or die("e".mysql_error());
  return $deposit_address;
}

function validate_address($bitcoin,$address) {
  $validate_info = $bitcoin->validateaddress($address);
  return ($validate_info['isvalid']);
}

function verify_message($bitcoin,$address,$signed,$message) {
  try {
    return($bitcoin->verifymessage($address,$signed,$message)==1);
  }
  catch (Exception $e) {
    return false;
  }
}

function add_funds($id_address,$nsats) {
  $query = "UPDATE `accounts` SET balance = balance + ".$nsats." WHERE (id_address = '".$id_address."' AND balance <= 18446744073709551615 - ".$nsats.") LIMIT 1";//18446744073709551615 is the max value of unsigned BIGINT type in the sql table
  mysql_query($query)or die("eadd_funds: ".mysql_error());
  return (mysql_affected_rows()==1);
}
function deduct_funds($id_address,$nsats) {
  $query = "UPDATE `accounts` SET balance = balance - ".$nsats." WHERE (id_address = '".$id_address."' AND balance >= ".$nsats.") LIMIT 1";
  mysql_query($query)or die("ededuct_funds: ".mysql_error());
  return (mysql_affected_rows()==1);
}
?>