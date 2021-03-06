<?php

namespace Drupal\photoprism_views\Plugin\views\query;

use Drupal\Core\Form\FormStateInterface;
use Drupal\photoprism\PhotoPrismSessionIdManager;
use Drupal\photoprism\PhotoPrismService;
use Drupal\photoprism_views\PhotoPrismBaseTableEndpointInterface;
use Drupal\photoprism_views\PhotoPrismBaseTableEndpointPluginManager;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * PhotoPrism views query plugin which wraps calls to the PhotoPrism API in order to
 * expose the results to views.
 *
 * @ingroup views_query_plugins
 *
 * @ViewsQuery(
 *   id = "photoprism",
 *   title = @Translation("PhotoPrism"),
 *   help = @Translation("Query against the PhotoPrism API.")
 * )
 */
class PhotoPrism extends QueryPluginBase {

  /**
   * PhotoPrism client.
   *
   * @var \Drupal\photoprism\PhotoPrismService
   */
  protected $PhotoPrismService;


  /**
   * PhotoPrism base table endpoint plugin manager.
   *
   * @var \Drupal\photoprism_views\PhotoPrismBaseTableEndpointPluginManager
   */
  protected $photoprismBaseTableEndpointPluginManager;

  /**
   * Array of relationships. Each array entry should be a
   * FitBitBaseTableEndpoint plugin_id.
   *
   * @var string[]
   */
  protected $relationships;

  /**
   * Collection of filter criteria.
   *
   * @var array
   */
  protected $where;

