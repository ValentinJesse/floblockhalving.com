<?php
require_once 'easyflorincoin.php';

// $florincoin = new Florincoin('TestNumberOne01','PasswordTest0101','localhost','6969');

// try {
// 	$info = $florincoin->getinfo();
// } catch (Exception $e) {
// 	echo nl2br($e->getMessage()).'<br />'."\n"; 
// 	die();
// }

// florincoin settings
$blockStartingReward = 100;
$blockHalvingSubsidy = 800000;
$blockTargetSpacing = 0.666667;
$maxCoins = 160000000;

$btcdata = file_get_contents('https://api.coinmarketcap.com/v1/ticker/bitcoin/');
$btcusdprice = json_decode($btcdata, true);
$btcprice = (float)$btcusdprice["0"]["price_usd"];

$flodata = file_get_contents('https://api.coinmarketcap.com/v1/ticker/florincoin/');
$floprice = json_decode($flodata, true);
$flprice = (float)$floprice["0"]["price_usd"];
$flrank = (float)$floprice["0"]["rank"];
$flbtcprice = (float)$floprice["0"]["price_btc"];

// $mxndata = file_get_contents('http://api.fixer.io/latest?base=USD');
// $mprice = json_decode($mxndata, true);
// $ratemxprice = (float)$mprice["rates"]["MXN"];
// $rateeuprice = (float)$mprice["rates"]["EUR"];
// $ratecnprice = (float)$mprice["rates"]["CNY"];
// $rateruprice = (float)$mprice["rates"]["RUB"];
// $ratejpprice = (float)$mprice["rates"]["JPY"];
// $ratebrprice = (float)$mprice["rates"]["BRL"];


$difficulty = json_decode(file_get_contents("http://florincoin.info/api/getdifficulty"), true);
$blocks = json_decode(file_get_contents("http://florincoin.info/api/getblockcount"), true);
$coins = json_decode(file_get_contents("http://florincoin.info/ext/getmoneysupply"), true);
$blocksRemaining = CalculateRemainingBlocks($blocks, $blockHalvingSubsidy);

$avgBlockTime = GetFileContents("timebetweenblocks.txt");
if (empty($avgBlockTime)) {
	$avgBlockTime = $blockTargetSpacing;
}


$blocksPerDay = (60 / $avgBlockTime) * 24;
$blockHalvingEstimation = $blocksRemaining / $blocksPerDay * 24 * 60 * 60;
$blockString = '+' . (int)$blockHalvingEstimation . ' second';
$blockReward = CalculateRewardPerBlock($blockStartingReward, $blocks, $blockHalvingSubsidy);
$coinsRemaining = $blocksRemaining * $blockReward;
$nextHalvingHeight = $blocks + $blocksRemaining;
$inflationRate = CalculateInflationRate($coins, $blockReward, $blocksPerDay);
$inflationRateNextHalving = CalculateInflationRate(CalculateTotalCoins($blockStartingReward, $nextHalvingHeight, $blockHalvingSubsidy), 
	CalculateRewardPerBlock($blockStartingReward, $nextHalvingHeight, $blockHalvingSubsidy), $blocksPerDay);
$price = $flprice;

$mxnprice = ($price * $ratemxprice);
$eurprice = ($price * $rateeuprice);
$cnyprice = ($price * $ratecnprice);
$rubprice = ($price * $rateruprice);
$jpyprice = ($price * $ratejpprice);
$brlprice = ($price * $ratebrprice);

function GetHalvings($blocks, $subsidy) {
	return (int)($blocks / $subsidy);
}

function CalculateRemainingBlocks($blocks, $subsidy) {
	$halvings = GetHalvings($blocks, $subsidy);
	if ($halvings == 0) {
		return $subsidy - $blocks;
	} else {
		$halvings += 1;
		return $halvings * $subsidy - $blocks;
	}
}

function CalculateRewardPerBlock($blockReward, $blocks, $subsidy) {
	$halvings = GetHalvings($blocks, $subsidy);
	$blockReward >>= $halvings;
	return $blockReward;
}

