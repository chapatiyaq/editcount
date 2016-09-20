<?php
require_once('connection.php');
$cookieFile = 'cookies.tmp';

//$merged_users = isset($_GET['merge']) ? $_GET['merge'] : true;
$merged_users = false;
$show_flags = isset($_GET['flags']) ? $_GET['flags'] : true;
$flags = array();
if ($show_flags) {
	$connection = Connection::getConnection();
	$stmt = $connection->prepare(
		'SELECT id, user_name, flag FROM lp_users ORDER BY id LIMIT 200');
	$stmt->execute();
	$result = $stmt->fetchAll();

	if (count($result)) {
		foreach ($result as $key => $data) {
			$flags[$data['user_name']] = $data['flag'];
		}
	}
}

$wiki_names = array(
	'starcraft' => 'Brood War',
	'starcraft2' => 'StarCraft II',
	'dota2' => 'Dota 2',
	'hearthstone' => 'Hearthstone',
	'heroes' => 'Heroes',
	'smash' => 'Smash Bros',
	'counterstrike' => 'Counter-Strike',
	'overwatch' => 'Overwatch',
	'commons' => 'Commons',
	'warcraft' => 'Warcraft',
	'fighters' => 'Fighting Games',
	'rocketleague' => 'Rocket League'
);
$get_wikis = (isset($_GET['wikis']) && is_array($_GET['wikis'])) ?
              $_GET['wikis'] : array_keys($wiki_names);
$clean_wikis_list = array();
foreach ($get_wikis as $wiki) {
	if (preg_match('/^(' . implode('|', array_keys($wiki_names)) . ')$/',
		$wiki)) {
		$clean_wikis_list[$wiki] = $wiki;
	}
}

/************************************************************/

function getContributions($curl, $postdata, $wiki, &$stats) {
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
	$data = unserialize(curl_exec($curl));

	$users = $data['query']['allusers'];
	foreach ($users as $user) {
		if ( !isset($stats[$user['name']]) ) {
			createStats($stats, $user['name']);
		}
		$stats[$user['name']]['count_' . $wiki] = $user['editcount'];
		$stats[$user['name']]['groups_' . $wiki] = $user['groups'];
	}

	return isset($data['continue']) ? $data['continue'] : 0;
}

function createStats(&$stats, $username) {
	global $wikis;

	$stats[$username] = array();
	foreach (array_keys($wikis) as $wiki) {
		$stats[$username]['count_' . $wiki] = 0;
		$stats[$username]['groups_' . $wiki] = array();
	}
}

function getMerges($curl, $postdata, $wiki, &$merges) {
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
	$data = unserialize(curl_exec($curl));

	$events = $data['query']['logevents'];
	foreach ($events as $event) {
		if ($event['action'] == 'mergeuser') {
			$merges[$wiki][] = array(
				'from' => $event['0'],
				'to' => $event['2']
			);
		}
	}

	return isset($data['continue']) ? $data['continue'] : 0;
}

function mergeUsers(&$stats, &$mergeStats, $merges) {
	global $wikis;

	foreach (array_keys($wikis) as $wiki) {
		foreach($merges[$wiki] as $merge) {
			$to = $merge['to'];
			$from = $merge['from'];

			if (!isset($stats[$to])) {
				createStats($stats, $to);
			}
			if (!isset($stats[$from])) {
				createStats($stats, $from);
			}
			if (!isset($mergeStats[$wiki][$to])) {
				if (!isset($mergeStats[$wiki][$to][$from])) {
					$mergeStats[$wiki][$to][$to] =
						$stats[$to]['count_' . $wiki];
					$mergeStats[$wiki][$to][$from] =
						$stats[$from]['count_' . $wiki];
				}
			} else {
				if (!isset($mergeStats[$wiki][$to][$from])) {
					$mergeStats[$wiki][$to][$from] =
						$stats[$from]['count_' . $wiki];
				}
			}

			$stats[$to]['count_' . $wiki] += $stats[$from]['count_' . $wiki];
			$stats[$from]['count_' . $wiki] = 0;
		}
	}
}

/************************************************************/

global $wikis;
$wikis = array();
foreach ($clean_wikis_list as $wiki) {
	$wikis[$wiki] = $wiki_names[$wiki];
}

