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
$wa->useStyle('com_ra_develop.admin')
        ->useScript('com_ra_develop.admin');

$toolsHelper = new HyperHelper;
$config = Factory::getConfig();
$database = $config->get('db');
$total_size = 0;
$db = Factory::getContainer()->get('DatabaseDriver');
$dbPrefix = $config->get('dbprefix');
$user = Factory::getApplication()->getIdentity();
$userId = $user->get('id');
$sql = "SELECT TABLE_NAME, DATA_LENGTH, INDEX_LENGTH, ";
$sql .= "ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS Size ";
$sql .= "FROM information_schema.TABLES ";
$sql .= "WHERE TABLE_SCHEMA = '" . $database . "' ";
if ($this->display_type == 'all') {
    echo "<h2>All tables</h2>";    
} else {
    $prefix = $this->params->get('prefix', 'ra');
    $sql .= "AND TABLE_NAME LIKE '" . $dbPrefix . $prefix . "_%' ";
    echo "<h2>Tables with prefix $prefix</h2>"; 
    $objTable = new HyperTable();
    $objTable->add_header("Table, Record count, Column count, Index count, Data size, Index size, Total size MB");   
}   
$sql .= "ORDER BY TABLE_NAME";
  //     echo $sql;
$target = 'index.php?option=com_ra_develop&view=schemata&Itemid=' . $this->menu_id;
echo $toolsHelper->showPrint($target) .'<br>';

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

$tables = $toolsHelper->getRows($sql);
if ($tables) {
    foreach ($tables as $table) {
        $name = $db->quoteName($table->TABLE_NAME);
        $target = 'index.php?option=com_ra_develop&view=schema&Itemid=' . $this->menu_id . '&table=';
        $target .= $table->TABLE_NAME;
        if ($this->display_type == 'all') {
           echo $toolsHelper->buildLink($target, $table->TABLE_NAME) . '<br>';
        } else {
            $objTable->add_item($toolsHelper->buildLink($target, $table->TABLE_NAME));
            $sql2 = "SELECT COUNT(*) FROM " . $name;
            $target .= '&layout=show';
            $count = $toolsHelper->getValue($sql2);
            if ($count !== false && $count !== null) {
                $objTable->add_item($toolsHelper->buildLink($target, number_format($count)));
            } else {
                $objTable->add_item('0');
            }

            $sql2 = "SELECT COUNT(COLUMN_NAME) FROM information_schema.COLUMNS WHERE TABLE_NAME='" . $name . "'";
            $col_count = $toolsHelper->getValue($sql2);
            $objTable->add_item($col_count !== false && $col_count !== null ? $col_count : '0');

            $sql2 = "SELECT COUNT(INDEX_NAME) FROM information_schema.STATISTICS ";
            $sql2 .= "WHERE TABLE_SCHEMA='$database' AND TABLE_NAME='" . $name . "'";
            $idx_count = $toolsHelper->getValue($sql2);
            $objTable->add_item($idx_count !== false && $idx_count !== null ? $idx_count : '0');

            $objTable->add_item(number_format($table->DATA_LENGTH));
            $objTable->add_item(number_format($table->INDEX_LENGTH));
            $objTable->add_item($table->Size);
            $total_size = $total_size + $table->DATA_LENGTH + $table->INDEX_LENGTH;
            $objTable->generate_line();
        }  
    }
}
if ($this->display_type != 'all') {
    $objTable->generate_table();    
    echo 'Total size: ' . $total_size / 1000 / 1000 . ' MB' . '<br>';
}
?>

