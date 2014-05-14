<?php
/**
 * @copyright	Copyright ©2013-2014 Red Snapper Ltd. All rights reserved.
 * @license		GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die('Restricted access');

JLoader::import('joomla.application.component.controller');

class CompositeController extends JControllerLegacy
{
	public function makeView($cpath, $tpath, $layout, $name, $type) {
		$view = null;
		$prefix = $this->name . 'view';
		if (file_exists($tpath)) {
			$view = $this->getView($name,$type,$prefix,array( 'layout' => $layout, 'base_path' => $cpath, 'template_path' => $tpath));
		} else {
			$view = $this->getView($name,$type,$prefix,array( 'layout' => 'default', 'base_path' => $cpath ));
		}
		if (! $view ) {
			$view = null;
			$app->enqueueMessage( 'Failed to make view with path ' . $tpath . ', layout ' . $layout . ', name ' . $name . ', type ' . $type ,'error');
		}
		return $view;
	}
}
