<?php

declare(strict_types=1);

namespace Drupal\compras\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Provides a compras form.
 */
final class ComprasForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'compras_compras';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['numero'] = [
      '#type' => 'textarea',
      '#title' => 'Número',
      '#required' => TRUE,
    ];

    $form['numerodfd'] = [
      '#type' => 'textarea',
      '#title' => 'Número do DFD',
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Salvar',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = Database::getConnection();

    $connection->insert('compras')
      ->fields([
        'numero' => $form_state->getValue('numero'),
        'numerodfd' => $form_state->getValue('numerodfd'),
      ])
      ->execute();

    \Drupal::messenger()->addMessage('Compra salva com sucesso!');
  }

}