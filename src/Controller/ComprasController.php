<?php

declare(strict_types=1);

namespace Drupal\compras\Controller;

use Drupal\Core\Controller\ControllerBase;

final class ComprasController extends ControllerBase {

  public function lista(): array {

    $query = \Drupal::database()
      ->select('compras_lista', 'c');

    $query->fields('c', [
      'id',
      'numero_contratacao',
      'no_dfd',
    ]);

    $query->orderBy('id', 'ASC');

    $resultados = $query->execute()->fetchAll();

    $rows = [];

    foreach ($resultados as $resultado) {
      $rows[] = [
        $resultado->numero_contratacao,
        $resultado->no_dfd,
      ];
    }

    return [
      '#type' => 'table',
      '#header' => [
        'Número de Contratação',
        'No DFD',
      ],
      '#rows' => $rows,
    ];
  }

}