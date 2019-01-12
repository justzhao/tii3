<?php
/**
 * A simple blacklist library.
 * 
 * @author Yametei
 * @version $Id: Blacklist..php 6770 2016-08-30 12:38:15Z alacner $
 */

abstract class Blaze_Blacklist extends Tattoo_Blacklist
{
	abstract protected function getConfig();
	
	public function __construct()
	{
		parent::__construct($this->getConfig());
	}
	
	public function find($texts)
	{
		return $this->check_text($texts)->check_regex()->is_blocked();
	}
}