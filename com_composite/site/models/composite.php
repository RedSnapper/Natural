<?php
defined('_JEXEC') or die('Restricted access');
JLoader::import('joomla.application.component.modelitem');

class CompositeModelComposite extends JModelItem
{
	protected $composition;
	public function getComposite()
	{
		if (!isset($this->composition))
		{
			$app  = JFactory::getApplication();
			$lang = JFactory::getLanguage();

			$jinput = clone $app->input;
			$mtype = $jinput->get('id');
			$app->input->set('hitcount',0);
			$base = JURI::base();
			$menu = $app->getMenu();
			$m = $menu->getItems('menutype',$mtype);
			ob_start();
			foreach ($m as $i) {
				$app->input->set('Itemid',$i->id);
				foreach ($i->query as $key => $value) {
					$app->input->set($key,$value);
				}
				$option = $i->query['option'];
				$cbase = substr($option,4);
				$ccbase = ucfirst($cbase);
				$cpath = JPATH_BASE . '/components/' . $option ;
				$lang->load($option);
				$lang->load($option,$cpath);
				$vname = $i->query['view'];
				$dpath = 'components.' . $option;
//				JLoader::discover($ccbase, $cpath, false, true);
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
				$cclass= $ccbase . 'Controller';
//				JLoader::load($cclass);
				$jc = new $cclass();
				$jc->addViewPath($cpath . '/views/');
				$jm = $jc->getModel($vname,'',array('ignore_request' => true));
				$jm->setState( $cbase . '.id', $i->query['id']);
				$jv = $jc->getView($vname,'html','',array('layout' => 'default','base_path' => $cpath));
				$jc->display();
			}
			$this->composition = ob_get_contents();
			ob_end_clean();
			$app->input = $jinput;
		}
		return $this->composition;
	}
}
