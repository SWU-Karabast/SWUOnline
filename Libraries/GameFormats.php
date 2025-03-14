<?php
class Formats {//keep these to 8 chars exactly for predictable SHMOP cache lengths
  //basic formats
  public static $PremierFormat = "premierf";
  public static $PremierStrict = "prstrict";
  public static $OpenFormat = "openform";
  public static $PreviewFormat = "previewf";
  //fun formats
  public static $PadawanFormat = "padawanf";
  public static $SandcrawlerFormat = "sndcrawl";
  public static $GalacticCivilWar = "civilwar";
  public static $CloneWars = "clonewar";
  public static $GreyJedi = "greyjedi";//TODO
  public static $GroundAssault = "bothgrnd";//TODO
  public static $AllWingsReportIn = "bothspce";//TODO
  public static $NowThereAreThreeOfThem = "nta3othm";//TODO
  //public static $NoLuck = "no_luckf";//TODO

  public static function FromCode($code) {
    $code = intval($code ?? 0);
    return match($code) {
      0 => Formats::$PremierFormat,
      1 => Formats::$PremierStrict,
      2 => Formats::$PreviewFormat,
      3 => Formats::$OpenFormat,
      4 => Formats::$PadawanFormat,
      5 => Formats::$SandcrawlerFormat,
      //6 =>
      //7 =>
      8 => Formats::$GalacticCivilWar,
      9 => Formats::$CloneWars,
      10 => Formats::$GreyJedi,
      11 => Formats::$GroundAssault,
      12 => Formats::$AllWingsReportIn,
      13 => Formats::$NowThereAreThreeOfThem,
      default => Formats::$PremierFormat,
    };
  }
}

class DeckValidation {
  private ValidationCode $_errorCode = ValidationCode::Valid;
  private array $_invalidCards = [];
  private array $_tooManyCopies = [];
  private string $_cardString = "";
  private string $_sideboardString = "";

  public function __construct($errorCode, $invalidCards, $tooManyCopies, $cardString, $sideboardString) {
    $this->_errorCode = $errorCode;
    $this->_invalidCards = $invalidCards;
    $this->_tooManyCopies = $tooManyCopies;
    $this->_cardString = $cardString;
    $this->_sideboardString = $sideboardString;
  }

