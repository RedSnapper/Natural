<?php
defined('_JEXEC') or die;
mb_internal_encoding( 'UTF-8' );

class Catmod
{
	public static function render($title,$item)
	{
		$v = new NView();
		$v->set("//h:p/@title",$title);
		$a = array();
		$a["name"] = $item->title;
		$a["text"] = $item->text;
		$a["item"] = $item;
		NMod::doModules($v,$a,$item);
		return $v->doc();
	}
}
