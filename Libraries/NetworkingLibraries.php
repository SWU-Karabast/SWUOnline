<?php
function ProcessInput($playerID, $mode, $buttonInput, $cardID, $chkCount, $chkInput, $isSimulation=false, $inputText="")
{
  global $gameName, $currentPlayer, $mainPlayer, $dqVars, $turn, $CS_CharacterIndex, $CS_PlayIndex, $decisionQueue, $CS_NextNAAInstant, $skipWriteGamestate, $combatChain, $landmarks;
  global $SET_PassDRStep, $actionPoints, $currentPlayerActivity, $redirectPath;
  global $dqState, $layers, $combatChainState;
  global $roguelikeGameID;
  switch ($mode) {
    case 3: //Play equipment ability
      MakeGamestateBackup();
      $index = $cardID;
      $found = -1;
      $character = &GetPlayerCharacter($playerID);
      $cardID = $character[$index];
      if ($index != -1 && IsPlayable($character[$index], $turn[0], "CHAR", $index)) {
        SetClassState($playerID, $CS_CharacterIndex, $index);
        SetClassState($playerID, $CS_PlayIndex, $index);
        PlayCard($cardID, "EQUIP", -1, $index);
      }
      else
      {
        echo("Play equipment ability " . $turn[0] . " Invalid Input<BR>");
        return false;
      }
      break;
    case 4: //Add something to your arsenal
      $found = HasCard($cardID);
      if ($turn[0] == "ARS" && $found >= 0) {
        $hand = &GetHand($playerID);
        unset($hand[$found]);
        $hand = array_values($hand);
        AddArsenal($cardID, $currentPlayer, "HAND", "DOWN");
        PassTurn();
      }
      else
      {
        echo($cardID . " " . $turn[0] . "<BR>");
        echo("Add to arsenal " . $turn[0] . " Invalid Input<BR>");
        return false;
      }
      break;
    case 5: //Card Played from resources
      $index = $cardID;
      $arsenal = &GetArsenal($playerID);
      if ($index < count($arsenal)) {
        $cardToPlay = $arsenal[$index];
        if (!IsPlayable($cardToPlay, $turn[0], "RESOURCES", $index)) break;
        $isExhausted = $arsenal[$index + 4] == 1;
        $uniqueID = $arsenal[$index + 5];
        RemoveArsenal($playerID, $index);
        AddTopDeckAsResource($playerID, isExhausted:$isExhausted);
        PlayCard($cardToPlay, "RESOURCES", -1, -1, $uniqueID);
      }
      else
      {
        echo("Play from arsenal " . $turn[0] . " Invalid Input<BR>");
        return false;
      }
      break;
    case 6: //Deprecated
      break;
    case 7: //Number input
      if ($turn[0] == "DYNPITCH") {
        ContinueDecisionQueue($buttonInput);
      }
      else
      {
        echo("Number input " . $turn[0] . " Invalid Input<BR>");
        return false;
      }
      break;
    case 8:
    case 9: //OPT, CHOOSETOP, CHOOSEBOTTOM
      if ($turn[0] == "OPT" || $turn[0] == "CHOOSETOP" || $turn[0] == "MAYCHOOSETOP" || $turn[0] == "CHOOSEBOTTOM") {
        $options = explode(",", $turn[2]);
        $found = -1;
        for ($i = 0; $i < count($options); ++$i) {
          if ($options[$i] == $buttonInput) {
            $found = $i;
            break;
          }
        }
        if ($found == -1) break; //Invalid input
        $deck = &GetDeck($playerID);
        if ($mode == 8) {
          array_unshift($deck, $buttonInput);
          WriteLog("Player " . $playerID . " put a card on top of the deck.");
        } else if ($mode == 9) {
          $deck[] = $buttonInput;
          WriteLog("Player " . $playerID . " put a card on the bottom of the deck.");
        }
        unset($options[$found]);
        $options = array_values($options);
        $options = implode(",", $options);
        $dqVars[0] = $options;
        if ($options != "") {
          PrependDecisionQueue($turn[0], $currentPlayer, $options);
        }
        ContinueDecisionQueue($buttonInput);
      }
      else
      {
        echo("Opt " . $turn[0] . " Invalid Input<BR>");
        return false;
      }
      break;
    case 10: //Item ability
      $index = $cardID; //Overridden to be index instead
      $items = &GetItems($playerID);
      if ($index >= count($items)) break; //Item doesn't exist
      $cardID = $items[$index];
      if (!IsPlayable($cardID, $turn[0], "PLAY", $index)) break; //Item not playable
      --$items[$index + 3];
      SetClassState($playerID, $CS_PlayIndex, $index);
      PlayCard($cardID, "PLAY", -1, $index, $items[$index + 4]);
      break;
    case 11: //CHOOSEDECK
      if ($turn[0] == "CHOOSEDECK" || $turn[0] == "MAYCHOOSEDECK") {
        $deck = &GetDeck($playerID);
        $index = $cardID;
        $cardID = $deck[$index];
        unset($deck[$index]);
        $deck = array_values($deck);
        ContinueDecisionQueue($cardID);
      }
      break;
    case 12: //HANDTOP
      if ($turn[0] == "HANDTOPBOTTOM") {
        $hand = &GetHand($playerID);
        $deck = &GetDeck($playerID);
        $cardID = $hand[$buttonInput];
        array_unshift($deck, $cardID);
        unset($hand[$buttonInput]);
        $hand = array_values($hand);
        ContinueDecisionQueue($cardID);
        WriteLog("Player " . $playerID . " put a card on the top of the deck.");
      }
      break;
    case 13: //HANDBOTTOM
      if ($turn[0] == "HANDTOPBOTTOM") {
        $hand = &GetHand($playerID);
        $deck = &GetDeck($playerID);
        $cardID = $hand[$buttonInput];
        $deck[] = $cardID;
        unset($hand[$buttonInput]);
        $hand = array_values($hand);
        ContinueDecisionQueue($cardID);
        WriteLog("Player " . $playerID . " put a card on the bottom of the deck.");
      }
      break;
    case 14: //Banish
      $index = $cardID;
      $banish = &GetBanish($playerID);
      $theirChar = &GetPlayerCharacter($playerID == 1 ? 2 : 1);
      if($index < 0 || $index >= count($banish))
      {
        echo("Banish Index " . $index . " Invalid Input<BR>");
        return false;
      }
      $cardID = $banish[$index];
      if($banish[$index + 1] == "INST") SetClassState($currentPlayer, $CS_NextNAAInstant, 1);
      if($banish[$index + 1] == "MON212" && TalentContains($theirChar[0], "LIGHT", $currentPlayer)) AddCurrentTurnEffect("MON212", $currentPlayer);
      SetClassState($currentPlayer, $CS_PlayIndex, $index);
      PlayCard($cardID, "BANISH", -1, $index, $banish[$index + 2]);
      break;

    case 16: case 18: //Decision Queue (15 and 18 deprecated)
      if(count($decisionQueue) > 0)
      {
        $index = $cardID;
        $isValid = false;
        $validInputs = explode(",", $turn[2]);
        for($i=0; $i<count($validInputs); ++$i)
        {
          if($validInputs[$i] == $index) $isValid = true;
        }
        if($isValid) ContinueDecisionQueue($index);
      }
      break;
    case 17: //BUTTONINPUT
      if (($turn[0] == "BUTTONINPUT" || $turn[0] == "CHOOSEARCANE" || $turn[0] == "BUTTONINPUTNOPASS" || $turn[0] == "CHOOSEFIRSTPLAYER")) {
        ContinueDecisionQueue($buttonInput);
      }
      break;
    case 19: //MULTICHOOSE X
      if (!str_starts_with($turn[0], "MULTICHOOSE") && !str_starts_with($turn[0], "MAYMULTICHOOSE")) break;
      $params = explode("-", $turn[2]);
      $maxSelect = intval($params[0]);
      $options = explode(",", $params[1]);
      if(count($params) > 2) $minSelect = intval($params[2]);
      else $minSelect = -1;
      if (count($chkInput) > $maxSelect) {
        WriteLog("You selected " . count($chkInput) . " items, but a maximum of " . $maxSelect . " is allowed. Reverting gamestate prior to that effect.");
        RevertGamestate();
        $skipWriteGamestate = true;
        break;
      }
      if ($minSelect != -1 && count($chkInput) < $minSelect && count($chkInput) < count($options)) {
        WriteLog("You selected " . count($chkInput) . " items, but a minimum of " . $minSelect . " is requested. Reverting gamestate prior to that effect.");
        RevertGamestate();
        $skipWriteGamestate = true;
        break;
      }
      $input = [];
      for ($i = 0; $i < count($chkInput); ++$i) {
        if ($chkInput[$i] < 0 || $chkInput[$i] >= count($options)) {
          WriteLog("You selected option " . $chkInput[$i] . " but that was not one of the original options. Reverting gamestate prior to that effect.");
          RevertGamestate();
          $skipWriteGamestate = true;
          break;
        }
        else {
          $input[] = $options[$chkInput[$i]];
        }
      }
      if (!$skipWriteGamestate) {
        ContinueDecisionQueue($input);
      }
      break;
    case 20: //YESNO
      if ($turn[0] == "YESNO" && ($buttonInput == "YES" || $buttonInput == "NO")) ContinueDecisionQueue($buttonInput);
      break;
    case 21: //Combat chain ability
      $index = $cardID; //Overridden to be index instead
      $cardID = $combatChain[$index];
      if (AbilityPlayableFromCombatChain($cardID) && IsPlayable($cardID, $turn[0], "PLAY", $index)) {
        SetClassState($playerID, $CS_PlayIndex, $index);
        PlayCard($cardID, "PLAY", -1);
      }
      break;
    case 22: //Aura ability
      $index = $cardID; //Overridden to be index instead
      $auras = &GetAuras($playerID);
      if ($index >= count($auras)) break; //Item doesn't exist
      $cardID = $auras[$index];
      if (!IsPlayable($cardID, $turn[0], "PLAY", $index)) break; //Aura ability not playable
      $auras[$index + 1] = 1; //Set status to used - for now
      SetClassState($playerID, $CS_PlayIndex, $index);
      PlayCard($cardID, "PLAY", -1, $index, $auras[$index+6]);
      break;
    case 23: //CHOOSECARD
      if ($turn[0] == "CHOOSECARD" || $turn[0] == "MAYCHOOSECARD") {
        $options = explode(",", $turn[2]);
        $found = -1;
        for ($i = 0; $i < count($options); ++$i) {
          if ($options[$i] == $buttonInput) {
            $found = $i;
            break;
          }
        }
        if ($found == -1) break; //Invalid input
        unset($options[$found]);
        $options = array_values($options);
        ContinueDecisionQueue($buttonInput);
      }
      break;
    case 24: //Ally Ability
      MakeGamestateBackup();
      $allies = &GetAllies($currentPlayer);
      $index = $cardID; //Overridden to be index instead
      if ($index >= count($allies)) break; //Ally doesn't exist
      $cardID = $allies[$index];
      if (!IsPlayable($cardID, $turn[0], "PLAY", $index)) break; //Ally not playable
      $abilityNames = GetAbilityNames($allies[$index], $index);
      if($abilityNames == "" || SearchCount($abilityNames) == 1) $allies[$index + 1] = 1;
      SetClassState($playerID, $CS_PlayIndex, $index);
      PlayCard($cardID, "PLAY", -1, $index, $allies[$index+5]);
      break;
    case 25: //Landmark Ability
      $index = $cardID;
      if ($index >= count($landmarks)) break; //Landmark doesn't exist
      $cardID = $landmarks[$index];
      if (!IsPlayable($cardID, $turn[0], "PLAY", $index)) break; //Landmark not playable
      SetClassState($playerID, $CS_PlayIndex, $index);
      PlayCard($cardID, "PLAY", -1);
      break;
    case 26: //Change setting
      $userID = "";
      if(!$isSimulation)
      {
        include "MenuFiles/ParseGamefile.php";
        include_once "./includes/dbh.inc.php";
        include_once "./includes/functions.inc.php";
        if($playerID == 1) $userID = $p1id;
        else $userID = $p2id;
      }
      $params = explode("-", $buttonInput);
      ChangeSetting($playerID, $params[0], $params[1], $userID);
      break;
    case 27: //Play card from hand by index
      MakeGamestateBackup();
      $found = $cardID;
      if ($found >= 0) {
        //Player actually has the card, now do the effect
        //First remove it from their hand
        $hand = &GetHand($playerID);
        if($found >= count($hand)) break;
        $cardID = $hand[$found];
        if(!IsPlayable($cardID, $turn[0], "HAND", $found)) break;
        unset($hand[$found]);
        $hand = array_values($hand);
        PlayCard($cardID, "HAND");
      }
      break;
    case 29: //CHOOSETOPOPPONENT
      if($turn[0] == "CHOOSETOPOPPONENT") {
        $otherPlayer = ($playerID == 1 ? 2 : 1);
        $options = explode(",", $turn[2]);
        $found = -1;
        for ($i = 0; $i < count($options); ++$i) {
          if ($options[$i] == $buttonInput) {
            $found = $i;
            break;
          }
        }
        if($found == -1) break; //Invalid input
        $deck = &GetDeck($otherPlayer);
        array_unshift($deck, $buttonInput);
        unset($options[$found]);
        $options = array_values($options);
        if(count($options) > 0) {
          PrependDecisionQueue($turn[0], $currentPlayer, implode(",", $options));
        }
        ContinueDecisionQueue($buttonInput);
      } else {
        echo ("Choose top opponent " . $turn[0] . " Invalid Input<BR>");
        return false;
      }
      break;
    case 30://String input
      $cardName = CardName(strtoupper($inputText));
      if($cardName != "") $inputText = $cardName;
      if($turn[2] == "OUT052" && $inputText == "Head Leads the Tail")//Validate the name
      {
        WriteLog("Must name another card");
        break;
      }
      ContinueDecisionQueue(GamestateSanitize($inputText));
      break;
    case 31: //Move layer deeper
      $index = $buttonInput;
      if($index >= $dqState[8]) break;
      $layer = [];
      for($i=$index; $i<$index+LayerPieces(); ++$i) $layer[] = $layers[$i];
      $counter = 0;
      for($i=$index + LayerPieces(); $i<($index + LayerPieces()*2); ++$i)
      {
        $layers[$i-LayerPieces()] = $layers[$i];
        $layers[$i] = $layer[$counter++];
      }
      break;
    case 32: //Move layer up
      $index = $buttonInput;
      if($index == 0) break;
      $layer = [];
      for($i=$index; $i<$index+LayerPieces(); ++$i) $layer[] = $layers[$i];
      $counter = 0;
      for($i=$index - LayerPieces(); $i<$index; ++$i)
      {
        $layers[$i+LayerPieces()] = $layers[$i];
        $layers[$i] = $layer[$counter++];
      }
      break;
    case 33: //Fully re-order layers
      break;
    case 34: //Claim Initiative
      global $initiativeTaken, $initiativePlayer, $isPass;
      WriteLog("Player " . $playerID . " claimed initiative.");
      $initiativePlayer = $currentPlayer;
      $otherPlayer = ($playerID == 1 ? 2 : 1);
      $roundPass = $initiativeTaken == ($otherPlayer + 2);
      $initiativeTaken = 1;
      $isPass = true;
      if($roundPass) BeginRoundPass();
      break;
    case 35://Play from discard
      MakeGamestateBackup();
      $found = $cardID;
      if ($found >= 0) {
        $discard = &GetDiscard($playerID);
        if($found >= count($discard)) break;
        $cardID = $discard[$found];
        $modifier = $discard[$found+1];
        if(!IsPlayable($cardID, $turn[0], "GY", $found)) break;
        if($modifier == "TTFREE") AddCurrentTurnEffect("TTFREE", $playerID);
        RemoveDiscard($playerID, $found);
        PlayCard($cardID, "GY");
      }
      break;
    case 99: //Pass
      global $isPass, $initiativeTaken, $dqState;
      $isPass = true;
      $otherPlayer = ($playerID == 1 ? 2 : 1);
      $roundPass = $initiativeTaken == ($otherPlayer + 2);
      $dqState[8] = -1;
      if($turn[0] == "M" && $initiativeTaken != 1 && !$roundPass) $initiativeTaken = $currentPlayer + 2;
      if(CanPassPhase($turn[0])) {
        PassInput(false);
      }
      break;
    case 100: //Break Chain
      if($currentPlayer == $mainPlayer && count($combatChain) == 0) {
        ResetCombatChainState();
        ProcessDecisionQueue();
      }
      break;
    case 101: //Pass block and Reactions
      ChangeSetting($playerID, $SET_PassDRStep, 1);
      if (CanPassPhase($turn[0])) {
        PassInput(false);
      }
      break;
    case 102: //Toggle equipment Active
      $index = $buttonInput;
      $char = &GetPlayerCharacter($playerID);
      $char[$index + 9] = ($char[$index + 9] == "1" ? "0" : "1");
      break;
    case 103: //Toggle my permanent Active
      $input = explode("-", $buttonInput);
      $index = $input[1];
      switch($input[0])
      {
        case "AURAS": $zone = &GetAuras($playerID); $offset = 7; break;
        case "ITEMS": $zone = &GetItems($playerID); $offset = 5; break;
        default: $zone = &GetAuras($playerID); $offset = 7; break;
      }
      $zone[$index + $offset] = ($zone[$index + $offset] == "1" ? "0" : "1");
      break;
    case 104: //Toggle other player permanent Active
      $input = explode("-", $buttonInput);
      $index = $input[1];
      switch($input[0])
      {
        case "AURAS": $zone = &GetAuras($playerID == 1 ? 2 : 1); $offset = 8; break;
        case "ITEMS": $zone = &GetItems($playerID == 1 ? 2 : 1); $offset = 6; break;
        default: $zone = &GetAuras($playerID == 1 ? 2 : 1); $offset = 8; break;
      }
      $zone[$index + $offset] = ($zone[$index + $offset] == "1" ? "0" : "1");
      break;
    case 10000: //Undo
      RevertGamestate();
      $skipWriteGamestate = true;
      WriteLog("Player " . $playerID . " undid their last action.");
      break;
    case 10001:
      RevertGamestate("preBlockBackup.txt");
      $skipWriteGamestate = true;
      WriteLog("Player " . $playerID . " cancel their blocks.");
      break;
    case 10003: //Revert to prior turn
      RevertGamestate($buttonInput);
      WriteLog("Player " . $playerID . " reverted back to a prior turn.");
      break;
    case 10005:
      WriteLog("Player " . $playerID ." manually subtracted 1 damage from themselves.", highlight: true);
      Restore(1, $playerID);
      break;
    case 10006:
      WriteLog("Player " . $playerID ." manually added 1 damage point to themselves.", highlight: true);
      LoseHealth(1, $playerID);
      break;
    case 10007:
      WriteLog("Player " . $playerID ." manually subtracted 1 damage from their opponent.", highlight: true);
      Restore(1, ($playerID == 1 ? 2 : 1));
      break;
    case 10008:
      WriteLog("Player " . $playerID ." manually added 1 damage to their opponent.", highlight: true);
      LoseHealth(1, ($playerID == 1 ? 2 : 1));
      break;
    case 10009:
      WriteLog("Player " . $playerID ." manually drew a card for themselves.", highlight: true);
      Draw($playerID, false);
      break;
    case 10010:
      WriteLog("Player " . $playerID ." manually drew a card for their opponent.", highlight: true);
      Draw(($playerID == 1 ? 2 : 1), false);
      break;
    case 10011:
      WriteLog("Player " . $playerID ." manually added a card to their hand.", highlight: true);
      $hand = &GetHand($playerID);
      $hand[] = $cardID;
      break;
    case 10012://Add damage to friendly ally
      WriteLog("Player " . $playerID ." manually added damage to a friendly unit.", highlight: true);
      $index = $buttonInput;
      $ally = new Ally("MYALLY-" . $index, $playerID);
      $ally->AddDamage(1);
      break;
    case 10013://Remove damage from friendly ally
      WriteLog("Player " . $playerID ." manually removed damage from a friendly unit.", highlight: true);
      $index = $buttonInput;
      $ally = new Ally("MYALLY-" . $index, $playerID);
      $ally->RemoveDamage(1);
      break;
    case 10014://Move a card
      if(!IsManualMode($playerID)) break;
      $paramArr = explode("!", $cardID);
      $draggedMZID = $paramArr[0];
      $draggedMZArr = explode("-", $draggedMZID);
      switch($draggedMZArr[0]) {
        case "MYHAND": $from = "HAND"; break;
        case "THEIRHAND": $from = "HAND"; break;
        case "MYALLY": $from = "ALLY"; break;
        case "THEIRALLY": $from = "ALLY"; break;
        default: $from = ""; break;
      }
      switch($paramArr[1]) {
        case "groundArena":
          $destination = "MYALLY";
          break;
        default:
          $destination = "";
          break;
      }
      MZAddZone($playerID, $destination . "," . $from, $draggedMZID);
      MZRemove($playerID, $draggedMZID);
      break;
    case 100000: //Quick Rematch
      if($isSimulation) return;
      if($turn[0] != "OVER") break;
      CloseDecisionQueue();
      global $decisionQueue;
      $decisionQueue = [];
      $otherPlayer = ($playerID == 1 ? 2 : 1);
      $char = &GetPlayerCharacter($otherPlayer);
      if ($char[0] != "DUMMY") {
        AddDecisionQueue("YESNO", $otherPlayer, "if you want a Quick Rematch?");
        AddDecisionQueue("NOPASS", $otherPlayer, "-", 1);
        AddDecisionQueue("QUICKREMATCH", $otherPlayer, "-", 1);
        AddDecisionQueue("OVER", $playerID, "-");
      } else {
        AddDecisionQueue("QUICKREMATCH", $otherPlayer, "-", 1);
      }
      ProcessDecisionQueue();
      break;
    case 100001: //Main Menu
      if($isSimulation) return;
      header("Location: " . $redirectPath . "/MainMenu.php");
      exit;
    case 100002: //Concede
      if($isSimulation) return;
      include_once "./includes/dbh.inc.php";
      include_once "./includes/functions.inc.php";
      $conceded = true;
      if(!IsGameOver()) PlayerWon(($playerID == 1 ? 2 : 1));
      break;
    case 100003: //Report Bug
      if($isSimulation) return;
      $bugCount = 0;
      $folderName = "./BugReports/" . $gameName . "-" . $bugCount;
      while ($bugCount < 10 && file_exists($folderName)) {
        ++$bugCount;
        $folderName = "./BugReports/" . $gameName . "-" . $bugCount;
      }
      if ($bugCount == 10) {
        WriteLog("Bug report file is temporarily full for this game. Please use the discord to report further bugs.");
      }
      mkdir($folderName, 0700, true);
      copy("./Games/$gameName/gamestate.txt", $folderName . "/gamestate.txt");
      copy("./Games/$gameName/gamestateBackup.txt", $folderName . "/gamestateBackup.txt");
      copy("./Games/$gameName/gamelog.txt", $folderName . "/gamelog.txt");
      copy("./Games/$gameName/beginTurnGamestate.txt", $folderName . "/beginTurnGamestate.txt");
      copy("./Games/$gameName/lastTurnGamestate.txt", $folderName . "/lastTurnGamestate.txt");
      WriteLog("Thank you for reporting a bug. To describe what happened, please report it on the discord server with the game number for reference (" . $gameName . "-" . $bugCount . ").");
      break;
    case 100004: //Full Rematch
      if($isSimulation) return;
      if($turn[0] != "OVER") break;
      $otherPlayer = ($playerID == 1 ? 2 : 1);
      AddDecisionQueue("YESNO", $otherPlayer, "if you want a Rematch?");
      AddDecisionQueue("REMATCH", $otherPlayer, "-", 1);
      ProcessDecisionQueue();
      break;
    case 100005: //Reserved to trigger user return from activity
      break;
    case 100006: // User inactive
      $currentPlayerActivity = 2;
      GamestateUpdated($gameName);
      break;
    case 100007: //Claim Victory when opponent is inactive
      if($isSimulation) return;
      include_once "./includes/dbh.inc.php";
      include_once "./includes/functions.inc.php";
      if(!IsGameOver()) PlayerWon(($playerID == 1 ? 1 : 2));
      break;
    case 100010: //Grant badge
      if($isSimulation) return;
      include "MenuFiles/ParseGamefile.php";
      include_once "./includes/dbh.inc.php";
      include_once "./includes/functions.inc.php";
      $myName = ($playerID == 1 ? $p1uid : $p2uid);
      $theirName = ($playerID == 1 ? $p2uid : $p1uid);
      if($playerID == 1) $userID = $p1id;
      else $userID = $p2id;
      if($userID != "")
      {
        AwardBadge($userID, 3);
        WriteLog($myName . " gave a badge to " . $theirName);
      }
      break;
    case 100012: //Create Replay
      if(!file_exists("./Games/" . $gameName . "/origGamestate.txt"))
      {
        WriteLog("Failed to create replay; original gamestate file failed to create.");
        return true;
      }
      include "MenuFiles/ParseGamefile.php";
      WriteLog("Player " . $playerID . " saved this game as a replay.");
      $pid = ($playerID == 1 ? $p1id : $p2id);
      $path = "./Replays/" . $pid . "/";
      if (!file_exists($path)) {
        mkdir($path, 0777, true);
      }
      if(!file_exists($path . "counter.txt")) $counter = 1;
      else {
        $counterFile = fopen($path . "counter.txt", "r");
        $counter = fgets($counterFile);
        fclose($counterFile);
      }
      mkdir($path . $counter . "/", 0777, true);
      copy("./Games/" . $gameName . "/origGamestate.txt", "./Replays/" . $pid . "/" . $counter . "/origGamestate.txt");
      copy("./Games/" . $gameName . "/commandfile.txt", "./Replays/" . $pid . "/" . $counter . "/replayCommands.txt");
      $counterFile = fopen($path . "counter.txt", "w");
      fwrite($counterFile, $counter+1);
      fclose($counterFile);
      break;
    case 100013: //Enable Spectate
      SetCachePiece($gameName, 9, "1");
      break;
    case 100014: //Report Player
      if($isSimulation) return;
      $reportCount = 0;
      $folderName = "./BugReports/" . $gameName . "-" . $reportCount;
      while ($reportCount < 5 && file_exists($folderName)) {
        ++$reportCount;
        $folderName = "./BugReports/" . $gameName . "-" . $reportCount;
      }
      if ($reportCount == 5) {
        WriteLog("Report file is full for this game. Please use discord for further reports.");
      }
      mkdir($folderName, 0700, true);
      copy("./Games/$gameName/gamestate.txt", $folderName . "/gamestate.txt");
      copy("./Games/$gameName/gamestateBackup.txt", $folderName . "/gamestateBackup.txt");
      copy("./Games/$gameName/gamelog.txt", $folderName . "/gamelog.txt");
      copy("./Games/$gameName/beginTurnGamestate.txt", $folderName . "/beginTurnGamestate.txt");
      copy("./Games/$gameName/lastTurnGamestate.txt", $folderName . "/lastTurnGamestate.txt");
      WriteLog("Thank you for reporting the player. The chat log has been saved to the server. Please report it to mods on the discord server with the game number for reference ($gameName).");
      break;
    case 100015:
      if($isSimulation) return;
      include_once "./includes/dbh.inc.php";
      include_once "./includes/functions.inc.php";
      $conceded = true;
      if(!IsGameOver()) PlayerWon(($playerID == 1 ? 2 : 1));
      header("Location: " . $redirectPath . "/MainMenu.php");
      break;
    default: break;
  }
  return true;
}

