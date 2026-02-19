<?php
/**
 * @version    1.0.0
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
		
		$msg = '[API SERVER] BuildsController::display() - Method: ' . $method . ', Start: ' . $limitstart . ', Limit: ' . $limit;
		file_put_contents('/tmp/api_builds_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
		
		return parent::display($cachable, $urlparams);
	}
}
