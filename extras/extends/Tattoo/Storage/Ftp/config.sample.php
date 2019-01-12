<?php
/**
 * To change this
 * @author Alacner Zhang <alacner@gmail.com>
 * @version $Id: config.sample.php 488 2014-10-14 10:03:34Z alacner $
 */

return array(
	array(
		'group_id' => 0,
		'ftp_capacity' => 100000,
		'is_closed' => false,
		'server' => array(
			array(
				'host' => 'localhost',
				//'port' => 21,
				//'timeout' => 90,
				//'username' => 'anonymous',
				//'password' => '',
				'base_path' => '/',
			),
			array(
				'host' => 'localhost',
				'base_path' => '/',
			),
		),
	),
);