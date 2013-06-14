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
  $query = "UPDATE `accounts` SET balance = balance + ".$nsats." WHERE (id_address = '".$id_address."' AND balance <= 18446744073709551615 - ".$nsats.") LIMIT 1";//18446744073709551615 is the max value of the unsigned BIGINT type for sql
  mysql_query($query)or die("eadd_funds: ".mysql_error());
  if (mysql_affected_rows()==1) {
    return true;
  }
  
  $query = "SELECT balance FROM `accounts` WHERE (id_address = '".$id_address."')";
  if (!($result = mysql_query($query)or die(mysql_error()))) {
    die("eNo address found when trying to add funds");
  }
  
  $row = mysql_fetch_row($result);
  $balance = $row[0];
  $total = bcadd($nsats,$balance);
  $over = bcsub($total,18000000000000000000);//setting to 18BTC even; this way we guarantee at least a 0.44 return to the bitcoin address every time, and avoid the problem of many small received tips resulting in many small bitcoin transactions
  $query = "UPDATE `accounts` SET balance = 18000000000000000000 WHERE id_address = '".$id_address."' LIMIT 1";
  mysql_query($query)or die("e".mysql_error());
  
  if (mysql_affected_rows()==1) {
    $after_tx_fee = bcsub($over,$tx_fee_nsats);
    if ($after_tx_fee > 0) {
      $bitcoin->sendtoaddress($id_address,nsats_to_btc($after_tx_fee));
      return true;
    }
  }
  return false;
}
function deduct_funds($id_address,$nsats) {
  $query = "UPDATE `accounts` SET balance = balance - ".$nsats." WHERE (id_address = '".$id_address."' AND balance >= ".$nsats.") LIMIT 1";
  mysql_query($query)or die("ededuct_funds: ".mysql_error());
  return (mysql_affected_rows()==1);
}
?>