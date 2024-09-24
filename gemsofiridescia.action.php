<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * GemsOfIridescia implementation : © Matheus Gomes matheusgomesforwork@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * gemsofiridescia.action.php
 *
 * GemsOfIridescia main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "actMyAction" here, then you can call it from your javascript code with:
 * this.bgaPerformAction("actMyAction", ...)
 *
 */

declare(strict_types=1);

/**
 * @property GemsOfIridescia $game
 */
class action_gemsofiridescia extends APP_GameAction
{
    /**
     * This is the constructor. Do not try to implement a `__construct` to bypass this method.
     */
    public function __default()
    {
        if ($this->isArg("notifwindow")) {
            $this->view = "common_notifwindow";
            $this->viewArgs["table"] = $this->getArg("table", AT_posint, true);
        } else {
            $this->view = "gemsofiridescia_gemsofiridescia";
            $this->trace("Complete re-initialization of board game.");
        }
    }

    /**
     * This method is called directly from the router. It asserts HTTP arguments and forwards them to the associated
     * table game method.
     *
     * @throws BgaSystemException
     */
}
