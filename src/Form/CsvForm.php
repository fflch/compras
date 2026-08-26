<?php

declare(strict_types=1);

namespace Drupal\compras\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Compras form.
 */
final class CsvForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'compras_csv';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['csv_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Arquivo CSV'),
      '#description' => $this->t('Envie um arquivo no formato .csv'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Send'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $all_files = \Drupal::request()->files->get('files', []);
    
    if (!empty($all_files['csv_file'])) {
      /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
      $file = $all_files['csv_file'];
      
      if ($file->getClientOriginalExtension() !== 'csv') {
        $form_state->setErrorByName('csv_file', $this->t('O arquivo precisa ser um CSV válido.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $all_files = \Drupal::request()->files->get('files', []);
    
    if (!empty($all_files['csv_file'])) {
      /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
      $file = $all_files['csv_file'];
      
      // Lê todo o conteúdo do arquivo temporário para uma string
      $csv_content = file_get_contents($file->getRealPath());

      // $csv_content agora contém o texto puro do CSV
      dd($csv_content);
    }

    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirect('<front>');
  }

}
