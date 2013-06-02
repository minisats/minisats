<?php
include("common.php");

//Iterate through transactions, and process any unprocessed transactions.

$found_unprocessed_receive_tx=true;
$start = 0; $count = 50;
while ($found_unprocessed_receive_tx) {//there is an if-break bit at the end of the loop
  echo 1;//these are used for debugging when cron is being run manually
  $recent_transactions = $bitcoin->listtransactions("*",$count,$start);
  if (sizeof($recent_transactions)==0) break;
  $found_unprocessed_receive_tx = false;//until proven otherwise
  for ($i=0; $i<sizeof($recent_transactions); $i++) {
    echo 2;
    //Oddly, while listtransactions returns the $count most RECENT transactions, the list is then ordered OLDEST-first, not newest-first.
    //Doesn't matter too much here, but it's worth mentioning.
    $tx = $recent_transactions[$i];
    if ($tx['category']!="receive") {
      continue;//we only care about received funds.
    }
    $found_unprocessed_receive_tx=true;//we've found at least one unprocessed transaction
    if ($tx['confirmations'] < $deposit_min_conf) {
      continue;//not enough confirmations yet
    }
    $txid = $tx['txid'];
    
    echo 3;
    //Check to see if we've already processed this transaction
    $query = "SELECT * FROM `processed_txs` WHERE `txid` = '".$txid."'";
    $result = mysql_query($query);
    if (mysql_num_rows($result)>0) {
      continue;//skip to the next transaction, since we've already processed this one
      //note: we want to continue, not break, because it's possible (due to blockhain-length-related conflicts) that there are unprocessed transactions after an already-processed one.
    }
    
    echo 4;
    //process it
    $deposit_address = $tx['address'];
    echo "(".$deposit_address.")";
    $amount = $tx['amount'];
    $query = "SELECT id_address FROM `accounts` WHERE deposit_address = '".$deposit_address."'";
    $result = mysql_query($query)or die(mysql_error());
    if (!($assoc = mysql_fetch_assoc($result))) {
      echo "a";
      //TODO:send back and log error
    }
    else {
      echo "b";
      $id_address = $assoc['id_address'];
      if (!(add_funds($id_address,btc_to_nsats($amount)))) {
        echo "c";
        //TODO:send back and log error
      }
      else {
        echo "d";
        //TODO:log successful deposit
      }
    }
    
    $query = "INSERT INTO `processed_txs` VALUES ('".$txid."')";
    mysql_query($query)or die(mysql_error());
    echo 5;
  }
  $start += $count;
}
?>