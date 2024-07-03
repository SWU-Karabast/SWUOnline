<?php

use SendGrid\Mail\Mail;

// Check for empty input signup
function emptyInputSignup($username, $email, $pwd, $pwdRepeat)
{
	if (empty($username) || empty($email) || empty($pwd) || empty($pwdRepeat)) {
		$result = true;
	} else {
		$result = false;
	}
	return $result;
}

// Check invalid username
function invalidUid($username)
{
	if (!ctype_alnum($username)) {
		$result = true;
	} else {
		$result = false;
	}
	return $result;
}

// Check invalid email
function invalidEmail($email)
{
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$result = true;
	} else {
		$result = false;
	}
	return $result;
}

// Check if passwords matches
function pwdMatch($pwd, $pwdrepeat)
{
	if ($pwd !== $pwdrepeat) {
		$result = true;
	} else {
		$result = false;
	}
	return $result;
}

// Check if username is in database, if so then return data
function uidExists($conn, $username)
{
	$conn = GetDBConnection();
	$sql = "SELECT * FROM users WHERE usersUid = ? OR usersEmail = ?;";
	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
		header("location: ../Signup.php?error=stmtfailed");
		exit();
	}

	mysqli_stmt_bind_param($stmt, "ss", $username, $email);
	mysqli_stmt_execute($stmt);

	// "Get result" returns the results from a prepared statement
	$resultData = mysqli_stmt_get_result($stmt);

	if ($row = mysqli_fetch_assoc($resultData)) {
		return $row;
	} else {
		$result = false;
		return $result;
	}
	mysqli_stmt_close($stmt);
	mysqli_close($conn);
}

// Insert new user into database
function createUser($conn, $username, $email, $pwd, $reportingServer = false)
{
	$conn = GetDBConnection();
	$sql = "INSERT INTO users (usersUid, usersEmail, usersPwd) VALUES (?, ?, ?);";

	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
		header("location: ../Signup.php?error=stmtfailed");
		exit();
	}

	$hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);

	mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashedPwd);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	mysqli_close($conn);
	header("location: ../Signup.php?error=none");
	exit();
}

function CreateUserAPI($conn, $username, $email, $pwd)
{
	$conn = GetDBConnection();
	$sql = "INSERT INTO users (usersUid, usersEmail, usersPwd) VALUES (?, ?, ?);";

	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
		return false;
	}

	$hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);

	mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashedPwd);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	mysqli_close($conn);
}

function loginFromCookie()
{
	$token = $_COOKIE["rememberMeToken"];
	$conn = GetDBConnection();
	$sql = "SELECT usersId, usersUid, usersEmail, patreonAccessToken, patreonRefreshToken, patreonEnum, isBanned FROM users WHERE rememberMeToken=?";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "s", $token);
		mysqli_stmt_execute($stmt);
		$data = mysqli_stmt_get_result($stmt);
		$row = mysqli_fetch_array($data, MYSQLI_NUM);
		mysqli_stmt_close($stmt);
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();
		if ($row != null && count($row) > 0) {
			$_SESSION["userid"] = $row[0];
			$_SESSION["useruid"] = $row[1];
			$_SESSION["useremail"] = $row[2];
			$patreonAccessToken = $row[3];
			$patreonRefreshToken = $row[4];
			$_SESSION["patreonEnum"] = $row[5];
			$_SESSION["isBanned"] = $row[6];
			try {
				PatreonLogin($patreonAccessToken);
			} catch (\Exception $e) {
			}
		} else {
			unset($_SESSION["userid"]);
			unset($_SESSION["useruid"]);
			unset($_SESSION["useremail"]);
		}
		session_write_close();
	}
	mysqli_close($conn);
}

function storeFabraryId($uid, $fabraryId)
{
	$conn = GetDBConnection();
	$sql = "UPDATE users SET fabraryId=? WHERE usersId=?";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "ss", $fabraryId, $uid);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
}

