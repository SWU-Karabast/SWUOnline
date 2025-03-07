<?php
class Formats {
  public static $PremierFormat = "premierf";
  public static $OpenFormat = "openform";
  public static $PadawanFormat = "padawanf";
  public static $SandcrawlerFormat = "sndcrawl";
}

class DeckValidation {
  private ValidationCode $_errorCode = ValidationCode::Valid;
  private array $_invalidCards = [];
  private string $_cardString = "";
  private string $_sideboardString = "";

  public function __construct($errorCode, $invalidCards, $cardString, $sideboardString) {
    $this->_errorCode = $errorCode;
    $this->_invalidCards = $invalidCards;
    $this->_cardString = $cardString;
    $this->_sideboardString = $sideboardString;
  }

  public function Error() {
    return match($this->_errorCode) {
      ValidationCode::DeckSize => "⚠️ Your deck does not meet the minimum size requirement",
      ValidationCode::SideboardSize => "⚠️ Your sideboard exceeds the maximum size",
      ValidationCode::FormatInvalid => "⚠️ Your deck contains cards that are not allowed in the ??? Format",
      default => "",
    };
  }

  public function InvalidCards() {
    return $this->_invalidCards;
  }

  public function IsValid() {
    return $this->_errorCode == ValidationCode::Valid;
  }

  public function CardString() {
    return $this->_cardString;
  }

  public function SideboardString() {
    return $this->_sideboardString;
  }
}

function ValidateDeck($format, $usesUuid, $leader, $base, $deckArr, $sideboardArr): DeckValidation {
  $previewSet = "LEG";
  $deckSize = 0;
  $cards = "";
  $invalidCards = [];
    for($i=0; $i<count($deckArr); ++$i) {
    if($usesUuid) $deckArr[$i]->id = CardIDLookup($deckArr[$i]->id);
    $deckArr[$i]->id = CardIDOverride($deckArr[$i]->id);
    $cardID = UUIDLookup($deckArr[$i]->id);
    $cardID = CardUUIDOverride($cardID);
    $deckSize += $deckArr[$i]->count;
    if(CardSet($cardID) == $previewSet && $format != Formats::$OpenFormat) {
      if(!in_array($cardID, $invalidCards)) $invalidCards[] = $cardID;
    }
    if(IsNotAllowed($cardID, $format)) {
      if(!in_array($cardID, $invalidCards)) $invalidCards[] = $cardID;
    }
    //if not common oor uncommon
    //iif not common
    for($j=0; $j<$deckArr[$i]->count; ++$j) {
      if($cards != "") $cards .= " ";
      $cards .= $cardID;
    }
  }
  if($deckSize < (50 + DeckModifier($base)) && $format != Formats::$OpenFormat) {
    return new DeckValidation(ValidationCode::DeckSize, [], "", "");
  }
  $sidebaordize = 0;
  $sideboardCards = "";
  for($i=0; $i<count($sideboardArr); ++$i) {
    if($usesUuid) $sideboardArr[$i]->id = CardIDLookup($sideboardArr[$i]->id);
    $sideboardArr[$i]->id = CardIDOverride($sideboardArr[$i]->id);
    $cardID = CardUUIDOverride(UUIDLookup($sideboardArr[$i]->id));
    $sidebaordize += $sideboardArr[$i]->count;
    if(CardSet($cardID) == $previewSet && $format != Formats::$OpenFormat) {
      if(!in_array($cardID, $invalidCards)) $invalidCards[] = $cardID;
    }
    if(IsNotAllowed($cardID, $format)) {
      if(!in_array($cardID, $invalidCards)) $invalidCards[] = $cardID;
    }
    for($j=0; $j<$sideboardArr[$i]->count; ++$j) {
      if($sideboardCards != "") $sideboardCards .= " ";
      $sideboardCards .= $cardID;
    }
    //if not common oor uncommon
    //iif not common
    if($sidebaordize > 10 && $format != Formats::$OpenFormat) {
      return new DeckValidation(ValidationCode::SideboardSize, [], "", "");
    }
  }
  if(count($invalidCards) > 0) {
    return new DeckValidation(ValidationCode::FormatInvalid, $invalidCards, "", "");
  }
  return new DeckValidation(ValidationCode::Valid, [], $cards, $sideboardCards);
}

function DeckModifier($base): int {
  return match($base) {
    "4301437393" => -5,//Thermal Oscillator
    "4028826022" => 10,//Data Vault
    default => 0,
  };
}

function IsNotAllowed($cardID, $format): bool {
  $premierRotation = ["SOR", "SHD", "TWI", "JTL", ];
  $padawanRotation = $premierRotation;
  $sandcrawlerRotation = $premierRotation;
  $banned = [
    "4626028465"//Boba Fett Leader SOR
  ];
  return match($format) {
    //Standard Rotation
    Formats::$PremierFormat => !in_array(CardSet($cardID), $premierRotation)
      || in_array($cardID, $banned)
      ,
    //Only Commons, any unbanned leader, no rare bases, no special cards unless they have a common variant
    Formats::$PadawanFormat => CardRarity($cardID) != "Common"
      || !in_array(CardSet($cardID), $padawanRotation)
      || in_array($cardID, $banned)
      || IsRareBase($cardID)
      ,
    //Only Uncommons and Commons, any unbanned leader, no rare bases, any special cards that aren't convention exclusives
    Formats::$SandcrawlerFormat => CardRarity ($cardID) == "Rare" || CardRarity($cardID) == "Legendary"
      || !in_array(CardSet($cardID), $sandcrawlerRotation )
      || in_array($cardID, $banned)
      || IsRareBase($cardID)
      ,
    default => false,
  };
}

function IsRareBase($cardID) {
  return match($cardID) {
    "2429341052"//Security Complex
    ,"8327910265"//Energy Conversion Lab
    ,"1393827469"//Tarkintown
    ,"2569134232"//Jedha City
    ,"6594935791"//Pau City
    ,"8589863038"//Droid Manufactory
    ,"6854189262"//Shadow Collective Camp
    ,"9652861741"//Petranaki Arena
    ,"1029978899"//Colossus
    ,"4028826022"//Data Vault
    ,"4301437393"//Thermal Oscillator
    ,"9586661707"//Nabat Village
    ,"1672815328"//Lake Country
      => true,
    default => false
  };
}

enum ValidationCode: int {
  case Valid = 0;
  case DeckSize = 1;
  case SideboardSize = 2;
  case FormatInvalid = 3;
}


?>