$stats = array();
if ($merged_users) {
	$merges = array();
	foreach(array_keys($wikis) as $wiki) {
		$merges[$wiki] = array();
		$mergeStats[$wiki] = array();
	}
}

// *--
// -*- cURL configuration
// --*
$cc = array();
$cc['options'] = array(
	CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; editcount/1.0; chapatiyaq@gmail.com)',
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_ENCODING => '',
	CURLOPT_COOKIEJAR => $cookieFile,
	CURLOPT_COOKIEFILE => $cookieFile,
	CURLOPT_POST => true,
	CURLOPT_TIMEOUT => 60
);

foreach (array_keys($wikis) as $wiki) {
	//echo "<b>$wiki</b></br>";

	// Trying to log in with the bot, for bigger queries...
	if (isset($loginName) && isset($loginPass) && $loginName !== '' && $loginPass !== '') {
		// *--
		// -*- Initialize a new cURL session
		// --*
		$curl = curl_init();
		curl_setopt_array($curl, $cc['options'] );
		curl_setopt($curl, CURLOPT_URL, 'http://wiki.teamliquid.net/' . $wiki . '/api.php');

		// *--
		// -*- Check user info
		// --*
		$postdata = http_build_query(array(
			'action' => 'query',
			'meta' => 'userinfo',
			'format' => 'php'
		));
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		$data = unserialize(curl_exec($curl));

		$loginStatus = '';
		$bot = false;
		if (!isset($data['query']['userinfo']['anon']) && $data['query']['userinfo']['name'] == $loginName) {
			$loginStatus = 'Logged in from cookie as ' . $loginName;
			$bot = true;
		} else {
			// *--
			// -*- Login
			// --*
			$postdata = http_build_query(array(
				'action' => 'login',
				'lgname' => $loginName,
				'format' => 'php'
			));
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
			$data = unserialize(curl_exec($curl));
			//echo '<h5>$data[\'login\']</h5><pre>' . print_r( $data['login'], true ) . '</pre>';
			$loginToken = $data['login']['token'];

			if ( $data['login']['result'] == 'NeedToken') {
				$postdata = http_build_query(array(
					'action' => 'login',
					'lgname' => $loginName,
					'lgpassword' => $loginPass,
					'lgtoken' => $loginToken,
					'format' => 'php'
				));
				curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
				$data = unserialize(curl_exec($curl));
				//echo '<h5>$data[\'login\']</h5><pre>' . print_r($data['login'], true) . '</pre>';

				if ($data['login']['result'] == 'Success') {
					$loginStatus = 'Logged in from login info as ' . $loginName;
					$bot = true;
					// *--
					// -*- Prepare cookie vars
					// --*
					$cookiePrefix = $data['login']['cookieprefix'];
					$cookieVars = array(
						$cookiePrefix . '_session=' . $data['login']['sessionid'],
						$cookiePrefix . 'UserID=' . $data['login']['lguserid'],
						$cookiePrefix . 'UserName=' . $data['login']['lgusername'],
						$cookiePrefix . 'Token=' . $data['login']['lgtoken']
					);
					$isNewCookieSet = setrawcookie($toluenoCookieName, implode('|', $cookieVars), strtotime('+1 day'), '/liquipedia/', 'tolueno.fr');
				} else {
					$loginStatus = 'Error when logging in as ' . $loginName;
					//exit(3);
					$bot = false;
				}
			} else {
				$loginStatus = 'Error when logging in as ' . $loginName;
				//exit(3);
				$bot = false;
			}
		}

		// Close the cURL session to save cookies
		curl_close($curl);
	} else {
		$bot = false;
	}

	// *--
	// -*- Initialize a new cURL session
	// --*
	$curl = curl_init();
	curl_setopt_array($curl, $cc['options']);
	curl_setopt($curl, CURLOPT_URL, 'http://wiki.teamliquid.net/' . $wiki . '/api.php');

	// Contributions
	$postdata = array(
		'action' => 'query',
		'list' => 'allusers',
		'auwitheditsonly' => true,
		'auprop' => 'editcount|groups',
		'aulimit' => ($bot ? 5000 : 500),
		'continue' => '',
		'format' => 'php'
	);
	$continue = getContributions($curl, $postdata, $wiki, $stats);
	while ( $continue !== 0 ) {
		$postdata['aufrom'] = str_replace(' ', '%20', $continue['aufrom']);
		$continue = getContributions($curl, $postdata, $wiki, $stats);
	}

	// Merges
	$postdata = array(
		'action' => 'query',
		'list' => 'logevents',
		'letype' => 'usermerge',
		'ledir' => 'newer',
		'lelimit' => ($bot ? 5000 : 500),
		'format' => 'php'
	);
	$continue = getMerges($curl, $postdata, $wiki, $merges);
	while ( $continue !== 0 ) {
		$postdata['lestart'] = str_replace(' ', '%20', $continue['lestart']);
		$postdata['continue'] = $continue['continue'];
		$continue = getMerges($curl, $postdata, $wiki, $merges);
	}

	curl_close($curl);
}


