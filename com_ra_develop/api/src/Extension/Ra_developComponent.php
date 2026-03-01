<?php
/**
 * @version    1.0.12
 * @package    com_ra_develop
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_develop\Api\Extension;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Psr\Container\ContainerInterface;

/**
 * API component bootstrap for com_ra_develop.
 *
 * @since  1.0.12
 */
class Ra_developComponent extends MVCComponent implements BootableExtensionInterface
{
    /**
     * @param   ContainerInterface  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0.12
     */
    public function boot(ContainerInterface $container)
    {
        // No API-specific bootstrapping required.
    }
}
