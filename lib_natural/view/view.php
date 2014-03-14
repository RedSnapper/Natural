<?php
defined('JPATH_PLATFORM') or die;
mb_internal_encoding( 'UTF-8' );

JLoader::import('joomla.utilities.utility');
JLoader::import('joomla.document.document');

/**
 * This provides support for xpaths and uses a DOMDocument instead of a string
**/

class NView {
//private GAPS 'enum' used for managing xpathing.
	const GAP_NONE = 1;
	const GAP_FOLLOWING = 2;
	const GAP_PRECEDING = 3;
	const GAP_CHILD = 4;
	const GAP_NATTR = 5;

// class members
	private $xp = NULL;				//xpath manager
	private $doc = NULL;			//DOMDocument (replaces _template)
	protected $xmlmsgs = array();	//parse messages.

	public function __construct($value = '') {
		set_error_handler(array($this, 'doMsg'), E_ALL | E_STRICT );
		try {
			switch(gettype($value)) {
				case 'NULL': 
				case 'string': {
					$this->con_string($value);
				} break;
				case 'object': {
					$this->con_object($value);
				} break;
				case 'resource': {
					$contents = '';
					while (!feof($value)) {
					  $contents .= fread($value,1024);
					}
					$this->con_string($contents);
				} break;
				default: {
					$this->doMsg("NView:: __construct does not (yet) support " . gettype($value));
				}
			}
		} catch (Exception $e) {
			$this->xmlmsgs[] = array($e->getCode(),$e->getMessage(),$e->getFile(), $e->getLine()); //this adds to the array.
		}
		restore_error_handler();
	}
	
	public function doc() {
		return $this->doc->documentElement;
	}

	public function show($asdocument = FALSE) {
		$this->showMessages();
		if (is_null($this->doc) || is_null($this->xp)) {
			echo '';
		} else {
			$this->tidyView();
			if ($asdocument) {
				echo $this->doc->saveXML();
			} else {
				$hs=$this->doc->saveXML();
				$s1='/<\?xml version="1.0"\?>/';
				$s2='/<!DOCTYPE \w+>/';
				$s3='/\sxmlns="http:\/\/www.w3.org\/1999\/xhtml"/';
				$ksub = array($s1 => '', $s2 => '', $s3 => '');
				echo trim(preg_replace(array_keys($ksub),array_values($ksub),$hs));
			}
		}
	}
	
	public function strToNode($value = NULL) {
		//bad Joomla! One should always xml-encode ampersands in URLs in HTML.
		$fragstr = preg_replace('/&(?![\w#]{1,7};)/i','&amp;',$value);
		$fnode = $this->doc->createDocumentFragment();
		set_error_handler(array($this, 'doMsg'), E_ALL | E_STRICT );
		try {
			$fnode->appendXML($fragstr);
		} catch (Exception $ex) {
			restore_error_handler();
			throw $ex;
		}
		restore_error_handler();
		return $fnode;
	}

	public function count($xpath,$ref = NULL) {
		if (!is_null($doc) && !is_null($xp)) {
			if (is_null($ref)) {
				$entries = $this->xp->query($xpath);
			} else {
				$entries = $this->xp->query($xpath,$ref);
			}
			if ($entries) {
				return $entries->length;
			} else {
				$this->doMsg($xpath);
				return 0;
			}
		} else {
			$this->doMsg('count() ' . $xpath . ' attempted on a non-document.');
		}
	}

//helper function..
	public function consume($xpath, $ref = NULL) {
		$retval = $this->get($xpath, $ref);
		$this->set($xpath,,$ref);
		return $retval;
	}

	public function get($xpath, $ref = NULL) {
		$retval = NULL;
		if (!is_null($doc) && !is_null($xp)) {
			set_error_handler(array($this,'doMsg'), E_ALL | E_STRICT );
			if (is_null($ref)) {
				$entries = $this->xp->query($xpath);
			} else {
				$entries = $this->xp->query($xpath,$ref);
			}
			if ($entries) {
				switch ( $entries->length ) {
					case 1: {
						$entry = $entries->item(0);
						if ($entry->nodeType == XML_TEXT_NODE ) {
							$retval = $entry->nodeValue;
						} elseif ( $entry->nodeType == XML_ATTRIBUTE_NODE ) {
							$retval = $entry->value;
						} else {
							$retval = $entry;
						}
					} break;
					case 0: break;
					default: {
						$retval=$entries;
					} break;
				}
			} else {
				$this->doMsg($xpath);
			}
			restore_error_handler();
		} else {
			$this->doMsg('get() ' . $xpath . ' attempted on a non-document.');
		}
		return $retval;
	}