function storeFabDBId($uid, $fabdbId)
{
	$conn = GetDBConnection();
	$sql = "UPDATE users SET fabdbId=? WHERE usersId=?";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "ss", $fabdbId, $uid);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
}

function GetDeckBuilderId($uid, $decklink)
{
	$conn = GetDBConnection();
	$sql = "SELECT fabraryId,fabdbId FROM users WHERE usersId=?";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "s", $uid);
		mysqli_stmt_execute($stmt);
		$data = mysqli_stmt_get_result($stmt);
		$row = mysqli_fetch_array($data, MYSQLI_NUM);
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
	$dbId = "";
	if (count($row) == 0) return "";
	if (str_contains($decklink, "fabrary")) $dbId = $row[0];
	else if (str_contains($decklink, "fabdb")) $dbId = $row[1];
	if ($dbId == "NULL") $dbId = "";
	return $dbId;
}

function addFavoriteDeck($userID, $decklink, $deckName, $heroID, $format = "")
{
	$conn = GetDBConnection();
	$deckName = implode("", explode("\"", $deckName));
	$deckName = implode("", explode("'", $deckName));
	$values = "'" . $decklink . "'," . $userID . ",'" . $deckName . "','" . $heroID . "','" . $format . "'";
	$sql = "INSERT IGNORE INTO favoritedeck (decklink, usersId, name, hero, format) VALUES (?, ?, ?, ?, ?);";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "sssss", $decklink, $userID, $deckName, $heroID, $format);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
}

function LoadFavoriteDecks($userID)
{
	if ($userID == "") return [];
	$conn = GetDBConnection();
	$sql = "SELECT decklink, name, hero, format from favoritedeck where usersId=?";
	$stmt = mysqli_stmt_init($conn);
	$output = [];
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "s", $userID);
		mysqli_stmt_execute($stmt);
		$data = mysqli_stmt_get_result($stmt);
		while ($row = mysqli_fetch_array($data, MYSQLI_NUM)) {
			for ($i = 0; $i < 4; ++$i) array_push($output, $row[$i]);
		}
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
	return $output;
}

//Challenge ID 1 = sigil of solace blue
//Challenge ID 2 = Talishar no dash
//Challenge ID 3 = Moon Wish
function logCompletedGameStats()
{
	global $winner, $currentRound, $gameName; //gameName is assumed by ParseGamefile.php
	global $p1id, $p2id, $p1IsChallengeActive, $p2IsChallengeActive, $p1DeckLink, $p2DeckLink, $firstPlayer;
	global $p1deckbuilderID, $p2deckbuilderID;
	$loser = ($winner == 1 ? 2 : 1);
	$columns = "WinningHero, LosingHero, NumTurns, WinnerDeck, LoserDeck, WinnerHealth, FirstPlayer, WinningPlayer";
	$values = "?, ?, ?, ?, ?, ?, ?, ?";
	$winnerDeck = file_get_contents("./Games/" . $gameName . "/p" . $winner . "Deck.txt");
	$loserDeck = file_get_contents("./Games/" . $gameName . "/p" . $loser . "Deck.txt");
	$winHero = GetCachePiece($gameName, ($winner == 1 ? 7 : 8));
	$loseHero = GetCachePiece($gameName, ($winner == 1 ? 8 : 7));

	$conn = GetDBConnection();

	if ($p1id != "" && $p1id != "-") {
		$columns .= ", " . ($winner == 1 ? "WinningPID" : "LosingPID");
		$values .= ", " . $p1id;
	}
	if ($p2id != "" && $p2id != "-") {
		$columns .= ", " . ($winner == 2 ? "WinningPID" : "LosingPID");
		$values .= ", " . $p2id;
	}

	$sql = "INSERT INTO completedgame (" . $columns . ") VALUES (" . $values . ");";
	$stmt = mysqli_stmt_init($conn);
	$gameResultID = 0;
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "ssssssss", $winHero, $loseHero, $currentRound, $winnerDeck, $loserDeck, GetHealth($winner), $firstPlayer, $winner);
		mysqli_stmt_execute($stmt);
		$gameResultID = mysqli_insert_id($conn);
		mysqli_stmt_close($stmt);
	}

	if ($p1IsChallengeActive == "1" && $p1id != "-") LogChallengeResult($conn, $gameResultID, $p1id, ($winner == 1 ? 1 : 0));
	if ($p2IsChallengeActive == "1" && $p2id != "-") LogChallengeResult($conn, $gameResultID, $p2id, ($winner == 2 ? 1 : 0));

	mysqli_close($conn);
}

