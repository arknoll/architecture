<?php

/**
 * @file
 * Contains \Drupal\architecture\Controller\ArchitectureController.
 */

namespace Drupal\architecture\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\Html;

/**
 * Controller for an Email alerts confirmation path.
 */
class ArchitectureController extends ControllerBase {

  /**
   * @var \Drupal\Core\Config\ConfigFactory.
   */
  protected $config;

  /**
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a ArchitectureController object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory.
   * @param \Drupal\Core\Config\ConfigManager $configManager
   *   The config manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(ConfigFactory $config, ConfigManager $configManager, EntityManagerInterface $entity_manager) {
    $this->config = $config;
    $this->configManager = $configManager;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.manager'),
      $container->get('entity.manager')
    );
  }
  /**
   * Generate report.
   */
  public function reports() {


    $content = [
      '#theme' => 'architecture_report',
      '#content_types' => $this->findConfiguration('node_type'),
      '#fields' => $this->fieldConfiguration(),
      '#roles' => $this->findConfiguration('user_role'),
      '#taxonomies'=> $this->findConfiguration('taxonomy_vocabulary'),
      '#views' => $this->findConfiguration('view'),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    return $content;
  }

  private function findConfiguration($config_type) {
    $entity_storage = $this->entityManager->getStorage($config_type);
    foreach ($entity_storage->loadMultiple() as $entity) {
      if (!$entity->status()) {
        continue;
      }
      $entity_id = $entity->id();
      if ($label = $entity->label()) {
        $names[$entity_id] = $this->t('@label (@id)', ['@label' => $label, '@id' => $entity_id]);
      }
      else {
        $names[$entity_id] = $entity_id;
      }
    }
    return $names;
  }

  private function fieldConfiguration() {

    $build['table'] = array(
      '#type' => 'table',
      '#header' => array(
        'label' => $this->t('Label'),
        'field_name' => array(
          'data' => $this->t('Machine name'),
          'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
        ),
        'field_type' => $this->t('Field type'),
      ),
      '#title' => '',
      '#rows' => array(),
      '#empty' => '',
    );

    $entity_storage = $this->entityManager->getStorage('node_type');
    foreach ($entity_storage->loadMultiple() as $base_entity) {

      $entities = array_filter($this->entityManager->getFieldDefinitions('node', $base_entity->bundle()), function ($field_definition) {
        return $field_definition instanceof FieldConfigInterface;
      });

      $build['table']['#rows'][$base_entity->id()] = array(
        'data' => array(
          'label' => $base_entity->label(),
        ),
      );

      foreach ($entities as $entity) {
        if ($row = $this->buildRow($entity)) {
          $build['table']['#rows'][$entity->id()] = $row;
        }
      }
    }
    return $build;
  }

  public function buildRow(EntityInterface $field_config) {
    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_storage = $field_config->getFieldStorageDefinition();

    $row = array(
      'id' => Html::getClass($field_config->getName()),
      'data' => array(
        'label' => $field_config->getLabel(),
        'field_name' => $field_config->getName(),
        'field_type' => $this->fieldTypeManager->getDefinitions()[$field_storage->getType()]['label'],
      ),
    );

    return $row;
  }
}
