<?php

/**
 * @version     3.4.6
 * @package     com_ra_tools
 * @copyright   Copyleft (C) 2021
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Charlie <webmaster@bigley.me.uk> - https://www.stokeandnewcastleramblers.org.uk

 * 12/03/25 CB Add Events / Bookings
 * 16/03/25 CB add Events / Users
 * 25/03/25 CB add events / dataload
 * 06/04/25 CB Events / Eventtypes
 * 13/04/25 CB list Users
 * 21/04/25 CB use JsonHelper to show feed
 * 01/05/25 CB check canDo->create for showing WalksRefresh
 * 14/06/25 CB add option for Events . apisites
 * 25/08/25 show title from View
 * 28/08/25 CB use apisites from com_ra_tools, not com_ra_events
 * 09/09/25 CB remove diagnostics for events menu
 * 14/11/25 CB Mailman recipients
 */
// No direct access
\defined('_JEXEC') or die;

//use \Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Ramblers\Component\Ra_events\Site\Helpers\EventsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\JsonHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

//use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;
//JHtml::_('behavior.tooltip');
$jsonHelper = new JsonHelper;
$toolsHelper = new ToolsHelper;

$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$component = ComponentHelper::getComponent('com_ra_tools');
$canDo = ContentHelper::getActions('com_ra_tools');

//echo '<div style="float: left">';     // Div for Org & logo
//echo $toolsHelper->showLogo();
//
echo '<div style="float: right">';
echo '<h3>System tools</h3>';
echo '<ul>';
echo '<li><a href="index.php?option=com_ra_tools&task=system.showAccess" target="_self">Show your access permissions</a></li>' . PHP_EOL;
if ($toolsHelper->isSuperuser()) {
    if ((ComponentHelper::isEnabled('com_ra_events', true)) OR (ComponentHelper::isEnabled('com_ra_mailman', true))) {
        echo '<li><a href="index.php?option=com_ra_tools&view=users" target="_self">List Users</a></li>' . PHP_EOL;
    }
    echo '<li><a href="index.php?option=com_ra_tools&view=apisites" target="_self">API sites</a></li>' . PHP_EOL;
    echo '<li><a href="index.php?option=com_ra_tools&task=system.AccessWizard" target="_self">Access Configuration Wizard</a></li>' . PHP_EOL;
    echo '<li><a href="index.php?option=com_ra_tools&view=reports" target="_self">System Reports</a></li>' . PHP_EOL;
//    echo 'Orig  https://walks-manager.ramblers.org.uk/api/volunteers/groups?api-key=742d93e8f409bf2b5aec6f64cf6f405e<br>';
    $target = $jsonHelper->setUrl('organisation', '');
    echo '<li><a href="' . $target . '"';
    echo ' target="_blank">Show Organisation feed</a></li>' . PHP_EOL;
    echo '<li><a href="index.php?option=com_ra_tools&amp;task=area_list.refreshAreas" target="_self">Refresh details of Areas</a></li>' . PHP_EOL;
    echo '<li><a href="index.php?option=com_ra_tools&amp;task=group_list.refreshGroups" target="_self">Refresh details of Groups</a></li>' . PHP_EOL;
}

if ($canDo->get('core.admin')) {
    $versions = $toolsHelper->getVersions('com_ra_tools');
    echo '<li><a href="index.php?option=com_config&view=component&component=com_ra_tools" target="_self">';
    echo "Configure com_ra_tools (version " . $versions->component . ")</a></li>" . PHP_EOL;
    echo '<li>(DB version is ' . $versions->db_version . ')</li>';
}

