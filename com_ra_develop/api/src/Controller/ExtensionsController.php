<?php
/**
 * @version    1.0.12
 * @package    com_ra_develop
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_develop\Api\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\ApiController;

/**
 * The Extensions API controller
 *
 * @since  1.0.12
 */
class ExtensionsController extends ApiController
{
    /**
     * The content type of the item.
     *
     * @var    string
     * @since  1.0.12
     */
    protected $contentType = 'extensions';

    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  1.0.12
     */
    protected $default_view = 'extensions';

    /**
     * Authorizes the request (override for temporary public access)
     *
     * @return bool
     * @since 1.0.12
     */
    protected function authorizeRequest()
    {
        // TEMPORARY: Allow public API access for testing
        // TODO: Remove this bypass once token authentication is working
        return true;
    }
}
