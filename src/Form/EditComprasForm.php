<?php

declare(strict_types=1);

namespace Drupal\compras\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Provides a compras edit form.
 */
final class EditComprasForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'compras_edit_compras';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $connection = Database::getConnection();

    $query = $connection
      ->select('compras', 'c')
      ->fields('c')
      ->condition('id', $id);

    $compra = $query->execute()->fetchObject();

    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $compra->id,
    ];

    $form['numero'] = [
      '#type' => 'textarea',
      '#title' => 'Número',
      '#required' => TRUE,
      '#default_value' => $compra->numero,
    ];

    $form['numerodfd'] = [
      '#type' => 'textarea',
      '#title' => 'Número do DFD',
      '#required' => TRUE,
      '#default_value' => $compra->numerodfd,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Atualizar',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = Database::getConnection();

    $connection->update('compras')
      ->fields([
        'numero' => $form_state->getValue('numero'),
        'numerodfd' => $form_state->getValue('numerodfd'),
      ])
      ->condition('id', $form_state->getValue('id'))
      ->execute();

    \Drupal::messenger()->addMessage('Compra atualizada com sucesso!');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
  }

}