echo '</ul>';
if (ComponentHelper::isEnabled('com_ra_mailman', true)) {
    $canDo = ContentHelper::getActions('com_ra_mailman');

    echo '<h3>Mail Manager</h3>';
    echo '<ul>';
    echo '<li><a href="index.php?option=com_ra_mailman&amp;view=mail_lsts" target="_self">Mailing lists</a></li>';
    echo '<li><a href="index.php?option=com_ra_mailman&amp;view=mailshots" target="_self">Mailshots</a></li>';
    if ($canDo->get('core.create')) {
        echo '<li><a href="index.php?option=com_ra_mailman&amp;view=subscriptions" target="_self">Subscriptions</a></li>';
        echo '<li><a href="index.php?option=com_ra_mailman&amp;view=recipients" target="_self">Recipients</a></li>';
        echo '<li><a href="index.php?option=com_ra_mailman&amp;view=profiles" target="_self">MailMan Users</a></li>';
        echo '<li><a href="index.php?option=com_ra_mailman&amp;view=dataload" target="_self">Import list of members</a></li>';
        echo '<li><a href="index.php?option=com_ra_mailman&amp;view=import_reports" target="_self">Import Reports</a></li>';
        echo '<li><a href="index.php?option=com_ra_mailman&amp;view=reports" target="_self">Mailman Reports</a></li>';
        echo '<li><a href="index.php?option=com_ra_mailman&task=system.checkRenewals" target="_self">Process Renewals</a></li>' . PHP_EOL;
        //         echo '<li><a href="index.php?option=com_ra_mailman&task=profiles.purgeTestdata" target="_self">Purge test data</a></li>' . PHP_EOL;
    }

    if ($canDo->get('core.admin')) {
        $versions = $toolsHelper->getVersions('com_ra_mailman');
        echo '<li><a href="index.php?option=com_config&view=component&component=com_ra_mailman" target="_self">';
        echo "Configure com_ra_mailman (version " . $versions->component . ")</a></li>" . PHP_EOL;
        echo '<li>(DB version is ' . $versions->db_version . ')</li>';
    }
    echo '</ul>' . PHP_EOL;
}
echo '</div>' . PHP_EOL;

echo '<h3>Organisation</h3>' . PHP_EOL;
echo '<ul>';
echo '<li><a href="index.php?option=com_ra_tools&amp;view=area_list" target="_self">List of Areas</a></li>' . PHP_EOL;
echo '<li><a href="index.php?option=com_ra_tools&amp;view=group_list" target="_self">List of Groups</a></li>';
echo '</ul>' . PHP_EOL;
//echo '</div>' . PHP_EOL;

if (ComponentHelper::isEnabled('com_ra_paths', true)) {
    $canDo = ContentHelper::getActions('com_ra_paths');

    echo '<h3>Path Maintenance</h3>';
    echo '<ul>';
    echo '<li><a href="index.php?option=com_ra_paths&amp;view=faults" target="_self">Fault Reports</a></li>';
    echo '<li><a href="index.php?option=com_ra_paths&amp;view=followups" target="_self">Followups</a></li>';
    if ($canDo->get('core.create')) {
        echo '<li><a href="index.php?option=com_ra_paths&amp;view=categories" target="_self">Categories</a></li>';
        echo '<li><a href="index.php?option=com_ra_paths&amp;view=statuses" target="_self">Statuses</a></li>';
        echo '<li><a href="index.php?option=com_ra_paths&amp;view=parishes" target="_self">Parishes</a></li>';
        echo '<li><a href="index.php?option=com_ra_paths&amp;view=boroughs" target="_self">Boroughs</a></li>';
        echo '<li><a href="index.php?option=com_ra_paths&amp;view=regions" target="_self">Regions</a></li>';
        echo '<li><a href="index.php?option=com_ra_paths&amp;view=sectors" target="_self">Sectors</a></li>';
    }
    echo '<li><a href="index.php?option=com_ra_paths&amp;view=enhancements" target="_self">Enhancements</a></li>';
    if ($canDo->get('core.admin')) {
        $versions = $toolsHelper->getVersions('com_ra_paths');
        echo '<li><a href="index.php?option=com_config&view=component&component=com_ra_paths" target="_self">';
        echo "Configure com_ra_paths (version " . $versions->component . ")</a></li>" . PHP_EOL;
        echo '<li>(DB version is ' . $versions->db_version . ')</li>';
    }
    echo '</ul>' . PHP_EOL;
}


if (ComponentHelper::isEnabled('com_ra_events', true)) {
//echo '<div style="float: left">';
    $versions = $toolsHelper->getVersions('com_ra_events');

    if (version_compare($versions->component, '2.1.1', 'ge')) {
        $eventsHelper = new EventsHelper;
        $eventsHelper->menusDashboard();
    } else {
        $canDo = ContentHelper::getActions('com_ra_events');
        echo '<h3>Events</h3>' . PHP_EOL;
        echo '<ul>' . PHP_EOL;
        echo '<li><a href="index.php?option=com_ra_events&amp;view=events" target="_self">List of Events</a></li>' . PHP_EOL;
        if ($canDo->get('core.create')) {
            echo '<li><a href="index.php?option=com_ra_events&amp;view=bookings" target="_self">List of Bookings</a></li>' . PHP_EOL;
            echo '<li><a href="index.php?option=com_ra_events&amp;view=reports" target="_self">Event Reports</a></li>' . PHP_EOL;
            echo '<li><a href="index.php?option=com_ra_events&amp;view=dataload" target="_self">Import list of bookings</a></li>' . PHP_EOL;
            echo '<li><a href="index.php?option=com_ra_toolss&amp;view=apisites" target="_self">API Sites</a></li>' . PHP_EOL;
        }
        if ($toolsHelper->isSuperuser()) {
            echo '<li><a href="index.php?option=com_ra_events&amp;view=eventtypes" target="_self">Event Types</a></li>' . PHP_EOL;
        }
        if ($canDo->get('core.admin')) {
            $versions = $toolsHelper->getVersions('com_ra_events');
            echo '<li><a href="index.php?option=com_config&view=component&component=com_ra_events" target="_self">';
            echo "Configure com_ra_events (version " . $versions->component . ")</a></li>" . PHP_EOL;
            echo '<li>(DB version is ' . $versions->db_version . ')</li>';
        }
        echo '</ul>' . PHP_EOL;
    }
//    echo '</div>' . PHP_EOL;
}

