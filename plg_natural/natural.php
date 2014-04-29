<?php
/**
 * @copyright	Copyright ©2013-2014 Red Snapper Ltd. All rights reserved.
 * @license		GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */
defined( '_JEXEC' ) or die;

class PlgSystemNatural extends JPlugin {
	public function onAfterInitialise() {

// JControllerLegacy is ONLY a monkey-patch. See #3462 / Tracker #33623
		JLoader::register('JControllerLegacy', JPATH_LIBRARIES . '/natural/legacy.php', true);

// JHtml is ONLY a monkey-patch for J3.21-3. See #3224 / Tracker# 32989
		JLoader::register('JHtml', JPATH_LIBRARIES . '/natural/html.php', true);

// Add Natural View / XPath support
		JLoader::register('JDocumentHTML', JPATH_LIBRARIES . '/natural/documenthtml.php', true);

// Helper registrations for JLoader
		JLoader::registerPrefix('N', JPATH_LIBRARIES . '/natural' );

	}
}
