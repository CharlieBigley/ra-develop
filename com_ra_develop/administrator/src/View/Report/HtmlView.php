<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_develop
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2024 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_develop\Administrator\View\Report;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Joomla\CMS\HTML\Helpers\Sidebar;

/**
 * View class for a list of Report.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView {

    protected $component_params;

    /**
     * Display the view
     *
     * @param   string  $tpl  Template name
     *
     * @return void
     *
     * @throws Exception
     */
    public function display($tpl = null) {
        $this->component_params = Componenthelper::getParams('com_ra_develop', 'ra');

        $this->sidebar = Sidebar::render();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function addToolbar() {

        ToolbarHelper::title(Text::_('COM_RA_DEVELOP_TITLE_REPORT'), "generic");

        $toolbar = Toolbar::getInstance('toolbar');

//        if ($canDo->get('core.admin')) {
        $toolbar->preferences('com_ra_develop');
//        }
        // Set sidebar action
        Sidebar::setAction('index.php?option=com_ra_develop&view=report');
    }

}
