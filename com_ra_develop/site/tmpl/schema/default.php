<?php

/**
 * @version    CVS: 1.0.1
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
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;
use Ramblers\Component\Ra_develop\Site\Helper\HyperHelper;
use Ramblers\Component\Ra_develop\Site\Helper\HyperTable;

$table = $this->table;
$config = Factory::getConfig();
$database = $config->get('db');
$dbPrefix = $config->get('dbprefix');
$objHelper = new HyperHelper;
$objTable = new HyperTable();
echo '<h2>Table ' . $this->table . ' from database ' . $database . '<h2>';
//        ToolBarHelper::title($this->prefix . 'Schema for ' . $database . ' ' . $table);
//        $target = 'index.php?option=com_hy_schema&task=reports.showTableSchema&table=' . $table;
//        echo $objHelper->showPrint($target);
$objTable->add_header("Seq,Column name,Type,Max size,Null,Key");
$sql = "SELECT ORDINAL_POSITION,COLUMN_NAME,DATA_TYPE,IS_NULLABLE,";
$sql .= "CHARACTER_MAXIMUM_LENGTH,COLUMN_KEY ";
$sql .= "FROM information_schema.COLUMNS ";
$sql .= "WHERE TABLE_SCHEMA='" . $database . "' AND TABLE_NAME ='" . $table . "' ";
$sql .= "ORDER BY ORDINAL_POSITION";
//echo $sql;
$columns = $objHelper->getRows($sql);

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
$back = "index.php?option=com_hy_schema&view=schemata";
echo $objHelper->backButton($back);

