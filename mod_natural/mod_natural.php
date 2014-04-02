<?php
defined( '_JEXEC' ) or die( 'Restricted access' );

$p_control = $params->get('Control','');
$p_view = $params->get('View','');
$p_strip = $params->get('strip','');
//ECHO!! Yeah, well - it's joomla.
echo NMod::asModule($p_control, $p_view, $p_strip, $attribs, $params);