function IsModeAsync($mode)
{
  switch($mode) {
    case 26: return true;
    case 102: return true;
    case 103: return true;
    case 104: return true;
    case 10000: return true;
    case 10003: return true;
    case 100000: return true;
    case 100001: return true;
    case 100002: return true;
    case 100003: return true;
    case 100004: return true;
    case 100006: return true;
    case 100007: return true;
    case 100010: return true;
    case 100012: return true;
    case 100015: return true;
  }
  return false;
}

function IsModeAllowedForSpectators($mode)
{
  switch ($mode) {
    case 100001: return true;
    default: return false;
  }
}

function ExitProcessInput()
{
  global $playerID, $redirectPath, $gameName;
  exit;
}

function PitchHasCard($cardID)
{
  global $currentPlayer;
  return SearchPitchForCard($currentPlayer, $cardID);
}

function HasCard($cardID)
{
  global $currentPlayer;
  $cardType = CardType($cardID);
  if($cardType == "C" || $cardType == "E" || $cardType == "W") {
    $character = &GetPlayerCharacter($currentPlayer);
    for($i = 0; $i < count($character); $i += CharacterPieces()) {
      if($character[$i] == $cardID) return $i;
    }
  } else {
    $hand = &GetHand($currentPlayer);
    for($i = 0; $i < count($hand); ++$i) {
      if($hand[$i] == $cardID) return $i;
    }
  }
  return -1;
}

