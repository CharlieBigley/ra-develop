<?php
/**
 * @version    CVS: 0.7.0
 * @package    Com_Ra_develop
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_develop\Site\Model;
// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\MVC\Model\FormModel;
use \Joomla\CMS\Object\CMSObject;
use \Joomla\CMS\Helper\TagsHelper;

/**
 * Ra_develop model.
 *
 * @since  0.7.0
 */
class BuildformModel extends FormModel
{
	private $item = null;


	/**
     * Perform the actual build process
     */
    private function build($component, $version)
    {
        // Get the ra-tools root directory (6 levels up: site/src/Model from ra-tools root)
        $scriptDir = dirname(dirname(dirname(dirname(__FILE__))));
        $componentDir = $scriptDir . '/' . $component;
        
        // Validate component directory exists
        if (!is_dir($componentDir)) {
            echo "Error: Component directory not found: $componentDir\n";
            return false;
        }
        
        // Extract the manifest filename from component name (com_ra_tools -> ra_tools)
        $manifestName = preg_replace('/^com_/', '', $component);
        
        echo "Starting build for component: $component, version: $version\n";
        echo "Script directory: $scriptDir\n";
        echo "Component directory: $componentDir\n";
        echo "Manifest name: $manifestName\n";
        die(); 
        // Change to component directory
        if (!chdir($componentDir)) {
            echo "Error: Could not change to directory: $componentDir\n";
            return false;
        }
        
        echo "Building $component-$version.zip...\n";
        
        $sourceManifest = 'administrator/' . $manifestName . '.xml';
      
        if (!file_exists($sourceManifest)) {
            echo "Error: Source manifest file not found: $sourceManifest\n";
            return false;
        }
        
        // Create zip with required folders, manifest, and script
        echo "  - Compressing files...\n";
        
        $zipFile = "$component-$version.zip";
        $zip = new \ZipArchive();
        
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            echo "Error: Could not create zip file\n";
            if (file_exists($destManifest)) {
                unlink($destManifest);
            }
            return false;
        }
        
        // Directories to include in the zip - discover dynamically
        $dirsToInclude = [];
        $excludeDirs = ['.', '..', '.git', '.gitignore', '.DS_Store'];
        
        // Scan for all directories in the component folder
        $items = scandir('.');
        foreach ($items as $item) {
            if (is_dir($item) && !in_array($item, $excludeDirs) && strpos($item, '.') !== 0) {
            echo 'including directory: ' . $item . "\n"; // --- IGNORE ---
			$dirsToInclude[] = $item;
            }
        }
        
        $filesToInclude = ['script.php', $sourceManifest];
        
        // Add files
        foreach ($filesToInclude as $file) {
            if (file_exists($file)) {
                $zip->addFile($file, $file);
            } else {
				echo "Warning: File not found and will be skipped: $file\n";
			}
        }
        
        // Add directories recursively
        foreach ($dirsToInclude as $dir) {
            if (is_dir($dir)) {
                $this->addDirToZip($zip, $dir, $dir);
            } else {
				echo "Warning: Directory not found and will be skipped: $dir\n";	
        	}
        }

        
        $zip->close();
        
