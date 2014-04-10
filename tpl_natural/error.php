<?php
defined('_JEXEC') or die;
mb_internal_encoding( 'UTF-8' );

$v = new NView();

$app = JFactory::getApplication();
$jdoc = JFactory::getDocument();
$debug = JFactory::getConfig()->get('debug_lang');
$user = JFactory::getUser();
$params = $app->getTemplate(true)->params;

$this->language = $jdoc->language;
$this->direction = $jdoc->direction;

// Detecting Active Variables
$option   = $app->input->getCmd('option', '');
$view     = $app->input->getCmd('view', '');
$layout   = $app->input->getCmd('layout', '');
$task     = $app->input->getCmd('task', '');
$itemid   = $app->input->getCmd('Itemid', '');
$sitename = $app->getCfg('sitename');

//Setting shorthands
$burl	= $this->baseurl;

JHtml::_('bootstrap.framework');
JHtml::_('bootstrap.loadCss', false, $this->direction);

//Now set the view with parameter, context, and acc. to parameter logic.
$v->set("/h:html/@lang",$jdoc->language);
$v->set("/h:html/@dir",$jdoc->direction);
$v->set("//h:title/@dir",$this->title . ':' . htmlspecialchars($this->error->getMessage()));

if ($params->get('templateColor')) {
    $tstyle = $v->get("//h:style[2]/text()");
    $ksub = array(  '#5396bd' => $params->get('templateColor') );
    $vstyle = str_replace(array_keys($ksub),array_values($ksub),$tstyle);
    $v->set("//h:style[2]/text()","" . $vstyle);
} else {
    $v->set("//h:style[2]");
}
$v->set("//h:link/@href","//fonts.googleapis.com/css?family=Open+Sans");
$v->set("//h:style[1]/text()","h1,h2,h3,h4,h5,h6,.site-title { font-family: 'Open+Sans',sans-serif; }");
$bclass = 'site ' . $option . ' view-' . $view
    . ($layout ? ' layout-' . $layout : ' no-layout')
    . ($task ? ' task-' . $task : ' no-task')
    . ($itemid ? ' itemid-' . $itemid : '')
    . ' fluid';
$v->set("//h:body/@class",$bclass);
$v->set("//h:div[@class='container']/@class",'container-fluid');
$v->set("//h:h2[@data-xp='error_head']/child-gap()",$this->error->getCode());
$v->set("//h:code[@data-xp='error']/child-gap()",htmlspecialchars(print_r($this->error,true)));