function Passed(&$turn, $playerID)
{
  return $turn[1 + $playerID];
}

function PassInput($autopass = false)
{
  global $turn, $currentPlayer, $initiativeTaken, $initiativePlayer;
  if($turn[0] == "END" || $turn[0] == "MAYMULTICHOOSETEXT" || $turn[0] == "MAYCHOOSECOMBATCHAIN" || $turn[0] == "MAYCHOOSEMULTIZONE" || $turn[0] == "MAYMULTICHOOSEAURAS" ||$turn[0] == "MAYMULTICHOOSEHAND" || $turn[0] == "MAYCHOOSEHAND" || $turn[0] == "MAYCHOOSEDISCARD" || $turn[0] == "MAYCHOOSEARSENAL" || $turn[0] == "MAYCHOOSEPERMANENT" || $turn[0] == "MAYCHOOSEDECK" || $turn[0] == "MAYCHOOSEMYSOUL" || $turn[0] == "MAYCHOOSETOP" || $turn[0] == "MAYCHOOSECARD" || $turn[0] == "INSTANT" || $turn[0] == "OK") {
    ContinueDecisionQueue("PASS");
  } else {
    if($autopass == true);
    else WriteLog("Player " . $currentPlayer . " passed.");
    if(Pass($turn, $currentPlayer, $currentPlayer)) {
      if($turn[0] == "M")
      {
        $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
        if($initiativeTaken == 1 && $initiativePlayer != $currentPlayer || $initiativeTaken == ($otherPlayer + 2)) {
          BeginRoundPass();
        } else {
          SkipHoldingPriorityNow($currentPlayer);
          BeginTurnPass();
        }
      }
      else PassTurn();
    }
  }
}

function Pass(&$turn, $playerID, &$currentPlayer)
{
  global $mainPlayer, $defPlayer;
  if($turn[0] == "M" || $turn[0] == "ARS") {
    return 1;
  } else if($turn[0] == "B") {
    AddLayer("DEFENDSTEP", $mainPlayer, "-");
    OnBlockResolveEffects();
    BeginningReactionStepEffects();
    ProcessDecisionQueue();
  } else if($turn[0] == "A") {
    if(count($turn) >= 3 && $turn[2] == "D") {
      return BeginChainLinkResolution();
    } else {
      $currentPlayer = $defPlayer;
      $turn[0] = "D";
      $turn[2] = "A";
    }
  } else if($turn[0] == "D") {
    if(count($turn) >= 3 && $turn[2] == "A") {
      return BeginChainLinkResolution();
    } else {
      $currentPlayer = $mainPlayer;
      $turn[0] = "A";
      $turn[2] = "D";
    }
  }
  return 0;
}

function BeginChainLinkResolution()
{
  global $mainPlayer, $turn;
  $turn[0] = "M";
  ChainLinkBeginResolutionEffects();
  AddDecisionQueue("RESOLVECHAINLINK", $mainPlayer, "-");
  ProcessDecisionQueue();
}

function ChainLinkBeginResolutionEffects()
{
  global $combatChain, $mainPlayer, $defPlayer, $CCS_CombatDamageReplaced, $combatChainState, $CCS_WeaponIndex, $CID_BloodRotPox;
  if(CardType($combatChain[0]) == "W") {
    $mainCharacterEffects = &GetMainCharacterEffects($mainPlayer);
    $index = $combatChainState[$CCS_WeaponIndex];
    for($i = 0; $i < count($mainCharacterEffects); $i += CharacterEffectPieces()) {
      if($mainCharacterEffects[$i] == $index) {
        switch($mainCharacterEffects[$i + 1]) {

          default: break;
        }
      }
    }
  }
  switch($combatChain[0])
  {
    default: break;
  }
}