        // Step 3: Verify zip was created
        if (file_exists($zipFile)) {
            echo "✓ Package created successfully: $zipFile\n";
                    
            echo "✓ Build complete\n";
            return true;
        } else {
            echo "✗ Error: Failed to create zip file\n";
            if (file_exists($destManifest)) {
                unlink($destManifest);
            }
            return false;
        }
    }

		/**
	 * Method to check in an item.
	 *
	 * @param   integer $id The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since   0.7.0
	 */
	public function checkin($id = null)
	{
		// Get the id.
		$id = (!empty($id)) ? $id : (int) $this->getState('build.id');
		
		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Attempt to check the row in.
			if (method_exists($table, 'checkin'))
			{
				if (!$table->checkin($id))
				{
					return false;
				}
			}
		}

		return true;
		
	}

	/**
	 * Method to check out an item for editing.
	 *
	 * @param   integer $id The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since   0.7.0
	 */
	public function checkout($id = null)
	{
		// Get the user id.
		$id = (!empty($id)) ? $id : (int) $this->getState('build.id');
		
		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Get the current user object.
			$user = Factory::getApplication()->getIdentity();

			// Attempt to check the row out.
			if (method_exists($table, 'checkout'))
			{
				if (!$table->checkout($user->get('id'), $id))
				{
					return false;
				}
			}
		}

		return true;
		
	}

	/**
	 * Method to delete data
	 *
	 * @param   int $pk Item primary key
	 *
	 * @return  int  The id of the deleted item
	 *
	 * @throws  Exception
	 *
	 * @since   0.7.0
	 */
	public function delete($id)
	{
		$user = Factory::getApplication()->getIdentity();

		
		if (empty($id))
		{
			$id = (int) $this->getState('build.id');
		}

		if ($id == 0 || $this->getItem($id) == null)
		{
				throw new \Exception(Text::_('COM_RA_DEVELOP_ITEM_DOESNT_EXIST'), 404);
		}

		if ($user->authorise('core.delete', 'com_ra_develop') !== true)
		{
				throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$table = $this->getTable();

		if ($table->delete($id) !== true)
		{
				throw new \Exception(Text::_('JERROR_FAILED'), 501);
		}

		return $id;
		
	}
	
	/**
	 * Method to get an ojbect.
	 *
	 * @param   integer $id The id of the object to get.
	 *
	 * @return  Object|boolean Object on success, false on failure.
	 *
	 * @throws  Exception
	 */
	public function getItem($id = null)
	{
		if ($this->item === null)
		{
			$this->item = false;

			if (empty($id))
			{
				$id = $this->getState('build.id');
			}

			// Get a level row instance.
			$table = $this->getTable();
			$properties = $table->getProperties();
			$this->item = ArrayHelper::toObject($properties, CMSObject::class);

			if ($table !== false && $table->load($id) && !empty($table->id))
			{
				$user = Factory::getApplication()->getIdentity();
				$id   = $table->id;
				

				$canEdit = $user->authorise('core.edit', 'com_ra_develop') || $user->authorise('core.create', 'com_ra_develop');

				if (!$canEdit && $user->authorise('core.edit.own', 'com_ra_develop'))
				{
					$canEdit = $user->id == $table->created_by;
				}

				if (!$canEdit)
				{
					throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
				}

				// Check published state.
				if ($published = $this->getState('filter.published'))
				{
					if (isset($table->state) && $table->state != $published)
					{
						return $this->item;
					}
				}

				// Convert the Table to a clean CMSObject.
				$properties = $table->getProperties(1);
				$this->item = ArrayHelper::toObject($properties, CMSObject::class);
							
			}
		}

		return $this->item;
	}

	/**
	 * Method to get the table
	 *
	 * @param   string $type   Name of the Table class
	 * @param   string $prefix Optional prefix for the table class name
	 * @param   array  $config Optional configuration array for Table object
	 *
	 * @return  Table|boolean Table if found, boolean false on failure
	 */
	public function getTable($type = 'Build', $prefix = 'Administrator', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}

	/**
	 * Get an item by alias
	 *
	 * @param   string $alias Alias string
	 *
	 * @return int Element id
	 */
	public function getItemIdByAlias($alias)
	{
		$table      = $this->getTable();
		$properties = $table->getProperties();

		if (!in_array('alias', $properties))
		{
				return null;
		}

		$table->load(array('alias' => $alias));
		$id = $table->id;

		
			return $id;
		
	}


	/**
	 * Method to get the form.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   array   $data     An optional array of data for the form to interogate.
	 * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form    A Form object on success, false on failure
	 *
	 * @since   0.7.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_ra_develop.build', 'buildform', array(
						'control'   => 'jform',
						'load_data' => $loadData
				)
		);

		if (empty($form))
		{
				return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  The default data is an empty array.
	 * @since   0.7.0
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_ra_develop.edit.build.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		if ($data)
		{
			
		// Support for multiple or not foreign key field: component_type_id
		$array = array();

		foreach ((array) $data->component_type_id as $value)
		{
			if (!is_array($value))
			{
				$array[] = $value;
			}
		}
		if(!empty($array)){

		$data->component_type_id = $array;
		}

			return $data;
		}

		return array();
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @since   0.7.0
	 *
	 * @throws  Exception
	 */
	protected function populateState()
	{
		$app = Factory::getApplication('com_ra_develop');

		// Load state from the request userState on edit or from the passed variable on default
		if (Factory::getApplication()->input->get('layout') == 'edit')
		{
			$id = Factory::getApplication()->getUserState('com_ra_develop.edit.build.id');
		}
		else
		{
			$id = Factory::getApplication()->input->get('id');
			Factory::getApplication()->setUserState('com_ra_develop.edit.build.id', $id);
		}

		$this->setState('build.id', $id);

		// Load the parameters.
		$params       = $app->getParams();
		$params_array = $params->toArray();

		if (isset($params_array['item_id']))
		{
				$this->setState('build.id', $params_array['item_id']);
		}

		$this->setState('params', $params);
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array $data The form data
	 *
	 * @return  bool
	 *
	 * @throws  Exception
	 * @since   0.7.0
	 */
	public function save($data)
	{
		$id    = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('build.id');
		$state = (!empty($data['state'])) ? 1 : 0;
		$user  = Factory::getApplication()->getIdentity();

		if ($id)
		{
			// Check the user can edit this item
			$authorised = $user->authorise('core.edit', 'com_ra_develop') || $authorised = $user->authorise('core.edit.own', 'com_ra_develop');
		}
		else
		{
			// Check the user can create new items in this section
			$authorised = $user->authorise('core.create', 'com_ra_develop');
		}

		if ($authorised !== true)
		{
			throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
		// Generate the installation file
		$this->build($data['component_name'], $data['version']);
		$table = $this->getTable();

		if(!empty($id))
		{
			$table->load($id);
		}
		
		
	try{
			if ($table->save($data) === true)
			{
				return $table->id;
			}
			else
			{
				Factory::getApplication()->enqueueMessage($table->getError(), 'error');
				return false;
			}
		}catch(\Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return false;
		}
			
	}

	/**
	 * Check if data can be saved
	 *
	 * @return bool
	 */
	public function getCanSave()
	{
		$table = $this->getTable();

		return $table !== false;
	}

    /**
     * Recursively add a directory to a ZipArchive
     */
    private function addDirToZip($zip, $dir, $zipPath = '')
    {
        $excludePatterns = ['.DS_Store', '.git'];
        
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            // Check if file should be excluded
            $shouldExclude = false;
            foreach ($excludePatterns as $pattern) {
                if (strpos($file, $pattern) !== false) {
                    $shouldExclude = true;
                    break;
                }
            }
            
            if ($shouldExclude) {
                continue;
            }
            
            $filePath = $dir . '/' . $file;
            $zipFilePath = $zipPath ? $zipPath . '/' . $file : $file;
            
            if (is_dir($filePath)) {
                $this->addDirToZip($zip, $filePath, $zipFilePath);
            } else {
                $zip->addFile($filePath, $zipFilePath);
            }
        }
	
	}	
}
