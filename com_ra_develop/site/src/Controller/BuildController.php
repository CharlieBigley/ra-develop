<?php
/**
 * @version    1.0.2
 * @package    com_ra_develop
 * @author     Barlie Chigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_develop\Site\Controller;

\defined('_JEXEC') or die;

use \Joomla\CMS\Application\SiteApplication;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Multilanguage;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\Controller\BaseController;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\Uri\Uri;
use \Joomla\Utilities\ArrayHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Build class.
 *
 * @since  1.6.0
 */
class BuildController extends BaseController
{
    private function build($component, $version)
    {
		//  get base directoryfrom component parameters
		$params = ComponentHelper::getParams('com_ra_develop');
		$gitBase = $params->get('git_base'); // Base directory where all git projects are located
		$toolsHelper = new ToolsHelper;
		$sql = 'SELECT sub_system.repository_name ';
		$sql .= 'FROM `#__ra_sub_systems` AS sub_system ';
		$sql .= 'INNER JOIN `#__ra_extensions` AS ext ON ext.subsystem_id = sub_system.id ';
		$sql .= 'WHERE ext.name="' . $component . '"';
		$sub_system  = $toolsHelper->getValue($sql);
        // Get the ra-tools root directory (6 levels up: site/src/Model from ra-tools root)
 //       $extensionDir = dirname(dirname(dirname(dirname(__FILE__))));
		$extensionDir = $gitBase . $sub_system . '/' . $component; // --- IGNORE ---
        // Validate  directory exists
        if (!is_dir($extensionDir)) {
            echo "Error: Project directory not found: $extensionDir\n";
            return false;
        }
//		$componentDir = $scriptDir . '/' . $component;        
//        // Validate component directory exists
//        if (!is_dir($componentDir)) {
//            echo "Error: Component directory not found: $componentDir\n";
//            return false;
//        }
        $type = substr($component,0,3); 
        // Extract the manifest filename from component name (com_ra_tools -> ra_tools)
        $manifestName = preg_replace('/^com_/', '', $component);
        
        echo "Starting build for $type: $component, version: $version" . '<br>';
        echo "Extension directory: $extensionDir" . '<br>';
//        echo "Component directory: $componentDir" . '<br>';
        echo "Manifest name: $manifestName" . '<br>';

        // Change to extensi directory
        if (!chdir($extensionDir)) {
            echo "Error: Could not change to directory: $extensionDir\n";
            return false;
        }
        
        echo "Building $component-$version.zip...\n";
		if ($type === 'com') {
        	$sourceManifest = 'administrator/' . $manifestName . '.xml';
		} else {
			$sourceManifest = $manifestName . '.xml';
		}
				
        if (!file_exists($sourceManifest)) {
            echo "Error: Source manifest file not found: $sourceManifest\n";
            return false;
        }
		$destManifest = 'manifest.xml';
        
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
            echo 'including directory: ' . $item . "<br>"; // --- IGNORE ---
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
public function test(){
	$this->build('mod_ra_events', '9.9.9');
}

	/**
	 * Method to check out an item for editing and redirect to the edit form.
	 *
	 * @return  void
	 *
	 * @since   0.1.0
	 *
	 * @throws  Exception
	 */
	public function edit()
	{
		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $this->app->getUserState('com_ra_develop.edit.build.id');
		$editId     = $this->input->getInt('id', 0);

		// Set the user id for the user to edit in the session.
		$this->app->setUserState('com_ra_develop.edit.build.id', $editId);

		// Get the model.
		$model = $this->getModel('Build', 'Site');

		// Check out the item
		if ($editId)
		{
			$model->checkout($editId);
		}

		// Check in the previous user.
		if ($previousId && $previousId !== $editId)
		{
			$model->checkin($previousId);
		}

		// Redirect to the edit screen.
		$this->setRedirect(Route::_('index.php?option=com_ra_develop&view=buildform&layout=edit', false));
	}

	/**
	 * Method to save data
	 *
	 * @return    void
	 *
	 * @throws  Exception
	 * @since   0.1.0
	 */
	public function publish()
	{
		// Checking if the user can remove object
		$user = $this->app->getIdentity();

		if ($user->authorise('core.edit', 'com_ra_develop') || $user->authorise('core.edit.state', 'com_ra_develop'))
		{
			$model = $this->getModel('Build', 'Site');

			// Get the user data.
			$id    = $this->input->getInt('id');
			$state = $this->input->getInt('state');

			// Attempt to save the data.
			$return = $model->publish($id, $state);

			// Check for errors.
			if ($return === false)
			{
				$this->setMessage(Text::sprintf('Save failed: %s', $model->getError()), 'warning');
			}

			// Clear the profile id from the session.
			$this->app->setUserState('com_ra_develop.edit.build.id', null);

			// Flush the data from the session.
			$this->app->setUserState('com_ra_develop.edit.build.data', null);

			// Redirect to the list screen.
			$this->setMessage(Text::_('COM_RA_DEVELOP_ITEM_SAVED_SUCCESSFULLY'));
			$menu = Factory::getApplication()->getMenu();
			$item = $menu->getActive();

			if (!$item)
			{
				// If there isn't any menu item active, redirect to list view
				$this->setRedirect(Route::_('index.php?option=com_ra_develop&view=builds', false));
			}
			else
			{
				$this->setRedirect(Route::_('index.php?Itemid='. $item->id, false));
			}
		}
		else
		{
			throw new \Exception(500);
		}
	}

	/**
	 * Check in record
	 *
	 * @return  boolean  True on success
	 *
	 * @since   0.1.0
	 */
	public function checkin()
	{
		// Check for request forgeries.
		$this->checkToken('GET');

		$id 	= $this->input->getInt('id', 0);
		$model 	= $this->getModel();
		$item 	= $model->getItem($id);

		// Checking if the user can remove object
		$user = $this->app->getIdentity();

		if ($user->authorise('core.manage', 'com_ra_develop') || $item->checked_out == $user->id) { 

			$return = $model->checkin($id);

			if ($return === false)
			{
				// Checkin failed.
				$message = Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError());
				$this->setRedirect(Route::_('index.php?option=com_ra_develop&view=build' . '&id=' . $id, false), $message, 'error');
				return false;
			}
			else
			{
				// Checkin succeeded.
				$message = Text::_('COM_RA_DEVELOP_CHECKEDIN_SUCCESSFULLY');
				$this->setRedirect(Route::_('index.php?option=com_ra_develop&view=build' . '&id=' . $id, false), $message);
				return true;
			}
		}
		else
		{
			throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	/**
	 * Remove data
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function remove()
	{
		// Checking if the user can remove object
		$user = $this->app->getIdentity();

		if ($user->authorise('core.delete', 'com_ra_develop'))
		{
			$model = $this->getModel('Build', 'Site');

			// Get the user data.
			$id = $this->input->getInt('id', 0);

			// Attempt to save the data.
			$return = $model->delete($id);

			// Check for errors.
			if ($return === false)
			{
				$this->setMessage(Text::sprintf('Delete failed', $model->getError()), 'warning');
			}
			else
			{
				// Check in the profile.
				if ($return)
				{
					$model->checkin($return);
				}

				$this->app->setUserState('com_ra_develop.edit.build.id', null);
				$this->app->setUserState('com_ra_develop.edit.build.data', null);

				$this->app->enqueueMessage(Text::_('COM_RA_DEVELOP_ITEM_DELETED_SUCCESSFULLY'), 'success');
				$this->app->redirect(Route::_('index.php?option=com_ra_develop&view=builds', false));
			}

			// Redirect to the list screen.
			$menu = Factory::getApplication()->getMenu();
			$item = $menu->getActive();
			$this->setRedirect(Route::_($item->link, false));
		}
		else
		{
			throw new \Exception(500);
		}
	}
}
