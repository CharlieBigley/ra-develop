<?php

/**
 * @version     1.0.11
 * @package     com_ra_develop
 * @copyright   Copyleft (C) 2021
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Charlie <webmaster@bigley.me.uk> - https://www.stokeandnewcastleramblers.org.uk

 * 28/01/26 CB created

 */
// No direct access
\defined('_JEXEC') or die;

//use \Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

//use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;
//JHtml::_('behavior.tooltip');
$toolsHelper = new ToolsHelper;

$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$component = ComponentHelper::getComponent('com_ra_develop');
$canDo = ContentHelper::getActions('com_ra_develop');

//echo '<div style="float: left">';     // Div for Org & logo
//echo $toolsHelper->showLogo();
//

if (ComponentHelper::isEnabled('com_ra_develop', true)) {
    $canDo = ContentHelper::getActions('com_ra_develop');
    echo '<div style="float: left">';
    echo '<h3>RA Development</h3>';
    echo '<h4>Reports</h4>';
    echo '<ul>';
    echo '<li><a href="index.php?option=com_ra_develop&amp;task=extensions.listExtensions" target="_self">Summary of extensions </a></li>';
    echo '<li><a href="index.php?option=com_ra_develop&amp;view=builds" target="_self">Builds </a></li>';
    echo '</ul>';
    echo '<h4>Maintenance</h4>';
    echo '<ul>';
    echo '<li><a href="index.php?option=com_ra_develop&amp;view=subsystems" target="_self">Sub Systems</a></li>';
    echo '<li><a href="index.php?option=com_ra_develop&amp;view=extension_types" target="_self">Extension Types</a></li>';
    echo '<li><a href="index.php?option=com_ra_develop&amp;view=extensions" target="_self">Extensions</a></li>';
    echo '</ul>';
    if ($canDo->get('core.admin')) {
        $versions = $toolsHelper->getVersions('com_ra_develop');
        echo '<li><a href="index.php?option=com_config&view=component&component=com_ra_develop" target="_self">';
        echo "Configure com_ra_develop (version " . $versions->component . ")</a></li>" . PHP_EOL;
        echo '<li>(DB version is ' . $versions->db_version . ')</li>';
    }
    echo '</ul>' . PHP_EOL;
    echo '<div style="float: right">';
}
