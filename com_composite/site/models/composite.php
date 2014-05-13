<?php

/**
 * @copyright	Copyright ¬©2013-2014 Red Snapper Ltd. All rights reserved.
 * @license		GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die('Restricted access');
JLoader::import('cms.application.applicationcms');
JLoader::import('joomla.application.component.modelitem');

class CompositeJInput extends JInput {

	public static function getData(JInput $j) {
		return $j->data;
	}

	public static function setData(JInput $j, array $a) {
		$j->data = $a;
	}
}


class CompositeJApplication extends JApplicationCms {
	public static function setTemplate($app, $tmpl = null) {
			$app->template = $tmpl;
	}
}

class CompositeModelComposite extends JModelItem
{
	protected $composition;
	protected $item;

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
			$app  = JFactory::getApplication();
			$lang = JFactory::getLanguage();
			$in_array = CompositeJInput::getData($app->input);
			$jinput = $app->input;
			$jtemplate = $app->getTemplate(true);
			$mtype = $jinput->get('id');
			$app->input->set('hitcount',0);
			$document = JFactory::getDocument();
			$vtype = $document->getType(); //'html'
			$base = JURI::base();
			$menu = $app->getMenu();
			$this->item = $menu->getActive();
			$m = $menu->getItems('menutype',$mtype);
			ob_start();
			foreach ($m as $i) {
				if ($i->type === "alias") {
					$ni = $i->params['aliasoptions'];
					$i = $menu->getItem($ni);
				}
				CompositeJApplication::setTemplate($app);
				$menu->setActive($i->id);
				$app->input->set('Itemid',$i->id);
				foreach ($i->query as $key => $value) {
					if ($key === "id") {
						$app->input->set("a_id",$value);
					}
					$app->input->set($key,$value);
				}
				$option = $i->query['option'];
				$cbase = substr($option,4);
				$ccbase = ucfirst($cbase);
				$cclass= $ccbase . 'Controller';
				$template = $app->getTemplate(true)->template;
				$cpath = JPATH_BASE . '/components/' . $option ;
				$lang->load($option);
				$lang->load($option,$cpath);
				$vname = $i->query['view'];
				$dpath = 'components.' . $option;
				$tpath = JPATH_THEMES . '/' . $template . '/' . $vtype . '/' . $option .'/'. $vname .'/';
//There must be a better way..
				JLoader::import($dpath. '.controller', JPATH_BASE );
				JLoader::import($dpath . '.models.' . $vname, JPATH_BASE );
				JLoader::import($dpath . '.models.' . $vname . 's', JPATH_BASE );
				JLoader::import($dpath . '.models.archive', JPATH_BASE );
				JLoader::import($dpath . '.models.category', JPATH_BASE );
				JLoader::import($dpath . '.models.categories', JPATH_BASE );
				JLoader::import($dpath . '.helpers.route', JPATH_BASE );
				JLoader::import($dpath . '.helpers.category', JPATH_BASE );
				JLoader::import($dpath . '.helpers.query', JPATH_BASE );
				JFormHelper::addFormPath($cpath . '/models/' . 'forms');
				JFormHelper::addFormPath($cpath . '/models/' . 'form');
				$jc = new $cclass(array( 'base_path' => $cpath, 'view_path' => $cpath . '/views/'));
				$jv = $jc->makeView($cpath, $tpath, $template, $vname , $vtype );
				try {
					$jc->display();
				}
				catch (Exception $e) {
					$app->enqueueMessage( $e->getMessage() . ' while rendering ' . $tpath . ' with layout ' . $template ,'error');
				}
			}
			$this->composition = ob_get_contents();
			ob_end_clean();
			$app->input = $jinput;
			CompositeJInput::setData($app->input,$in_array);
			$menu->setActive($this->item->id);
			CompositeJApplication::setTemplate($app,$jtemplate);
		}
		return $this->composition;
	}
}