function ResolveChainLink()
{
  global $combatChain, $combatChainState, $currentPlayer, $mainPlayer, $defPlayer, $currentTurnEffects, $CCS_CombatDamageReplaced, $CCS_LinkTotalAttack;
  global $CCS_DamageDealt, $CCS_HitsWithWeapon;
  UpdateGameState($currentPlayer);
  BuildMainPlayerGameState();

  $target = GetAttackTarget();
  if($target == "THEIRALLY--1") {
    CloseCombatChain(true);
    ProcessDecisionQueue();
    return;//Means the target was already destroyed
  }

  $totalAttack = 0;
  $totalDefense = 0;
  $attackerMZ = AttackerMZID($mainPlayer);
  $attackerArr = explode("-", $attackerMZ);
  $attacker = new Ally($attackerMZ, $mainPlayer);
  $hasOverwhelm = HasOverwhelm($attacker->CardID(), $mainPlayer, $attacker->Index());
  $attackerID = $attacker->CardID();
  $attackerSurvived = 1;
  $totalAttack = $attacker->CurrentPower();
  $combatChainState[$CCS_LinkTotalAttack] = $totalAttack;
  LogCombatResolutionStats($totalAttack, 0);

  $targetArr = explode("-", $target);
  if ($targetArr[0] == "THEIRALLY") {
    //Construct the combatants
    $index = $targetArr[1];
    $defender = new Ally($target, $defPlayer);
    //Resolve the combat
    $defenderPower = $defender->CurrentPower();
    if($defenderPower < 0) $defenderPower = 0;
    $excess = $totalAttack - $defender->Health();
    $damageDealt = 0;
    $destroyed = $defender->DealDamage($totalAttack, bypassShield:HasSaboteur($attackerID, $mainPlayer, $attacker->Index()), fromCombat:true, damageDealt:$combatChainState[$CCS_DamageDealt]);
    if($destroyed) ClearAttackTarget();
    if($attackerArr[0] == "MYALLY" && (!$destroyed || ($combatChain[0] != "9500514827" && $combatChain[0] != "4328408486" && !SearchCurrentTurnEffects("8297630396", $mainPlayer)))) { //Han Solo shoots first; also Incinerator Trooper
      $attackerDestroyed = $attacker->DealDamage($defenderPower, fromCombat:true);
      if($attackerDestroyed) {
        ClearAttacker();
        $attackerSurvived = 0;
      }
    }
    if($hasOverwhelm && $destroyed) DealDamageAsync($defPlayer, $excess, "OVERWHELM", $attackerID);
    else if($attackerID == "3830969722") { //Blizzard Assault AT-AT
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal " . $excess . " damage to");
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:arena=Ground");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE," . $excess, 1);
    }
    AddDecisionQueue("RESOLVECOMBATDAMAGE", $mainPlayer, $totalAttack);
  } else {
    if ($combatChainState[$CCS_CombatDamageReplaced] == 1) $damage = 0;
    else $damage = $totalAttack - $totalDefense;
    DamageTrigger($defPlayer, $damage, "COMBAT", $combatChain[0]); //Include prevention
    AddDecisionQueue("RESOLVECOMBATDAMAGE", $mainPlayer, "-");
  }
  if($attackerSurvived) {
    CompletesAttackEffect($attackerID);
  }
  ProcessDecisionQueue();
}

function ResolveCombatDamage($damageDone)
{
  global $combatChain, $combatChainState, $currentPlayer, $mainPlayer, $currentTurnEffects;
  global $CCS_DamageDealt, $CCS_HitsWithWeapon, $EffectContext, $CS_HitsWithWeapon, $CS_DamageDealt;
  global $CS_HitsWithSword;
  $wasHit = $damageDone > 0;

  PrependLayer("FINALIZECHAINLINK", $mainPlayer, "0");

  WriteLog("Combat resulted in <span style='color:Crimson;'>$damageDone damage</span>");

  if(!DelimStringContains(CardSubtype($combatChain[0]), "Ally")) {
    SetClassState($mainPlayer, $CS_DamageDealt, GetClassState($mainPlayer, $CS_DamageDealt) + $damageDone);
  }

  if($wasHit)
  {
    if(!IsAllyAttackTarget()) $combatChainState[$CCS_DamageDealt] = $damageDone;
    if(CardType($combatChain[0]) == "W") {
      ++$combatChainState[$CCS_HitsWithWeapon];
      IncrementClassState($mainPlayer, $CS_HitsWithWeapon);
      if(SubtypeContains($combatChain[0], "Sword", $mainPlayer)) IncrementClassState($mainPlayer, $CS_HitsWithSword);
    }
    if(!HitEffectsArePrevented())
    {
      for($i = 1; $i < count($combatChain); $i += CombatChainPieces()) {
        if($combatChain[$i] == $mainPlayer) {
          $EffectContext = $combatChain[$i - 1];
          ProcessHitEffect($combatChain[$i - 1]);
        }
      }
      for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
        if(IsCombatEffectActive($currentTurnEffects[$i])) {
          if($currentTurnEffects[$i + 1] == $mainPlayer) {
            $shouldRemove = EffectHitEffect($currentTurnEffects[$i]);
            if($shouldRemove == 1) RemoveCurrentTurnEffect($i);
          }
        }
      }
      $currentTurnEffects = array_values($currentTurnEffects); //In case any were removed
      MainCharacterHitAbilities();
      MainCharacterHitEffects();
      ArsenalHitEffects();
      AuraHitEffects($combatChain[0]);
      ItemHitEffects($combatChain[0]);
      AttackDamageAbilities(GetClassState($mainPlayer, $CS_DamageDealt));
    }
  }
  $currentPlayer = $mainPlayer;
  ProcessDecisionQueue(); //Any combat related decision queue logic should be main player gamestate
}

function FinalizeChainLink($chainClosed = false)
{
  global $turn, $actionPoints, $combatChain, $mainPlayer, $currentTurnEffects, $currentPlayer, $combatChainState, $actionPoints, $CCS_DamageDealt;
  global $mainClassState, $CS_AtksWWeapon, $CCS_GoesWhereAfterLinkResolves, $CS_LastAttack, $CCS_LinkTotalAttack, $CS_NumSwordAttacks, $chainLinks, $chainLinkSummary;
  global $CS_AnotherWeaponGainedGoAgain, $CCS_HitThisLink, $initiativeTaken;
  $chainClosed = true;
  UpdateGameState($currentPlayer);
  BuildMainPlayerGameState();

  ChainLinkResolvedEffects();

  $chainLinks[] = array();
  $CLIndex = count($chainLinks) - 1;
  for ($i = 1; $i < count($combatChain); $i += CombatChainPieces()) {
    $cardType = CardType($combatChain[$i - 1]);
    if ($cardType != "W" || $cardType != "E" || $cardType != "C") {
      $params = explode(",", GoesWhereAfterResolving($combatChain[$i - 1], "COMBATCHAIN", $combatChain[$i]));
      $goesWhere = $params[0];
      $modifier = (count($params) > 1 ? $params[1] : "NA");
      if ($i == 1 && $combatChainState[$CCS_GoesWhereAfterLinkResolves] != "GY") {
        $goesWhere = $combatChainState[$CCS_GoesWhereAfterLinkResolves];
      }
      switch ($goesWhere) {
        case "BOTDECK":
          AddBottomDeck($combatChain[$i - 1], $mainPlayer, "CC");
          break;
        case "HAND":
          AddPlayerHand($combatChain[$i - 1], $mainPlayer, "CC");
          break;
        case "SOUL":
          AddSoul($combatChain[$i - 1], $combatChain[$i], "CC");
          break;
        case "GY": /*AddGraveyard($combatChain[$i-1], $combatChain[$i], "CC");*/
          break; //Things that would go to the GY stay on till the end of the chain
        case "BANISH":
          BanishCardForPlayer($combatChain[$i - 1], $mainPlayer, "CC", $modifier);
          break;
        case "MEMORY":
          AddMemory($combatChain[$i - 1], $mainPlayer, "CC", "DOWN");
          break;
        default:
          break;
      }
    }
    $chainLinks[$CLIndex][] = $combatChain[$i-1]; //Card ID
    $chainLinks[$CLIndex][] = $combatChain[$i]; //Player ID
    $chainLinks[$CLIndex][] = ($goesWhere == "GY" && $combatChain[$i+1] != "PLAY" ? "1" : "0"); //Still on chain? 1 = yes, 0 = no
    $chainLinks[$CLIndex][] = $combatChain[$i+1]; //From
    $chainLinks[$CLIndex][] = $combatChain[$i+4]; //Attack Modifier
    $chainLinks[$CLIndex][] = $combatChain[$i+5]; //Defense Modifier
  }

  $chainLinkSummary[] = $combatChainState[$CCS_DamageDealt];
  $chainLinkSummary[] = $combatChainState[$CCS_LinkTotalAttack];
  $chainLinkSummary[] = "-";//Talent
  $chainLinkSummary[] = "-";//Class
  $chainLinkSummary[] = SerializeCurrentAttackNames();
  $numHitsOnLink = ($combatChainState[$CCS_DamageDealt] > 0 ? 1 : 0);
  $numHitsOnLink += intval($combatChainState[$CCS_HitThisLink]);
  $chainLinkSummary[] = $numHitsOnLink;//Num hits on link

  //Clean up combat effects that were used and are one-time
  CleanUpCombatEffects();
  CopyCurrentTurnEffectsFromCombat();
  $hasChainedAction = FinalizeChainLinkEffects();
  ProcessAfterCombatLayer();

  //Don't change state until the end, in case it changes what effects are active
  SetClassState($mainPlayer, $CS_LastAttack, $combatChain[0]);

  $combatChain = [];
  if ($chainClosed) {
    ResetCombatChainState();
    $turn[0] = "M";
    if($initiativeTaken == 1) FinalizeAction();
    else PassInput(true);
  } else {
    ResetChainLinkState();
  }
  
  if($hasChainedAction) ProcessDecisionQueue();
}

function CleanUpCombatEffects($weaponSwap=false)
{
  global $currentTurnEffects, $mainPlayer;
  for ($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $effectArr = explode(",", $currentTurnEffects[$i]);
    if (IsCombatEffectActive($effectArr[0]) && (!IsCombatEffectLimited($i) || $currentTurnEffects[$i+1] != $mainPlayer) && !IsCombatEffectPersistent($effectArr[0])) {
      --$currentTurnEffects[$i + 3];
      if ($currentTurnEffects[$i + 3] == 0) RemoveCurrentTurnEffect($i);
    }
  }
}

