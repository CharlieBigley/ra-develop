<?php
/**
 * @version    1.0.1
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
		file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $debugMsg, FILE_APPEND);
		
		$this->setState('data.source', $this->dataSource);

		if ($this->dataSource === 'remote')
		{
			$this->remoteSiteId = (int) $params->get('remote_site_id', 0);
			$this->setState('data.remote_site_id', $this->remoteSiteId);
			
			file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . " Remote mode - site ID: {$this->remoteSiteId}\n", FILE_APPEND);
			
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
					file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . " Remote URL: {$this->remoteSiteUrl}\n", FILE_APPEND);
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
		$app = Factory::getApplication();
		$dataSource = $this->getState('data.source');
		
		// Log diagnostic info
		$logMsg = 'BuildsModel::getItems() - data.source state: ' . ($dataSource ? $dataSource : 'NULL/FALSE');
		$app->enqueueMessage('DIAG: ' . $logMsg, 'notice');
		\Joomla\CMS\Log\Log::add($logMsg, \Joomla\CMS\Log\Log::INFO, 'com_ra_develop');

		// If remote mode, fetch from API
		if ($dataSource === 'remote')
		{
			$app->enqueueMessage('DIAG: Routing to getRemoteItems()', 'notice');
			return $this->getRemoteItems();
		}

		$app->enqueueMessage('DIAG: Routing to parent::getItems()', 'notice');
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
			// Build API URL to get total count
			$apiUrl = $this->remoteSiteUrl . '/api/v1/ra_develop/builds';
			
			$params = array(
				'limit' => 1  // We only need count, not data
			);

			// Add search filter if present
			$search = $this->getState('filter.search');
			if (!empty($search))
			{
				$params['filter'] = $search;
			}

			$query = http_build_query($params);
			$fullUrl = $apiUrl . '?' . $query;

			// Make HTTP request
			$http = HttpFactory::getHttp();
			$response = $http->get($fullUrl);

			if ($response->code !== 200)
			{
				throw new \Exception('API count request failed with status code: ' . $response->code);
			}

			$data = json_decode($response->body);

			// Try to get total count from response headers or metadata
			if (isset($data->total))
			{
				return (int) $data->total;
			}
			elseif (isset($data->{'@odata.count'}))
			{
				return (int) $data->{'@odata.count'};
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
		$app = Factory::getApplication();

		// Diagnostic: Check remote site URL
		if (empty($this->remoteSiteUrl))
		{
			$msg = 'Diagnostic: Remote site URL is empty. Remote site ID: ' . $this->remoteSiteId;
			$app->enqueueMessage($msg, 'warning');
			file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
			return array();
		}

		$msg = '[REMOTE INVOKE] Remote site URL: ' . $this->remoteSiteUrl;
		if ($this->remoteSiteToken)
		{
			$msg .= ', Token present: yes';
		}
		$app->enqueueMessage($msg, 'notice');
		file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);

		try
		{
			// Build API URL with query parameters
			$apiUrl = $this->remoteSiteUrl . '/api/v1/ra_develop/builds';
			
			// Add pagination parameters
			$limit = $this->getState('list.limit', 25);
			$start = $this->getState('list.start', 0);
			
			$params = array(
				'limit' => $limit,
				'start' => $start
			);

			// Add ordering
			$orderCol = $this->getState('list.ordering', 'build_date');
			$orderDirn = $this->getState('list.direction', 'DESC');
			
			if ($orderCol && $orderDirn)
			{
				$params['sort'] = $orderCol . ':' . strtolower($orderDirn);
			}

			// Add search filter if present
			$search = $this->getState('filter.search');
			if (!empty($search))
			{
				$params['filter'] = $search;
			}

			// Build query string
			$query = http_build_query($params);
			$fullUrl = $apiUrl . '?' . $query;

			$msg = '[REMOTE] API request URL: ' . $fullUrl;
			$app->enqueueMessage($msg, 'notice');
			file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);

			// Make HTTP request
			$http = HttpFactory::getHttp();
			
			// Prepare headers with security token if available
			$options = array();
			if (!empty($this->remoteSiteToken))
			{
				$options['headers'] = array(
					'Authorization' => 'Bearer ' . $this->remoteSiteToken
				);
				$msg = '[REMOTE] Adding Authorization header with token';
				file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
			}
			
			$msg = '[REMOTE] Attempting HTTP GET request...';
			$app->enqueueMessage($msg, 'notice');
			file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
			
			$response = $http->get($fullUrl, $options);

			$msg = '[REMOTE] HTTP response code: ' . $response->code;
			$app->enqueueMessage($msg, 'notice');
			file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);

			// Check for error response codes
			if ($response->code >= 400)
			{
				$errorBody = is_string($response->body) ? substr($response->body, 0, 500) : print_r($response->body, true);
				$msg = '[REMOTE] API error response code ' . $response->code . ': ' . $errorBody;
				file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ERROR: ' . $msg . "\n", FILE_APPEND);
				throw new \Exception('API request failed with status code: ' . $response->code . '. Response: ' . $errorBody);
			}

			$msg = '[REMOTE] Response received, decoding JSON...';
			$app->enqueueMessage($msg, 'notice');
			file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
			
			$data = json_decode($response->body);

			if ($data === null && json_last_error() !== JSON_ERROR_NONE)
			{
				throw new \Exception('JSON decode error: ' . json_last_error_msg() . '. Body: ' . substr($response->body, 0, 500));
			}

			$msg = '[REMOTE] JSON decoded successfully. Data type: ' . gettype($data);
			$app->enqueueMessage($msg, 'notice');
			file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);

			if (empty($data))
			{
				$msg = '[REMOTE] Response data is empty';
				$app->enqueueMessage($msg, 'notice');
				file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
				return array();
			}

			// Extract items from API response
			// API returns items in 'data' array if following JSON:API spec, or direct array
			$items = is_array($data) ? $data : (isset($data->data) ? $data->data : array());

			$msg = '[REMOTE] SUCCESS: Extracted ' . count($items) . ' items from remote API';
			$app->enqueueMessage($msg, 'notice');
			file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);

			// Convert to objects if needed and standardize field names
			$result = array();
			foreach ($items as $item)
			{
				if (is_array($item))
				{
					$item = (object) $item;
				}

				// Ensure all expected fields exist
				if (!isset($item->extension_type))
				{
					$item->extension_type = '';
				}

				$result[] = $item;
			}

			$msg = '[REMOTE] Successfully processed ' . count($result) . ' remote items for display';
			$app->enqueueMessage($msg, 'notice');
			file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
			return $result;
		}
		catch (\Exception $e)
		{
			$msg = '[REMOTE] ERROR fetching remote builds: ' . $e->getMessage();
			$app->enqueueMessage($msg, 'error');
			file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
			$msg = '[REMOTE] Stack trace - ' . $e->getTraceAsString();
			$app->enqueueMessage($msg, 'warning');
			file_put_contents('/tmp/builds_model_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
			return array();
		}
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
