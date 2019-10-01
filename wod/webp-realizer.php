<?php

namespace WebPExpress;

include 'autoloader.php';

WebPRealizer::preventDirectAccess('webp-realizer.php');
WebPRealizer::processRequest();