function BeginRoundPass()
{
  global $mainPlayer;
  WriteLog("Both players have passed; ending the phase.");
  CurrentEffectStartRegroupAbilities();
  AddDecisionQueue("RESUMEROUNDPASS", $mainPlayer, "-");
  ProcessDecisionQueue();
}

function ResumeRoundPass()
{
  global $initiativeTaken, $mainPlayer, $currentRound, $currentTurnEffects, $nextTurnEffects, $initiativePlayer;
  global $MakeStartTurnBackup;
  ResetClassState(1);
  ResetClassState(2);
  AllyBeginEndTurnEffects();
  AllyEndTurnAbilities(1);
  AllyEndTurnAbilities(2);
  LogEndTurnStats($mainPlayer);
  CurrentEffectEndTurnAbilities();
  ResetCharacter(1);
  ResetCharacter(2);
  CharacterEndTurnAbilities(1);
  CharacterEndTurnAbilities(2);
  UnsetTurnModifiers();
  $currentTurnEffects = $nextTurnEffects;
  $nextTurnEffects = [];
  $mainPlayer = $initiativePlayer == 1 ? 2 : 1;
  $initiativeTaken = 0;
  EndTurnProcedure($initiativePlayer);
  EndTurnProcedure($initiativePlayer == 1 ? 2 : 1);
  $currentRound+= 1;
  WriteLog("<span style='color:#6E6DFF;'>A new round has begun</span>");
  CharacterStartTurnAbility(1);
  CharacterStartTurnAbility(2);
  AllyBeginRoundAbilities(1);
  AllyBeginRoundAbilities(2);
  CurrentEffectStartTurnAbilities();
  ProcessDecisionQueue();
  $MakeStartTurnBackup = true;
}

function BeginTurnPass()
{
  global $mainPlayer, $defPlayer, $decisionQueue;
  ResetCombatChainState(); // The combat chain must be closed prior to the turn ending. The close step is outlined in 7.8 - specifically: CR 2.1 - 7.8.7. Fifth and finally, the Close Step ends, and the Action Phase continues. The Action Phase will always continue after the combat chain is closed - so there is another round of priority windows
  ProcessDecisionQueue();
}

function PlayerSuppress($player)
{
  $banish = &GetBanish($player);
  for($i = count($banish) - BanishPieces(); $i >= 0; $i -= BanishPieces()) {
    if($banish[$i + 1] == "SUPPRESS") {
      $cardID = $banish[$i];
      if(IsAlly($cardID)) PlayAlly($cardID, $player);
      else if(CardTypeContains($cardID, "ITEM")) PutItemIntoPlayForPlayer($cardID, $player);
      else if(CardTypeContains($cardID, "WEAPON")) AddCharacter($cardID, $player);
      RemoveBanish($player, $i);
    }
  }
}

function EndStep()
{
  global $mainPlayer, $turn;
  FinishTurnPass();
  AuraBeginEndPhaseTriggers();
  BeginEndPhaseEffectTriggers();
  PlayerSuppress(1);
  PlayerSuppress(2);
}

//CR 2.0 4.4.2. - Beginning of the end phase
function FinishTurnPass()
{
  global $mainPlayer;
  ClearLog();
  ResetCombatChainState();
  ItemEndTurnAbilities();
  AuraBeginEndPhaseAbilities();
  BeginEndPhaseEffects();
  PermanentBeginEndPhaseEffects();
  AddDecisionQueue("PASSTURN", $mainPlayer, "-");
  ProcessDecisionQueue();
}

function PassTurn()
{
  global $playerID, $currentPlayer, $turn, $mainPlayer, $mainPlayerGamestateStillBuilt;
  if (!$mainPlayerGamestateStillBuilt) {
    UpdateGameState($currentPlayer);
    BuildMainPlayerGameState();
  }

  FinalizeTurn();
}

function FinalizeTurn()
{
  //4.4.1. Players do not get priority during the End Phase.
  global $currentPlayer, $currentRound, $playerID, $turn, $combatChain, $actionPoints, $mainPlayer, $defPlayer, $currentTurnEffects, $nextTurnEffects;
  global $mainHand, $defHand, $mainDeck, $mainItems, $defItems, $defDeck, $mainCharacter, $defCharacter, $mainResources, $defResources;
  global $mainAuras, $firstPlayer, $lastPlayed, $layerPriority, $EffectContext;

  $EffectContext = "-";

  $banish = &GetBanish($mainPlayer);
  for($i = count($banish) - BanishPieces(); $i >= 0; $i -= BanishPieces()) {
    if($banish[$i + 1] == "INT") {
      AddMemory($banish[$i], $mainPlayer, "BANISH", "DOWN");
      RemoveBanish($mainPlayer, $i);
    }
  }

  AuraEndTurnAbilities();

  ArsenalEndTurn($mainPlayer);
  ArsenalEndTurn($defPlayer);

  //Reset Auras
  for ($i = 0; $i < count($mainAuras); $i += AuraPieces()) {
    $mainAuras[$i + 1] = 2; //If it were destroyed, it wouldn't be in the auras array
  }

  //4.4.3d All players lose all action points and resources.
  $mainResources[0] = 0;
  $mainResources[1] = 0;
  $defResources[0] = 0;
  $defResources[1] = 0;
  $lastPlayed = [];

  ResetCharacterEffects();
  UnsetTurnBanish();
  AuraEndTurnCleanup();

  DoGamestateUpdate();

  //Update all the player neutral stuff
  if ($mainPlayer == 2) {
    $currentRound+= 1;
  }
  $turn[0] = "M";
  //$turn[1] = $mainPlayer == 2 ? $turn[1] + 1 : $turn[1];
  $turn[2] = "";
  $turn[3] = "";
  $actionPoints = 1;
  $combatChain = []; //TODO: Add cards to the discard pile?...
  for ($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
    $effectCardID = explode("-", $currentTurnEffects[$i]);
    WriteLog("Start of turn effect for " . CardLink($effectCardID[0], $effectCardID[0]) . " is now active.");
  }
  $defPlayer = $mainPlayer;
  $mainPlayer = ($mainPlayer == 1 ? 2 : 1);
  $currentPlayer = $mainPlayer;

  BuildMainPlayerGameState();

  //Start of turn effects
  if ($mainPlayer == 1) StatsStartTurn();
  ItemBeginTurnEffects($mainPlayer);
  StartTurnAbilities();

  $layerPriority[0] = ShouldHoldPriority(1);
  $layerPriority[1] = ShouldHoldPriority(2);

  DoGamestateUpdate();
  ProcessDecisionQueue();
}

function SwapTurn() {
  global $turn, $mainPlayer, $combatChain, $actionPoints, $defPlayer, $currentPlayer;
  $turn[0] = "M";
  //$turn[1] = $mainPlayer == 2 ? $turn[1] + 1 : $turn[1];
  $turn[2] = "";
  $turn[3] = "";
  $actionPoints = 1;
  $combatChain = []; //TODO: Add cards to the discard pile?...
  $defPlayer = $mainPlayer;
  $mainPlayer = ($mainPlayer == 1 ? 2 : 1);
  $currentPlayer = $mainPlayer;
  BuildMainPlayerGameState();
}

