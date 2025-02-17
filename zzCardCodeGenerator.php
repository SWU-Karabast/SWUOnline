<?php
  //EN : English
  //ES : Spanish
  //DE : German
  //FR : French
  //IT : Italian

  include './zzImageConverter.php';

  $hasMoreData = true;
  $page = 1;
  $titleArray = [];
  $subtitleArray = [];
  $costArray = [];
  $hpArray = [];
  $powerArray = [];
  $upgradeHPArray = [];
  $upgradePowerArray = [];
  $aspectsArray = [];
  $traitsArray = [];
  $arenasArray = [];
  $uuidLookupArray = [];
  $typeArray = [];
  $type2Array = [];
  $uniqueArray = [];
  $hasPlayArray = [];
  $hasDestroyedArray = [];
  $setArray = [];
  $cardIDArray = [];
  
  $language = "EN";

  while ($hasMoreData)
  {
    $jsonUrl = "https://admin.starwarsunlimited.com/api/cards?locale=" . $language. "&pagination[page]=" . $page . "&pagination[pageSize]=100&filters[variantOf][id][\$null]=true";
    $curl = curl_init();
    $headers = array(
      "Content-Type: application/json",
    );
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($curl, CURLOPT_URL, $jsonUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $cardData = curl_exec($curl);
    curl_close($curl);


    $response = json_decode($cardData);
    $meta = $response->meta;

    for ($i = 0; $i < count($response->data); ++$i)
    {
      $card = $response->data[$i];
      $card = $card->attributes;

      if($card->variantOf->data != null) continue;

      $cardNumber = $card->cardNumber;
      if($cardNumber < 10) $cardNumber = "00" . $cardNumber;
      else if($cardNumber < 100) $cardNumber = "0" . $cardNumber;
      $set = $card->expansion->data->attributes->code;
      $cardID= $set . "_" . $cardNumber;
      switch($card->cardUid) {
        case "3463348370"://Battle droid
          $cardID = "TWI_T01";
          break;
        case "3941784506"://Clone Trooper
          $cardID = "TWI_T02";
          break;
      }

      AddToArrays($cardID, $card->cardUid);

      $definedType = $card->type->data->attributes->name;
      if($definedType == "Token Unit") $definedType = "Unit";
      $imageUrl = $card->artFront->data->attributes->formats->card->url;

      //$imageUrl = "https://swudb.com/cards/" . $set . "/" . $cardNumber . ".png";


      CheckImage($card->cardUid, $imageUrl, $language,  $definedType, set:$set);
      if($card->artBack->data != null) {
        $type2 = $card->type2->data == null ? "" : $card->type2->data->attributes->name;
        if($type2 == "Leader Unit" || $type2 == "Leader Unité" || $type2 = "Unidad Líder" || $type2 == "Anführer-Einheit" || $type2 == "Unità Leader") $definedType = "Unit"; 
        $imageUrl = $card->artBack->data->attributes->formats->card->url;
        echo("$imageUrl");
        echo("  ");
        $arr = explode("_", $imageUrl);
        $arr = explode(".", $arr[count($arr)-1]);
        $uuid = $arr[0];
        CheckImage($uuid, $imageUrl, $language, $definedType, isBack:true, set:$set);
        AddToArrays($cardID, $uuid);
      }
    }

    echo("Page: " . $meta->pagination->page . "/" . $meta->pagination->pageCount . "<BR>");
    ++$page;
    $hasMoreData = $page <= $meta->pagination->pageCount;
  }
  /*
  subtypes - array
  */


  if (!is_dir("./GeneratedCode")) mkdir("./GeneratedCode", 777, true);

  if($language == "EN"){
    $generateFilename = "./GeneratedCode/GeneratedCardDictionaries.php";
    $handler = fopen($generateFilename, "w");

    fwrite($handler, "<?php\r\n");

    $DEFAULT_CARD_TITLE = "";
    $DEFAULT_CARD_SUBTITLE = "";
    $DEFAULT_CARD_COST = 0;
    $DEFAULT_CARD_HP = 0;
    $DEFAULT_CARD_POWER = 0;
    $DEFAULT_CARD_UPGRADE_HP = 0;
    $DEFAULT_CARD_UPGRADE_POWER = 0;
    $DEFAULT_CARD_ASPECTS = "";
    $DEFAULT_CARD_TRAITS = "";
    $DEFAULT_CARD_ARENAS = "";
    $DEFAULT_CARD_TYPE = "Unit";
    $DEFAULT_CARD_TYPE2 = "";
    $DEFAULT_CARD_UNIQUE = 0;
    $DEFAULT_CARD_HAS_WHEN_PLAYED = false;
    $DEFAULT_CARD_HAS_WHEN_DESTROYED = false;
    $DEFAULT_CARD_SET = "";
    $DEFAULT_CARD_UUID = "";

    GenerateFunction($titleArray, $handler, "CardTitle", true, $DEFAULT_CARD_TITLE);
    GenerateFunction($subtitleArray, $handler, "CardSubtitle", true, $DEFAULT_CARD_SUBTITLE);
    GenerateFunction($costArray, $handler, "CardCost", false, $DEFAULT_CARD_COST);
    GenerateFunction($hpArray, $handler, "CardHPDictionary", false, $DEFAULT_CARD_HP);
    GenerateFunction($powerArray, $handler, "CardPower", false, $DEFAULT_CARD_POWER);
    GenerateFunction($upgradeHPArray, $handler, "CardUpgradeHPDictionary", false, $DEFAULT_CARD_UPGRADE_HP);
    GenerateFunction($upgradePowerArray, $handler, "CardUpgradePower", false, $DEFAULT_CARD_UPGRADE_POWER);
    GenerateFunction($aspectsArray, $handler, "CardAspects", true, $DEFAULT_CARD_ASPECTS);
    GenerateFunction($traitsArray, $handler, "CardTraits", true, $DEFAULT_CARD_TRAITS);
    GenerateFunction($arenasArray, $handler, "CardArenas", true, $DEFAULT_CARD_ARENAS);
    GenerateFunction($typeArray, $handler, "DefinedCardType", true, $DEFAULT_CARD_TYPE);
    GenerateFunction($type2Array, $handler, "DefinedCardType2", true, $DEFAULT_CARD_TYPE2);
    GenerateFunction($uniqueArray, $handler, "CardIsUnique", false, $DEFAULT_CARD_UNIQUE);
    GenerateFunction($hasPlayArray, $handler, "HasWhenPlayed", false, $DEFAULT_CARD_HAS_WHEN_PLAYED);
    GenerateFunction($hasDestroyedArray, $handler, "HasWhenDestroyed", false, $DEFAULT_CARD_HAS_WHEN_DESTROYED);
    GenerateFunction($setArray, $handler, "CardSet", true, $DEFAULT_CARD_SET);
    GenerateFunction($uuidLookupArray, $handler, "UUIDLookup", true, $DEFAULT_CARD_UUID);
    GenerateFunction($cardIDArray, $handler, "CardIDLookup", true, $DEFAULT_CARD_UUID);
    GenerateCardTitles($titleArray, $handler);
    GenerateUnimplementedCards($handler);
    fwrite($handler, "?>");

    fclose($handler);


    $generateFilename = "./GeneratedCode/GeneratedCardDictionaries.js";
    $handler = fopen($generateFilename, "w");
    GenerateFunction($titleArray, $handler, "CardTitle", true, "", language:"js");
    fclose($handler);
  }

  function GenerateUnimplementedCards($handler) {
    $unimplementedCards = [];
    $files = glob('./UnimplementedCards/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    foreach($files as $file) {
      $filename = basename($file);
      $cardId = pathinfo($filename, PATHINFO_FILENAME);
      $unimplementedCards[$cardId] = true;
    }
    
    fwrite($handler, "function IsUnimplemented(\$cardID) {\r\n");
    fwrite($handler, "  \$unimplementedCards = " . var_export($unimplementedCards, true) . ";\r\n");
    fwrite($handler, "  return isset(\$unimplementedCards[\$cardID]);\r\n");
    fwrite($handler, "}\r\n");
  }

  
  function GenerateCardTitles($titleArray, $handler) {
    echo "Generating CardTitles<br>";
    $uniqueTitles = array_unique($titleArray);
    sort($uniqueTitles);
    $strTitles = implode("|", $uniqueTitles);
    fwrite($handler, "function CardTitles() {\r\n");
    fwrite($handler, "  return " . var_export($strTitles, true) . ";\r\n");
    fwrite($handler, "}\r\n\r\n");
  }

  function GenerateFunction($cardArray, $handler, $functionName, $isString, $defaultValue, $language = "PHP")
  {
    echo "Generating " . $functionName . " (" . $language . ")<br>";

    if($language == "PHP") {
      fwrite($handler, "function " . $functionName . "(\$cardID) {\r\n");
      fwrite($handler, "  \$data = " . var_export($cardArray, true) . ";\r\n");
      fwrite($handler, "  return isset(\$data[\$cardID]) ? \$data[\$cardID] : " . ($isString ? "\"" . $defaultValue . "\"" : var_export($defaultValue, true)) . ";\r\n");
      fwrite($handler, "}\r\n\r\n");
    } else if($language == "js") {
      fwrite($handler, "function " . $functionName . "(cardID) {\r\n");
      fwrite($handler, "  const data = " . json_encode($cardArray) . ";\r\n");
      fwrite($handler, "  return data[cardID] !== undefined ? data[cardID] : " . ($isString ? "\"" . $defaultValue . "\"" : var_export($defaultValue, true)) . ";\r\n");
      fwrite($handler, "}\r\n\r\n");
    }
  }

  function AddToArrays($cardID, $uuid)
  {
    global $uuidLookupArray, $titleArray, $subtitleArray, $costArray, $hpArray, $powerArray, $upgradeHPArray, $upgradePowerArray;
    global $typeArray, $type2Array, $uniqueArray, $card, $aspectsArray, $traitsArray, $arenasArray, $hasPlayArray;
    global $hasDestroyedArray, $setArray, $cardIDArray;
    global $DEFAULT_CARD_TITLE, $DEFAULT_CARD_SUBTITLE, $DEFAULT_CARD_COST, $DEFAULT_CARD_HP, $DEFAULT_CARD_POWER, $DEFAULT_CARD_UPGRADE_HP;
    global $DEFAULT_CARD_UPGRADE_POWER, $DEFAULT_CARD_ASPECTS, $DEFAULT_CARD_TRAITS, $DEFAULT_CARD_ARENAS, $DEFAULT_CARD_TYPE, $DEFAULT_CARD_TYPE2;
    global $DEFAULT_CARD_UNIQUE, $DEFAULT_CARD_HAS_WHEN_PLAYED, $DEFAULT_CARD_HAS_WHEN_DESTROYED, $DEFAULT_CARD_SET, $DEFAULT_CARD_UUID;

    // UUID Lookup
    if ($uuid != "8752877738" && $uuid != "2007868442" && $uuid != $DEFAULT_CARD_UUID && !isset($uuidLookupArray[$cardID])) {
      $uuidLookupArray[$cardID] = $uuid;
    }

    // Title
    if ($card->title && $card->title != $DEFAULT_CARD_TITLE) {
      $titleArray[$uuid] = str_replace('"', "'", $card->title);
    }

    // Subtitle
    if ($card->subtitle && $card->subtitle != $DEFAULT_CARD_SUBTITLE) {
      $subtitleArray[$uuid] = str_replace('"', "'", $card->subtitle);
    }

    // Cost
    if ($card->cost && $card->cost != $DEFAULT_CARD_COST) {
      $costArray[$uuid] = $card->cost;
    }

    // HP
    if ($card->hp && $card->hp != $DEFAULT_CARD_HP) {
      $hpArray[$uuid] = $card->hp;
    }

    // Power
    if ($card->power && $card->power != $DEFAULT_CARD_POWER) {
      $powerArray[$uuid] = $card->power;
    }

    // Upgrade HP
    if ($card->upgradeHp && $card->upgradeHp != $DEFAULT_CARD_UPGRADE_HP) {
      $upgradeHPArray[$uuid] = $card->upgradeHp;
    }

    // Upgrade Power
    if ($card->upgradePower && $card->upgradePower != $DEFAULT_CARD_UPGRADE_POWER) {
      $upgradePowerArray[$uuid] = $card->upgradePower;
    }

    // Type
    $definedType = $card->type->data->attributes->name;
    if ($definedType == "Token Unit") $definedType = "Unit";
    else if($definedType == "Token Upgrade") $definedType = "Upgrade";
    if ($definedType && $definedType != $DEFAULT_CARD_TYPE) {
      $typeArray[$uuid] = $definedType;
    }

    // Type 2
    $definedType2 = $card->type2->data ? $card->type2->data->attributes->name : "";
    if ($definedType2 == "Leader Unit" || $definedType2 == "Leader Unité" || $definedType2 == "Unidad Líder" || $definedType2 == "Anführer-Einheit" || $definedType2 == "Unità Leader") $definedType2 = "Unit";
    if ($definedType2 && $definedType2 != $DEFAULT_CARD_TYPE2) {
      $type2Array[$uuid] = $definedType2;
    }

    // Set
    $set = $card->expansion->data->attributes->code;
    if ($set && $set != $DEFAULT_CARD_SET) {
      $setArray[$uuid] = $set;
    }

    // Card ID
    if ($cardID && $cardID != $DEFAULT_CARD_UUID) {
      $cardIDArray[$uuid] = $cardID;
    }

    // Unique
    $unique = $card->unique == "true" ? 1 : 0;
    if ($unique != $DEFAULT_CARD_UNIQUE) {
      $uniqueArray[$uuid] = $unique;
    }

    // Has When Played
    if ($card->text && (str_contains($card->text, "When Played") || str_contains($card->text, "When played"))) {
      $hasPlayArray[$uuid] = true;
    }

    // Has When Destroyed
    if ($card->text && (str_contains($card->text, "When Defeated:") || str_contains($card->text, "When defeated:"))) {
      $hasDestroyedArray[$uuid] = true;
    }

    // Aspects
    $aspects = [];
    foreach ($card->aspects->data as $aspect) {
      $name = $aspect->attributes->name;
      if ($name && $name != $DEFAULT_CARD_ASPECTS) {
        $aspects[] = $name;
      }
    }
    foreach ($card->aspectDuplicates->data as $aspect) {
      $name = $aspect->attributes->name;
      if ($name && $name != $DEFAULT_CARD_ASPECTS) {
        $aspects[] = $name;
      }
    }
    if (count($aspects) > 0) {
      $aspectsArray[$uuid] = implode(",", $aspects);
    }

    // Traits
    $traits = [];
    foreach ($card->traits->data as $trait) {
      $name = $trait->attributes->name;
      if ($name && $name != $DEFAULT_CARD_TRAITS) {
        $traits[] = $name;
      }
    }
    if (count($traits) > 0) {
      $traitsArray[$uuid] = implode(",", $traits);
    }

    // Arenas
    $arenas = [];
    foreach ($card->arenas->data as $arena) {
      $name = $arena->attributes->name;
      if ($name && $name != $DEFAULT_CARD_ARENAS) {
        $arenas[] = $name;
      }
    }
    if (count($arenas) > 0) {
      $arenasArray[$uuid] = implode(",", $arenas);
    }
  }

?>
