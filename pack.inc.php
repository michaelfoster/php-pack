<?php

class PackStream {
	private static $data = Array();
	private $path, $position;
	
	function stream_open($path, $mode, $options, &$opened_path) {
		// make it easier to parse
		$mode = $mode[0];
		
		if(in_array($mode, array('r', 'w', 'c', 'x')))
			$this->position = 0;
		else
			$this->position = isset(self::$data[$path]) ? strlen(self::$data[$path]) : 0;
		
		if($mode == 'r' && !isset(self::$data[$path]))
			return false;
		
		if($mode == 'x' && isset(self::$data[$path]))
			return false;
		
		if($mode == 'w' && isset(self::$data[$path]))
			unset(self::$data[$path]);
		
		if(in_array($mode, array('w', 'a', 'x', 'c')) && !isset(self::$data[$path]))
			self::$data[$path] = '';
		
		$this->path = $path;
		
		return true;
	}
	
	function unlink($path) {
		if(!isset(self::$data[$path]))
			return false;
		unset(self::$data[$path]);
		return true;
	}
	
	function stream_write($data) {
		$before = substr(self::$data[$this->path], 0, $this->position);
		$after = substr(self::$data[$this->path], $this->position + strlen($data));
		self::$data[$this->path] = $before . $data . $after;
		
		$this->position += strlen($data);
		
		return strlen($data);
	}
	
	function stream_read($len) {
		$chunk = substr(self::$data[$this->path], $this->position, $len);
		$this->position += strlen($chunk);
		return $chunk;
	}
	
	function stream_eof() {
		return $this->position >= strlen(self::$data[$this->path]);
	}
	
	function stream_seek($offset, $whence) {
		switch($whence) {
			case SEEK_SET:
				if($offset > strlen(self::$data[$this->path]) || $offset < 0)
					return false;
				
				$this->position = $offset;
				return true;
			case SEEK_CUR:
				if($offset < 0)
					return false;
				$this->position += $offset;
				return true;
			case SEEK_END:
				if(strlen(self::$data[$this->path]) + $offset < 0)
					return false;
				$this->position = strlen(self::$data[$this->path]) + $offset;
				return true;
			default:
				return false;
		}
	}
	
	function stream_stat() {
		return $this->url_stat($this->path);
	}
	
	function url_stat($path) {
		if(!isset(self::$data[$path]))
			return false;
		
		return array(
			'dev' => 0,
			'ino' => 0,
			'mode' => 0,
			'nlink' => 0,
			'uid' => 0,
			'gid' => 0,
			'rdev' => 0,
			'size' => strlen(self::$data[$path]),
			'atime' => 0,
			'mtime' => 0,
			'ctime' => 0,
			'blksize' => 0,
			'blocks' => 0
		);
	}
	
	function stream_tell() {
		return $this->position;
	}
}

class Pack {
	public static $included = Array();
	public static $__file__;
	
	public static function relative($from, $to) {
		$arFrom = explode('/', rtrim($from, '/'));
		$arTo = explode('/', rtrim($to, '/'));
		while(!empty($arFrom) && !empty($arTo) && $arFrom[0] == $arTo[0]) {
			array_shift($arFrom);
			array_shift($arTo);
		}
		$ret = str_pad('', count($arFrom) * 3, '../') . implode('/', $arTo);
		
		return preg_replace('/^\.\//', '', $ret);
	}
	
	public static function realpath($path, $return_old_path_if_nonexistent = false) {
		$old_path = $path;
		
		if($path[0] != '/') {
			$file = basename($path);
			$cd = dirname(self::$__file__);
		
			$dirs = explode('/', $path);
			array_pop($dirs);
		
			foreach($dirs as $dir) {
				if($dir == '..') {
					$cd = preg_replace('/^(.*)\/(.*)/', '$1', $cd);
					if(empty($cd))
						$cd = '/';
				} else {
					$cd .= ($cd == '/' ? '' : '/') . $dir;
				}
			}
			$path = $cd . '/' . $file;
		}
		
		
		$realpath = 'pack://' . self::relative(dirname(self::$__file__), $path);
		
		if($return_old_path_if_nonexistent && !file_exists($realpath))
			return $old_path;
		
		return $realpath;
	}

	public static function _include($path, $type) {
		$path = self::realpath($path);
		
		// echo token_name($type) . ' - ' . $path . "\n";
		
		if(!file_exists($path)) {
			trigger_error('Failed to include ' . $path, $type == T_REQUIRE || $type == T_REQUIRE_ONCE ? E_USER_ERROR : E_USER_WARNING);
		}
		
		if($type == T_REQUIRE_ONCE || $type == T_INCLUDE_ONCE) {
			if(in_array($path, self::$included))
				return;
		}
		
		self::$included[] = $path;
		
		$inc = '?>' . file_get_contents($path);
		
		self::$__file__ = preg_replace('/^pack:\/\//', '', $path);
		
		return $inc;
	}
}

@stream_wrapper_unregister('pack');
stream_wrapper_register('pack', 'PackStream');
Pack::$__file__ = __FILE__;

