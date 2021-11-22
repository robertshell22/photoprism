<?php

namespace Drupal\photoprism_views_example\Plugin\views\query;

use Drupal\Core\Form\FormStateInterface;
use Drupal\photoprism\PhotoPrismSessionIdManager;
use Drupal\photoprism\PhotoPrismService;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * PhotoPrism views query plugin which wraps calls to the PhotoPrism API in order to
 * expose the results to views.
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
   * PhotoPrism access token manager for loading access tokens from the database.
   *
   * @var \Drupal\photoprism\PhotoPrismSessionIdManager
   */
  protected $PhotoPrismSessionIdManager;

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
   * @param PhotoPrismSessionIdManager $photoprism_session_id_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PhotoPrismService $photoprism_service, PhotoPrismSessionIdManager $photoprism_session_id_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->PhotoPrismService = $photoprism_service;
    $this->PhotoPrismSessionIdManager = $photoprism_session_id_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('photoprism.client'),
      $container->get('photoprism.session_id_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    // Set the units according to the setting on the view.
    if (!empty($this->options['accept_lang'])) {
      $this->PhotoPrismService->setAcceptLang($this->options['accept_lang']);
    }
    // Check if we have a filter. Iterate over $this->where to gather up the
    // filtering conditions to pass along to the API. Note that views allows
    // grouping of conditions, as well as group operators. This does not apply
    // to us, as the PhotoPrism API has no such concept, nor do we support this
    // concept for filtering connected PhotoPrism Drupal users.
    if (isset($this->where)) {
      foreach ($this->where as $where_group => $where) {
        foreach ($where['conditions'] as $condition) {
          // Remove dot from begining of the string.
          $field_name = ltrim($condition['field'], '.');
          $filters[$field_name] = $condition['value'];
        }
      }
    }
    // We currently only support uid, ignore any other filters that may be
    // configured.
    $uids = isset($filters['uid']) ? [$filters['uid']] : NULL;
    if ($session_ids = $this->PhotoPrismSessionIdManager->loadMultipleSessionId($uids)) {
      $index = 0;
      foreach ($session_ids as $uid => $session_id) {
        if ($data = $this->PhotoPrismService->getResourceOwner($session_id)) {
          $data = $data->toArray();

          $row['display_name'] = $data['displayName'];
          $row['average_daily_steps'] = $data['averageDailySteps'];
          $row['avatar'] = [
            'avatar' => $data['avatar'],
            'avatar150' => $data['avatar150'],
          ];
          $row['height'] = $data['height'];
          $row['uid'] = $uid;
          // 'index' key is required.
          $row['index'] = $index++;
          $view->result[] = new ResultRow($row);
        }
      }
    }

    // Do a default sort by average daily steps. This would be better suited as
    // a Views sort plugin, but we'll keep it simple for now
    if (!empty($view->result)) {
      usort($view->result, function ($a, $b) {
        if ($a->average_daily_steps < $b->average_daily_steps) {
          return 1;
        }
        else if ($a->average_daily_steps > $b->average_daily_steps) {
          return -1;
        }
        else {
          return 0;
        }
      });
      // Re-index array
      $index = 0;
      foreach ($view->result as &$row) {
        $row->index = $index++;
      }
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
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['accept_lang'] = [
      'default' => NULL,
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['accept_lang'] = [
      '#type' => 'select',
      '#options' => [
        '' => $this->t('Metric'),
        'en_US' => $this->t('US'),
        'en_GB' => $this->t('UK'),
      ],
      '#title' => $this->t('Unit system'),
      '#default_value' => $this->options['accept_lang'],
      '#description' => $this->t('Set the unit system to use for PhotoPrism API requests.'),
    ];
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
