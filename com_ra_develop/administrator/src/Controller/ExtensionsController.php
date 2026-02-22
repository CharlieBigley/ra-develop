<?php
/**
 * @version    1.0.11
 * @package    com_ra_develop
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_develop\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

/**
 * Components list controller class.
 *
 * @since  0.4.0
 */
class ExtensionsController extends AdminController
{
    public function cancel($key = null, $urlVar = null) {
        $this->setRedirect('index.php?option=com_ra_tools&view=dashboard');
    }	
/**
	 * Method to clone existing Components
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
	public function duplicate()
	{
		// Check for request forgeries
		$this->checkToken();

		// Get id(s)
		$pks = $this->input->post->get('cid', array(), 'array');

		try
		{
			if (empty($pks))
			{
				throw new \Exception(Text::_('COM_RA_DEVELOP_NO_ELEMENT_SELECTED'));
			}

			ArrayHelper::toInteger($pks);
			$model = $this->getModel();
			$model->duplicate($pks);
			$this->setMessage(Text::_('COM_RA_DEVELOP_ITEMS_SUCCESS_DUPLICATED'));
		}
		catch (\Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}

		$this->setRedirect('index.php?option=com_ra_develop&view=extensions');
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    Optional. Model name
	 * @param   string  $prefix  Optional. Class prefix
	 * @param   array   $config  Optional. Configuration array for model
	 *
	 * @return  object	The Model
	 *
	 * @since   0.4.0
	 */
	public function getModel($name = 'Extension', $prefix = 'Administrator', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}


    public function listExtensions(){
        ToolBarHelper::title('Summary of Extensions', 'generic');
        $sql = 'SELECT s.name AS subsystem_name, e.name AS extension_name, t.name AS type_name, "" AS max ';
        $sql .= 'FROM #__ra_extensions AS e ';
        $sql .= 'LEFT JOIN #__ra_sub_systems AS s ON s.id = e.subsystem_id ';
        $sql .= 'LEFT JOIN #__ra_extension_types AS t ON t.id = e.extension_type_id ';
        $sql .= 'ORDER BY s.name, e.name';
        $toolsHelper = new ToolsHelper;
        $objTable = new ToolsTable;
        $objTable->add_header("Sub system,Extension Name,Extension Type, Latest Version");
        $rows = $toolsHelper->getRows($sql);
        foreach ($rows as $row) {
            $objTable->add_item($row->subsystem_name);
            $objTable->add_item($row->extension_name);
            $objTable->add_item($row->type_name);
			$objTable->add_item($row->max);
            $objTable->generate_line();
        }
        $objTable->generate_table();
        $back = 'administrator/index.php?option=com_ra_tools&view=dashboard';
        echo $toolsHelper->backButton($back);
    }

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   0.4.0
	 *
	 * @throws  Exception
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$pks   = $this->input->post->get('cid', array(), 'array');
		$order = $this->input->post->get('order', array(), 'array');

		// Sanitize the input
		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		Factory::getApplication()->close();
	}
}
