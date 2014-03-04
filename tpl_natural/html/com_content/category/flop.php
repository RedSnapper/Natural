<?php
defined('_JEXEC') or die;
mb_internal_encoding( 'UTF-8' );

class Flop
{
	public static function render( $item )
	{
		$v = new NView();	//defaults to this filename.xhtml
		$v->set("//h:h4/comment()",$item->title );
		$v->set("//h:section/comment()",$item->text);
		return $v->doc();
	}
}
