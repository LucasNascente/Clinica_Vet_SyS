<?php
// =============================================================
//  VetSys – Dog API Helper
//  Arquivo : includes/dog_api_helper.php
//  Descrição: Funções para integração com The Dog API
// =============================================================

/**
 * Busca todas as raças de cachorro da Dog API
 * @return array Lista de raças ou array vazio em caso de erro
 */
function obterRacasAPI(): array
{
    $url = 'https://dog.ceo/api/breeds/list/all';
    
    try {
        $resposta = @file_get_contents($url);
        
        if ($resposta === false) {
            error_log('[Dog API] Erro ao conectar em: ' . $url);
            return [];
        }
        
        $dados = json_decode($resposta, true);
        
        if (!isset($dados['status']) || $dados['status'] !== 'success') {
            error_log('[Dog API] Resposta inválida: ' . $resposta);
            return [];
        }
        
        $racas = isset($dados['message']) ? $dados['message'] : [];
        
        // Transforma o formato da API em um array simples
        // A API retorna: {"shiba": ["black", "red", "sesame", "white"]}
        // Queremos: ["Shiba", "Shiba Inu", ...]
        $racas_simples = [];
        
        foreach ($racas as $raca => $subracas) {
            // Capitaliza a raça principal
            $raca_capitalizada = ucwords(str_replace('-', ' ', $raca));
            $racas_simples[$raca] = $raca_capitalizada;
            
            // Se tem sub-raças, adiciona também
            if (!empty($subracas) && is_array($subracas)) {
                foreach ($subracas as $subraca) {
                    $nome_completo = $raca_capitalizada . ' ' . ucwords(str_replace('-', ' ', $subraca));
                    $chave = $raca . '-' . $subraca;
                    $racas_simples[$chave] = $nome_completo;
                }
            }
        }
        
        // Ordena alfabeticamente
        asort($racas_simples);
        
        return $racas_simples;
        
    } catch (Exception $e) {
        error_log('[Dog API] Exceção: ' . $e->getMessage());
        return [];
    }
}

/**
 * Busca uma imagem aleatória de uma raça específica
 * @param string $raca Nome da raça (em formato URL: shiba, golden-retriever, etc)
 * @return string URL da imagem ou string vazia em caso de erro
 */
function obterFotoRacaAPI(string $raca): string
{
    if (empty($raca)) {
        return '';
    }
    
    // Trata raças compostas (ex: "shiba-black" → "shiba/black")
    $raca = str_replace('-', '/', $raca);
    $url = 'https://dog.ceo/api/breed/' . urlencode($raca) . '/images/random';
    
    try {
        $resposta = @file_get_contents($url);
        
        if ($resposta === false) {
            error_log('[Dog API] Erro ao conectar em: ' . $url);
            return '';
        }
        
        $dados = json_decode($resposta, true);
        
        if (!isset($dados['status']) || $dados['status'] !== 'success') {
            error_log('[Dog API] Erro ao buscar imagem: ' . $resposta);
            return '';
        }
        
        return $dados['message'] ?? '';
        
    } catch (Exception $e) {
        error_log('[Dog API] Exceção: ' . $e->getMessage());
        return '';
    }
}

/**
 * Valida se uma raça existe na Dog API
 * @param string $raca Nome da raça
 * @param array $racas_validas Lista de raças válidas
 * @return bool
 */
function validarRacaAPI(string $raca, array $racas_validas): bool
{
    return isset($racas_validas[$raca]) || in_array($raca, array_keys($racas_validas), true);
}

/**
 * Cache simples: salva raças em sessão por 1 hora
 */
function obterRacasComCache(): array
{
    $cache_key = 'dog_api_racas_cache';
    $cache_timeout_key = 'dog_api_racas_timeout';
    
    // Se está em cache e não expirou
    if (isset($_SESSION[$cache_key], $_SESSION[$cache_timeout_key])) {
        if (time() < $_SESSION[$cache_timeout_key]) {
            return $_SESSION[$cache_key];
        }
    }
    
    // Busca nova dados
    $racas = obterRacasAPI();
    
    // Salva em cache por 3600 segundos (1 hora)
    $_SESSION[$cache_key] = $racas;
    $_SESSION[$cache_timeout_key] = time() + 3600;
    
    return $racas;
}
