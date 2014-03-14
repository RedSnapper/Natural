<?php
defined('JPATH_PLATFORM') or die;
mb_internal_encoding( 'UTF-8' );

JLoader::import('html.renderer.modules'); 	//module helper

class NMod
{
/* v is the view instance. mm is a module domnode identified by the attribute @data-jmod */
	protected static function doModule( &$v, &$mm, &$attribs, &$p, $count = 1 )
	{
	//hard set attributes in the view override any inherited attributes.
		$contents="";
		$name = $v->get("@data-jmod",$mm);
		$style = $v->get("@style",$mm);
		$title = $v->get("@title",$mm);
		if (!empty($style)) { $attribs["style"] = $style; }
		if (!empty($title)) { $attribs["title"] = $title; }
		$prefix = 'data-';
		if (! is_null($mm->attributes) ) {
			foreach ($mm->attributes as $attr) {
				$anam = $attr->nodeName;
				if (substr($anam, 0, 5) == $prefix) {
					$anam = substr($anam, 5);
					$attribs[$anam] = $attr->nodeValue;
				}
			}
		}
		$module = JModuleHelper::getModule($name, $title);
		if (!is_null($module)) {
			$contents = JModuleHelper::renderModule($module, $attribs);
		}
		$xpath = "(//*[@data-jmod])[". $count . "]";
		$v->set($xpath,$contents);
	}

	public static function doModules(&$v,$a = array(),$p)
	{
		$mml = $v->get("//*[@data-jmod]");
		if ($mml instanceof DOMNodeList) {
			$count = $mml->length;
			for($pos=$count; $pos > 0 ; $pos-- ) { //xpath uses 1-indexing
				NMod::doModule( $v, $mml->item($pos - 1), $a, $p, $pos );
			}
		} else {
			if ($mml instanceof DOMNode) {
				NMod::doModule( $v, $mml, $a, $p, 1 );
			}
		}
	}

	public static function asModule($ctrl, $view, $strip, $a = array(), $p )
	{
		if ($strip == "1") {
			$wss   = array("\r\n", "\n", "\r", "\t"); //what we will remove
			$view = str_replace($wss,"", $view);
		}
		$v = new NView($view);
		eval($ctrl);
		NMod::doModules($v,$a,$p);
		return $v->show(FALSE); //don't want to render as document, yet.
	}

};
