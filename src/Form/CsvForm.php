<?php

declare(strict_types=1);

namespace Drupal\compras\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

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
        $form_state->setErrorByName(
          'csv_file',
          $this->t('O arquivo precisa ser um CSV válido.')
        );
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

      $handle = fopen($file->getRealPath(), 'r');

      if ($handle !== FALSE) {

        // Lê a primeira linha para descobrir o separador.
        $primeira_linha = fgets($handle);

        if ($primeira_linha !== FALSE) {
          if (str_contains($primeira_linha, '|')) {
            $delimitador = '|';
          }
          elseif (str_contains($primeira_linha, ';')) {
            $delimitador = ';';
          }
          else {
            $delimitador = ',';
          }
        }
        else {
          $delimitador = ',';
        }

        // Volta para o início do arquivo.
        rewind($handle);

        // Lê o cabeçalho.
        $cabecalho = fgetcsv($handle, 0, $delimitador);

        // Lê cada linha do CSV.
        $linhas = [];
        while (($linha = fgetcsv($handle, 0, $delimitador)) !== FALSE) {
          $linhas[] = $linha;
        }
        $database = Database::getConnection();
        foreach ($linhas as $linha) {
          $numero_contratacao = trim($linha[0]);
          $no_dfd = trim($linha[1]);

  // Verifica se a informação já existe.
          $existe = $database->select('compras_lista', 'c')
            ->fields('c', ['id'])
            ->condition('numero_contratacao', $numero_contratacao)
            ->condition('no_dfd', $no_dfd)
            ->execute()
            ->fetchField();
            
          if ($existe) {
            $this->messenger()->addWarning(
              $this->t(
                'Informação já existente no banco: @numero - @dfd. Uma nova entrada será adicionada.',
                [
                  '@numero' => $numero_contratacao,
                  '@dfd' => $no_dfd,
                ]
              )
          );
        }
   // Salva a informação independentemente de ela já existir.
        $database->insert('compras_lista')
        ->fields([
          'numero_contratacao' => $numero_contratacao,
          'no_dfd' => $no_dfd,
        ])
        ->execute();
    }

        fclose($handle);
      }
    }

    $this->messenger()->addStatus(
      $this->t('CSV importado com sucesso.')
    );
  }
}