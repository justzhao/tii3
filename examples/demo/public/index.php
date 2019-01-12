<?php
/**
 * 入口程序
 * 
 * @author Yametei
 * @version $Id: index.php 6770 2016-08-30 12:38:15Z alacner $
 */

$_directories = array(
    dirname(__FILE__) . '/../configs/local/configuration.php',// local first
    dirname(__FILE__) . '/../configs/configuration.php',
);

foreach ($_directories as $_file) {
    if (is_file($_file)) {
        require_once $_file;
        break;
    }
}
unset($_directories, $_file);

if (!class_exists('Desire_Version')) {//check framework has already loaded
    trigger_error("The desire framework not loaded correctly", E_USER_ERROR);
}

//session start
if (Desire_Config::get('desire.application.session.start', false)) {
    Desire_Application_Session::start(Desire_Config::get('desire.application.session.handler', null));
}

Desire_Application::run();