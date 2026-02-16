<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Hy_schema
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2024 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Ramblers\Component\Ra_develop\Site\Helper\HyperHelper;
use Ramblers\Component\Ra_develop\Site\Helper\HyperTable;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_hy_schema.admin')
        ->useScript('com_hy_schema.admin');

$user = Factory::getApplication()->getIdentity();
$userId = $user->get('id');

$prefix = $this->component_params->get('prefix', 'ra');
echo "<h2>Tables with prefix $prefix</h2>";

$objHelper = new HyperHelper;
$config = Factory::getConfig();
$database = $config->get('db');
$dbPrefix = $config->get('dbprefix');
$total_size = 0;
$db = Factory::getContainer()->get('DatabaseDriver');
//return;
ToolBarHelper::title('Hyper Reports');
$target = 'index.php?option=com_hy_schema&view=entity';
ToolBarHelper::title($this->prefix . "Database schema for " . $database);
echo $objHelper->showPrint($target);
$objTable = new HyperTable();
$objTable->add_header("Table, Record count, Column count, Index count, Data size, Index size, Total size MB");
$sql = "SELECT TABLE_NAME, DATA_LENGTH, INDEX_LENGTH, ";
$sql .= "ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS Size ";
$sql .= "FROM information_schema.TABLES ";
$sql .= "WHERE TABLE_SCHEMA = '" . $database . "' AND ";
$sql .= "TABLE_NAME LIKE '" . $dbPrefix . "ra_%' ";
//        $sql .= " OR TABLE_NAME = '" . $dbPrefix . "users') ";
$sql .= "ORDER BY TABLE_NAME";
//        echo $sql;
/*
  UNION
  SELECT 'TOTALS:' AS 'TABLE_NAME',
  sum(DATA_LENGTH) AS 'DATA_LENGTH',
  sum(INDEX_LENGTH) AS 'INDEX_LENGTH',
  sum(data_length + INDEX_LENGTH) AS 'Size'";
  if (JDEBUG) {
  //           Factory::getApplication()->enqueueMessage($this->sql, 'notice');
  echo $sql;
  }
 */

$tables = $objHelper->getRows($sql);
foreach ($tables as $table) {
    $name = $db->quoteName($table->TABLE_NAME);
    $target = 'administrator/index.php?option=com_hy_schema&task=reports.showTableSchema&table=' . $name;
    $objTable->add_item($objHelper->buildLink($target, $table->TABLE_NAME));

    $sql2 = "SELECT COUNT(*) FROM " . $name;
    $target = 'administrator/index.php?option=com_hy_schema&task=reports.showTable&table=' . $name;
    $count = $objHelper->getvalue($sql2);
    $objTable->add_item($objHelper->buildLink($target, number_format($count)));

    $sql2 = "SELECT COUNT(COLUMN_NAME) FROM information_schema.COLUMNS WHERE TABLE_NAME='" . $name . "'";
    $objTable->add_item($objHelper->getvalue($sql2));

    $sql2 = "SELECT COUNT(INDEX_NAME) FROM information_schema.STATISTICS ";
    $sql2 .= "WHERE TABLE_SCHEMA='$database' AND TABLE_NAME='" . $name . "'";
    $objTable->add_item($objHelper->getvalue($sql2));

    $objTable->add_item(number_format($table->DATA_LENGTH));
    $objTable->add_item(number_format($table->INDEX_LENGTH));
    $objTable->add_item($table->Size);
    $total_size = $total_size + $table->DATA_LENGTH + $table->INDEX_LENGTH;
    $objTable->generate_line();
}
$objTable->generate_table();
echo 'Number of tables in ' . $database . ': ' . $objTable->num_rows . ', ';
echo 'Total size: ' . $total_size / 1000 / 1000 . ' MB' . '<br>';
?>

