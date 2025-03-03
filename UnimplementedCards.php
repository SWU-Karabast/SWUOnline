<?php
include_once 'MenuBar.php';
include_once 'Header.php';
include_once 'GeneratedCode/GeneratedCardDictionaries.php';

$cardsList = [
    "Admiral Yularen, Fleet Coordinator",
    "All Wings Report In",
    "Annihilator, Tagge's Flagship",
    "Apology Accepted",
    "Astromech Pilot",
    "Attack Run",
    "Barrel Roll",
    "Bunker Defender",
    "Cat and Mouse",
    "City in the Clouds",
    "Cloaked StarViper",
    "Clone Combat Squadron",
    "Coordinated Front",
    "Corporate Defense Shuttle",
    "Corporate Light Cruiser",
    "Covering the Wing",
    "Crackshot V-Wing",
    "Darth Vader, Scourge of Squadrons",
    "Death Star Plans",
    "Decimator of Dissidents",
    "Dedicated Wingmen",
    "Dilapidated Ski Speeder",
    "Diversion",
    "Dornean Gunship",
    "Evasive Maneuver",
    "Face Off",
    "Flanking Fang Fighter",
    "Fly Casual",
    "Focus Fire",
    "Grim Valor",
    "Hondo Ohnaka, Superfluous Swindler",
    "Hopeful Volunteer",
    "Hotshot Maneuver",
    "In the Heat of Battle",
    "Indoctrinated Conscript",
    "Insurgent Saboteurs",
    "Jam Communications",
    "Jarek Yeager, Coordinating with the Resistance",
    "Jedi Light Cruiser",
    "Jump to Lightspeed",
    "Kimogila Heavy Fighter",
    "L3-37, Get Out of My Seat",
    "Lightspeed Assault",
    "Major Vonreg, Red Baron",
    "Massassi Temple",
    "MC30 Assasult Frigate",
    "Munificent Frigate",
    "Omicron Strike Craft",
    "Outer Rim Outlaws",
    "Orbiting K-Wing",
    "Perimeter AT-RT",
    "Piercing Shot",
    "Punch It",
    "Prototype TIE Advanced",
    "Rafa Martez, Shrewd Sister",
    "Republic Y-Wing",
    "Resistance Blue Squadron",
    "Retrofitted Airspeeder",
    "Rogue-class Starfighter",
    "Royal Security Fighter",
    "Scramble Fighters",
    "Seasoned Fleet Admiral",
    "Shadowed Hover Tank",
    "Shield Generator Complex",
    "Shoot Down",
    "Sidon Ithano, The Crimson Corsair",
    "Skyway Cloud Car",
    "Sullustan Spacer",
    "Supporting Eta-2",
    "Sweep the Area",
    "System Shock",
    "Targeting Computer",
    "Tam Ryvora, Searching for Purpose",
    "Techno Union Transport",
    "The Starhawk, Prototype Battleship",
    "There Is No Escape",
    "They Hate That Ship",
    "Torpedo Barrage",
    "U-Wing Lander",
    "Unity of Purpose",
    "Veteran Fleet Officer",
    "Vonreg's TIE Interceptor, Ace of the First Order",
    "Wing Guard Security Team",
    "X-34 Landspeeder"
];

?>

<style>
  body {
    background-size: cover;
    background-position: center;
    background-image: url('./Images/arena-bg.webp');
    width: 100%;
    min-height: 100vh;
    margin: 0;
    background-repeat: no-repeat;
    background-attachment: fixed;
  }
</style>

<div class="core-wrapper" style="height: auto; padding-bottom: 40px;">
    <div class="game-browser-wrapper">
        <div class="game-browser container bg-yellow" style="height: auto; margin-right: 20px;">
            <div style="text-align: center; margin-top: 4px;">
                <h2>Unimplemented Cards</h2>
                <p style="margin-bottom: 20px;">The cards we have images for, but are not implemented are below</p>
            </div>
            <div class="container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
                <?php
                $files = glob('./UnimplementedCards/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
                foreach($files as $file) {
                    $filename = basename($file);
                    $cardId = pathinfo($filename, PATHINFO_FILENAME);
                    $cardName = CardTitle($cardId);

                    // Get image dimensions
                    list($width, $height) = getimagesize($file);
                    $isLandscape = $width > $height;

                    // Calculate styles for landscape images
                    $rotateStyle = $isLandscape ? 'transform: rotate(90deg);' : '';
                    $containerStyle = 'position: relative; padding-top: 140%;'; // Aspect ratio container
                    $imgWrapperStyle = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;';
                    $imgStyle = $isLandscape ? 'max-width: 140%; max-height: 100%; border-radius: 4px;' : 'max-width: 100%; max-height: 100%; border-radius: 4px;';

                    echo "<div style='background: rgba(255,255,255,0.1); padding: 10px; border-radius: 8px; display: flex; flex-direction: column;'>";
                    echo "<div style='$containerStyle'>";
                    echo "<div style='$imgWrapperStyle'>";
                    echo "<img src='UnimplementedCards/$filename' alt='$cardName' style='$imgStyle $rotateStyle'>";
                    echo "</div>";
                    echo "</div>";
                    echo "<div style='margin-top: 10px; margin-bottom: 4px; text-align: center; font-size: 14px; font-weight: 500; color: #fff; display: flex; align-items: center; justify-content: center; flex: 1;'>$cardName</div>";
                    echo "</div>";
                }
                ?>
            </div>
            <div class="cards-list-wrapper" style="text-align: center; padding: 20px 0; margin-top: 40px; max-width: 100%; overflow: hidden;">
            <h3 style="color: #fff; font-size: 24px;">Here are a list of unimplemented cards we don't have images from the <a href="https://starwarsunlimited.com/cards" style="color: #007bff; text-decoration: underline;" target="_blank">SWU website</a>.</h3>
                <ul style="list-style-type: none; padding: 0; display: flex; flex-wrap: wrap; justify-content: center; max-width: 100%; margin: 0 auto;">
                    <?php
                    foreach ($cardsList as $card) {
                        echo "<li style='display: inline-block; margin-right: 15px; margin-bottom: 10px; font-size: 18px; color: #fff; word-wrap: break-word;'>" . htmlspecialchars($card) . "</li>";
                    }
                    ?>
                 </ul>
            </div>
        </div>
    </div>
</div>

