<?php

/**
 * @version    2.0.1
 * @package    com_ra_develop
 * @author     Barlie Chigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_develop\Site\Model;
// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\Model\ListModel;
use \Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use \Joomla\CMS\Helper\TagsHelper;
use \Joomla\CMS\Layout\FileLayout;
use \Joomla\Database\ParameterType;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Http\HttpFactory;
use \Joomla\Uri\Uri;
use Ramblers\Component\Ra_develop\Site\Helper\JsonHelper;


/**
 * Methods supporting a list of Ra_develop records.
 *
 * @since  0.1.0
 */
class BuildsModel extends ListModel
{
	/**
	 * Data source mode ('local' or 'remote')
	 * @var string
	 */
	protected $dataSource = 'local';

	/**
	 * Remote site ID for API queries
	 * @var int
	 */
	protected $remoteSiteId = null;

	/**
	 * Remote site URL
	 * @var string
	 */
	protected $remoteSiteUrl = null;

	/**
	 * Remote site security token
	 * @var string
	 */
	protected $remoteSiteToken = null;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see    JController
	 * @since  0.1.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'state', 'a.state',
				'ordering', 'a.ordering',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by',
				'build_date', 'a.build_date',
				'environment', 'a.environment',
				'component_id', 'a.component_id',
				'version', 'a.version',
			);
		}

		parent::__construct($config);
	}

	

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   0.1.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// List state information.
		parent::populateState('a.build_date', 'DESC');

		$app = Factory::getApplication();
		$list = $app->getUserState($this->context . '.list');

		$value = $app->getUserState($this->context . '.list.limit', $app->get('list_limit', 25));
		$list['limit'] = $value;
		
		$this->setState('list.limit', $value);

		$value = $app->input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $value);

		$ordering  = $this->getUserStateFromRequest($this->context .'.filter_order', 'filter_order', 'a.build_date');
		$direction = strtoupper($this->getUserStateFromRequest($this->context .'.filter_order_Dir', 'filter_order_Dir', 'DESC'));
		
		$this->setState('list.ordering', $ordering);
		$this->setState('list.direction', $direction);
		
		if(!empty($ordering) || !empty($direction))
		{
			$list['fullordering'] = $ordering . ' ' . $direction;
		}

		$app->setUserState($this->context . '.list', $list);

		// Capture data source mode and remote site selection from menu item params
		$params = $app->getParams('com_ra_develop');
		$this->dataSource = $params->get('data_source', 'local');
		
		// DEBUG: Write to file to check if params are being read
		$debugMsg = "populateState: data_source=" . $this->dataSource . ", remote_site_id=" . $params->get('remote_site_id', 'NOT_SET') . "\n";
		file_put_contents(JPATH_ADMINISTRATOR . '/logs/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $debugMsg, FILE_APPEND);
		
		$this->setState('data.source', $this->dataSource);

		if ($this->dataSource === 'remote')
		{
			$this->remoteSiteId = (int) $params->get('remote_site_id', 0);
			$this->setState('data.remote_site_id', $this->remoteSiteId);
			
			file_put_contents(JPATH_ADMINISTRATOR . '/logs/builds_model_debug.log', date('Y-m-d H:i:s') . " Remote mode - site ID: {$this->remoteSiteId}\n", FILE_APPEND);
			
			// Retrieve the remote site URL
			if ($this->remoteSiteId > 0)
			{
				$db = $this->getDbo();
				$query = $db->getQuery(true)
					->select('url, `token`')
					->from($db->quoteName('#__ra_api_sites'))
					->where('id = ' . (int) $this->remoteSiteId);
				
				$result = $db->setQuery($query)->loadObject();
				if ($result)
				{
					$this->remoteSiteUrl = $result->url;
					$this->remoteSiteToken = $result->token;
					file_put_contents(JPATH_ADMINISTRATOR . '/logs/builds_model_debug.log', date('Y-m-d H:i:s') . " Remote URL: {$this->remoteSiteUrl}\n", FILE_APPEND);
				}
			}
		}

		$context = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $context);

		// Split context into component and optional section
		if (!empty($context))
		{
			$parts = FieldsHelper::extract($context);

			if ($parts)
			{
				$this->setState('filter.component', $parts[0]);
				$this->setState('filter.section', $parts[1]);
			}
		}
	}

	/**
	 * Build an SQL query to load the list data for local mode.
	 *
	 * @return  DatabaseQuery
	 *
	 * @since   0.1.0
	 */
	protected function getListQuery()
	{
		// For remote mode, return null - data will be fetched via API
		if ($this->getState('data.source') === 'remote')
		{
			return null;
		}

		// Create a new query object for local mode.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
					$this->getState(
							'list.select', 'DISTINCT a.*'
					)
			);

		$query->from('`#__ra_builds` AS a');
		
	$query->select('t.name AS extension_type');
	$query->join('LEFT', '#__ra_extensions AS e ON e.name=a.component_name');
	$query->join('LEFT', '#__ra_extension_types AS t ON t.id=e.extension_type_id');
		
	if (!Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_ra_develop'))
	{
		$query->where('a.state = 1');
	}
	else
	{
		$query->where('(a.state IN (0, 1))');
	}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('( a.component_id LIKE ' . $search . ' )');
			}
		}
		

		
		
		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'ASC');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Method to get an array of data items
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function getItems()
	{
		$dataSource = $this->getState('data.source');
		// If remote mode, fetch from API
		if ($dataSource === 'remote')
		{
			return $this->getRemoteItems();
		}
		// Otherwise use local database query
		$items = parent::getItems();
		return $items;
	}

	/**
	 * Override parent _getListCount to handle remote mode
	 * TEMPORARILY DISABLED - testing if causing 500 error
	 * 
	 * @return integer Number of items
	 */
	protected function _getListCount_DISABLED()
	{
		// For remote mode, we can't use the standard query-based counting
		// Return a static count or fetch from remote
		if ($this->getState('data.source') === 'remote')
		{
			// For remote API, we'll use a placeholder count or fetch dynamically
			// This is used for pagination calculation
			return $this->getRemoteTotalCount();
		}

		// For local mode, use parent implementation
		return parent::_getListCount();
	}

	/**
	 * Get the pagination object for remote mode
	 * 
	 * @return object Pagination object
	 */
	public function getPagination()
	{
		// For remote mode, create a proper Joomla pagination object
		if ($this->getState('data.source') === 'remote')
		{
			$app = Factory::getApplication();
			$app->enqueueMessage('DIAG: getPagination() called in remote mode', 'notice');
			
			$limitstart = $this->getState('list.start', 0);
			$limit = $this->getState('list.limit', 25);
			$total = $this->getRemoteTotalCount();
			
			// Use proper Joomla Pagination class
			$pagination = new \Joomla\CMS\Pagination\Pagination(
				$total,
				$limitstart,
				$limit
			);
			
			$app->enqueueMessage('DIAG: Remote pagination - total: ' . $total . ', pages: ' . $pagination->pagesTotal, 'notice');
			
			return $pagination;
		}

		// For local mode, use parent implementation
		return parent::getPagination();
	}

	/**
	 * Get total count from remote API
	 *
	 * @return integer Total count of available records
	 */
	protected function getRemoteTotalCount()
	{
		if (empty($this->remoteSiteUrl))
		{
			return 0;
		}

		try
		{
			// Build API URL to get total count with trimmed base URL
			$debugUrl = rtrim($this->remoteSiteUrl, '/');
			$endpoint = '/api/index.php/v1/ra_develop/builds?limit=1';
			$search = $this->getState('filter.search');
			if (!empty($search)) {
				$endpoint .= '&filter=' . urlencode($search);
			}
			// Use JsonHelper with verbose=1
			$api_site_id = $this->remoteSiteId;
			$response = \Ramblers\Component\Ra_develop\Site\Helper\JsonHelper::fetchApiData($api_site_id, $endpoint, 1);
			if (isset($response['error'])) {
				throw new \Exception('API count request failed: ' . $response['error']);
			}
			// Try to get total count from response headers or metadata
			if (isset($response['total'])) {
				return (int) $response['total'];
			} elseif (isset($response['@odata.count'])) {
				return (int) $response['@odata.count'];
			} elseif (isset($response['data']) && is_array($response['data'])) {
				return count($response['data']);
			}
			// Default fallback: return reasonable number for pagination
			return 100;
		}
		catch (\Exception $e)
		{
			Factory::getApplication()->enqueueMessage('Error fetching remote count: ' . $e->getMessage(), 'warning');
			return 0;
		}
	}

	/**
	 * Fetch builds from remote API endpoint
	 *
	 * @return mixed Array of build objects or empty array on failure
	 */
	protected function getRemoteItems()
	{
		// Use JsonHelper to fetch remote build records
		$app = Factory::getApplication();
		$params = $app->getParams('com_ra_develop');
		$verbose = (int) $params->get('api_verbose', 0);
		$api_site_id = $this->remoteSiteId;
		$endpoint = '/api/index.php/v1/ra_develop/builds?limit=' . $this->getState('list.limit', 25) . '&start=' . $this->getState('list.start', 0);

		// Add ordering and filter if present
		$orderCol = $this->getState('list.ordering', 'build_date');
		$orderDirn = $this->getState('list.direction', 'DESC');
		if ($orderCol && $orderDirn) {
			$endpoint .= '&sort=' . $orderCol . ':' . strtolower($orderDirn);
		}
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			$endpoint .= '&filter=' . urlencode($search);
		}

		// Call JsonHelper
		$response = JsonHelper::fetchApiData($api_site_id, $endpoint, $verbose);
		if (isset($response['data']) && is_array($response['data'])) {
			$items = array();
			foreach ($response['data'] as $item) {
				if (isset($item['attributes'])) {
					$items[] = (object) $item['attributes'];
				} else {
					$items[] = (object) $item;
				}
			}
			return $items;
		}
		// Error or empty
		return array();
	}

	/**
	 * Overrides the default function to check Date fields format, identified by
	 * "_dateformat" suffix, and erases the field if it's not correct.
	 *
	 * @return void
	 */
	protected function loadFormData()
	{
		$app              = Factory::getApplication();
		$filters          = $app->getUserState($this->context . '.filter', array());
		$error_dateformat = false;

		foreach ($filters as $key => $value)
		{
			if (strpos($key, '_dateformat') && !empty($value) && $this->isValidDate($value) == null)
			{
				$filters[$key]    = '';
				$error_dateformat = true;
			}
		}

		if ($error_dateformat)
		{
			$app->enqueueMessage(Text::_("COM_RA_DEVELOP_SEARCH_FILTER_DATE_FORMAT"), "warning");
			$app->setUserState($this->context . '.filter', $filters);
		}

		return parent::loadFormData();
	}

	/**
	 * Checks if a given date is valid and in a specified format (YYYY-MM-DD)
	 *
	 * @param   string  $date  Date to be checked
	 *
	 * @return bool
	 */
	private function isValidDate($date)
	{
		$date = str_replace('/', '-', $date);
		return (date_create($date)) ? Factory::getDate($date)->format("Y-m-d") : null;
	}
}
