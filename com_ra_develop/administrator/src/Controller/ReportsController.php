<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_develop
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2024 Charlie Bigley
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
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Ramblers\Component\Ra_develop\Site\Helper\HyperHelper;
use Ramblers\Component\Ra_develop\Site\Helper\HyperTable;

/**
 * Report list controller class.
 *
 * @since  1.0.0
 */
class ReportsController extends AdminController {

    protected $db;
    protected $objApp;
    protected $objHelper;
    protected $prefix;

    public function __construct() {
        parent::__construct();
        $this->db = Factory::getContainer()->get('DatabaseDriver');
        $this->objHelper = new HyperHelper;
        $this->objApp = Factory::getApplication();
        $this->prefix = 'Reports: ';
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
    }

    function showTable() {
// display given number of records from the specified table
        $table = $this->objApp->input->getCmd('table', '');
        $limit = $this->objApp->input->getInt('limit', '50');

        $config = Factory::getConfig();
        $database = $config->get('db');
        $dbPrefix = $config->get('dbprefix');
        ToolBarHelper::title($this->prefix . "$limit records from $database $table");
        $found_id = false;
        $sql = 'SELECT * FROM ' . '#__' . substr($table, strlen($dbPrefix));
//        echo '#__' . substr($table, strlen($dbPrefix)) . ': ' . strlen($dbPrefix) . " $dbPrefix $table<br> $sql<br>";
        $this->objHelper->showQuery($sql);
        $back = "administrator/index.php?option=com_ra_develop&view=report";
        echo $this->objHelper->backButton($back);
        return;

        $columns = $this->objHelper->getRows($sql);
        if ($columns === false) {
            echo "Error for:<br>$sql<br>";
            echo $this->objHelper > error;
            return false;
        }
        if ($this->objHelper->rows == 0) {
            echo "No data found for:<br>$sql<br>";
            echo $this->objHelper->error;
            return false;
        }
        $ipointer = 0;
        foreach ($columns as $column) {
            $fields[$ipointer] = $column->COLUMN_NAME;
            $ipointer++;
        }
        $sql = 'SELECT ';
        $ipointer = 0;

        foreach ($fields as $field) {
            if ($field == 'id') {
                $found_id = true;
            }
            if ($field == 'password') {

            } else {
                if ($ipointer > 0) {
                    $sql .= ', ';
                }
                $sql .= $field;
                $ipointer++;
            }
        }
        $sql .= ' FROM ' . $dbPrefix;
        if (substr($table, 0, 1) == '#') {
            $sql .= substr($table, 3);
        } else {
            $sql .= $table;
        }
        if ($found_id) {
            $sql .= ' ORDER BY id DESC';
        }
        echo $sql;
        if ($this->objHelper->showSql($sql)) {
            echo "<h5>End of records for " . $table . "</h5>";
        } else {
            echo 'Error: ' . $this->objHelper->error . '<br>';
        }
        $back = "administrator/index.php?option=com_ra_develop&view=report";
        echo $this->objHelper->backButton($back);
    }

    public function showTableSchema() {
        $table = $this->objApp->input->getCmd('table', '');
        $config = Factory::getConfig();
        $database = $config->get('db');
//        $dbPrefix = $config->get('dbprefix');
        $objTable = new HyperTable();
        ToolBarHelper::title($this->prefix . 'Schema for ' . $database . ' ' . $table);
        $target = 'index.php?option=com_ra_develop&task=reports.showTableSchema&table=' . $table;
        echo $this->objHelper->showPrint($target);
        $objTable->add_header("Seq,Column name,Type,Max size,Null,Key");
        $sql = "SELECT ORDINAL_POSITION,COLUMN_NAME,DATA_TYPE,IS_NULLABLE,";
        $sql .= "CHARACTER_MAXIMUM_LENGTH,COLUMN_KEY ";
        $sql .= "FROM information_schema.COLUMNS ";
        $sql .= "WHERE TABLE_SCHEMA='" . $database . "' AND TABLE_NAME ='" . $table . "' ";
        $sql .= "ORDER BY ORDINAL_POSITION";
        $columns = $this->objHelper->getRows($sql);

        foreach ($columns as $column) {
            $objTable->add_item(number_format($column->ORDINAL_POSITION));
            $objTable->add_item($column->COLUMN_NAME);
            $objTable->add_item($column->DATA_TYPE);
            $objTable->add_item($column->CHARACTER_MAXIMUM_LENGTH);
            $objTable->add_item($column->IS_NULLABLE);
            $objTable->add_item($column->COLUMN_KEY);
            $objTable->generate_line();
        }
        $objTable->generate_table();
        echo ($objTable->num_rows - 1) . ' columns in the table<br>';
        $back = "administrator/index.php?option=com_ra_develop&view=report";
        echo $this->objHelper->backButton($back);
    }

}