if ($merged_users) {
	mergeUsers($stats, $mergeStats, $merges);
}

foreach ($stats as &$userstats) {
	$userstats['count_total'] = 0;
	$userstats['bot'] = false;
	foreach (array_keys($wikis) as $wiki) {
		$userstats['count_total'] += $userstats['count_' . $wiki];
		$userstats['bot'] |= in_array('bot', $userstats['groups_' . $wiki]);
	}
}
unset($userstats);

uasort($stats, 'cmp');
function cmp($a, $b) {
	return $b['count_total'] - $a['count_total'];
}
?>
	<div class="flexbox">
		<div id="users">
			<div class="box-title">Users</div>
			<table>
				<tr class="header-row">
					<th class="pos"></th>
					<th class="name">Name</th>
					<?php foreach($wikis as $url_part => $name) {
						echo '<th class="wiki-column ' . $url_part . '">'
						     . '<div title="' . $name . '"></div>' . '</th>';
					} ?>
					<th class="total-column">Total</th>
				</tr>
<?php
$i = 1;
$total = array();
foreach(array_keys($wikis) as $wiki) {
	$total[$wiki] = 0;
}
foreach($stats as $username => $userstats) {
	foreach(array_keys($wikis) as $wiki) {
		$total[$wiki] += $userstats['count_' . $wiki];
	}
}
$total['all'] = 0;
foreach(array_keys($wikis) as $wiki) {
	$total['all'] += $total[$wiki];
}
echo '<tr class="total-row">';
echo '<td class="pos"></td>';
echo '<td class="name">Total</td>';
foreach(array_keys($wikis) as $wiki) {
	echo '<td class="wiki-column ' . $wiki . '">' . $total[$wiki] . '</td>';
}
echo '<td class="total-column">' . $total['all'] . '</td>';
echo '</tr>';
$flagStats = array();
foreach($stats as $username => $userstats) {
	echo '<tr class="user-row">';
	echo '<td class="pos">' . $i . '.</td>';
	echo '<td class="name">';
	if ($show_flags && isset($flags[$username])) {
		echo '<span class="flag-icon flag-icon-' . $flags[$username] . '"></span>&nbsp;';
		
		if (!$userstats['bot']) {
			if (!isset($flagStats[$flags[$username]])) {
				$flagStats[$flags[$username]] = array(
					'usercount_total' => 1,
					'count_total' => $userstats['count_total'],
					'counts_total' => array($userstats['count_total']),
				);
				foreach($wikis as $url_part => $name) {
					if ($userstats['count_' . $url_part] > 0) {
						$flagStats[$flags[$username]]['usercount_' . $url_part] = 1;
					} else {
						$flagStats[$flags[$username]]['usercount_' . $url_part] = 0;
					}
					$flagStats[$flags[$username]]['count_' . $url_part] = $userstats['count_' . $url_part];
				}
			} else {
				$flagStats[$flags[$username]]['usercount_total']++;
				$flagStats[$flags[$username]]['count_total'] += $userstats['count_total'];
				$flagStats[$flags[$username]]['counts_total'][] = $userstats['count_total'];
				foreach($wikis as $url_part => $name) {
					if ($userstats['count_' . $url_part] > 0) {
						$flagStats[$flags[$username]]['usercount_' . $url_part]++;
						$flagStats[$flags[$username]]['count_' . $url_part] += $userstats['count_' . $url_part];
					}
				}
			}
		}
	}
	echo '<a href="http://tolueno.fr/liquipedia/userstats/?user=' . $username . '">' . $username . '</a></td>';
	foreach ($wikis as $url_part => $name) {
		echo '<td class="wiki-column ' . $url_part . ' ' . implode(' ', $userstats['groups_' . $url_part]) . '">';

		if ($merged_users && isset($mergeStats[$url_part][$username])) {
			$subCounts = array();
			foreach( $mergeStats[$url_part][$username] as $subUser => $subCount) {
				$subCounts[] = $subCount . ' edits as ' . $subUser;
			}
			$breakdown = implode(', &#10;', $subCounts);
			echo '<abbr title="' . $breakdown . '">m</abbr>&nbsp;';
		}

		if ($userstats['count_' . $url_part] != 0) {
			echo '<a title="Contributions on Liquipedia: ' . $name
				. '" href="http://wiki.teamliquid.net/' . $url_part . '/Special:Contributions/' . $username . '">'
				. $userstats['count_' . $url_part] . '</a>';
		} else {
			echo '-';
		}

		echo '</td>';
	}
	echo '<td class="total">' . $userstats['count_total'] . '</td>';
	echo '</tr>';
	++$i;
	if ($i > 2000)
		break;
}
?>
			</table>
		</div>