function PlayCard($cardID, $from, $dynCostResolved = -1, $index = -1, $uniqueID = -1, $skipAbilityType = false)
{
  global $playerID, $turn, $currentPlayer, $actionPoints, $layers;
  global $layerPriority, $lastPlayed;
  global $decisionQueue, $CS_PlayIndex, $CS_PlayUniqueID, $CS_LayerPlayIndex, $CS_LastDynCost, $CS_NumCardsPlayed;
  global $mainPlayer, $CS_DynCostResolved, $CS_NumVillainyPlayed, $CS_NumEventsPlayed, $CS_NumClonesPlayed;
  $resources = &GetResources($currentPlayer);
  $pitch = &GetPitch($currentPlayer);
  $dynCostResolved = intval($dynCostResolved);
  $layerPriority[0] = ShouldHoldPriority(1);
  $layerPriority[1] = ShouldHoldPriority(2);
  $playingCard = $turn[0] != "P" && ($turn[0] != "B" || count($layers) > 0);
  if($uniqueID > 0) {
    $uniqueIndex = SearchAlliesForUniqueID($uniqueID, $currentPlayer);
    if($uniqueIndex != -1) $index = $uniqueIndex;
  }
  if($dynCostResolved == -1) {
    //CR 5.1.1 Play a Card (CR 2.0) - Layer Created
    if($playingCard)
    {
      SetClassState($currentPlayer, $CS_PlayIndex, $index);
      $layerIndex = AddLayer($cardID, $currentPlayer, $from, "-", "-");
      SetClassState($currentPlayer, $CS_LayerPlayIndex, $layerIndex);
    }
    //Announce the card being played
    WriteLog("Player " . $playerID . " " . PlayTerm($turn[0], $from, $cardID) . " " . CardLink($cardID, $cardID), $turn[0] != "P" ? $currentPlayer : 0);

    LogPlayCardStats($currentPlayer, $cardID, $from);
    if($playingCard) {
      ClearAdditionalCosts($currentPlayer);
      $lastPlayed = [];
      $lastPlayed[0] = $cardID;
      $lastPlayed[1] = $currentPlayer;
      $lastPlayed[2] = CardType($cardID);
      $lastPlayed[3] = "-";
      SetClassState($currentPlayer, $CS_PlayUniqueID, $uniqueID);
    }
    if(count($layers) > 0 && $layers[count($layers)-LayerPieces()] == "ENDTURN") $layers[count($layers)-LayerPieces()] = "RESUMETURN"; //Means the defending player played something, so the end turn attempt failed
  }
  if($turn[0] != "P") {
    if($dynCostResolved >= 0) {
      SetClassState($currentPlayer, $CS_DynCostResolved, $dynCostResolved);
      if($from == "RESOURCES") $baseCost = SmuggleCost($cardID, $currentPlayer, $index) + SelfCostModifier($cardID, $from);
      else $baseCost = ($from == "PLAY" || $from == "EQUIP" ? AbilityCost($cardID, $index) : (CardCost($cardID) + SelfCostModifier($cardID, $from)));
      if(!$playingCard) $resources[1] += $dynCostResolved;
      else {
        $frostbitesPaid = AuraCostModifier($cardID);
        $isAlternativeCostPaid = IsAlternativeCostPaid($cardID, $from);
        if($isAlternativeCostPaid)
        {
          $baseCost = 0;
          AddAdditionalCost($currentPlayer, "ALTERNATIVECOST");
        }
        $resources[1] += ($dynCostResolved > 0 ? $dynCostResolved : $baseCost) + CurrentEffectCostModifiers($cardID, $from) + $frostbitesPaid + CharacterCostModifier($cardID, $from) + BanishCostModifier($from, $index) + ItemCostModifiers($cardID);
        if($isAlternativeCostPaid && $resources[1] > 0) WriteLog("<span style='color:red;'>Alternative costs do not offset additional costs.</span>");
      }
      if($resources[1] < 0) $resources[1] = 0;
      LogResourcesUsedStats($currentPlayer, $resources[1]);
    } else {
      $dqCopy = $decisionQueue;
      $decisionQueue = [];
      //CR 5.1.3 Declare Costs Begin (CR 2.0)
      $resources[1] = 0;
      if($playingCard) $dynCost = DynamicCost($cardID); //CR 5.1.3a Declare variable cost (CR 2.0)
      else $dynCost = "";
      if($playingCard) AddPrePitchDecisionQueue($cardID, $from, $index, $skipAbilityType); //CR 5.1.3b,c Declare additional/optional costs (CR 2.0)
      if($dynCost != "") {
        AddDecisionQueue("DYNPITCH", $currentPlayer, $dynCost);
        AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_LastDynCost);
      }

      //CR 5.1.4. Declare Modes and Targets
      //CR 5.1.4a Declare targets for resolution abilities
      if($from != "PLAY" && ($turn[0] != "B" || (count($layers) > 0 && $layers[0] != ""))) GetLayerTarget($cardID);
      //Right now only units in play can attack
      if($from == "PLAY") AddDecisionQueue("GETTARGETOFATTACK", $currentPlayer, $cardID . "," . $from);

      if($dynCost == "") AddDecisionQueue("PASSPARAMETER", $currentPlayer, "0");
      else AddDecisionQueue("GETCLASSSTATE", $currentPlayer, $CS_LastDynCost);
      AddDecisionQueue("RESUMEPAYING", $currentPlayer, $cardID . "-" . $from . "-" . $index);
      $decisionQueue = array_merge($decisionQueue, $dqCopy);
      ProcessDecisionQueue();
      //MISSING CR 5.1.3d Decide if action that can be played as instant will be
      //MISSING CR 5.1.3e Decide order of costs to be paid
      return;
    }
  } else if($turn[0] == "P") {
    //$pitchValue = PitchValue($cardID);
    $resources[0] += 1;
    AddMemory($cardID, $currentPlayer, "HAND", "DOWN");
  }
  $resourceCards = &GetResourceCards($currentPlayer);
  for($i = 0; $i < count($resourceCards); $i += ResourcePieces()) {
    if($resources[1] == 0) break;
    if($resourceCards[$i+4] == "0") {
      $resourceCards[$i+4] = "1";
      --$resources[1];
    }
  }
  if($resources[1] > 0) {
    WriteLog("Not enough resources to pay for that. Reverting gamestate.");
    RevertGamestate();
  }
  //CR 2.0 5.1.7. Pay Asset-Costs
  if($resources[0] < $resources[1]) {
    if($turn[0] != "P") {
      $turn[2] = $turn[0];
      $turn[3] = $cardID;
      $turn[4] = $from;
    }
    $turn[0] = "P";
    return; //We know we need to pitch more, short circuit here
  }
  $resources[0] -= $resources[1];
  $resourcesPaid = GetClassState($currentPlayer, $CS_DynCostResolved);
  $resources[1] = 0;
  if($turn[0] == "P") {
    $turn[0] = $turn[2];
    $cardID = $turn[3];
    $from = $turn[4];
    $playingCard = $turn[0] != "P" && ($turn[0] != "B" || count($layers) > 0);
  }
  if(GetClassState($currentPlayer, $CS_LastDynCost) != 0 && DynamicCost($cardID) != "") WriteLog(CardLink($cardID, $cardID) . " was played with a cost of " . GetClassState($currentPlayer, $CS_LastDynCost));
  $cardType = CardType($cardID);
  $abilityType = "";
  $playType = $cardType;
  PlayerMacrosCardPlayed();
  //We've paid resources, now pay action points if applicable
  if($playingCard) {
    $canPlayAsInstant = CanPlayAsInstant($cardID, $index, $from);
    if(IsStaticType($cardType, $from, $cardID)) {
      $playType = GetResolvedAbilityType($cardID, $from);
      $abilityType = $playType;
      if($abilityType == "A" && !$canPlayAsInstant) ResetCombatChainState();
      PayAbilityAdditionalCosts($cardID);
      ActivateAbilityEffects();
    } else {
      if($cardType == "A" && !$canPlayAsInstant) {
        ResetCombatChainState();
      }
      CombatChainPlayAbility($cardID);
      ItemPlayAbilities($cardID, $from);
      if(AspectContains($cardID, "Villainy", $currentPlayer)) IncrementClassState($currentPlayer, $CS_NumVillainyPlayed);
      IncrementClassState($currentPlayer, $CS_NumCardsPlayed);
      if(DefinedTypesContains($cardID, "Event", $currentPlayer)) IncrementClassState($currentPlayer, $CS_NumEventsPlayed);
      if(TraitContains($cardID, "Clone", $currentPlayer)) IncrementClassState($currentPlayer, $CS_NumClonesPlayed);
    }
    if ($playType == "A" || $playType == "AA") {
      if (!$canPlayAsInstant) --$actionPoints;
      if ($cardType == "A" && $abilityType == "") {
      }
    }
    PayAdditionalCosts($cardID, $from);
  }
  if($from == "BANISH") {
    $index = GetClassState($currentPlayer, $CS_PlayIndex);
    $banish = &GetBanish($currentPlayer);
    for($i = $index + BanishPieces() - 1; $i >= $index; --$i) {
      unset($banish[$i]);
    }
    $banish = array_values($banish);
  }

  AddDecisionQueue("RESUMEPLAY", $currentPlayer, $cardID . "|" . $from . "|" . $resourcesPaid . "|" . GetClassState($currentPlayer, $CS_PlayIndex) . "|" . GetClassState($currentPlayer, $CS_PlayUniqueID));
  ProcessDecisionQueue();
}

function PlayCardSkipCosts($cardID, $from)
{
  global $currentPlayer, $layers, $turn;
  $cardType = CardType($cardID);
  if (($turn[0] == "M" || $turn[0] == "ATTACKWITHIT") && $cardType == "AA") GetTargetOfAttack($cardID);
  if ($turn[0] != "B" || (count($layers) > 0 && $layers[0] != "")) {
    if (HasBoost($cardID)) Boost();
    GetLayerTarget($cardID);
    MainCharacterPlayCardAbilities($cardID, $from);
    AuraPlayAbilities($cardID, $from);
  }
  PlayCardEffect($cardID, $from, 0);
}

function GetLayerTarget($cardID)
{
  global $currentPlayer;
  if(DefinedTypesContains($cardID, "Upgrade", $currentPlayer)) 
  {
    $upgradeFilter = UpgradeFilter($cardID);
    AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID);
    AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
    AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
    if($upgradeFilter != "") AddDecisionQueue("MZFILTER", $currentPlayer, $upgradeFilter);
    AddDecisionQueue("PASSREVERT", $currentPlayer, "-");
    AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attach <0>");
    AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
    AddDecisionQueue("SETLAYERTARGET", $currentPlayer, $cardID, 1);
    AddDecisionQueue("SHOWSELECTEDTARGET", $currentPlayer, "-", 1);
  } else {
    $targetType = PlayRequiresTarget($cardID);
    if($targetType != -1)
    {
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a target for <0>");
      AddDecisionQueue("FINDINDICES", $currentPlayer, "ARCANETARGET," . $targetType);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a target for <0>");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETLAYERTARGET", $currentPlayer, $cardID, 1);
      AddDecisionQueue("SHOWSELECTEDTARGET", $currentPlayer, "-", 1);
    }
  }
}

function AddPrePitchDecisionQueue($cardID, $from, $index = -1, $skipAbilityType = false)
{
  global $currentPlayer, $CS_AdditionalCosts;
  if (!$skipAbilityType && IsStaticType(CardType($cardID), $from, $cardID)) {
    $names = GetAbilityNames($cardID, $index, validate:true);
    if ($names != "") {
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose which ability to activate");
      AddDecisionQueue("BUTTONINPUT", $currentPlayer, $names);
      AddDecisionQueue("SETABILITYTYPE", $currentPlayer, $cardID);
    }
  }
  switch ($cardID) {
    case "9644107128"://Bamboozle
      if(SearchCount(SearchHand($currentPlayer, aspect:"Cunning")) > 0) {
        AddDecisionQueue("YESNO", $currentPlayer, "if_you_want_to_discard_a_Cunning_card", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:aspect=Cunning", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
        AddDecisionQueue("ADDDISCARD", $currentPlayer, "HAND", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "9644107128", 1);
        AddDecisionQueue("WRITELOG", $currentPlayer, CardLink("9644107128", "9644107128") . "_alternative_cost_was_paid.", 1);
      }
      break;
    case "1705806419"://Force Throw
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose player to discard a card");
      AddDecisionQueue("BUTTONINPUT", $currentPlayer, "Yourself,Opponent");
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AdditionalCosts);
      break;
    case "4772866341"://Pillage
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose player to discard 2 cards");
      AddDecisionQueue("BUTTONINPUT", $currentPlayer, "Yourself,Opponent");
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AdditionalCosts);
      break;
    case "7262314209"://Mission Briefing
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose player to draw 2 cards");
      AddDecisionQueue("BUTTONINPUT", $currentPlayer, "Yourself,Opponent");
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AdditionalCosts);
      break;
    default:
      break;
  }
}

//Find the legal targets for an attack
function GetTargetOfAttack($attackID)
{
  global $mainPlayer, $combatChainState, $CCS_AttackTarget, $CCS_IsAmbush, $CCS_CantAttackBase;
  $defPlayer = $mainPlayer == 1 ? 2 : 1;
  $targets = "";
  $sentinelTargets = "";
  if($combatChainState[$CCS_CantAttackBase] == 0 && $combatChainState[$CCS_IsAmbush] != 1){
    $targets = "THEIRCHAR-0";
  } else {
    $combatChainState[$CCS_CantAttackBase] = 0;
  }
  $attacker = new Ally(AttackerMZID($mainPlayer));
  $attackerUpgrades = $attacker->GetUpgrades();
  for($i=0; $i<count($attackerUpgrades); ++$i)
  {
    if($attackerUpgrades[$i] == "3099663280") $targets = "";//Entrenched
  }
  $allies = &GetAllies($defPlayer);
  for($i = 0; $i < count($allies); $i += AllyPieces()) {
    if($attacker->CardID() != "5464125379" && CardArenas($attacker->CardID()) != CardArenas($allies[$i]) && !SearchCurrentTurnEffects("4663781580", $mainPlayer)) continue;//Strafing Gunship, Swoop Down
    if(!AllyCanBeAttackTarget($defPlayer, $i, $allies[$i])) continue;
    if($targets != "") $targets .= ",";
    $targets .= "THEIRALLY-" . $i;
    if(HasSentinel($allies[$i], $defPlayer, $i) && CardArenas($attacker->CardID()) == CardArenas($allies[$i])) {
      if($sentinelTargets != "") $sentinelTargets .= ",";
      $sentinelTargets .= "THEIRALLY-" . $i;
    }
  }
  if($sentinelTargets != "" && !HasSaboteur($attackID, $mainPlayer, $attacker->Index())) $targets = $sentinelTargets;
  if(SearchCount($targets) > 1) {
    PrependDecisionQueue("PROCESSATTACKTARGET", $mainPlayer, "-");
    PrependDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, $targets);
    PrependDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a target for the attack");
  } else if($targets == "") {
    WriteLog("There are no valid targets for this attack. Reverting gamestate.");
    RevertGamestate();
  } else {
    SetAttackTarget($targets);
  }
}

