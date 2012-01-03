<?php

/**
	Timer plug-in for the PHP Fat-Free Framework

	The contents of this file are subject to the terms of the GNU General
	License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2009-2011 F3::Factory
	Bong Cosca <bong.cosca@yahoo.com>

		@package Timer
		@version 2.0.4
**/

//! Timer plug-in
class Timer extends Base {

	//! Stopwatch
	private
		$timer;

	//! Time difference
	public
		$elapsed;

	/**
		Pause timer and compute time difference
			@public
	**/
	function stop() {
		$this->elapsed+=microtime(TRUE)-$timer;
	}

	/**
		Start/resume the timer
			@public
	**/
	function start() {
		$this->timer=microtime(TRUE);
	}

	/**
		Class constructor
			@public
	**/
	function __construct() {
		$this->start();
		$this->elapsed=0;
	}

}