<?php
uasort($flagStats, 'cmp');
?>
		<div id="flags">
			<div class="box-title">Flags (no bots)</div>
			<table>
				<tr class="header-row">
					<th rowspan="2" class="pos"></th>
					<th rowspan="2" class="flag">Flag</th>
					<?php foreach($wikis as $url_part => $name) {
						echo '<th colspan="3" class="wiki-column ' . $url_part . '">' . $name . '</th>';
					} ?>
					<th colspan="4" class="total-column">Total</th>
				</tr>
				<tr class="header-row">
					<?php foreach($wikis as $url_part => $name) {
						echo '<th class="wiki ' . $url_part . '">Users</th>';
						echo '<th class="wiki ' . $url_part . '">Edits</th>';
						echo '<th class="wiki ' . $url_part . '"><abbr title="Edits per user">E/U</abbr></th>';
					} ?>
					<th class="total">Users</th>
					<th class="total">Edits</th>
					<th class="total"><abbr title="Edits per user">E/U</abbr></th>
					<th class="total">Median</th>
				</tr>
				<?php $i = 1;
				foreach ($flagStats as $flag => $stats) {
					echo '<tr class="flag-row">';
					echo '<td class="pos">' . $i . '.</td>';
					echo '<td class="flag">';
					echo '<span class="flag-icon flag-icon-' . $flag . '"></span>';
					echo '</td>';
					foreach($wikis as $url_part => $name) {
						echo '<td class="wiki ' . $url_part . '">' . $stats['usercount_' . $url_part] . '</td>';
						echo '<td class="wiki ' . $url_part . '">' . $stats['count_' . $url_part] . '</td>';
						echo '<td class="wiki ' . $url_part . '">';
						if ($stats['usercount_' . $url_part] > 0)
							echo round($stats['count_' . $url_part] / $stats['usercount_' . $url_part], 1);
						else
							echo 0;
						echo '</td>';
					}
					echo '<td class="total">' . $stats['usercount_total'] . '</td>';
					echo '<td class="total">' . $stats['count_total'] . '</td>';
					if ($stats['usercount_total'] > 0)
						echo '<td class="total">' . round($stats['count_total'] / $stats['usercount_total'], 1) . '</td>';
					else
						echo 0;
					asort($stats['counts_total']);
					$n0 = floor(($stats['usercount_total'] + 1) / 2) - 1; 
					$n1 = ceil(($stats['usercount_total'] + 1) / 2) - 1;
					echo '<td class="total">';
					echo round(($stats['counts_total'][$n0] + $stats['counts_total'][$n1]) / 2, 1);
					echo '</td>';
					echo '</tr>';
					$i++;
				} ?>
			</table>
		</div>
	</div>