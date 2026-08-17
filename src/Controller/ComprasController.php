<?php

declare(strict_types=1);

namespace Drupal\compras\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

/**
 * Returns responses for compras routes.
 */
final class ComprasController extends ControllerBase {

  // Listar.
  public function index() {
    $connection = Database::getConnection();
    $query = $connection->select('compras', 'c')->fields('c');
    $compras = $query->execute();

    $items = [];

    foreach ($compras as $compra) {
      $items[] = [
        '#markup' =>
          '<hr>
          <strong>Número:</strong> ' . $compra->numero . '<br>
          <strong>Número do DFD:</strong> ' . $compra->numerodfd . '<br>
          <a href="/compras/' . $compra->id . '/edit"> Editar </a> <br>
          <a href="/compras/' . $compra->id . '/delete"> Apagar </a> ',
      ];
    }

    return [
      '#cache' => [
        'max-age' => 0,
      ],
      'content' => $items,
    ];
  }

  // Deletar.
  public function delete($id) {
    $connection = Database::getConnection();

    $connection->delete('compras')
      ->condition('id', $id)
      ->execute();

    \Drupal::messenger()->addMessage('Compra apagada com sucesso');

    return $this->redirect('compras.index');
  }

}