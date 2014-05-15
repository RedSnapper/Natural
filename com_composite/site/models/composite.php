<?php

/**
 * @copyright	Copyright ©2013-2014 Red Snapper Ltd. All rights reserved.
 * @license		GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die('Restricted access');
JLoader::import('joomla.application.component.modelitem');

class CompositeModelComposite extends JModelItem
{
	protected $composition;
	protected $item;

//used externally to load the current component item
//which, for com_composite, is a menu-item.
	public function getItem() {
		if (!isset($this->item))
		{
			$app  = JFactory::getApplication();
			$menu = $app->getMenu();
			$this->item = $menu->getActive();
		}
		return $this->item;
	}

	public function getComposite()
	{
		if (!isset($this->composition))
		{
			$app = JFactory::getApplication();
			$menu = $app->getMenu();
			$this->item = $menu->getActive();
			$m = $menu->getItems('menutype',$app->input->get('id'));
			$nc = new NComposite();
			$nc->pushState();
			ob_start();
			foreach ($m as $i) {
				$nc->doComposite($i);
			}
			$this->composition = ob_get_contents();
			ob_end_clean();
			$nc->popState();
		}
		return $this->composition;
	}
}
