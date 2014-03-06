<?php
defined( '_JEXEC' ) or die;

class PlgSystemNatural extends JPlugin {
	public function onAfterInitialise() {
		JLoader::register('JDocumentHTML', JPATH_LIBRARIES . '/natural/documenthtml.php', true);

// JHtml is ONLY a monkey-patch for J3.21. See tracker# 32989
		JLoader::register('JHtml', JPATH_LIBRARIES . '/natural/html.php', true);

// Helper registrations for JLoader
		JLoader::registerPrefix('N', JPATH_LIBRARIES . '/natural' );
	}
}
