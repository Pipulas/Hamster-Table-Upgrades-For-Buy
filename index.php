<?php
// ID пользователя в Telegram :
$tg_id = '';

// Ключ авторизации в Hamster Kombat :
$auth_key_hk = '';

// ==============================================================================================================
class API_HMSTR
{
	public $api_url = 'https://api.hamsterkombatgame.io/clicker/';
	public $auth_key = '';
	public $TGid = '';
	
	public function cURL($method, $POSTFIELDS = false)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->api_url . $method);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		if($POSTFIELDS) curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->auth_key, 'Content-Type: application/json'));
		$result = curl_exec($ch); if (curl_errno($ch)) echo 'Error:' . curl_error($ch); curl_close($ch);
		return json_decode($result, true);
	}
	
	public function upgrades_for_buy()
	{
		$result = $this->cURL('upgrades-for-buy');
		return $result['upgradesForBuy'];
	}
	
	public function buy_upgrade($upgradeId)
	{
		$timestamp = time() . rand(100,999);
		$result = $this->cURL('buy-upgrade', '{"upgradeId":"'.$upgradeId.'","timestamp":'.$timestamp.'}');
		return $result;
	}
	
	public function sync()
	{
		return $this->cURL('sync');
	}
}
// ==============================================================================================================

$api = new API_HMSTR();
$api->auth_key = $auth_key_hk;
$api->TGid = $tg_id;

if($_GET['request'] == 'buy') 
{
	$api->buy_upgrade($_GET['id']);
	header('Location:' . $_SERVER['HTTP_REFERER']);
}

$upgradesForBuy = $api->upgrades_for_buy();

foreach ($upgradesForBuy as $k => $v)
{
if ($v['profitPerHourDelta'] == 0) unset($upgradesForBuy[$k]); $upgradesForBuy[$k]['priceForOne'] = floor($v['price'] / (++$v['profitPerHourDelta'] - 2));
if ($upgradesForBuy[$k]['isAvailable'] != 1) unset($upgradesForBuy[$k]);
if ($upgradesForBuy[$k]['isExpired'] == 1) unset($upgradesForBuy[$k]);
}

usort($upgradesForBuy, function($a,$b) { return ($a['priceForOne'] - $b['priceForOne']); });

