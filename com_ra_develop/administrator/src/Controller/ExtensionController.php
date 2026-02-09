<?php
/**
 * @version    1.0.2
 * @package    com_ra_develop
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_develop\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

/**
 * Extension controller class.
 *
 * @since  0.4.0
 */
class ExtensionController extends FormController
{
	protected $view_list = 'extensions';
}
