<?php

$_GET['action'] = 'Preview';

//print_r($_SERVER);
$root =& XCube_Root::getSingleton();
$root->mController->execute();
