<?php
defined('_JEXEC') or die;
mb_internal_encoding( 'UTF-8' );

$v = new NView();	//defaults to this filename.xhtml

$params  = $this->item->params;
$user    = JFactory::getUser();

$this->pageclass_sfx;
$v->set("//h:article/@class",$this->pageclass_sfx);
$v->set("//h:section[1]/comment()",$this->item->text);
/*
if ($params->get('show_noauth') == true && $user->get('guest')) {
	$v->set("//h:section[h:code]");
} else {
	$v->set("//h:code/comment()",print_r($this->item,true));
}
*/
echo $v->show();
