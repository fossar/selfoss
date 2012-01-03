<?php

/**
	SQL database pack for the PHP Fat-Free Framework

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2009-2010 F3 Factory
	Bong Cosca <bong.cosca@yahoo.com>

		@package SQLDB
		@version 1.4.4
**/

//! SQL database pack
class SQLdb extends Core {

	//! Minimum framework version required to run
	const F3_Minimum='1.4.0';

	//@{
	//! Locale-specific error/exception message
	const
		TEXT_DBConnect='Database connection failed',
		TEXT_DBError='Database error - {@CONTEXT}';
	//@}

	//! Axon sync interval
	const SYNC_Default=60;

	/**
		Execute SQL statement
			@param $cmd string
			@param $bind mixed
			@param $id string
			@private
	**/
	private static function sqlExec($cmd,$bind=NULL,$id='DB') {
		// Execute SQL statement
		$db=&self::$global[$id];
		if (is_null($bind))
			$db['query']=$db['pdo']->query($cmd);
		else {
			$db['query']=$db['pdo']->prepare($cmd);
			if (is_object($db['query'])) {
				foreach ($bind as $key=>$val) {
					if (!is_array($val))
						$val=array($val,PDO::PARAM_STR);
					$db['query']->bindValue(
						$key,$val[0],isset($val[1])?$val[1]:NULL
					);
				}
				$db['query']->execute();
			}
		}
		// Check SQLSTATE
		if ($db['pdo']->errorCode()!=PDO::ERR_NONE) {
			// Gather info about error
			$error=$db['pdo']->errorInfo();
			self::$global['CONTEXT']=
				$error[0].' ('.$error[1].') '.$error[2];
			trigger_error(self::TEXT_DBError);
			return;
		}
		// Gather real SQL queries for profiler
		if (!isset(self::$stats[$id]))
			self::$stats[$id]=array(
				'cache'=>array(),
				'queries'=>array()
			);
		if (!isset(self::$stats[$id]['queries'][$cmd]))
			self::$stats[$id]['queries'][$cmd]=0;
		self::$stats[$id]['queries'][$cmd]++;
		// Save result
		$db['result']=$db['query']->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
		Bind values to parameters in SQL statement(s) and execute
			@return mixed
			@param $cmds mixed
			@param $bind mixed
			@param $id string
			@param $ttl integer
			@public
	**/
	public static function sqlBind($cmds,$bind=NULL,$id='DB',$ttl=0) {
		$db=&self::$global[$id];
		// Connect to database once
		if (!$db || !$db['dsn']) {
			// Can't connect without a DSN!
			trigger_error(self::TEXT_DBConnect);
			return;
		}
		if (!isset($db['pdo'])) {
			$ext='pdo_'.stristr($db['dsn'],':',TRUE);
			if (!in_array($ext,get_loaded_extensions())) {
				// PHP extension not activated
				self::$global['CONTEXT']=$ext;
				trigger_error(self::TEXT_PHPExt);
				return;
			}
			try {
				$db['pdo']=new PDO(
					$db['dsn'],
					isset($db['user'])?$db['user']:NULL,
					isset($db['password'])?$db['password']:NULL,
					isset($db['options'])?$db['options']:
						array(PDO::ATTR_EMULATE_PREPARES=>FALSE)+(
							preg_match('/^mysql:/',$db['dsn'])?
								array(
									PDO::MYSQL_ATTR_INIT_COMMAND=>
										'SET NAMES utf8;'
								):
								array()
						)
				);
			} catch (Exception $xcpt) {}
			if (!isset($db['pdo'])) {
				// Unable to connect
				trigger_error(self::TEXT_DBConnect);
				return;
			}
			// Define connection attributes
			$attrs=explode('|',
				'AUTOCOMMIT|ERRMODE|CASE|CLIENT_VERSION|CONNECTION_STATUS|'.
				'PERSISTENT|PREFETCH|SERVER_INFO|SERVER_VERSION|TIMEOUT'
			);
			// Save attributes in DB global variable
			foreach ($attrs as $attr) {
				// Suppress warning if PDO driver doesn't support attribute
				$val=@$db['pdo']->
					getAttribute(constant('PDO::ATTR_'.$attr));
				if ($val)
					$db['attributes'][$attr]=$val;
			}
		}
		if (!is_array($cmds))
			// Convert to array to prevent code duplication
			$cmds=array($cmds);
		// Remove empty elements
		$cmds=array_diff($cmds,array(NULL));
		$db['result']=NULL;
		if (count($cmds)>1)
			// More than one SQL statement specified
			$db['pdo']->beginTransaction();
		foreach ($cmds as $cmd) {
			if (isset(self::$global['ERROR']) && self::$global['ERROR'])
				break;
			$cmd=F3::resolve($cmd);
			if ($ttl) {
				// Cache results
				$hash='sql.'.F3::hashCode($cmd);
				$db=&self::$global[$id];
				$cached=Cache::cached($hash);
				if ($cached && (time()-$cached['time'])<$ttl) {
					// Gather cached SQL queries for profiler
					if (!isset(self::$stats[$id]))
						self::$stats[$id]=array(
							'cache'=>array(),
							'queries'=>array()
						);
					if (!isset(self::$stats[$id]['cache'][$cmd]))
						self::$stats[$id]['cache'][$cmd]=0;
					self::$stats[$id]['cache'][$cmd]++;
					// Retrieve from cache
					$db=Cache::fetch($hash);
				}
				else {
					self::sqlExec($cmd,NULL,$id);
					if (!self::$global['ERROR']) {
						// Save to cache
						unset($db['pdo'],$db['query']);
						Cache::store($hash,$db);
					}
				}
			}
			else
				// Execute SQL statement(s)
				self::sqlExec($cmd,$bind,$id);
		}
		if (count($cmds)>1) {
			$func=self::$global['ERROR']?'rollBack':'commit';
			call_user_func(array($db['pdo'],$func));
		}
		return $db['result'];
	}

	/**
		Process SQL statement(s)
			@return mixed
			@param $cmds mixed
			@param $id string
			@param $ttl integer
			@public
	**/
	public static function sql($cmds,$id='DB',$ttl=0) {
		return self::sqlBind($cmds,NULL,$id,$ttl);
	}

	/**
		Return PDO class constant corresponding to data type
			@return integer
			@param $value mixed
			@public
	**/
	public static function type($value) {
		if (is_null($value))
			return PDO::PARAM_NULL;
		elseif (is_bool($value))
			return PDO::PARAM_BOOL;
		elseif (is_int($value))
			return PDO::PARAM_INT;
		elseif (is_string($value))
			return PDO::PARAM_STR;
		return PDO::PARAM_LOB;
	}

	/**
		Bootstrap code
			@public
	**/
	public static function onLoad() {
		if (!isset(self::$global['SYNC']))
			self::$global['SYNC']=self::SYNC_Default;
	}

}
