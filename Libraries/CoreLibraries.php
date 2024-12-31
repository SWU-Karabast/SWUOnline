<?php
function DelimStringContains($str, $find)
{
  $arr = explode(",", $str);
  for($i=0; $i<count($arr); ++$i)
  {
    if($arr[$i] == $find) return true;
  }
  return false;
}

function DelimStringShares(string $str1, string $str2): bool {
  $arr1 = explode(",", $str1);
  $arr2 = explode(",", $str2);
  return ArrayShares($arr1, $arr2);
}

function ArrayShares(array $list1, array $list2): bool {
  $commonItems = array_intersect($list1, $list2);
  return !empty($commonItems);
}

function RandomizeArray(&$arr, $skipSeed = false){
  $n = count($arr);
  for ($i = $n - 1; $i > 0; $i--) {
    $j = $skipSeed ? random_int(0, $i) : mt_rand(0, $i);
    [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
  }
}

function GetRandom($low=-1, $high=-1)
{
  global $randomSeeded;
  if(!$randomSeeded) SeedRandom();

  if($low == -1) return mt_rand();
  return mt_rand($low, $high);
}

function SeedRandom()
{
  global $randomSeeded, $currentRound, $turn, $currentPlayer, $layers, $combatChain;
  $seedString = $currentRound. implode("", $turn) . $currentPlayer;
  if(count($layers) > 0) for($i=0; $i<count($layers); ++$i) $seedString .= $layers[$i];
  if(count($combatChain) > 0) for($i=0; $i<count($combatChain); ++$i) $seedString .= $combatChain[$i];

  $char = &GetPlayerCharacter(1);
  for($i=0; $i<count($char); ++$i) $seedString .= $char[$i];
  $char = &GetPlayerCharacter(2);
  for($i=0; $i<count($char); ++$i) $seedString .= $char[$i];

  $banish = &GetBanish(1);
  for($i=0; $i<count($banish); ++$i) $seedString .= $banish[$i];
  $banish = &GetBanish(2);
  for($i=0; $i<count($banish); ++$i) $seedString .= $banish[$i];

  $discard = &GetDiscard(1);
  for($i=0; $i<count($discard); ++$i) $seedString .= $discard[$i];
  $banish = &GetDiscard(2);
  for($i=0; $i<count($discard); ++$i) $seedString .= $discard[$i];

  $deck = &GetDeck(1);
  for($i=0; $i<count($deck); ++$i) $seedString .= $deck[$i];
  $banish = &GetDeck(2);
  for($i=0; $i<count($deck); ++$i) $seedString .= $deck[$i];

  $seedString = hash("sha256", $seedString);
  mt_srand(crc32($seedString));
  $randomSeeded = true;
}
?>
