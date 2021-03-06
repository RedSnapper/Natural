<?php
/**
 * @package     LibNatural
 * @subpackage  View
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @copyright   Copyright (C) 2005 - 2014 Red Snapper Ltd. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;
mb_internal_encoding('UTF-8');

JLoader::import('joomla.utilities.utility');
JLoader::import('joomla.document.document');

/**
 * NView class, provides DomNode and easy XPath support
 *
 * (private) members:
 * xp is the xpath manager
 * doc is the internal DOMDocument
 * msgs is the (error) message array.
 *
 * @package  LibNatural
 * @since    3.2
 */
class NView
{
	const GAP_NONE = 1;
	const GAP_FOLLOWING = 2;
	const GAP_PRECEDING = 3;
	const GAP_CHILD = 4;
	const GAP_NATTR = 5;

	private $xp = null;
	private $doc = null;
	protected $msgs = array();

	public function __clone()
    {
		$this->initDoc();
		$this->doc = $this->doc->cloneNode(true);
		$this->initXPath();
    }

	public function __construct($value = '') {
		set_error_handler(array($this, 'doMsg'), E_ALL | E_STRICT);
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
			$this->doMsg($e->getCode(),"NView: " . $e->getMessage(),$e->getFile(), $e->getLine());
		}
		restore_error_handler();
	}

	public function doc() {
		return $this->doc->documentElement;
	}

	public function show($asdocument = false) {
		$this->showMessages();
		if (is_null($this->doc) || is_null($this->xp)) {
			return "";
		} else {
			$this->tidyView();
			if ($asdocument) {
				return $this->doc->saveXML();
			} else {
				$hs=$this->doc->saveXML();
				$s1='/<\?xml version="1.0"\?>/';
				$s2='/<!DOCTYPE \w+>/';
				$s3='/\sxmlns="http:\/\/www.w3.org\/1999\/xhtml"/';
				$ksub = array($s1, $s2, $s3);
				return trim(preg_replace($ksub,'',$hs));
			}
		}
	}

	public function strToNode($value = null) {
		// One should always xml-encode ampersands in URLs in HTML.
		// Joomla doesn't always do this.
		$fragstr = preg_replace('/&(?![\w#]{1,7};)/i','&amp;',$value);
		$fnode = $this->doc->createDocumentFragment();
		set_error_handler(array($this, 'doMsg'), E_ALL | E_STRICT);
		try {
			$fnode->appendXML($fragstr);
		} catch (Exception $ex) {
			$this->doMsg('Attempted fragment:',htmlspecialchars(print_r($fragstr,true)));
			restore_error_handler();
			throw $ex;
		}
		restore_error_handler();
		return $fnode;
	}

	public function count($xpath,$ref = null) {
		if (!is_null($this->doc) && !is_null($this->xp)) {
			if (is_null($ref)) {
				$entries = $this->xp->query($xpath);
			} else {
				$entries = $this->xp->query($xpath,$ref);
			}
			if ($entries) {
				return $entries->length;
			} else {
				$this->doMsg('NView: count() ' . $xpath . ' failed.');
				return 0;
			}
		} else {
			$this->doMsg('NView: count() ' . $xpath . ' attempted on a non-document.');
		}
	}

	public function consume($xpath, $ref = null) {
		$retval = null;
		$retval = $this->get($xpath, $ref);
		if (!is_null($retval)) {
			$this->set($xpath,null,$ref);
		}
		return $retval;
	}

	public function get($xpath, $ref = null) {
		$retval = null;
		if (!is_null($this->doc) && !is_null($this->xp)) {
			set_error_handler(array($this,'doMsg'),E_ALL | E_STRICT);
			if (is_null($ref)) {
				$entries = $this->xp->query($xpath);
			} else {
				$entries = $this->xp->query($xpath,$ref);
			}
			if ($entries) {
				switch ($entries->length) {
					case 1: {
						$entry = $entries->item(0);
						if ($entry->nodeType == XML_TEXT_NODE) {
							$retval = $entry->nodeValue;
						} elseif ($entry->nodeType == XML_ATTRIBUTE_NODE) {
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
				$this->doMsg('NView::get() ' . $xpath . ' failed.');
			}
			restore_error_handler();
		} else {
			$this->doMsg('NView::get() ' . $xpath . ' attempted on a non-document.');
		}
		return $retval;
	}

	public function set($xpath,$value = null,$ref = null) {
		//replace node at string xpath with node 'value'.
		set_error_handler(array($this,'doMsg'), E_ALL | E_STRICT);
		if (!is_null($this->doc) && !is_null($this->xp)) {
			$gap = self::GAP_NONE;
			if (substr($xpath,-6)=="-gap()") {
				$xpath = substr($xpath,0,-6); //remove the -gap();
				if (substr($xpath,-6)=="/child") {
					$xpath = substr($xpath,0,-6); //remove the child;
					$gap=self::GAP_CHILD;
				}
				elseif (substr($xpath,-10)=="/preceding") {
					$xpath = substr($xpath,0,-10); //remove the child;
					$gap=self::GAP_PRECEDING;
				}
				elseif (substr($xpath,-10)=="/following") {
					$xpath = substr($xpath,0,-10); //remove the child;
					$gap=self::GAP_FOLLOWING;
				}
			}
			//now act according to value type.
			switch (gettype($value)) {
				case "NULL": {
					if ($gap==self::GAP_NONE) {
						if (is_null($ref)) {
							$entries = $this->xp->query($xpath);
						} else {
							$entries = $this->xp->query($xpath,$ref);
						}
						if ($entries) {
							foreach($entries as $entry) {
								if ($entry instanceof DOMAttr) {
									$entry->parentNode->removeAttributeNode($entry);
								} else {
									$n = $entry->parentNode->removeChild($entry);
									unset($n); //not sure if this is needed..
								}
							}
						} else {
							$this->doMsg('NView::set() ' . $xpath . ' failed.');
						}
					}
				} break;
				case "boolean":
				case "integer":
				case "double":
				case "string":
				case "double":
				case "object" : { //probably a node.
					if (gettype($value) != "object" || is_subclass_of($value,'DOMNode') || $value instanceof DOMNodeList || $value instanceof NView) {
						if (is_null($ref)) {
							$entries = $this->xp->query($xpath);
						} else {
							$entries = $this->xp->query($xpath,$ref);
						}
						if ($entries) {
							if ($entries->length == 0 && $gap == self::GAP_NONE) { //maybe this is a new attribute!?
								$spoint = strrpos($xpath,'/');
								$apoint = strrpos($xpath,'@');
								if ($apoint == $spoint+1) {
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
									$this->doMsg('NView::set() ' . $xpath . ' failed.');
								}
							}
							if ($value instanceof NView) {
								$value = $value->doc->documentElement;
							}
							foreach($entries as $entry) {
								if ($gap == self::GAP_NATTR && $entry->nodeType==XML_ELEMENT_NODE && isset($aname)) {
									$entry->setAttribute($aname,htmlspecialchars(utf8_encode($value),ENT_COMPAT,'',false));
								} else {
									if ($entry->nodeType == XML_ATTRIBUTE_NODE && gettype($value) != "object") {
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
														if (is_null($entry->nextSibling)) {
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
											if (gettype($value) != "object") {
												$nodf = $this->strToNode('' . $value);
												$node = $this->doc->importNode($nodf, true);
											} else {
												$nodc = $value->cloneNode(true);
												$node = $this->doc->importNode($nodc, true);
											}
											if (!is_null($node->firstChild))
											{
												switch ($gap) {
													case self::GAP_NONE: {
														$entry->parentNode->replaceChild($node, $entry);
													} break;
													case self::GAP_PRECEDING: {
														$entry->parentNode->insertBefore($node, $entry);
													} break;
													case self::GAP_FOLLOWING: {
														if (is_null($entry->nextSibling)) {
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
							}
							if (gettype($value) != "object" || $value->nodeType == XML_TEXT_NODE && $gap != self::GAP_NONE) {
								$this->doc->normalizeDocument();
							}
						} else {
							$this->doMsg('NView::set() ' . $xpath . ' failed.');
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
		if (count($this->msgs) !== 0) {
			$app = JFactory::getApplication();
			foreach($this->msgs as $m) {
				if ($m[3] == 0) {
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
		if (!is_null($value->doc)) {
			$this->initDoc();
			$this->doc = $value->doc->cloneNode(true);
			$this->initXPath();
		}
	}

	private function con_node($value) {
		if ($value->nodeType == XML_DOCUMENT_NODE) {
			$this->doc = $value->cloneNode(true);
			$this->initXPath();
		} elseif ($value->nodeType == XML_ELEMENT_NODE) {
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
			$this->doMsg("NView:: __construct does not (yet) support construction from nodes of type " . $value->nodeType);
		}
	}

	private function con_file($value) {
		if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
			$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,3);
		} else {
			$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		}
		if (empty($value)) {
			$xview= str_replace(".php",".xhtml",$bt[2]['file']);
		} else {
			if (strpos($value,'.') === false) {
				$value .= '.xhtml';
			}
			if (!file_exists($value)) {
				//bt[3] is the caller to the construct()
				$xview = dirname($bt[2]['file']) . '/' . $value;
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
		} elseif (strpos($value,'<') === false) {
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

	public function doModules($a = array(),$p)
	{
		$mml = $this->get("//*[@data-jmod]");
		if ($mml instanceof DOMNodeList) {
			$count = $mml->length;
			for($pos=$count; $pos > 0 ; $pos-- ) { //xpath uses 1-indexing
				NMod::doModule( $this, $mml->item($pos - 1), $a, $p, $pos );
			}
		} else {
			if ($mml instanceof DOMNode) {
				NMod::doModule( $this, $mml, $a, $p, 1 );
			}
		}
	}

	public function doComponents() {
		$entries = $this->xp->query("//*[@data-comp]");
		if ($entries->length > 0) {
			$app = JFactory::getApplication();
			$menu = $app->getMenu();
			$nc = new NComposite();
			$nc->pushState();
			foreach($entries as $entry) {
				$alias = $this->get('@data-comp',$entry);
				$mi = $menu->getItems('alias',$alias,true);
				if ( !empty($mi)) {
					ob_start();
					$nc->doComposite($mi,$entry);
					$this->set('.',ob_get_contents(),$entry);
					ob_end_clean();
				} else {
					$this->doMsg("Alias '" . $alias . "' not found while adding data-comp.");
				}
			}
			$nc->popState();
			$this->set("//*[@data-comp]");
		}
	}

	private function fixHrefs() {
		//This is a lightweight alias translator using !home or whatever.
		$xq = "(//*)[starts-with(@href,'!')]";
		$entries = $this->xp->query($xq);
		if ($entries->length > 0) {
			$urls = array();	//simple cache so that each link is only processed once..
			$base = JURI::base();
			$menu = JApplication::getInstance('site')->getMenu();	//assumes front-end..
			foreach($entries as $entry)
			{
				$url="#";
				$alias = substr($this->get('@href',$entry),1); //'!foo' --> 'foo'
				$back = strpbrk($alias,'?#&');
				if ( $back === false ) {
					$back="";
				} else {
					$alias = substr($alias,0,-strlen($back));
				}
				if (isset($urls[$alias])) {
					$url=$urls[$alias];
				} else {
					$mi = $menu->getItems('alias',$alias,true);
					if ( !empty($mi)) {
						$url = $base . $mi->route;
						$urls[$alias]=$url;
					} else {
						$this->doMsg("Alias '" . $alias . "' not found while fixing !alias hrefs.");
					}
				}
				$this->set('@href',$url . $back,$entry);
			}
		}
	}

//void elements area, base, br, col, hr, img, input, link, meta, param, command, keygen,source
	private function tidyView() {
		$this->fixHrefs();
		$xq = "//*[not(node())][not(contains('[area|base|br|col|hr|img|input|link|meta|param|command|keygen|source]',local-name()))]";
		$entries = $this->xp->query($xq);
		if ($entries) {
			foreach($entries as $entry) {
				$entry->appendChild($this->doc->createTextNode(''));
			}
		}
	}

	/**
	* parser message handler..
	*/
	function doMsg($errno, $errstr='', $errfile='', $errline=0) {
		$this->msgs[] = array($errno, $errstr, $errfile, $errline); //this adds to the array.
	}


}