	public function set($xpath,$value = NULL,$ref = NULL) {
		//replace node at string xpath with node 'value'.
		set_error_handler(array($this,'doMsg'), E_ALL | E_STRICT );
		if (!is_null($doc) && !is_null($xp)) {
			$gap = self::GAP_NONE;
			if (substr($xpath,-6)=="-gap()") {
				$xpath = substr($xpath,0,-6); //remove the -gap();
				if (substr($xpath,-6)=="/child") {
					$xpath = substr($xpath,0,-6); //remove the child;
					$gap=self::GAP_CHILD;
				}
				if (substr($xpath,-10)=="/preceding") {
					$xpath = substr($xpath,0,-10); //remove the child;
					$gap=self::GAP_PRECEDING;
				}
				if (substr($xpath,-10)=="/following") {
					$xpath = substr($xpath,0,-10); //remove the child;
					$gap=self::GAP_FOLLOWING;
				}
			}
			//now act according to value type.
			switch ( gettype($value) ) {
				case "NULL": {
					if ($gap==self::GAP_NONE) {
						if (is_null($ref)) {
							$entries = $this->xp->query($xpath);
						} else {
							$entries = $this->xp->query($xpath,$ref);
						}
						if ($entries) {
							foreach($entries as $entry) {
								$n = $entry->parentNode->removeChild($entry);
								unset($n); //not sure if this is needed..
							}
						} else {
							$this->doMsg($xpath);
						}
					}
				} break;
				case "boolean":
				case "integer":
				case "double":
				case "string":
				case "double":
				case "object" : { //probably a node.
					if ( gettype($value) != "object" || is_subclass_of($value,'DOMNode') || $value instanceof DOMNodeList || $value instanceof NView ) {
						if (is_null($ref)) {
							$entries = $this->xp->query($xpath);
						} else {
							$entries = $this->xp->query($xpath,$ref);
						}
						if ($entries) {
							if ( $entries->length == 0 && $gap == self::GAP_NONE) { //maybe this is a new attribute!?
								$spoint = strrpos($xpath,'/');
								$apoint = strrpos($xpath,'@');
								if ( $apoint == $spoint+1 ) {
									$aname= substr($xpath,$apoint+1); //grab the attribute name.
									$xpath= substr($xpath,0,$spoint); //resize the xpath.
									$gap=self::GAP_NATTR;
								}
								if (is_null($ref)) {
									$entries = $this->xp->query($xpath);
								} else {
									$entries = $this->xp->query($xpath,$ref);
								}
								if (!$entries) {
									$this->doMsg($xpath);
								}
							}
							if ($value instanceof NView) {
								$value = $value->doc->documentElement;
							}
							foreach($entries as $entry) {
								if ( $gap == self::GAP_NATTR && $entry->nodeType==XML_ELEMENT_NODE && isset($aname) ) {
									$entry->setAttribute($aname,htmlspecialchars(utf8_encode($value),ENT_COMPAT,'',false));
								} else {
									if ($entry->nodeType == XML_ATTRIBUTE_NODE && gettype($value) != "object" ) {
										switch ($gap) {
											case self::GAP_NONE: {
												$entry->value = utf8_encode($value);
											} break;
											case self::GAP_PRECEDING: {
												$entry->value = utf8_encode($value) . $entry->value ;
											} break;
											case self::GAP_FOLLOWING:
											case self::GAP_CHILD: {
												$entry->value .= utf8_encode($value);
											} break;
										}
									} else {
										if ($value instanceof DOMNodeList) {
											foreach($value as $nodi) {
												$nodc = $nodi->cloneNode(true);
												$node = $this->doc->importNode($nodc, true);
												switch ($gap) {
													case self::GAP_NONE: {
														$entry->parentNode->replaceChild($node, $entry);
													} break;
													case self::GAP_PRECEDING: {
														$entry->parentNode->insertBefore($node, $entry);
													} break;
													case self::GAP_FOLLOWING: {
														if ($entry->nextSibling == NULL) {
															$entry->parentNode->appendChild($node);
														} else {
															$entry->parentNode->insertBefore($node,$entry->nextSibling);
														}
													} break;
													case self::GAP_CHILD: {
														$entry->appendChild($node);
													} break;
												}
											}
										} else {
											if (gettype($value) != "object" ) {
												$nodf = $this->strToNode('' . $value);
												$node = $this->doc->importNode($nodf, true);
											} else {
												$nodc = $value->cloneNode(true);
												$node = $this->doc->importNode($nodc, true);
											}
											switch ($gap) {
												case self::GAP_NONE: {
													$entry->parentNode->replaceChild($node, $entry);
												} break;
												case self::GAP_PRECEDING: {
													$entry->parentNode->insertBefore($node, $entry);
												} break;
												case self::GAP_FOLLOWING: {
													if ($entry->nextSibling == NULL) {
														$entry->parentNode->appendChild($node);
													} else {
														$entry->parentNode->insertBefore($node,$entry->nextSibling);
													}
												} break;
												case self::GAP_CHILD: {
													$entry->appendChild($node);
												} break;
											}
										}
									}
								}
							}
							if ( gettype($value) != "object" || $value->nodeType == XML_TEXT_NODE && $gap != self::GAP_NONE) {
								$this->doc->normalizeDocument();
							}
						} else {
							$this->doMsg($xpath);
						}
					} else {
						$this->doMsg("NView: Unknown value type of object ". gettype($value) ." found");
					}
				} break;
				default: { //treat as text.
					$this->doMsg("NView: Unknown value type of object ". gettype($value) ." found");
				}
			}
		}
		else
		{
			$this->doMsg('set() ' . $xpath . ' attempted on a non-document.');
		}
		restore_error_handler();
		return $this;
	}
	