if (ComponentHelper::isEnabled('com_ra_walks', true)) {
    $canDo = ContentHelper::getActions('com_ra_walks');
    echo '<h3>Walks</h3>' . PHP_EOL;
    echo '<ul>' . PHP_EOL;
    echo '<li><a href="index.php?option=com_ra_walks&amp;view=walks" target="_self">List of Walks</a></li>' . PHP_EOL;
    echo '<li><a href="index.php?option=com_ra_walks&amp;view=reports" target="_self">Walk Reports</a></li>' . PHP_EOL;

    if (ComponentHelper::isEnabled('com_ra_wf', true)) {
        if (ContentHelper::getActions('com_ra_wf')->get('core.create')) {
            echo ' <li><a href="index.php?option=com_ra_wf&task=walks.refresh" target="_self">Refresh details of Walks</a></li>' . PHP_EOL;
        }
    } else {
        if ($canDo->get('core.create')) {
            echo ' <li><a href="index.php?option=com_ra_walks&task=walks.refresh" target="_self">Refresh details of Walks</a></li>' . PHP_EOL;
        }
    }
    if ($canDo->get('core.admin')) {
        $versions = $toolsHelper->getVersions('com_ra_walks');
        echo '<li><a href="index.php?option=com_config&view=component&component=com_ra_walks" target="_self">';
        echo "Configure com_ra_walks (version " . $versions->component . ")</a></li>" . PHP_EOL;
        echo '<li>(DB version is ' . $versions->db_version . ')</li>';
    }
    echo '</ul>' . PHP_EOL;
}

if (ComponentHelper::isEnabled('com_ra_wf', true)) {
    echo '<h3>Walks Follow</h3>' . PHP_EOL;
    echo '<ul>' . PHP_EOL;
    echo '<li><a href="index.php?option=com_ra_wf&amp;view=walks" target="_self">List of Walks to Follow</a></li>' . PHP_EOL;

    echo '<li><a href="index.php?option=com_ra_wf&amp;view=profiles" target="_self">Walks Follow profiles</a></li>' . PHP_EOL;
    echo '<li><a href="index.php?option=com_ra_wf&amp;view=reports" target="_self">Walks Follow Reports</a></li>' . PHP_EOL;
    if ($canDo->get('core.admin')) {
        $versions = $toolsHelper->getVersions('com_ra_wf');
        echo '<li><a href="index.php?option=com_config&view=component&component=com_ra_wf" target="_self">';
        echo "Configure com_ra_wf (version " . $versions->component . ")</a></li>" . PHP_EOL;
        echo '<li>(DB version is ' . $versions->db_version . ')</li>';
    }
    echo '</ul>' . PHP_EOL;
}

if (ComponentHelper::isEnabled('com_ra_sg', true)) {   // Self Guided
    echo '<h3>Walks</h3>' . PHP_EOL;
    echo '<ul>' . PHP_EOL;
    echo '<li><a href="index.php?option=com_ra_tools&amp;view=sg_list" target="_self">Self Guided walks</a></li>' . PHP_EOL;
    echo '<li><a href="index.php?option=com_categories&amp;extension=com_ra_tools" target - "_self">Categories</a></li>' . PHP_EOL;
    if ($canDo->get('core.admin')) {
        $versions = $toolsHelper->getVersions('com_ra_sg');
        echo '<li><a href="index.php?option=com_config&view=component&component=com_ra_sg" target="_self">';
        echo "Configure com_ra_sg (version " . $versions->component . ")</a></li>" . PHP_EOL;
        echo '<li>(DB version is ' . $versions->db_version . ')</li>';
    }
    echo '</ul>' . PHP_EOL;
}