  public function Error($format) {
    $display = str_replace(" Format", "", FormatDisplayName($format));
    return match($this->_errorCode) {
      ValidationCode::DeckSize => "⚠️ Your deck does not meet the minimum size requirement",
      ValidationCode::DeckMax => "⚠️ Your deck exceeds the maximum size",
      ValidationCode::SideboardSize => "⚠️ Your sideboard exceeds the maximum size",
      ValidationCode::FormatInvalid => "⚠️ Your deck contains cards that are not allowed in the $display Format",
      ValidationCode::TooManyCopies => "⚠️ Your deck is valid, but it contains too many copies of one or more cards",
      ValidationCode::TooFewCopies => "⚠️ Your deck is valid, but it contains too few copies of one or more cards",
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

  public function RejectionDetail($format) {
    return match($format) {
      Formats::$PadawanFormat => "Only Common cards are allowed, with the exception of Rare Leaders. No Rare Bases are allowed, and no Special rarity cards unless they have a Common variant.",
      Formats::$SandcrawlerFormat => "Only Uncommon and Common cards are allowed, with the exception of Leaders. No Rare Bases are allowed, and any Special rarity cards that don't have a Rare or Legendary variant are allowed.",
      Formats::$GalacticCivilWar => "Only cards with the Rebel or Imperial traits are allowed.",
      Formats::$CloneWars => "Only cards with the Republic or Separatist traits are allowed.",
      default => "",
    };
  }
}

function ValidateDeck($format, $usesUuid, $leader, $base, $deckArr, $sideboardArr): DeckValidation {
  $previewSet = "LEG";
  $deckSize = 0;
  $cards = "";
  $invalidCards = [];
  $totalCopies = [];
  $tooManyCopies = [];
  for($i=0; $i<count($deckArr); ++$i) {
    if($usesUuid) $deckArr[$i]->id = CardIDLookup($deckArr[$i]->id);
    $deckArr[$i]->id = CardIDOverride($deckArr[$i]->id);
    $cardID = UUIDLookup($deckArr[$i]->id);
    $cardID = CardUUIDOverride($cardID);
    $deckSize += $deckArr[$i]->count;
    if(!IsAllowed($cardID, $format) && !in_array($cardID, $invalidCards)) $invalidCards[] = $cardID;
    $totalCopies[] = ["id" => $cardID, "count" => $deckArr[$i]->count];
    for($j=0; $j<$deckArr[$i]->count; ++$j) {
      if($cards != "") $cards .= " ";
      $cards .= $cardID;
    }
  }
  if($deckSize < (50 + DeckModifier($base)) && $format != Formats::$OpenFormat) {
    return new DeckValidation(ValidationCode::DeckSize, [], [], "", "");
  }
  $sideboardSize = 0;
  $sideboardCards = "";
  if($sideboardArr == null) $sideboardArr = [];
  for($i=0; $i<count($sideboardArr); ++$i) {
    if($usesUuid) $sideboardArr[$i]->id = CardIDLookup($sideboardArr[$i]->id);
    $sideboardArr[$i]->id = CardIDOverride($sideboardArr[$i]->id);
    $cardID = CardUUIDOverride(UUIDLookup($sideboardArr[$i]->id));
    $sideboardSize += $sideboardArr[$i]->count;
    if(CardSet($cardID) == $previewSet && $format != Formats::$OpenFormat) {
      if(!in_array($cardID, $invalidCards)) $invalidCards[] = $cardID;
    }
    if(!IsAllowed($cardID, $format) && !in_array($cardID, $invalidCards)) $invalidCards[] = $cardID;
    $found = false;
    for($j=0; $j<count($totalCopies); ++$j) {
      if($totalCopies[$j]["id"] == $cardID) {
        $totalCopies[$j]["count"] += $sideboardArr[$i]->count;
        $found = true;
        break;
      }
    }
    if(!$found) $totalCopies[] = ["id" => $cardID, "count" => $sideboardArr[$i]->count];
    for($j=0; $j<$sideboardArr[$i]->count; ++$j) {
      if($sideboardCards != "") $sideboardCards .= " ";
      $sideboardCards .= $cardID;
    }
  }
  for($i=0; $i<count($totalCopies); ++$i) {
    if(CardExceedsNumCopies($format, $totalCopies[$i]["id"], $totalCopies[$i]["count"])) {
      $tooManyCopies[] = $totalCopies[$i]["id"];
    }
  }
  if($format == Formats::$PremierStrict && $sideboardSize > 10) {
    return new DeckValidation(ValidationCode::SideboardSize, [], [], "", "");
  }
  if(count($invalidCards) > 0) {
    return new DeckValidation(ValidationCode::FormatInvalid, $invalidCards, $tooManyCopies, "", "");
  }
  if(count($tooManyCopies) > 0) {
    return new DeckValidation(ValidationCode::TooManyCopies, $invalidCards, $tooManyCopies, "", "");
  }
  return new DeckValidation(ValidationCode::Valid, [], [], $cards, $sideboardCards);
}

function DeckModifier($base): int {
  return match($base) {
    "4301437393" => -5,//Thermal Oscillator
    "4028826022" => 10,//Data Vault
    default => 0,
  };
}

function IsAllowed($cardID, $format): bool {
  $banned = [
    "4626028465"//Boba Fett Leader SOR
  ];
  if($format == Formats::$OpenFormat) return true;
  if(!CardInRotation($format, $cardID)) return false;
  return match($format) {
    //All cards in rotation are allowed except for banned cards
    Formats::$PremierFormat,
    Formats::$PremierStrict,
    Formats::$PreviewFormat
      => !in_array($cardID, $banned)
      ,
    //Only Commons, any unbanned leader, no Rare bases, no Special cards unless they have a Common variant
    Formats::$PadawanFormat => (CardRarity($cardID) == "Common"
        || CardIDIsLeader($cardID) && CardRarity($cardID) == "Rare")
      && !in_array($cardID, $banned)
      && !IsRareBase($cardID)
      ,
    //Only Uncommons and Commons, any unbanned leader, no Rare bases, any Special cards that don't have a rare or legendary variant
    Formats::$SandcrawlerFormat => CardRarity ($cardID) != "Rare" && CardRarity($cardID) != "Legendary"
      && !in_array($cardID, $banned)
      && !IsRareBase($cardID)
      ,
    Formats::$GalacticCivilWar => DefinedCardType($cardID) == "Base"
        || TraitContains($cardID, "Rebel") || TraitContains($cardID, "Imperial")
      ,
    Formats::$CloneWars => !DefinedCardType($cardID) == "Base"
        || TraitContains($cardID, "Republic") || TraitContains($cardID, "Separatist")
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

function CardInRotation($format, $cardID): bool {
  $premierRotation = ["SOR", "SHD", "TWI", "JTL", ];
  $padawanRotation = $premierRotation;
  $sandcrawlerRotation = $premierRotation;
  $civiWarRotation = $premierRotation;
  $cloneWarRotation = $premierRotation;
  $previewRotation = array_merge($premierRotation, ["LEG"]);
  return match($format) {
    Formats::$PremierFormat, Formats::$PremierStrict => in_array(CardSet($cardID), $premierRotation),
    Formats::$PadawanFormat => in_array(CardSet($cardID), $padawanRotation),
    Formats::$SandcrawlerFormat => in_array(CardSet($cardID), $sandcrawlerRotation),
    Formats::$PreviewFormat => in_array(CardSet($cardID), $previewRotation),
    Formats::$GalacticCivilWar => in_array(CardSet($cardID), $civiWarRotation),
    Formats::$CloneWars => in_array(CardSet($cardID), $cloneWarRotation),
    Formats::$OpenFormat => true,
    default => false,
  };
}

function CardExceedsNumCopies($format, $cardID, $count): bool {
  if($format == Formats::$OpenFormat) return false;
  //restrictions for specific formats
  if($format == Formats::$NowThereAreThreeOfThem)
    return $count > 10;
  //all other formats
  switch($cardID) {
    case "2177194044"://Swarming Vulture Droid
      return $count > 15;
    default:
      return $count > 3;
  }
}

function CardSubceedsNumCopies($format, $cardID, $count): bool {
  //restrictions for specific formats
  if($format == Formats::$NowThereAreThreeOfThem)
    return $count < 10;
  //all other formats
  return false;
}

function FormatDisplayName($format) {
  return match($format) {
    Formats::$PremierFormat => "Premier",
    Formats::$PremierStrict => "Premier Strict",
    Formats::$PreviewFormat => "Preview",
    Formats::$PadawanFormat => "Padawan",
    Formats::$SandcrawlerFormat => "Sandcrawler",
    Formats::$GalacticCivilWar => "Galactic Civil War",
    Formats::$CloneWars => "Clone Wars",
    Formats::$OpenFormat => "Open Format",
    default => "Unknown",
  };
}

enum ValidationCode: int {
  case Valid = 0;
  case DeckSize = 1;
  case SideboardSize = 2;
  case FormatInvalid = 3;
  case DeckMax = 4;
  case TooManyCopies = 5;
  case TooFewCopies = 6;
}


?>