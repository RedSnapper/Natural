<?php
defined ( '_JEXEC' ) or die;
class plgContentArticle extends JPlugin {
	protected $autoloadLanguage = true;
	function onContentPrepareForm($form, $data) {
		$app = JFactory::getApplication();
		$option = $app->input->get('option');
		switch($option) {
			case 'com_content': {
				if ( $app->isAdmin() ) {
					// one cannot switch files based on the contents of data, because
					// data will be empty on for the compose part of the process.
					// however, one could use the parameter - but this will be system-wide.
					JForm::addFormPath(__DIR__ . '/forms');
					$form->loadFile('default', false);
				}
			} break;
			default: break;
		}
		return true;
	}
}
