<?php
/**
 * @version    1.0.7
 * @package    com_ra_develop
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_develop\Api\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\Factory;

/**
 * The Builds API controller
 *
 * @since  1.0.0
 */
class BuildsController extends ApiController
{
    /**
     * The content type of the item.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $contentType = 'builds';

    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $default_view = 'builds';

	/**
	 * Authorizes the request (override for temporary public access)
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	protected function authorizeRequest()
	{
		// TEMPORARY: Allow public API access for testing
		// TODO: Remove this bypass once token authentication is working
		return true;
	}

	/**
	 * Log API requests
	 * 
	 * @return void
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$app = Factory::getApplication();
		$method = $app->input->getMethod();
		$limitstart = $app->input->get('start', 0, 'uint');
		$limit = $app->input->get('limit', 25, 'uint');
		
		$msg = '[API SERVER] BuildsController::display() invoked';
		$msg .= "\n  Method: " . $method;
		$msg .= "\n  Start: " . $limitstart;
		$msg .= "\n  Limit: " . $limit;
		$msg .= "\n  Request URI: " . $_SERVER['REQUEST_URI'] ?? 'N/A';
		$msg .= "\n  Query String: " . $_SERVER['QUERY_STRING'] ?? 'N/A';
		$logPath = JPATH_ADMINISTRATOR . '/logs/api_builds_debug.log';
		file_put_contents($logPath, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->insert($db->quoteName('#__ra_logfile'))
			->set('sub_system = ' . $db->quote('RA Develop'))
			->set('record_type = ' . $db->quote(10))
			->set('ref = ' . $db->quote('builds'))
			->set('message = ' . $db->quote($msg));
		$db->setQuery($query)->execute();
		
		return parent::display($cachable, $urlparams);
	}
}
