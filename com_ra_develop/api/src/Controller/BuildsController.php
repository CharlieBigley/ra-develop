<?php
/**
 * @version    1.0.0
 * @package    com_ra_develop
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_develop\Api\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\ApiController;

/**
 * The Builds API controller
 *
 * @since  1.0.0
 */
class BuildsController extends ApiController
{
    /**
     * The content type of the item.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $contentType = 'builds';

    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $default_view = 'builds';
}