function LogChallengeResult($conn, $gameResultID, $playerID, $result)
{
	WriteLog("Writing challenge result for player " . $playerID);
	$challengeId = 3;
	$sql = "INSERT INTO challengeresult (gameId, challengeId, playerId, result) VALUES (?, ?, ?, ?);";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "ssss", $gameResultID, $challengeId, $playerID, $result); //Challenge ID 1 = sigil of solace blue
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
}

function SerializeGameResult($player, $DeckLink, $deckAfterSB, $gameID = "", $opposingHero = "", $gameName = "", $deckbuilderID = "")
{
	global $winner, $currentRound, $CardStats_TimesPlayed, $CardStats_TimesActivated, $CardStats_TimesResourced, $firstPlayer;
	global $TurnStats_DamageThreatened, $TurnStats_DamageDealt, $TurnStats_CardsPlayedOffense, $TurnStats_CardsPlayedDefense, $TurnStats_CardsPitched, $TurnStats_CardsBlocked;
	global $TurnStats_ResourcesUsed, $TurnStats_CardsLeft, $TurnStats_DamageBlocked, $TurnStats_ResourcesLeft;
	$DeckLink = explode("/", $DeckLink);
	$DeckLink = $DeckLink[count($DeckLink) - 1];
	$deckAfterSB = explode("\r\n", $deckAfterSB);
	if (count($deckAfterSB) == 1) return "";
	$deckAfterSB = $deckAfterSB[1];
	$deck = [];
	if ($gameID != "") $deck["gameId"] = $gameID;
	if ($gameName != "") $deck["gameName"] = $gameName;
	$deck["deckId"] = $DeckLink;
	$deck["turns"] = intval($currentRound);
	$deck["result"] = ($player == $winner ? 1 : 0);
	$deck["firstPlayer"] = ($player == $firstPlayer ? 1 : 0);
	if ($opposingHero != "") $deck["opposingHero"] = $opposingHero;
	if ($deckbuilderID != "") $deck["deckbuilderID"] = $deckbuilderID;
	$deck["cardResults"] = [];
	$deckAfterSB = explode(" ", $deckAfterSB);
	$deduplicatedDeck = [];
	for ($i = 0; $i < count($deckAfterSB); ++$i) {
		if ($i > 0 && $deckAfterSB[$i] == $deckAfterSB[$i - 1]) continue; //Don't send duplicates
		array_push($deduplicatedDeck, $deckAfterSB[$i]);
	}
	for ($i = 0; $i < count($deduplicatedDeck); ++$i) {
		$deck["cardResults"][$i] = [];
		$deck["cardResults"][$i]["cardId"] = GetNormalCardID($deduplicatedDeck[$i]);
		$deck["cardResults"][$i]["played"] = 0;
		$deck["cardResults"][$i]["blocked"] = 0;
		$deck["cardResults"][$i]["pitched"] = 0;
		$deck["cardResults"][$i]["cardName"] = CardName($deduplicatedDeck[$i]);
		//$deck["cardResults"][$i]["pitchValue"] = PitchValue($deduplicatedDeck[$i]);
	}
	$cardStats = &GetCardStats($player);
	for ($i = 0; $i < count($cardStats); $i += CardStatPieces()) {
		for ($j = 0; $j < count($deck["cardResults"]); ++$j) {
			if ($deck["cardResults"][$j]["cardId"] == GetNormalCardID($cardStats[$i])) {
				$deck["cardResults"][$j]["played"] = $cardStats[$i + $CardStats_TimesPlayed];
				$deck["cardResults"][$j]["blocked"] = $cardStats[$i + $CardStats_TimesActivated];
				$deck["cardResults"][$j]["pitched"] = $cardStats[$i + $CardStats_TimesResourced];
				break;
			}
		}
	}
	$turnStats = &GetTurnStats($player);
	$otherPlayerTurnStats = &GetTurnStats(($player == 1 ? 2 : 1));
	for ($i = 0; $i < count($turnStats); $i += TurnStatPieces()) {
		$deck["turnResults"][$i]["cardsUsed"] = ($turnStats[$i + $TurnStats_CardsPlayedOffense] + $turnStats[$i + $TurnStats_CardsPlayedDefense]);
		$deck["turnResults"][$i]["cardsBlocked"] = $turnStats[$i + $TurnStats_CardsBlocked];
		$deck["turnResults"][$i]["cardsPitched"] = $turnStats[$i + $TurnStats_CardsPitched];
		$deck["turnResults"][$i]["resourcesUsed"] = $turnStats[$i + $TurnStats_ResourcesUsed];
		$deck["turnResults"][$i]["resourcesLeft"] = $turnStats[$i + $TurnStats_ResourcesLeft];
		$deck["turnResults"][$i]["cardsLeft"] = $turnStats[$i + $TurnStats_CardsLeft];
		$deck["turnResults"][$i]["damageDealt"] = $turnStats[$i + $TurnStats_DamageDealt];
		$deck["turnResults"][$i]["damageTaken"] = $otherPlayerTurnStats[$i + $TurnStats_DamageDealt];
	}
	return json_encode($deck);
}