  /**
   * PhotoPrism constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param PhotoPrismService $photoprism_service
   * @param PhotoPrismBaseTableEndpointPluginManager $photoprism_base_table_endpoint_plugin_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PhotoPrismService $photoprism_service, PhotoPrismBaseTableEndpointPluginManager $photoprism_base_table_endpoint_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->PhotoPrismService = $photoprism_service;
    $this->photoprismBaseTableEndpointPluginManager = $photoprism_base_table_endpoint_plugin_manager;
    $this->relationships = [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('photoprism.service'),
      $container->get('plugin.manager.photoprism_base_table_endpoints')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(ViewExecutable $view) {
    // Mostly modeled off of \Drupal\views\Plugin\views\query\Sql::build()

    // Store the view in the object to be able to use it later.
    $this->view = $view;

    $view->initPager();

    // Let the pager modify the query to add limits.
    $view->pager->query();

    $view->build_info['query'] = $this->query();
    $view->build_info['count_query'] = $this->query(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function query($get_count = FALSE) {
    // Fill up the $query array with properties that we will use in forming the
    // API request.
    $query = [];

    // Iterate over $this->where to gather up the filtering conditions to pass
    // along to the API. Note that views allows grouping of conditions, as well
    // as group operators. This does not apply to us, as the PhotoPrism API has no
    // such concept, nor do we support this concept for filtering connected
    // PhotoPrism Drupal users.
    if (isset($this->where)) {
      foreach ($this->where as $where_group => $where) {
        foreach ($where['conditions'] as $condition) {
          // Remove dot from begining of the string.
          $field_name = ltrim($condition['field'], '.');
          $query[$field_name] = $condition['value'];
        }
      }
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {

    // Grab data regarding conditions placed on the query.
    $query = $view->build_info['query'];
      // Need the data about the table to know which endpoint to use.
      $views_data = Views::viewsData();
      $base_table = $this->view->storage->get('base_table');
      $base_table_data = $views_data->get($base_table);

      // Here we combine the base_table_endpoint_id of the base table with any
      // added via relatiionships. Since all relationships defined by
      // photoprism_views module are bi-directional, we use array_unique to ensure
      // that we only ever hit each endpoint once. Unlike a SQL relationship
      // there is no distinction between a relationship one vertex away and a
      // relationship n verticies away.
      $photoprism_endpoint_ids = array_unique(array_merge([$base_table_data['table']['base']['photoprism_base_table_endpoint_id']], $this->relationships));
      $index = 0;

        $row = [];
        foreach ($photoprism_endpoint_ids as $photoprism_endpoint_id) {
          /** @var PhotoPrismBaseTableEndpointInterface $photoprism_endpoint */
          $photoprism_endpoint = $this->photoprismBaseTableEndpointPluginManager->createInstance($photoprism_endpoint_id);
          if ($data = $photoprism_endpoint->getRow($query)) {
            // The index key is very important. Views uses this to look up values
            // for each row. Without it, views won't show any of your result rows.
            $row = array_merge($row, $data);
          }
        }
        // If we got some data back from the API for this user, add defaults and
        // expose as a row to views.
        if (!empty($row)) {
          $row['index'] = $index++;
          $view->result[] = new ResultRow($row);
        }
      }



  /**
   * Adds a simple condition to the query. Collect data on the configured filter
   * criteria so that we can appropriately apply it in the query() and execute()
   * methods.
   *
   * @param $group
   *   The WHERE group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param $field
   *   The name of the field to check.
   * @param $value
   *   The value to test the field against. In most cases, this is a scalar. For more
   *   complex options, it is an array. The meaning of each element in the array is
   *   dependent on the $operator.
   * @param $operator
   *   The comparison operator, such as =, <, or >=. It also accepts more
   *   complex options such as IN, LIKE, LIKE BINARY, or BETWEEN. Defaults to =.
   *   If $field is a string you have to use 'formula' here.
   *
   * @see \Drupal\Core\Database\Query\ConditionInterface::condition()
   * @see \Drupal\Core\Database\Query\Condition
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->setWhereGroup('AND', $group);
    }

    $this->where[$group]['conditions'][] = [
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    ];
  }

  /**
   * Add a relationship. For PhotoPrism views query backends, a relationship
   * corresponds to a PhotoPrismBaseTableEndpoint plugin_id, which will be used to
   * fetch rows from that endpoint in addition to the base table requested.
   *
   * @param string $endpoint_plugin_id
   *   Plugin id for a PhotoPrismBaseTableEndpoint plugin.
   */
  public function addRelationship($endpoint_plugin_id) {
    $this->relationships[] = $endpoint_plugin_id;
  }

  /**
   * The following methods replicate the interface of Views' default SQL query
   * plugin backend to simplify the Views integration of the PhotoPrism
   * API. It's necessary to define these, since many handlers assume they are
   * working against a SQL query plugin backend. There is an issue that details
   * this lack of an enforced contract as a bug
   * (https://www.drupal.org/node/2484565). Sigh.
   *
   * @see https://www.drupal.org/node/2484565
   */

  /**
   * Ensures a table exists in the query.
   *
   * This replicates the interface of Views' default SQL backend to simplify
   * the Views integration of the PhotoPrism API. Since the PhotoPrism API has no
   * concept of "tables", this method implementation does nothing. If you are
   * writing PhotoPrism API-specific Views code, there is therefore no reason at all
   * to call this method.
   * See https://www.drupal.org/node/2484565 for more information.
   *
   * @return string
   *   An empty string.
   */
  public function ensureTable($table, $relationship = NULL) {
    return '';
  }

  /**
   * Adds a field to the table. In our case, the Fitibt API has no
   * notion of limiting the fields that come back, so tracking a list
   * of fields to fetch is irrellevant for us. Hence this function body is more
   * or less empty and it serves only to satisfy handlers that may assume an
   * addField method is present b/c they were written against Views' default SQL
   * backend.
   *
   * This replicates the interface of Views' default SQL backend to simplify
   * the Views integration of the PhotoPrism API.
   *
   * @param string $table
   *   NULL in most cases, we could probably remove this altogether.
   * @param string $field
   *   The name of the metric/dimension/field to add.
   * @param string $alias
   *   Probably could get rid of this too.
   * @param array $params
   *   Probably could get rid of this too.
   *
   * @return string
   *   The name that this field can be referred to as.
   *
   * @see \Drupal\views\Plugin\views\query\Sql::addField()
   */
  public function addField($table, $field, $alias = '', $params = []) {
    return $field;
  }

  /**
   * End of methods necessary to replicate the interface of Views' default SQL
   * query plugin backend to simplify the Views integration of the PhotoPrism API.
   */
}
