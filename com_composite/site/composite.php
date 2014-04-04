<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JLoader::import('joomla.application.component.controller');

// Get an instance of the controller prefixed by Composite
$controller = JControllerLegacy::getInstance('Composite');

// Perform the Request task
$input = JFactory::getApplication()->input;
$controller->execute($input->getCmd('task'));

// Redirect if set by the controller
$controller->redirect();
