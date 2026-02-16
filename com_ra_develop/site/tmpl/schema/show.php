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

use \Joomla\CMS\Factory;
use Ramblers\Component\Ra_develop\Site\Helper\HyperHelper;

$table = $this->table;

$objHelper = new HyperHelper;

echo '<h2>10 records from table ' . $this->table . '<h2>';

$sql = 'SELECT * FROM ' . $this->table . ' LIMIT 10';
$objHelper->showQuery($sql);
$back = "index.php?option=com_hy_schema&view=schemata";
echo $objHelper->backButton($back);
