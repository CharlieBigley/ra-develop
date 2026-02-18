<?php
/**
 * @version    1.0.0
 * @package    com_ra_develop
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_develop\Api\View\Builds;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;

/**
 * The Builds JSON API view
 *
 * @since  1.0.0
 */
class JsonapiView extends BaseApiView
{
    /**
     * The fields to render item in the documents
     *
     * These fields are exposed when fetching a single build record.
     * Includes both base table fields and read-only fields from joins.
     *
     * @var    array
     * @since  1.0.0
     */
    protected $fieldsToRenderItem = [
        'id',
        'component_name',
        'version',
        'build_date',
        'notes',
        'replace',
        'version_note',
        'state',
        'created_by',
        'modified_by',
        'extension_type',  // Read-only: from extension_types join
    ];

    /**
     * The fields to render items in the documents
     *
     * These fields are exposed when fetching multiple build records.
     * Includes base table fields and joined data for display purposes.
     *
     * @var    array
     * @since  1.0.0
     */
    protected $fieldsToRenderList = [
        'id',
        'component_name',
        'version',
        'build_date',
        'version_note',
        'state',
        'extension_type',  // Read-only: from extension_types join
    ];
}
