<?php
/**
 * @version    1.0.1
 * @package    com_ra_develop
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_develop\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;

/**
 * View class for the component dashboard.
 *
 * @since  0.3.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		// Check for errors.
//		if (count($errors = $this->get('Errors')))
//		{
//			throw new \Exception(implode("\n", $errors));
//		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   0.3.0
	 */
	protected function addToolbar()
	{
		$canDo = ContentHelper::getActions('com_ra_develop');

		ToolbarHelper::title(Text::_('COM_RA_DEVELOP'), "generic");

		if ($canDo->get('core.admin'))
		{
			$toolbar = Toolbar::getInstance('toolbar');
			$toolbar->preferences('com_ra_develop');
		}
	}
}