function CalculateTotalCoins($blockReward, $blocks, $subsidy) {
	$halvings = GetHalvings($blocks, $subsidy);
	if ($halvings == 0) {
		return $blocks * $blockReward;
	} else {
		$coins = 0;
		for ($i = 0; $i < $halvings; $i++) {
			$coins += $blockReward * $subsidy;
			$blocks -= $subsidy;
			$blockReward = $blockReward / 2; 
		}
		$coins += $blockReward * $blocks;
		return $coins;
	}
}

function CalculateInflationRate($totalCoins, $blockReward, $blocksPerDay) {
	return pow((($totalCoins + $blockReward) / $totalCoins ), (365 * $blocksPerDay)) - 1;
}

function GetFileContents($filename) {
	$file = fopen($filename, "r") or die("Unable to open file!");
	$result = fread($file,filesize($filename));
	fclose($file);
	return $result;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Florincoin Block Reward Halving Countdown website">
	<meta name="author" content="">
	<meta http-equiv="refresh" content="300">
	<link rel="icon" type="image/png" href="favicon-32x32.png" sizes="32x32" />
	<link rel="icon" type="image/png" href="favicon-16x16.png" sizes="16x16" />
	<title>Florincoin $ <?=number_format($price, 4);?> BTC $ <?=number_format($btcprice, 4);?> - Florincoin Block Reward Halving Countdown</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
	<link rel="stylesheet" href="css/flipclock.css">
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="js/flipclock.js"></script>	
</head>
<body>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-105828584-1', 'auto');
  ga('send', 'pageview');

</script>
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.8";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
	<div class="container">
		<div class="page-header" style="text-align:center">
			<h3>Florincoin Block Reward Halving Countdown</h3>
		</div>
		<div class="flip-counter clock" style="display: flex; align-items: center; justify-content: center;"></div>
		<script type="text/javascript">
			var clock;

			$(document).ready(function() {
				clock = $('.clock').FlipClock(<?=$blockHalvingEstimation?>, {
					clockFace: 'DailyCounter',
					countdown: true
				});
			});
		</script>
		<div style="text-align:center">
			Reward-Drop ETA date: <strong><?=date('d M Y H:i:s', strtotime($blockString, time()))?></strong><br/><br/>
			<p>The Next Florincoin block mining reward halves on block number 2,400,000.<br/> Florincoin's block mining reward halves every 800,000 blocks, therefore the coin reward will decrease from 25 to 12.5 coins.  
			<br/><br/>
		</div>
		<div align="right"><div class="fb-like" data-href="https://www.facebook.com/FlorinCoin/" data-layout="standard" data-action="like" data-size="small" data-show-faces="false" data-share="true"></div></div> <br/><br/>


			<!-- CSS -->
			<link type="text/css" rel="stylesheet" href="css/ycp.css" />

			<!-- Selector by Id -->
			<div id="unix" data-ycp_title="FLO & Apps built on top of its blockchain" data-ycp_channel="PLWhzez8edjhHeJxh2HQDef8VzrlkSOjJY"></div> <!-- By ChannelId -->

			<!-- jQuery -->
			<!-- <script src="//code.jquery.com/jquery-2.1.1.min.js"></script> -->
			<script src="js/ycp.js"></script>
			<script>
			$(function() {
			        
			    $("#unix").ycp({
			        apikey : 'xxx',
			        playlist : 4,
			        autoplay : false,
			        related : true
			    });
						
			    $(".demo").ycp({
			        apikey : 'xxx'
			    });
			            
			});
			</script>
			<br/><br/>


		<table class="table table-striped">
		    <tr><td><b>Coin Market cap (worldwide rank):</b></td><td align = "right"><a href="http://coinmarketcap.com/currencies/florincoin/" target="_blank"><?=number_format($flrank)?></a></td></tr>
			<!-- <tr><td><b>Actual Mining Percentage:</b></td><td align = "right"><?=number_format($flstakereward, 2) . ' % / Year';?></td></tr> -->
			<tr><td><b>Total Florincoin coins in circulation:</b></td><td align = "right"><?=number_format($coins)?></td></tr>
			<tr><td><b>Total Florincoin coins to ever be produced:</b></td><td align = "right"><?=number_format($maxCoins)?></td></tr>
			<tr><td><b>Percentage of total Florincoin coins mined:</b></td><td align = "right"><?=number_format($coins / $maxCoins * 100 / 1, 4)?>%</td></tr>
			<tr><td><b>Total Florincoin coins left to mine:</b></td><td align = "right"><?=number_format($maxCoins - $coins)?></td></tr>
			<tr><td><b>Total Florincoin coins left to mine until next block halving:</b></td><td align = "right"><?= number_format($coinsRemaining);?></td></tr>
			<tr><td><b>Approximate Florincoin coins generated per day:</b></td><td align = "right"><?=number_format($blocksPerDay * $blockReward);?></td></tr>
			<tr><td><b>Bitcoin price (USD):</b></td><td align = "right">$ <?=number_format($btcprice, 4);?> <img src="../images/flag-usa.png"></td></tr>
			<tr><td><b>Florincoin price (BTC):</b></td><td align = "right">฿ <?=number_format($flbtcprice, 8);?> <img src="../images/bitcoin.png"></td></tr>
			<tr><td><b>Florincoin price (USD):</b></td><td align = "right">$ <?=number_format($price, 4);?> <img src="../images/flag-usa.png"></td></tr>
			<!-- <tr><td><b>Florincoin price (EUR):</b></td><td align = "right">€ <?=number_format($eurprice, 4);?> <img src="../images/flag-european-union.png"></td></tr>
			<tr><td><b>Florincoin price (CNY):</b></td><td align = "right">¥ <?=number_format($cnyprice, 4);?> <img src="../images/flag-china.png"></td></tr>
			<tr><td><b>Florincoin price (MXN):</b></td><td align = "right">$ <?=number_format($mxnprice, 4);?> <img src="../images/flag-mexico.png"></td></tr>
			<tr><td><b>Florincoin price (RUB):</b></td><td align = "right">&#x20bd; <?=number_format($rubprice, 4);?> <img src="../images/flag-russia.png"></td></tr>
			<tr><td><b>Florincoin price (JPY):</b></td><td align = "right">¥ <?=number_format($jpyprice, 4);?> <img src="../images/flag-japan.png"></td></tr>
			<tr><td><b>Florincoin price (BRL):</b></td><td align = "right">R$ <?=number_format($brlprice, 4);?> <img src="../images/flag-brazil.png"></td></tr> -->
			<tr><td><b>Market capitalization (USD):</b></td><td align = "right">$<?=number_format($coins * $price, 2);?></td></tr>
			<tr><td><b>Florincoin inflation rate per annum:</b></td><td align = "right"><?=number_format($inflationRate * 100 / 1, 2);?>%</td></tr>
			<tr><td><b>Florincoin inflation rate per annum at next block halving event:</b></td><td align = "right"><?=number_format($inflationRateNextHalving * 100 / 1, 2);?>%</td></tr>
			<tr><td><b>Florincoin inflation per day (USD):</b></td><td align = "right">$<?=number_format($blocksPerDay * $blockReward * $price);?></td></tr>
			<tr><td><b>Florincoin inflation until the next block halving event based on current price (USD):</b></td><td align = "right">$<?=number_format($coinsRemaining * $price);?></td></tr>
			<tr><td><b>Total blocks:</b></td><td align = "right"><a href="https://florincoin.info//" target="_blank"><?=number_format($blocks);?></a></td></tr>
			<tr><td><b>Blocks until mining reward is halved:</b></td><td align = "right"><?=number_format($blocksRemaining);?></td></tr>
			<tr><td><b>Approximate block generation time:</b></td><td align = "right">40 seconds</td></tr>
			<tr><td><b>Approximate blocks generated per day:</b></td><td align = "right"><?=$blocksPerDay;?></td></tr>
			<tr><td><b>Difficulty:</b></td><td align = "right"><?=number_format($difficulty);?></td></tr>
		</table>
		<div style="text-align:center">
			<img src="images/florincoin_logo.png" width="100px"; height="100px">
			<br/>
			<h2><a href="http://www.florincoin.org" target="_blank">Florincoin Website</a></h2>
		</div>
	</div>
</body>
</html>