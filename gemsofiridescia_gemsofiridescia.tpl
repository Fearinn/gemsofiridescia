{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- GemsOfIridescia implementation : © Matheus Gomes matheusgomesforwork@gmail.com
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------
-->
<link rel="stylesheet" href="https://use.typekit.net/jim0ypy.css" />
<audio
  id="audiosrc_gemsofiridescia_coins"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_coins.mp3"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_o_gemsofiridescia_coins"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_coins.ogg"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_gemsofiridescia_dice"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_dice.mp3"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_o_gemsofiridescia_dice"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_dice.ogg"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_gemsofiridescia_explosion"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_explosion.mp3"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_o_gemsofiridescia_explosion"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_explosion.ogg"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_gemsofiridescia_flip"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_flip.mp3"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_o_gemsofiridescia_flip"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_flip.ogg"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_gemsofiridescia_gems"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_gems.mp3"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_o_gemsofiridescia_gems"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_gems.ogg"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_gemsofiridescia_iridia"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_iridia.mp3"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_o_gemsofiridescia_iridia"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_iridia.ogg"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_gemsofiridescia_rhom"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_rhom.mp3"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_o_gemsofiridescia_rhom"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_rhom.ogg"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_gemsofiridescia_swapping"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_swapping.mp3"
  preload="none"
  autobuffer
></audio>
<audio
  id="audiosrc_o_gemsofiridescia_swapping"
  src="{GAMETHEMEURL}img/sounds/gemsofiridescia_swapping.ogg"
  preload="none"
  autobuffer
></audio>
<div id="goi_gameArea" class="goi_gameArea">
  <div class="goi_relicsContainer">
    <div id="goi_relicsDeck" class="goi_relicsDeck"></div>
    <div id="goi_relicsMarket" class="goi_relicsMarket"></div>
  </div>
  <div id="goi_itemsZone" class="goi_itemsZone whiteblock">
    <div id="goi_void" class="goi_void"></div>
    <div class="goi_itemsContainer">
      <h3 id="goi_activeItemsTitle" class="goi_zoneTitle"></h3>
      <div id="goi_activeItems" class="goi_activeItems"></div>
    </div>
    <div class="goi_itemsContainer">
      <h3 id="goi_itemsDiscardTitle" class="goi_zoneTitle"></h3>
      <div id="goi_itemsDiscard" class="goi_itemsDiscard"></div>
    </div>
  </div>
  <div id="goi_board" class="goi_board">
    <div id="goi_scoringTrack" class="goi_scoringTrack"></div>
    <div class="goi_tilesGrid">
      <div id="goi_tilesRow-9" class="goi_tilesRow">
        <div id="goi_tileContainer-53" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-54" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-55" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-56" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-57" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-58" class="goi_tileContainer"></div>
      </div>
      <div id="goi_tilesRow-8" class="goi_tilesRow">
        <div id="goi_tileContainer-46" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-47" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-48" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-49" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-50" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-51" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-52" class="goi_tileContainer"></div>
      </div>
      <div id="goi_tilesRow-7" class="goi_tilesRow">
        <div id="goi_tileContainer-40" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-41" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-42" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-43" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-44" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-45" class="goi_tileContainer"></div>
      </div>
      <div id="goi_tilesRow-6" class="goi_tilesRow">
        <div id="goi_tileContainer-33" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-34" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-35" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-36" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-37" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-38" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-39" class="goi_tileContainer"></div>
      </div>
      <div id="goi_tilesRow-5" class="goi_tilesRow">
        <div id="goi_tileContainer-27" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-28" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-29" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-30" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-31" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-32" class="goi_tileContainer"></div>
      </div>
      <div id="goi_tilesRow-4" class="goi_tilesRow">
        <div id="goi_tileContainer-20" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-21" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-22" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-23" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-24" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-25" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-26" class="goi_tileContainer"></div>
      </div>
      <div id="goi_tilesRow-3" class="goi_tilesRow">
        <div id="goi_tileContainer-14" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-15" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-16" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-17" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-18" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-19" class="goi_tileContainer"></div>
      </div>
      <div id="goi_tilesRow-2" class="goi_tilesRow">
        <div id="goi_tileContainer-7" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-8" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-9" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-10" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-11" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-12" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-13" class="goi_tileContainer"></div>
      </div>
      <div id="goi_tilesRow-1" class="goi_tilesRow">
        <div id="goi_tileContainer-1" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-2" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-3" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-4" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-5" class="goi_tileContainer"></div>
        <div id="goi_tileContainer-6" class="goi_tileContainer"></div>
      </div>
    </div>
    <div id="goi_gemDice" class="goi_gemDice"></div>
    <div id="goi_explorersBoard" class="goi_explorersBoard"></div>
    <div id="goi_stoneDice" class="goi_stoneDice"></div>
  </div>
  <div id="goi_playerZones" class="goi_playerZones"></div>
  <div id="goi_merchant" class="goi_merchant">
    <div id="goi_itemsDeck" class="goi_itemsDeck"></div>
    <div id="goi_itemsMarket" class="goi_itemsMarket">
      <div id="goi_itemPlaceholder" class="goi_itemPlaceholder"></div>
    </div>
  </div>
</div>

{OVERALL_GAME_FOOTER}

<script type="text/javascript"></script>
