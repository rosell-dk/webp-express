<?php

include_once __DIR__ . '/classes/State.php';
use \WebPExpress\State;

// Test if plugin is activated for the first time, or simply reactivated
if (State::getState('configured', false)) {
    include __DIR__ . "/reactivate.php";
} else {
    include __DIR__ . "/activate-first-time.php";
}