function GetNormalCardID($cardID)
{
	switch ($cardID) {
		case "MON405":
			return "BOL002";
		case "MON400":
			return "BOL006";
		case "MON407":
			return "CHN002";
		case "MON401":
			return "CHN006";
		case "MON406":
			return "LEV002";
		case "MON400":
			return "LEV005";
		case "MON404":
			return "PSM002";
		case "MON402":
			return "PSM007";
	}
	return $cardID;
}

function SavePatreonTokens($accessToken, $refreshToken)
{
	if (!isset($_SESSION["userid"])) return;
	$userID = $_SESSION["userid"];
	$conn = GetDBConnection();
	$sql = "UPDATE users SET patreonAccessToken=?, patreonRefreshToken=? WHERE usersid=?";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "sss", $accessToken, $refreshToken, $userID);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
}

function LoadBadges($userID)
{
	if ($userID == "") return "";
	$conn = GetDBConnection();
	$sql = "SELECT pb.playerId,pb.badgeId,pb.intVariable,bs.topText,bs.bottomText,bs.image,bs.link FROM playerbadge pb join badges bs on bs.badgeId = pb.badgeId WHERE pb.playerId = ?;";
	$stmt = mysqli_stmt_init($conn);
	$output = [];
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "s", $userID);
		mysqli_stmt_execute($stmt);
		$data = mysqli_stmt_get_result($stmt);
		while ($row = mysqli_fetch_array($data, MYSQLI_NUM)) {
			for ($i = 0; $i < 7; ++$i) array_push($output, $row[$i]);
		}
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
	return $output;
}

function GetMyAwardableBadges($userID)
{
	return [];
	/*
	if ($userID == "") return "";
	$output = [];
	$conn = GetDBConnection();
	$sql = "select * from userassignablebadge where playerId=?";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "s", $userID);
		mysqli_stmt_execute($stmt);
		$data = mysqli_stmt_get_result($stmt);
		while ($row = mysqli_fetch_array($data, MYSQLI_NUM)) {
			array_push($output, $row[0]);
		}
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
	return $output;
	*/
}

