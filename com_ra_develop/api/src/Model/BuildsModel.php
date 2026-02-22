<?php
/**
 * @version    1.0.11
 * @package    com_ra_develop
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_develop\Api\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

/**
 * API Model for Builds table
 *
 * Provides data access methods specifically designed for REST API consumption.
 * Handles pagination, filtering, sorting, and field selection for API responses.
 *
 * @since  1.0.6
 */
class BuildsModel extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since  1.0.0
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
				'component_name', 'a.component_name',
				'version', 'a.version',
				'extension_type', 't.name',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Set default ordering to build_date descending
		parent::populateState('a.build_date', 'DESC');

		$app = Factory::getApplication();

		// Get pagination limit
		$value = $app->getUserState($this->context . '.list.limit', $app->get('list_limit', 25));
		$this->setState('list.limit', $value);

		// Get pagination start
		$value = $app->input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $value);

		// Get ordering column and direction from input
		$ordering  = $app->input->get('filter_order', 'a.build_date', 'cmd');
		$direction = strtoupper($app->input->get('filter_order_Dir', 'DESC', 'cmd'));

		$this->setState('list.ordering', $ordering);
		$this->setState('list.direction', $direction);

		// Get search filter
		$search = $app->input->get('filter_search', '', 'string');
		$this->setState('filter.search', $search);
	}

	/**
	 * Build an SQL query to load the API list data.
	 *
	 * @return  \Joomla\Database\DatabaseQuery
	 *
	 * @since   1.0.0
	 */
	protected function getListQuery()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select distinct builds with extension type
		$query->select(
			'DISTINCT a.id, a.component_name, a.version, a.build_date, a.notes, a.replace, a.version_note, a.state, a.created_by, a.modified_by'
		);

		$query->from($db->quoteName('#__ra_builds') . ' AS a');

		// Join with extensions table
		$query->select('e.extension_type_id');
		$query->join('LEFT', $db->quoteName('#__ra_extensions') . ' AS e ON e.name = a.component_name');

		// Join with extension types table to get type name
		$query->select('t.name AS extension_type');
		$query->join('LEFT', $db->quoteName('#__ra_extension_types') . ' AS t ON t.id = e.extension_type_id');

		// Filter by state - show all published items
		$user = Factory::getApplication()->getIdentity();
		if (!$user->authorise('core.edit', 'com_ra_develop'))
		{
			$query->where('a.state = 1');
		}
		else
		{
			$query->where('(a.state IN (0, 1))');
		}

		// Apply search filter
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				// Search in component name
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('(a.component_name LIKE ' . $search . ')');
			}
		}

		// Add ordering
		$orderCol  = $this->getState('list.ordering', 'a.build_date');
		$orderDirn = $this->getState('list.direction', 'DESC');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Get items with support for API field filtering
	 *
	 * @return mixed Array of items or false on failure
	 *
	 * @since  1.0.0
	 */
	public function getItems()
	{
		$app = Factory::getApplication();
		
		// Log API request
		$msg = '[API SERVER] BuildsModel::getItems() invoked - building query';
		file_put_contents('/tmp/api_builds_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
		
		$items = parent::getItems();
		
		$msg = '[API SERVER] Query executed - retrieved ' . count($items) . ' records from database';
		file_put_contents('/tmp/api_builds_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);

		// Standardize field names for API response
		foreach ($items as &$item)
		{
			// Ensure extension_type field exists
			if (!isset($item->extension_type))
			{
				$item->extension_type = '';
			}
		}

		$msg = '[API SERVER] Returning ' . count($items) . ' items to API client';
		file_put_contents('/tmp/api_builds_debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);

		return $items;
	}
}
