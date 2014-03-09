<?php
defined('_JEXEC') or die;
mb_internal_encoding( 'UTF-8' );

JLoader::import('flop', dirname(__FILE__));
JLoader::import('catmod', dirname(__FILE__));

$v = new NView();	//defaults to this filename.xhtml
$s = new NView($v->get("//h:section"),'xml'); //load section
$v->set("//h:section"); //... and release

foreach ($this->items as $item) {
	switch ( $item->xreference ) {
		case "flop": {
			$v->set("//h:article/child-gap()",Flop::render($item));
		} break;
		default: {
			if(empty($item->xreference)) {
				$i = new NView($s,'class'); //copy section
				$p = $item->params;
				$i->set("//h:h3/comment()",$item->title );
				$i->set("//h:section/comment()",$item->text);
				$v->set("//h:article/child-gap()",$i);
				$mutext = $item->params->get('mutext','');
				$mumore = $item->params->get('mumore','');
				$muval = $item->params->get('muval','');
				if(empty($muval)) { $v->set("//h:p[3]"); } else { $v->set("//h:p[3]/child-gap()",$muval); }
				if(empty($mumore)) { $v->set("//h:p[2]"); } else { $v->set("//h:p[2]/child-gap()",$mumore); }
				if(empty($mutext)) { $v->set("//h:p[1]"); } else { $v->set("//h:p[1]/child-gap()",$mutext); }
			} else {
				$v->set("//h:article/child-gap()",Catmod::render($item->xreference,$item));
			}
		} break;
	}
}
echo $v->show();
