<?php

declare(strict_types=1);

namespace Drupal\compras\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use League\Csv\CharsetConverter;

use League\Csv\Reader;

/**
 * Provides a Compras form.
 */
final class CsvForm extends FormBase {

/**
   * Mapeamento do cabeçalho do CSV para o nome do campo no banco de dados.
   */
  protected const MAPEAMENTO_COLUNAS = [
    'Número da contratação' => 'numero_contratacao',
    'Status da contratação' => 'status_contratacao',
    'Situação da Execução' => 'situacao_execucao',
    'Título da contratação' => 'titulo_contratacao',
    'Categoria da contratação' => 'categoria_contratacao',
    'UASG Atual' => 'uasg_atual',
    'Data estimada para o início do processo de contratação' => 'data_estimada_inicio',
    'Data estimada para a conclusão do processo de contratação' => 'data_estimada_conclusao',
    'Prazo estimado de duração do processo de contratação (dias)' => 'prazo_estimado_dias',
    'Área requisitante' => 'area_requisitante',
    'Nº DFD' => 'no_dfd',
    'Prioridade' => 'prioridade',
    'Nº do Item no DFD' => 'numero_item_dfd',
    'Data da conclusão da Contratação no DFD' => 'data_conclusao_dfd',
    'Classificação da Contratação' => 'classificacao_contratacao',
    'Código Classe/Grupo' => 'codigo_classe_grupo',
    'Nome Classe/Grupo' => 'nome_classe_grupo',
    'Código PDM material' => 'codigo_pdm_material',
    'Nome do PDM material' => 'nome_pdm_material',
    'Código material/serviço' => 'codigo_material_servico',
    'Descrição material/serviço' => 'descricao_material_servico',
    'Unidade Fornecimento' => 'unidade_fornecimento',
    'Valor Unitário' => 'valor_unitario',
    'Quantidade' => 'quantidade',
    'Valor Total' => 'valor_total',
  ];
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
        return;
      }

      try {
        $csv = Reader::createFromPath($file->getRealPath(), 'r');
        $csv->setDelimiter($this->detectarDelimitador($file->getRealPath()));
        
        // Garante UTF-8 no leitor
        $this->aplicarUtf8($csv, $file->getRealPath());

        $csv->setHeaderOffset(0);

        // Obtém e limpa o cabeçalho do CSV enviado
        $cabecalho = array_map('trim', $csv->getHeader());
        $colunas_esperadas = array_keys(self::MAPEAMENTO_COLUNAS);

        // Verifica quais colunas obrigatórias estão faltando
        $colunas_faltantes = array_diff($colunas_esperadas, $cabecalho);

        if (!empty($colunas_faltantes)) {
          $form_state->setErrorByName(
            'csv_file',
            $this->t('O arquivo CSV não possui todas as colunas obrigatórias. Colunas ausentes: @colunas', [
              '@colunas' => implode(', ', $colunas_faltantes),
            ])
          );
        }
      }
      catch (\Exception $e) {
        $form_state->setErrorByName(
          'csv_file',
          $this->t('Erro ao ler o arquivo CSV: @erro', ['@erro' => $e->getMessage()])
        );
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $all_files = \Drupal::request()->files->get('files', []);

    if (empty($all_files['csv_file'])) {
      return;
    }

    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
    $file = $all_files['csv_file'];

    try {
      $csv = Reader::createFromPath($file->getRealPath(), 'r');
      $csv->setDelimiter($this->detectarDelimitador($file->getRealPath()));
      
      // Converte caracteres acentuados para UTF-8 se necessário
      $this->aplicarUtf8($csv, $file->getRealPath());

      $csv->setHeaderOffset(0);

      // Carrega todos os registros para memória
      $registros = iterator_to_array($csv->getRecords());

      if (empty($registros)) {
        $this->messenger()->addWarning($this->t('O arquivo CSV está vazio.'));
        return;
      }

      // 1. Identifica todos os anos presentes no arquivo CSV
      $anos_encontrados = [];
      foreach ($registros as $linha) {
        $numero_contratacao = trim($linha['Número da contratação'] ?? '');

        // Extrai os 4 dígitos do ano após a barra (ex: "5/2027" -> "2027")
        if (preg_match('/\/(\d{4})$/', $numero_contratacao, $matches)) {
          $ano = $matches[1];
          $anos_encontrados[$ano] = $ano;
        }
      }

      $database = Database::getConnection();
      $transaction = $database->startTransaction();

      try {
        // 2. Apaga do banco os registros existentes dos anos identificados
        if (!empty($anos_encontrados)) {
          foreach ($anos_encontrados as $ano) {
            $deletados = $database->delete('compras_lista')
              ->condition('numero_contratacao', '%' . $database->escapeLike('/' . $ano), 'LIKE')
              ->execute();

            if ($deletados > 0) {
              $this->messenger()->addStatus(
                $this->t('Registros do ano @ano já existentes foram removidos (@count registros apagados).', [
                  '@ano' => $ano,
                  '@count' => $deletados,
                ])
              );
            }
          }
        }
        else {
          $this->messenger()->addWarning(
            $this->t('Não foi possível identificar o ano na coluna "Número da contratação". Nenhum registro antigo foi apagado.')
          );
        }

        // 3. Insere todas as novas linhas do CSV no banco de dados
        $total_importados = 0;

        foreach ($registros as $linha) {
          $linha = array_map('trim', $linha);

          $campos_db = [
            'numero_contratacao'         => $linha['Número da contratação'] ?? NULL,
            'status_contratacao'         => $linha['Status da contratação'] ?? NULL,
            'situacao_execucao'          => $this->tratarTexto($linha['Situação da Execução'] ?? NULL),
            'titulo_contratacao'         => $linha['Título da contratação'] ?? NULL,
            'categoria_contratacao'      => $linha['Categoria da contratação'] ?? NULL,
            'uasg_atual'                 => $linha['UASG Atual'] ?? NULL,
            'data_estimada_inicio'       => $linha['Data estimada para o início do processo de contratação'] ?? NULL,
            'data_estimada_conclusao'    => $linha['Data estimada para a conclusão do processo de contratação'] ?? NULL,
            'prazo_estimado_dias'        => $this->tratarInteiro($linha['Prazo estimado de duração do processo de contratação (dias)'] ?? NULL),
            'area_requisitante'          => $linha['Área requisitante'] ?? NULL,
            'no_dfd'                     => $linha['Nº DFD'] ?? NULL,
            'prioridade'                 => $linha['Prioridade'] ?? NULL,
            'numero_item_dfd'            => $this->tratarInteiro($linha['Nº do Item no DFD'] ?? NULL),
            'data_conclusao_dfd'         => $linha['Data da conclusão da Contratação no DFD'] ?? NULL,
            'classificacao_contratacao'  => $linha['Classificação da Contratação'] ?? NULL,
            'codigo_classe_grupo'        => $linha['Código Classe/Grupo'] ?? NULL,
            'nome_classe_grupo'          => $linha['Nome Classe/Grupo'] ?? NULL,
            'codigo_pdm_material'        => $this->tratarTexto($linha['Código PDM material'] ?? NULL),
            'nome_pdm_material'          => $this->tratarTexto($linha['Nome do PDM material'] ?? NULL),
            'codigo_material_servico'    => $this->tratarTexto($linha['Código material/serviço'] ?? NULL),
            'descricao_material_servico' => $this->tratarTexto($linha['Descrição material/serviço'] ?? NULL),
            'unidade_fornecimento'       => $this->tratarTexto($linha['Unidade Fornecimento'] ?? NULL),
            'valor_unitario'             => $this->tratarDecimal($linha['Valor Unitário'] ?? NULL),
            'quantidade'                 => $this->tratarDecimal($linha['Quantidade'] ?? NULL),
            'valor_total'                => $this->tratarDecimal($linha['Valor Total'] ?? NULL),
          ];

          $database->insert('compras_lista')
            ->fields($campos_db)
            ->execute();

          $total_importados++;
        }

        $this->messenger()->addStatus(
          $this->t('Importação concluída com sucesso! Total de @count novos registros salvos.', [
            '@count' => $total_importados,
          ])
        );
      }
      catch (\Exception $e) {
        $transaction->rollBack();
        throw $e;
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError(
        $this->t('Erro ao processar e salvar o arquivo CSV: @erro', ['@erro' => $e->getMessage()])
      );
    }
  }

  /**
   * Detecta se o separador do CSV é ';', '|' ou ','.
   */
  private function detectarDelimitador(string $filepath): string {
    $handle = fopen($filepath, 'r');
    if ($handle !== FALSE) {
      $primeira_linha = fgets($handle);
      fclose($handle);

      if ($primeira_linha !== FALSE) {
        if (str_contains($primeira_linha, '|')) {
          return '|';
        }
        if (str_contains($primeira_linha, ';')) {
          return ';';
        }
      }
    }
    return ',';
  }

  /**
   * Converte texto vazio para NULL.
   */
  private function tratarTexto(?string $valor): ?string {
    if ($valor === NULL || trim($valor) === '') {
      return NULL;
    }
    return trim($valor);
  }

  /**
   * Converte string de inteiro para int ou NULL.
   */
  private function tratarInteiro(?string $valor): ?int {
    if ($valor === NULL || trim($valor) === '') {
      return NULL;
    }
    return (int) trim($valor);
  }

  /**
   * Converte valor decimal em formato PT-BR (Ex: "100.000,0000") para float legível no MySQL.
   */
  private function tratarDecimal(?string $valor): ?float {
    if ($valor === NULL || trim($valor) === '') {
      return NULL;
    }
    // Remove separador de milhar (.) e substitui vírgula por ponto
    $valor_limpo = str_replace('.', '', trim($valor));
    $valor_limpo = str_replace(',', '.', $valor_limpo);

    return is_numeric($valor_limpo) ? (float) $valor_limpo : NULL;
  }
  
  /**
   * Garante que o objeto Reader do CSV entregue os dados em UTF-8.
   */
  private function aplicarUtf8(Reader $csv, string $filepath): Reader {
    $conteudo = file_get_contents($filepath);
    
    // Detecta a codificação atual do arquivo
    $encoding = mb_detect_encoding($conteudo, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], TRUE);

    // Se não for UTF-8 (ou for ISO-8859-1 / Windows-1252), aplica a conversão
    if ($encoding !== 'UTF-8') {
      $converter = (new CharsetConverter())
        ->inputEncoding($encoding ?: 'ISO-8859-1')
        ->outputEncoding('UTF-8');

      // Aplica o conversor de charset no leitor do CSV
      $csv->addFormatter($converter);
    }

    return $csv;
  }
}