<?php
/**
 * @package     LibNatural
 *
 * @copyright   Copyright (C) 2005 - 2014 Red Snapper Ltd. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;
mb_internal_encoding( 'UTF-8' );

JLoader::import('html.renderer.modules'); 	//module helper

class NMod
{
/* v is the view instance. mm is a module domnode identified by the attribute @data-jmod */
	protected static function doModule( &$nv, &$mm, &$attribs, &$p, $count = 1 )
	{
	//hard set attributes in the view override any inherited attributes.
		$contents="";
		$name = $nv->get("@data-jmod",$mm);
		$style = $nv->get("@style",$mm);
		$title = $nv->get("@title",$mm);
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
		$nv->set($xpath,$contents);
	}

	public static function doModules(&$nv,$a = array(),$p)
	{
		$mml = $nv->get("//*[@data-jmod]");
		if ($mml instanceof DOMNodeList) {
			$count = $mml->length;
			for($pos=$count; $pos > 0 ; $pos-- ) { //xpath uses 1-indexing
				NMod::doModule( $nv, $mml->item($pos - 1), $a, $p, $pos );
			}
		} else {
			if ($mml instanceof DOMNode) {
				NMod::doModule( $nv, $mml, $a, $p, 1 );
			}
		}
	}

	public static function asModule($ctrl, $view, $strip, $a = array(), $p )
	{
		if ($strip == "1") {
			$wss   = array("\r\n", "\n", "\r", "\t"); //what we will remove
			$view = str_replace($wss,"", $view);
		}
		$nv = new NView($view);
		if (!empty($ctrl)) {
			$app = JFactory::getApplication();
			ob_start();
			try {
				if (eval($ctrl) === FALSE) {
					$app->enqueueMessage('Module control failed: ' . $ctrl,'error');
				}
			} catch (Exception $e) {
				$app->enqueueMessage( $e->getMessage() . ' while evaluating <code>' . print_r($ctrl,true) . '</code>', 'error');
			}
			$output = ob_get_contents();
			ob_end_clean();
			if (!empty($output)) {
				$app->enqueueMessage( 'Spurious output while evaluating <code>' . print_r($ctrl,true) . '</code> with <pre>' . $output . '</pre>', 'error');
			}
		}
		NMod::doModules($nv,$a,$p);
		return $nv->show(FALSE); //don't want to render as document, yet.
	}

};
