<?php

namespace WebPExpress;

include 'autoloader.php';

WebPOnDemand::preventDirectAccess('webp-on-demand.php');
WebPOnDemand::processRequest();