function PayAbilityAdditionalCosts($cardID)
{
  global $currentPlayer;
  switch ($cardID) {
    case "MON000":
      for($i = 0; $i < 2; ++$i) {
        AddDecisionQueue("FINDINDICES", $currentPlayer, "HANDPITCH,2");
        AddDecisionQueue("CHOOSEHANDCANCEL", $currentPlayer, "<-", 1);
        AddDecisionQueue("MULTIREMOVEHAND", $currentPlayer, "-", 1);
        AddDecisionQueue("DISCARDCARD", $currentPlayer, "HAND", 1);
      }
      break;
    default:
      break;
  }
}

function PayAdditionalCosts($cardID, $from)
{
  global $currentPlayer, $CS_AdditionalCosts, $CS_CharacterIndex, $CS_PlayIndex, $CS_PreparationCounters;
  if(RequiresDiscard($cardID)) {
    $discarded = DiscardRandom($currentPlayer, $cardID);
    if($discarded == "") {
      WriteLog("You do not have a card to discard. Reverting gamestate.");
      RevertGamestate();
      return;
    }
    SetClassState($currentPlayer, $CS_AdditionalCosts, $discarded);
  }
  switch($cardID) {
    case "8615772965"://Vigilance
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose 2 modes");
      AddDecisionQueue("MULTICHOOSETEXT", $currentPlayer, "2-Mill,Heal,Defeat,Shield-2");
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AdditionalCosts, 1);
      AddDecisionQueue("SHOWMODES", $currentPlayer, $cardID, 1);
      break;
    case "0073206444"://Command
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose 2 modes");
      AddDecisionQueue("MULTICHOOSETEXT", $currentPlayer, "2-Experience,Deal Damage,Resource,Return Unit-2");
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AdditionalCosts, 1);
      AddDecisionQueue("SHOWMODES", $currentPlayer, $cardID, 1);
      break;
    case "3736081333"://Aggression
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose 2 modes");
      AddDecisionQueue("MULTICHOOSETEXT", $currentPlayer, "2-Draw,Defeat Upgrades,Ready Unit,Deal Damage-2");
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AdditionalCosts, 1);
      AddDecisionQueue("SHOWMODES", $currentPlayer, $cardID, 1);
      break;
    case "3789633661"://Cunning
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose 2 modes");
      AddDecisionQueue("MULTICHOOSETEXT", $currentPlayer, "2-Return Unit,Buff Unit,Exhaust Units,Discard Random-2");
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AdditionalCosts, 1);
      AddDecisionQueue("SHOWMODES", $currentPlayer, $cardID, 1);
      break;
    default:
      break;
  }
}

function MaterializeCardEffect($cardID)
{
  global $currentPlayer;
  switch($cardID)
  {

    default:
      break;
  }
}

function UpdateLinkAttack()
{
  global $mainPlayer, $combatChainState, $CCS_LinkBaseAttack, $combatChain;
  if(count($combatChain) == 0) return;
  $ally = new Ally(AttackerMZID($mainPlayer), $mainPlayer);
  $cardID = $ally->CardID();
  $baseAttackSet = CurrentEffectBaseAttackSet($cardID);
  if($baseAttackSet != -1) $attackValue = $baseAttackSet;
  else $attackValue = $ally->CurrentPower();
  $combatChainState[$CCS_LinkBaseAttack] = BaseAttackModifiers($attackValue);
}

function PlayCardEffect($cardID, $from, $resourcesPaid, $target = "-", $additionalCosts = "-", $uniqueID = "-1", $layerIndex = -1)
{
  global $turn, $combatChain, $currentPlayer, $defPlayer, $combatChainState, $CCS_AttackPlayedFrom, $CS_PlayIndex;
  global $CS_CharacterIndex, $CS_NumNonAttackCards, $CS_PlayCCIndex, $CS_NumAttacks, $CCS_LinkBaseAttack;
  global $CCS_WeaponIndex, $EffectContext, $CCS_AttackUniqueID, $CS_NumEventsPlayed, $CS_AfterPlayedBy, $layers;
  global $CS_NumDragonAttacks, $CS_NumIllusionistAttacks, $CS_NumIllusionistActionCardAttacks, $CCS_IsBoosted;
  global $SET_PassDRStep, $CS_AbilityIndex, $CS_NumMandalorianAttacks;

  if($layerIndex > -1) SetClassState($currentPlayer, $CS_PlayIndex, $layerIndex);
  if(intval($uniqueID) != -1) $index = SearchForUniqueID($uniqueID, $currentPlayer);
  if(!isset($index)) $index = GetClassState($currentPlayer, $CS_PlayIndex);
  if($index > -1) SetClassState($currentPlayer, $CS_PlayIndex, $index);

  $definedCardType = CardType($cardID);
  //Figure out where it goes
  $openedChain = false;
  $chainClosed = false;
  $isBlock = ($turn[0] == "B" && count($layers) == 0); //This can change over the course of the function; for example if a phantasm gets popped
  if (GoesOnCombatChain($turn[0], $cardID, $from)) {
    if($from == "PLAY" && $uniqueID != "-1" && $index == -1 && !DelimStringContains(CardSubType($cardID), "Item")) { WriteLog(CardLink($cardID, $cardID) . " does not resolve because it is no longer in play."); return; }
    $index = AddCombatChain($cardID, $currentPlayer, $from, $resourcesPaid);
    if ($index == 0) {
      ChangeSetting($defPlayer, $SET_PassDRStep, 0);
      $combatChainState[$CCS_AttackPlayedFrom] = $from;
      if ($definedCardType != "AA") $combatChainState[$CCS_WeaponIndex] = GetClassState($currentPlayer, $CS_PlayIndex);
      $chainClosed = ProcessAttackTarget();
      $baseAttackSet = CurrentEffectBaseAttackSet($cardID);
      if($baseAttackSet != -1) $attackValue = $baseAttackSet;
      else if(IsAllyAttacking()) {
        $ally = new Ally("MYALLY-" . GetClassState($currentPlayer, $CS_PlayIndex), $currentPlayer);
        $attackValue = $ally->CurrentPower();
        $ally->IncrementTimesAttacked();
        if(GetAttackTarget() == "THEIRCHAR-0") {
          //Add attacker to defender's list of units that attacked their base this phase.
          global $CS_UnitsThatAttackedBase;
          AppendClassState($defPlayer, $CS_UnitsThatAttackedBase, $ally->UniqueID(), false);
        }
      }
      else $attackValue = ($baseAttackSet != -1 ? $baseAttackSet : AttackValue($cardID));
      $combatChainState[$CCS_LinkBaseAttack] = BaseAttackModifiers($attackValue);
      $combatChainState[$CCS_AttackUniqueID] = $uniqueID;
      $openedChain = true;
      if($definedCardType == "AA" || $definedCardType == "W")
      {
        $char = &GetPlayerCharacter($currentPlayer);
        $char[1] = 1;
      }
      if (!$chainClosed) {
        IncrementClassState($currentPlayer, $CS_NumAttacks);
        if(TraitContains($cardID, "Mandalorian", $currentPlayer, $index)) IncrementClassState($currentPlayer, $CS_NumMandalorianAttacks);
        ArsenalAttackAbilities();
        OnAttackEffects($cardID);
      }
      if (!$chainClosed || $definedCardType == "AA") {
        AuraAttackAbilities($cardID);
        if ($from == "PLAY" && IsAlly($cardID))
        {
          AllyAttackAbilities($cardID);
          SpecificAllyAttackAbilities($cardID);
        }
      }
    }
    else { //On chain, but not index 0
      if($definedCardType == "DR") OnDefenseReactionResolveEffects();
    }
    SetClassState($currentPlayer, $CS_PlayCCIndex, $index);
  } else if ($from != "PLAY") {
    $cardSubtype = CardSubType($cardID);
    if ($definedCardType != "C" && $definedCardType != "E" && $definedCardType != "W") {
      $goesWhere = GoesWhereAfterResolving($cardID, $from, $currentPlayer, resourcesPaid:$resourcesPaid, additionalCosts:$additionalCosts);
      switch ($goesWhere) {
        case "BOTDECK":
          AddBottomDeck($cardID, $currentPlayer, $from);
          break;
        case "HAND":
          AddPlayerHand($cardID, $currentPlayer, $from);
          break;
        case "GY":
          AddGraveyard($cardID, $currentPlayer, $from);
          break;
        case "SOUL":
          AddSoul($cardID, $currentPlayer, $from);
          break;
        case "BANISH":
          BanishCardForPlayer($cardID, $currentPlayer, $from, "NA");
          break;
        case "ALLY":
          PlayAlly($cardID, $currentPlayer);
          break;
        case "RESOURCE":
          AddResources($cardID, $currentPlayer, $from, "DOWN", isExhausted:"1");
          break;
        case "MEMORY":
          AddMemory($cardID, $currentPlayer, $from, "DOWN");
          break;
        case "ATTACHTARGET":
          MZAttach($currentPlayer, $target, $cardID);
          //When you play an upgrade on this unit (e.g. Fenn Rau)
          $mzArr = explode("-", $target);
          if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
            $owner = MZPlayerID($currentPlayer, $target);
            $targetAlly = new Ally($target, $owner);
            switch($targetAlly->CardID()) {
              case "3399023235"://Fenn Rau
                if($currentPlayer == $owner) {
                  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
                  AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
                  AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give -2/-2", 1);
                  AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
                  AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
                  AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
                  AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "3399023235-2,HAND", 1);
                  AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
                  AddDecisionQueue("MZOP", $currentPlayer, "REDUCEHEALTH,2", 1);
                }
                break;
              default: break;
            }
          }
          break;
        default:
          break;
      }
    }
  }
  //Resolve Effects
  if(!$isBlock) {
    CurrentEffectPlayOrActivateAbility($cardID, $from);
    if($from != "PLAY") {
      CurrentEffectPlayAbility($cardID, $from);
      ArsenalPlayCardAbilities($cardID);
      CharacterPlayCardAbilities($cardID, $from);
    }
    $EffectContext = $cardID;
    if(!$chainClosed) {
      if(GetClassState($currentPlayer, $CS_AfterPlayedBy) != "-") AfterPlayedByAbility(GetClassState($currentPlayer, $CS_AfterPlayedBy));
      if(DefinedTypesContains($cardID, "Event", $currentPlayer) && SearchCurrentTurnEffects("3401690666", $currentPlayer, remove:true)) {
        //Relentless
        WriteLog("<span style='color:red;'>The event does nothing because of Relentless.</span>");
      }
      else {
        MainCharacterPlayCardAbilities($cardID, $from);
        AuraPlayAbilities($cardID, $from);
        PermanentPlayAbilities($cardID, $from);
        
        $abilityIndex = GetClassState($currentPlayer, $CS_AbilityIndex);
        $playIndex = GetClassState($currentPlayer, $CS_PlayIndex);
        $layerName = "PLAYABILITY";
        if($from == "PLAY" || $from == "EQUIP") {
          if(GetResolvedAbilityType($cardID) == "A") $layerName = "ACTIVATEDABILITY";
          else $layerName = "ATTACKABILITY";
        }
        if($layerName == "ATTACKABILITY") { if(HasAttackAbility($cardID)) PlayAbility($cardID, "PLAY", "0"); }
        //TODO: Fix this Relentless and first light and The Mandalorian hack
        else if($from == "PLAY" || $from == "EQUIP" || HasWhenPlayed($cardID) || $cardID == "3401690666" || $cardID == "4783554451" || $cardID == "4088c46c4d" || DefinedTypesContains($cardID, "Event", $currentPlayer) || DefinedTypesContains($cardID, "Upgrade", $currentPlayer)) AddLayer($layerName, $currentPlayer, $cardID, $from . "!" . $resourcesPaid . "!" . $target . "!" . $additionalCosts . "!" . $abilityIndex . "!" . $playIndex, "-", $uniqueID, append:true);
        else if($from != "PLAY" && $from != "EQUIP") {
          AddAllyPlayAbilityLayers($cardID, $from);
        }
      }
    }
    if($from != "PLAY") {
      $index = LastAllyIndex($currentPlayer);
      if(HasShielded($cardID, $currentPlayer, $index)) {
        $allies = &GetAllies($currentPlayer);
        AddLayer("TRIGGER", $currentPlayer, "SHIELDED", "-", "-", $allies[$index + 5], append:true);
      }
      if(HasAmbush($cardID, $currentPlayer, $index, $from)) {
        $allies = &GetAllies($currentPlayer);
        AddLayer("TRIGGER", $currentPlayer, "AMBUSH", "-", "-", $allies[$index + 5], append:true);
      }
    }
    if (!$openedChain) ResolveGoAgain($cardID, $currentPlayer, $from);
    CopyCurrentTurnEffectsFromAfterResolveEffects();
  }

  if ($CS_CharacterIndex != -1 && CanPlayAsInstant($cardID)) {
    RemoveCharacterEffects($currentPlayer, GetClassState($currentPlayer, $CS_CharacterIndex), "INSTANT");
  }
  //Now determine what needs to happen next
  SetClassState($currentPlayer, $CS_PlayIndex, -1);
  SetClassState($currentPlayer, $CS_CharacterIndex, -1);
  ProcessDecisionQueue();
}