	/**
	* private functions
	*/
	private function showMessages() { //returns parser messages and flushes.
		if ( count($this->xmlmsgs) !== 0) {
			$app = JFactory::getApplication();
			foreach($this->xmlmsgs as $m) {
				if ( $m[3] == 0) {
					$msg = "<p><b>" . $m[0] . "</b></p>";
				} else {
					$msg = "<p><i>" . $m[2] . "</i> Line: <i>" . $m[3] . "</i>; <b>" . $m[1] . "</b></p>";
				}
				$app->enqueueMessage($msg,'error');
			}
		}
	}
	
	private function initDoc() {
		$this->doc = new DOMDocument("1.0","utf-8");
		$this->doc->preserveWhiteSpace = true;
		$this->doc->formatOutput = false;
	}
	
	private function initXPath() {
		$this->xp = new DOMXPath($this->doc);
		$this->xp->registerNamespace("h","http://www.w3.org/1999/xhtml");
	}

	private function con_class($value) {
		$this->initDoc();
		$this->doc = $value->doc->cloneNode(true);
		$this->initXPath();
	}
	
	private function con_node($value) {
		if ( $value->nodeType == XML_DOCUMENT_NODE ) {
			$this->doc = $value->cloneNode(true);
			$this->initXPath();
		} elseif ( $value->nodeType == XML_ELEMENT_NODE ) {
			$this->initDoc();
			$node = $this->doc->importNode($value, true);
			$this->doc->appendChild($node);
			$olde= $value->ownerDocument->documentElement;
			if ($olde->hasAttributes()) {
				$myde= $this->doc->documentElement;
				foreach ($olde->attributes as $attr) {
					if (substr($attr->nodeName,0,6)=="xmlns:") {
						$myde->removeAttribute($attr->nodeName);
						$natr = $this->doc->importNode($attr,true);
						$myde->setAttributeNode($natr);
					}
				}
			}
			$this->initXPath();
		} else {
			$this->doMsg("NView:: __construct does not (yet) support construction from nodes of type " . $value->nodeType );
		}
	}
		
	private function con_file($value) {
		if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
			$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1);
		} else {
			$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		}
		if (empty($value)) {
			$xview= str_replace(".php",".xhtml",$bt[0]['file']);
		} else {
			if (! strpos($value,'.')) {
				$value .= '.xhtml';
			}
			if (!file_exists($value)) {
				$xview = dirname($bt[0]['file']) . '/' . $value;
			} else {
				$xview = $value;
			}
		}
		if (file_exists($xview)) {
			$this->initDoc();
			$data = file_get_contents($xview);
			$wss = array("\r\n", "\n", "\r", "\t"); //what we will remove
			$data = str_replace($wss,"", $data);
			$this->doc->loadXML($data);
			$this->initXPath();
		} else {
			$this->doMsg("NView: File '" . $value . "' wasn't found. ");
		}
	}

	private function con_string($value) {

		// If there is no value, look for a file adjacent to this.
		if (empty($value)) {
			$this->con_file($value); //handle implicit in file..
		} elseif (!strpos($value,'<')) {
			$this->con_file($value);
		} else {

			// Treat value as xml to be parsed.
			$wss   = array("\r\n", "\n", "\r", "\t"); //
			$value = str_replace($wss,"", $value);
			$this->initDoc();
			$this->doc->loadXML($value);
			$this->initXPath();
		}
	}

	private function con_object($value) {
		if ($value instanceof NView)
		{
			$this->con_class($value);
		} 
		elseif (is_subclass_of($value,'DOMNode'))
		{
			$this->con_node($value);
		} 
		else {
			$this->doMsg("NView: object constructor only uses instances of NView or subclasses of DOMNode.");
		}
	}
	
//void elements area, base, br, col, hr, img, input, link, meta, param, command, keygen,source
	private function tidyView() {
		$xq = "//*[not(node())][not(self::area or self::base or self::br or self::col or self::hr or self::img or self::input or self::link or self::meta or self::param or self::command or self::keygen or self::source)]";
		$entries = $this->xp->query($xq);
		if ($entries) {
			foreach($entries as $entry) { $entry->appendChild($this->doc->createTextNode('')); }
		} else {
			$this->doMsg($xq);
		}
	}

	/**
	* parser message handler..
	*/
	function doMsg($errno, $errstr='', $errfile='', $errline=0) {
		$this->xmlmsgs[] = array($errno, $errstr, $errfile, $errline); //this adds to the array.
	}


}