function AwardBadge($userID, $badgeID)
{
	if ($userID == "") return "";
	$conn = GetDBConnection();
	$sql = "insert into playerbadge (playerId, badgeId, intVariable) values (?, ?, 1) ON DUPLICATE KEY UPDATE intVariable = intVariable + 1;";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "ss", $userID, $badgeID);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
}

function SaveSetting($playerId, $settingNumber, $value)
{
	if ($playerId == "") return;
	$conn = GetDBConnection();
	$sql = "insert into savedsettings (playerId, settingNumber, settingValue) values (?, ?, ?) ON DUPLICATE KEY UPDATE settingValue = VALUES(settingValue);";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "sss", $playerId, $settingNumber, $value);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
}

function LoadSavedSettings($playerId)
{
	if ($playerId == "") return [];
	$output = [];
	$conn = GetDBConnection();
	$sql = "select settingNumber,settingValue from `savedsettings` where playerId=(?)";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "s", $playerId);
		mysqli_stmt_execute($stmt);
		$data = mysqli_stmt_get_result($stmt);
		while ($row = mysqli_fetch_array($data, MYSQLI_NUM)) {
			array_push($output, $row[0]);
			array_push($output, $row[1]);
		}
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
	return $output;
}

function LoadBlockedPlayers($playerId)
{
	if ($playerId == "") return [];
	$output = [];
	$conn = GetDBConnection();
	$sql = "select blockedPlayer from `blocklist` where blockingPlayer=(?)";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "s", $playerId);
		mysqli_stmt_execute($stmt);
		$data = mysqli_stmt_get_result($stmt);
		while ($row = mysqli_fetch_array($data, MYSQLI_NUM)) {
			array_push($output, $row[0]);
		}
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
	return $output;
}

function SendEmail($userEmail, $url)
{
	include "../APIKeys/APIKeys.php";
	require '../vendor/autoload.php';

	$email = new Mail();
	$email->setFrom("no-reply@karabast.net", "No-Reply");
	$email->setSubject("Karabast Password Reset Link");
	$email->addTo($userEmail);
	$email->addContent(
		"text/html",
		"
        <p>
          We recieved a password reset request. The link to reset your password is below.
          If you did not make this request, you can ignore this email
        </p>
        <p>
          Here is your password reset link: </br>
          <a href=$url>Password Reset</a>
        </p>
      "
	);
	$sendgrid = new \SendGrid($sendgridKey);
	try {
		$response = $sendgrid->send($email);
		print $response->statusCode() . "\n";
		print_r($response->headers());
		print $response->body() . "\n";
	} catch (Exception $e) {
		echo 'Caught exception: ' . $e->getMessage() . "\n";
	}
}

function SendEmailAPI($userEmail, $url)
{
	include "../APIKeys/APIKeys.php";
	require '../vendor/autoload.php';

	$email = new Mail();
	$email->setFrom("no-reply@talishar.net", "No-Reply");
	$email->setSubject("Talishar Password Reset Link");
	$email->addTo($userEmail);
	$email->addContent(
		"text/html",
		"
        <p>
          We recieved a password reset request. The link to reset your password is below.
          If you did not make this request, you can ignore this email
        </p>
        <p>
          Here is your password reset link: </br>
          <a href=$url>Password Reset</a>
        </p>
      "
	);
	$sendgrid = new \SendGrid($sendgridKey);
	try {
		$response = $sendgrid->send($email);
	} catch (Exception $e) {
		echo 'Caught exception: ' . $e->getMessage() . "\n";
	}
}

function BanPlayer($uid)
{
	$conn = GetDBConnection();
	$sql = "UPDATE users SET isBanned = true WHERE usersUid = ?";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "s", $uid);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
	mysqli_close($conn);
}