$sync = $api->sync();
$balanceCoins = $sync['clickerUser']['balanceCoins'];
$earnPassivePerHour = $sync['clickerUser']['earnPassivePerHour'];
$earnPassivePerSec = $sync['clickerUser']['earnPassivePerSec'];
?>
<!DOCTYPE html>
<html>
   <head>
      <title>Hamster Upgrades For Buy</title>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/semantic-ui@2.5.0/dist/semantic.min.css">
      <link id="favicon" rel="shortcut icon" href="https://hamsterkombatgame.io/favicon.ico">
      <script src="https://code.jquery.com/jquery-3.5.0.min.js" integrity="sha256-xNzN2a4ltkB44Mc/Jz3pT4iU1cmeR0FkXs4pru/JxaQ=" crossorigin="anonymous"></script>
      <script src="https://cdn.jsdelivr.net/npm/semantic-ui@2.5.0/dist/semantic.min.js"></script>
      <style>@import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap');</style>
      <style>
         body {
         font-family: Roboto, Verdana; 
         }
         .ui.container.main {
         width: 95%;
         padding-top: 20px;
         padding-bottom: 20px;
         }
         .ui.table {
         line-height: 1em;
         }
         .ui.menu {
         background: #f9fafb;
         }
         .green {
         color: #398758;
         }
         .red {
         color: #ce3434;
         }
         .yellow {
         color: #bb9862;;
         }
         .orange {
         color: #db8705;;
         }
         #index table {
         border-collapse: separate;
         border-spacing: 0px 10px;
         }
         #index a {
         color: #278bbe;
         text-decoration: none;
         }
         #index a:hover {
         color: #278bbe;
         text-decoration: underline;
         }
      </style>
   </head>
   <body>
      <script type = "text/javascript">
         function update_balance()
         {
            var $bCoins = $('#bCoinsHide').text();
            var $res = parseInt($bCoins) + <?php echo round($earnPassivePerSec); ?>;
            $('#bCoinsHide').text($res);
            $('#bCoins').text($res.toLocaleString());
         }
         
         const intervalId = setInterval(update_balance, 1000);
      </script>
      <div class="ui container main">
         <div class="ui secondary segment">
            <p>Ваш баланс в игре: <span class="red" id="bCoins"><?php echo number_format($balanceCoins, 0, ' ', ' '); ?></span><span class="red">$</span> | Прибыль в час: <span class="red"><?php echo number_format($earnPassivePerHour, 0, ' ', ' '); ?>$</span> | Прибыль в секунду: <span class="red"><?php echo number_format($earnPassivePerSec, 0, ' ', ' '); ?>$</span> <span id="bCoinsHide" style="display:none;"><?php echo floor($balanceCoins); ?></span></p>
         </div>
         <table class="ui celled striped selectable compact table">
            <thead>
               <tr class="center aligned">
                  <th>#</th>
                  <th>Название</th>
                  <th>Купить</th>
                  <th>Цена за единицу прибыли</th>
                  <th>Цена</th>
                  <th>Прибыль</th>
                  <th>Уровень Up</th>
                  <th>Секция</th>
               </tr>
            </thead>
            <?php
               $count = 1;
               $priceAll = 0;
               $profit = 0;
               
               foreach ($upgradesForBuy as $k => $v)
               {
               
					$html = '<tr class="center aligned">';
					$html .= '<td>'.$count.'</td>';
               
               	if ($balanceCoins >= $v['price']) 
               	{
               		
               		if ($v['cooldownSeconds'] == 0)
               		{
						$html .= '<td><span class="green">'.$v['name'].'</span></td>';
               			$html .= '<td><a href="?request=buy&id='.$v['id'].'"><i class="green dollar sign icon"></i></a></td>'; 
               			
               		} else {
               
						$html .= '<td><span class="red">'.$v['name'].'</span></td>';
               			$html .= '<td><span class="red"><i class="red clock outline icon"></i> '.date("H:i:s", mktime(null, null, $v['cooldownSeconds'])).'</span></td>'; 
               		}
               
               	} else {
               
               		if ($v['cooldownSeconds'] == 0) 
               		{
               			$html .= '<td><span class="red">'.$v['name'].'</span></td>';
               			$html .= '<td><span class="red">---</span></td>';
               			
               		} else {
               			
               			$html .= '<td><span class="red">'.$v['name'].'</span></td>';
               			$html .= '<td><span class="red"><i class="red clock outline icon"></i> '.date("H:i:s", mktime(null, null, $v['cooldownSeconds'])).'</span></td>'; 
               		}
               	}
               	
               	$html .= '<td><span class="orange">'.number_format($v['priceForOne'], 0, ' ', ' ').'</span></td>';
               	$html .= '<td><span class="red">'.number_format($v['price'], 0, ' ', ' ').'</span></td>';
               	$html .= '<td><span class="green">+ '.number_format($v['profitPerHourDelta'], 0, ' ', ' ').'</span></td>';
               	$html .= '<td>'.$v['level'].'</td>';
               	$html .= '<td>'.$v['section'].'</td>';
               	$html .= '</tr>';
               	
               	$count++; $priceAll = $priceAll + $v['price']; $profit = $profit + $v['profitPerHourDelta'];
               
				echo $html;
               
               }
               
               $html .= '<tr class="center aligned"><td>#</td><td>Итого:</td><td>---</td><td><span class="orange">---</span></td><td><span class="red">'.number_format($priceAll, 0, ' ', ' ').'</span></td><td><span class="green">+ '.number_format($profit, 0, ' ', ' ').'</span></td><td>---</td><td>---</td></tr>';
               
               echo $html;
               ?>
         </table>
      </div>
   </body>
</html>