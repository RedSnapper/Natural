<?php
defined('_JEXEC') or die;
mb_internal_encoding( 'UTF-8' );

$app = JFactory::getApplication();
$jdoc = JFactory::getDocument();
$input = $app->input;
$params = $this->params;

//shorthand
$v = & $this->nv;

// Detecting Active Variables
$option   = $input->getCmd('option', '');
$view     = $input->getCmd('view', '');
$layout   = $input->getCmd('layout', '');
$task     = $input->getCmd('task', '');
$itemid   = $input->getCmd('Itemid', '');

JHtml::_('bootstrap.framework');
JHtml::_('bootstrap.loadCss', false, $this->direction);

//Now set the view with parameter, context, and acc. to parameter logic.
$v->set("/h:html/@lang",$jdoc->language);
$v->set("/h:html/@dir",$jdoc->direction);

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

$topleft = $this->countModules('topleft');
$topright = $this->countModules('topright');
$panels  = $this->countModules('panel');
$aside = $this->countModules('aside');

if (!$topleft && !$topright) {
    $v->set("//h:header"); //delete container.
} else {
	if (!$topleft) {
    	$v->set("//h:div[h:div[@data-name='topleft']]"); //delete container.
	}
	if (!$topright) {
    	$v->set("//h:div[h:div[@data-name='topright']]"); //delete container.
	}
}
if (! $this->countModules('banner')) {
    $v->set("//h:div[@data-name='banner']"); //delete div.
}
if (! $this->countModules('footer')) {
    $v->set("//h:footer"); //delete container.
}

if (!$panels) {
    $v->set("//*[@id='panels']"); //delete container if there is none.
}
if (! $aside ) {
    $v->set("//*[@id='aside']"); //delete container-container.
}
if (! $this->countModules('navigation')) {
    $v->set("//h:nav"); //delete container.
}

if ($panels) {
    if ($aside) {
        $mainwidth = "span6";
    }
    else{
        $mainwidth = "span9";
    }
} elseif ($aside) {
	$mainwidth = "span9";
} else {
	$mainwidth = "span12";
}
$v->set("//h:main/@class",$mainwidth);
