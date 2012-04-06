<?php

class Packer {
	private	$sources,
		$tokens,
		$path;
	
	private static $include_cache = Array();
	
	public function add($path) {
		if(($source = @file_get_contents($this->path = $path)) === false)
			throw new Exception('Failed to load ' . $path);
		
		$this->sources[$path] = $source;
		
		$this->tokens[$path] = @token_get_all($this->sources[$path]);
		$this->tokens[$path] = $this->pack($path);
	}
	
	public function pack($path) {
		$new_tokens = Array();
		$tokens = &$this->tokens[$path];
		$token_count = count($tokens);
		for($i = 0; $i < $token_count; $i++) {
			$token = $tokens[$i];
			
			if(is_array($token)) {
				$type = $token[0];
				$content = $token[1];
				$line = $token[2];
				
				if(in_array($type, Array(T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE))) {
					$bracketed = false;
					for($x = $i + 1; $x < $token_count; $x++) {
						if((is_array($tokens[$x]) && $tokens[$x][0] != T_WHITESPACE) || $tokens[$x] == '(')
							break;
					}
					
					$bracketed = $tokens[$x] === '(';
					
					$depth = $bracketed ? 1 : 0;
					for($x = $x; $x < $token_count; $x++) {
						if(is_array($tokens[$x]))
							continue;
						
						if($tokens[$x] == '(')
							$depth++;
						elseif($tokens[$x] == ')')
							$depth--;
						
						if(($tokens[$x] == ')' && $depth <= 0) || $tokens[$x] == ';')
							break;
					}
					
					
					$new_tokens[] = Array(T_DOC_COMMENT, '/* ');
					for($t = $i; $t <= $x; $t++)
						$new_tokens[] = is_array($tokens[$t]) ? $tokens[$t][1] : $tokens[$t];
					$new_tokens[] = Array(T_DOC_COMMENT, ' */');
					
					$temp_var = md5(rand());
					
					$new_tokens[] = "\n(\n";
					$new_tokens[] = "Pack::__file_temp('$temp_var') && \n";
					$new_tokens[] = "(eval(Pack::_include(";
					for($t = $i + 1; $t < $x ; $t++)
						$new_tokens[] = is_array($tokens[$t]) ? $tokens[$t][1] : $tokens[$t];
					$new_tokens[] = ", " . token_name($type) . ")) || true)\n";
					$new_tokens[] = "&& Pack::__file_temp_restore('$temp_var')";
					$new_tokens[] = "\n)" . $tokens[$x] . "\n";
					
					$i = $x;
				} elseif($type == T_FILE) {
					// $new_tokens[] = 'Pack::$__file__';
					$new_tokens[] = 'dirname(__FILE__) . "/' . addslashes($path) . '"';
				} elseif($type == T_STRING) {
					for($x = $i - 1; $x > 0; $x--) {
						if(!is_array($tokens[$x]) || $tokens[$x][0] != T_WHITESPACE)
							break;
					}
					
					$prev = $tokens[$x];
					if(is_array($prev) && ($prev[0] == T_DOUBLE_COLON || $prev[0] == T_OBJECT_OPERATOR)) {
						$new_tokens[] = $content;
					} else {
						switch($content) {
							case 'readfile':
							case 'file_get_contents':
							case 'file_put_contents':
							case 'file':
							case 'stat':
							case 'fileatime':
							case 'filectime':
							case 'filegroup':
							case 'fileinode':
							case 'filemtime':
							case 'fileowner':
							case 'fileperms':
							case 'filesize':
							case 'filetype':						
							case 'file_exists':
							case 'unlink':
							case 'fopen':
							case 'is_readable':
							case 'is_writable':
							case 'simplexml_load_file':
							case 'rename':
								$new_tokens[] = $token;
								for($x = $i + 1; $x < $token_count; $x++) {
									$new_tokens[] = $tokens[$x];
									if($tokens[$x] == '(')
										break;
								}
				
								$new_tokens[] = 'Pack::realpath(';
				
								$depth = 0;
								for($x = $x + 1; $x < $token_count; $x++) {
									if(is_array($tokens[$x])) {
										$new_tokens[] = $tokens[$x][1];
										continue;
									}
					
									if(in_array($tokens[$x], array('(', '{'))) {
										$depth++;
									} elseif(in_array($tokens[$x], array(')', '}'))) {
										$depth--;
									}
					
									if(in_array($tokens[$x], array(')', ','))) {
										if($depth < 0 || ($depth == 0 && $tokens[$x] == ',')) {
											break;
										}
									}
					
									$new_tokens[] = $tokens[$x];
								}
				
				
								$new_tokens[] = ', true)';
				
								$new_tokens[] = $tokens[$x];
				
								$i = $x;
				
								break;
							case 'is_file':
							case 'is_dir':
							case 'chdir':
							case 'getcwd':
								$new_tokens[] = 'Pack::' . $content;
								break;
							default:
								$new_tokens[] = $content;
						}
					}
				} else {
					$new_tokens[] = $token;
				}
			} else {
				$new_tokens[] = $token;
			}
		}
		
		return $new_tokens;
	}
	
	public function header_code() {
		$inc = file_get_contents(dirname(__FILE__) . '/pack.inc.php');
		$inc = substr($inc, strpos($inc, '<?php') + 5);
		
		foreach(array_keys($this->sources) as $path) {
			$inc .= "file_put_contents('pack://" . addslashes($path) . "', base64_decode('" . base64_encode($this->code($path, true)) . "'));\n";
		}
		
		return $inc;
	}
	
	public function code($path, $no_header = false) {		
		$opened = $no_header;
		$php = '';
		foreach($this->tokens[$path] as $token) {
			if(is_array($token)) {
				$php .= $token[1];
				
				if(!$opened && $token[0] == T_OPEN_TAG) {
					$php .= $this->header_code();
					$opened = true;
				}
			} else {
				$php .= $token;
			}
		}
		
		return $php;
	}	
}

