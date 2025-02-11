<?php
include_once "GeneratedCode/GeneratedCardDictionaries.php" ;
// get query params from url
$cardID = $_GET['cardID'];

echo "<h2>Generated Code V2</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><td>Title</td><td>" . CardTitle($cardID) . "</td><td>" . gettype(CardTitle($cardID)) . "</td></tr>";
echo "<tr><td>Subtitle</td><td>" . CardSubtitle($cardID) . "</td><td>" . gettype(CardSubtitle($cardID)) . "</td></tr>";
echo "<tr><td>Cost</td><td>" . CardCost($cardID) . "</td><td>" . gettype(CardCost($cardID)) . "</td></tr>";
echo "<tr><td>HP</td><td>" . CardHPDictionary($cardID) . "</td><td>" . gettype(CardHPDictionary($cardID)) . "</td></tr>";
echo "<tr><td>Power</td><td>" . CardPower($cardID) . "</td><td>" . gettype(CardPower($cardID)) . "</td></tr>";
echo "<tr><td>Upgrade HP</td><td>" . CardUpgradeHPDictionary($cardID) . "</td><td>" . gettype(CardUpgradeHPDictionary($cardID)) . "</td></tr>";
echo "<tr><td>Upgrade Power</td><td>" . CardUpgradePower($cardID) . "</td><td>" . gettype(CardUpgradePower($cardID)) . "</td></tr>";
echo "<tr><td>Aspects</td><td>" . CardAspects($cardID) . "</td><td>" . gettype(CardAspects($cardID)) . "</td></tr>";
echo "<tr><td>Traits</td><td>" . CardTraits($cardID) . "</td><td>" . gettype(CardTraits($cardID)) . "</td></tr>";
echo "<tr><td>Arenas</td><td>" . CardArenas($cardID) . "</td><td>" . gettype(CardArenas($cardID)) . "</td></tr>";
echo "<tr><td>Type</td><td>" . DefinedCardType($cardID) . "</td><td>" . gettype(DefinedCardType($cardID)) . "</td></tr>";
echo "<tr><td>Type2</td><td>" . DefinedCardType2($cardID) . "</td><td>" . gettype(DefinedCardType2($cardID)) . "</td></tr>";
echo "<tr><td>Is Unique</td><td>" . CardIsUnique($cardID) . "</td><td>" . gettype(CardIsUnique($cardID)) . "</td></tr>";
echo "<tr><td>Has When Played</td><td>" . HasWhenPlayed($cardID) . "</td><td>" . gettype(HasWhenPlayed($cardID)) . "</td></tr>";
echo "<tr><td>Has When Destroyed</td><td>" . HasWhenDestroyed($cardID) . "</td><td>" . gettype(HasWhenDestroyed($cardID)) . "</td></tr>";
echo "</table>";
?>