function ProcessAttackTarget()
{
  global $defPlayer;
  $target = explode("-", GetAttackTarget());
  if ($target[0] == "THEIRAURAS") {
    $auras = &GetAuras($defPlayer);
    if (HasSpectra($auras[$target[1]])) {
      DestroyAura($defPlayer, $target[1]);
      CloseCombatChain();
      return true;
    }
  }
  else if($target[0] == "THEIRALLY") {
    $ally = new Ally($target[0] . "-" . $target[1], $defPlayer);
    AllyAttackedAbility($ally->CardID(), $target[1]);
  }
  return false;
}

function WriteGamestate()
{
  global $gameName, $playerHealths;
  global $p1Hand, $p1Deck, $p1CharEquip, $p1Resources, $p1Arsenal, $p1Items, $p1Auras, $p1Discard, $p1Pitch, $p1Banish;
  global $p1ClassState, $p1CharacterEffects, $p1Soul, $p1CardStats, $p1TurnStats, $p1Allies, $p1Permanents, $p1Settings;
  global $p2Hand, $p2Deck, $p2CharEquip, $p2Resources, $p2Arsenal, $p2Items, $p2Auras, $p2Discard, $p2Pitch, $p2Banish;
  global $p2ClassState, $p2CharacterEffects, $p2Soul, $p2CardStats, $p2TurnStats, $p2Allies, $p2Permanents, $p2Settings;
  global $landmarks, $winner, $firstPlayer, $currentPlayer, $currentRound, $turn, $actionPoints, $combatChain, $combatChainState;
  global $currentTurnEffects, $currentTurnEffectsFromCombat, $nextTurnEffects, $decisionQueue, $dqVars, $dqState;
  global $layers, $layerPriority, $mainPlayer, $lastPlayed, $chainLinks, $chainLinkSummary, $p1Key, $p2Key;
  global $permanentUniqueIDCounter, $inGameStatus, $animations, $currentPlayerActivity;
  global $p1TotalTime, $p2TotalTime, $lastUpdateTime;
  $filename = "./Games/" . $gameName . "/gamestate.txt";
  $handler = fopen($filename, "w");

  $lockTries = 0;
  while (!flock($handler, LOCK_EX) && $lockTries < 10) {
    usleep(100000); //50ms
    ++$lockTries;
  }

  if ($lockTries == 10) { fclose($handler); exit; }

  fwrite($handler, implode(" ", $playerHealths) . "\r\n");

  //Player 1
  fwrite($handler, implode(" ", $p1Hand) . "\r\n");
  fwrite($handler, implode(" ", $p1Deck) . "\r\n");
  fwrite($handler, implode(" ", $p1CharEquip) . "\r\n");
  fwrite($handler, implode(" ", $p1Resources) . "\r\n");
  fwrite($handler, implode(" ", $p1Arsenal) . "\r\n");
  fwrite($handler, implode(" ", $p1Items) . "\r\n");
  fwrite($handler, implode(" ", $p1Auras) . "\r\n");
  fwrite($handler, implode(" ", $p1Discard) . "\r\n");
  fwrite($handler, implode(" ", $p1Pitch) . "\r\n");
  fwrite($handler, implode(" ", $p1Banish) . "\r\n");
  fwrite($handler, implode(" ", $p1ClassState) . "\r\n");
  fwrite($handler, implode(" ", $p1CharacterEffects) . "\r\n");
  fwrite($handler, implode(" ", $p1Soul) . "\r\n");
  fwrite($handler, implode(" ", $p1CardStats) . "\r\n");
  fwrite($handler, implode(" ", $p1TurnStats) . "\r\n");
  fwrite($handler, implode(" ", $p1Allies) . "\r\n");
  fwrite($handler, implode(" ", $p1Permanents) . "\r\n");
  fwrite($handler, implode(" ", $p1Settings) . "\r\n");

  //Player 2
  fwrite($handler, implode(" ", $p2Hand) . "\r\n");
  fwrite($handler, implode(" ", $p2Deck) . "\r\n");
  fwrite($handler, implode(" ", $p2CharEquip) . "\r\n");
  fwrite($handler, implode(" ", $p2Resources) . "\r\n");
  fwrite($handler, implode(" ", $p2Arsenal) . "\r\n");
  fwrite($handler, implode(" ", $p2Items) . "\r\n");
  fwrite($handler, implode(" ", $p2Auras) . "\r\n");
  fwrite($handler, implode(" ", $p2Discard) . "\r\n");
  fwrite($handler, implode(" ", $p2Pitch) . "\r\n");
  fwrite($handler, implode(" ", $p2Banish) . "\r\n");
  fwrite($handler, implode(" ", $p2ClassState) . "\r\n");
  fwrite($handler, implode(" ", $p2CharacterEffects) . "\r\n");
  fwrite($handler, implode(" ", $p2Soul) . "\r\n");
  fwrite($handler, implode(" ", $p2CardStats) . "\r\n");
  fwrite($handler, implode(" ", $p2TurnStats) . "\r\n");
  fwrite($handler, implode(" ", $p2Allies) . "\r\n");
  fwrite($handler, implode(" ", $p2Permanents) . "\r\n");
  fwrite($handler, implode(" ", $p2Settings) . "\r\n");

  fwrite($handler, implode(" ", $landmarks) . "\r\n");
  fwrite($handler, $winner . "\r\n");
  fwrite($handler, $firstPlayer . "\r\n");
  fwrite($handler, $currentPlayer . "\r\n");
  fwrite($handler, $currentRound. "\r\n");
  fwrite($handler, implode(" ", $turn) . "\r\n");
  fwrite($handler, $actionPoints . "\r\n");
  fwrite($handler, implode(" ", $combatChain) . "\r\n");
  fwrite($handler, implode(" ", $combatChainState) . "\r\n");
  fwrite($handler, implode(" ", $currentTurnEffects) . "\r\n");
  fwrite($handler, implode(" ", $currentTurnEffectsFromCombat) . "\r\n");
  fwrite($handler, implode(" ", $nextTurnEffects) . "\r\n");
  fwrite($handler, implode(" ", $decisionQueue) . "\r\n");
  fwrite($handler, implode(" ", $dqVars) . "\r\n");
  fwrite($handler, implode(" ", $dqState) . "\r\n");
  fwrite($handler, implode(" ", $layers) . "\r\n");
  fwrite($handler, implode(" ", $layerPriority) . "\r\n");
  fwrite($handler, $mainPlayer . "\r\n");
  fwrite($handler, implode(" ", $lastPlayed) . "\r\n");
  fwrite($handler, count($chainLinks) . "\r\n");
  for ($i = 0; $i < count($chainLinks); ++$i) {
    fwrite($handler, implode(" ", $chainLinks[$i]) . "\r\n");
  }
  fwrite($handler, implode(" ", $chainLinkSummary) . "\r\n");
  fwrite($handler, $p1Key . "\r\n");
  fwrite($handler, $p2Key . "\r\n");
  fwrite($handler, $permanentUniqueIDCounter . "\r\n");
  fwrite($handler, $inGameStatus . "\r\n"); //Game status -- 0 = START, 1 = PLAY, 2 = OVER
  fwrite($handler, implode(" ", $animations) . "\r\n"); //Animations
  fwrite($handler, $currentPlayerActivity . "\r\n"); //Current Player activity status -- 0 = active, 2 = inactive
  fwrite($handler, "\r\n"); //Unused
  fwrite($handler, "\r\n"); //Unused
  fwrite($handler, $p1TotalTime . "\r\n"); //Player 1 total time
  fwrite($handler, $p2TotalTime . "\r\n"); //Player 2 total time
  fwrite($handler, $lastUpdateTime . "\r\n"); //Last update time
  fclose($handler);
}

function AddEvent($type, $value)
{
  global $events;
  $events[] = $type;
  $events[] = $value;
}

?>
