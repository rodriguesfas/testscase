<?php
/**
 * Interface administrativa para gerenciar desafios do CodeDojo
 * Permite criar, editar e visualizar desafios, casos de teste e submissões
 */

// Configuração do banco
$config = [
    'host' => 'localhost',
    'port' => '5432',
    'dbname' => 'codedojo',
    'user' => 'codedojo_user',
    'password' => 'codedojo_pass'
];

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}", 
        $config['user'], 
        $config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Erro ao conectar ao banco: " . $e->getMessage() . "\n");
}

/**
 * Lista todos os desafios
 */
function listChallenges($pdo) {
    echo "📋 DESAFIOS CADASTRADOS\n";
    echo str_repeat("=", 70) . "\n";
    
    $stmt = $pdo->query("
        SELECT c.*, 
               COUNT(tc.id) as total_tests,
               COUNT(s.id) as total_submissions
        FROM challenges c
        LEFT JOIN test_cases tc ON c.id = tc.challenge_id
        LEFT JOIN submissions s ON c.id = s.challenge_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    
    $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($challenges)) {
        echo "Nenhum desafio encontrado.\n\n";
        return;
    }
    
    foreach ($challenges as $challenge) {
        echo sprintf("🎯 %s (%s)\n", $challenge['title'], $challenge['name']);
        echo sprintf("   Dificuldade: %s | Testes: %d | Submissões: %d\n", 
                    ucfirst($challenge['difficulty']), 
                    $challenge['total_tests'], 
                    $challenge['total_submissions']);
        echo sprintf("   Criado em: %s\n", $challenge['created_at']);
        echo "   " . str_repeat("-", 60) . "\n";
        if ($challenge['description']) {
            echo "   " . substr($challenge['description'], 0, 100) . "...\n";
            echo "\n";
        }
    }
}

/**
 * Mostra detalhes de um desafio específico
 */
function showChallenge($pdo, $challengeName) {
    $stmt = $pdo->prepare("SELECT * FROM challenges WHERE name = ?");
    $stmt->execute([$challengeName]);
    $challenge = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$challenge) {
        echo "❌ Desafio '$challengeName' não encontrado.\n";
        return;
    }
    
    echo "🎯 DETALHES DO DESAFIO: {$challenge['title']}\n";
    echo str_repeat("=", 70) . "\n";
    echo "Nome: {$challenge['name']}\n";
    echo "Dificuldade: " . ucfirst($challenge['difficulty']) . "\n";
    echo "Limite de tempo: {$challenge['time_limit']}ms\n";
    echo "Limite de memória: {$challenge['memory_limit']}MB\n";
    echo "Criado em: {$challenge['created_at']}\n\n";
    
    if ($challenge['description']) {
        echo "📝 DESCRIÇÃO:\n";
        echo str_repeat("-", 30) . "\n";
        echo $challenge['description'] . "\n\n";
    }
    
    // Casos de teste
    echo "🧪 CASOS DE TESTE:\n";
    echo str_repeat("-", 30) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT folder_number, COUNT(*) as test_count
        FROM test_cases 
        WHERE challenge_id = ? 
        GROUP BY folder_number 
        ORDER BY folder_number
    ");
    $stmt->execute([$challenge['id']]);
    $testStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($testStats)) {
        echo "Nenhum caso de teste cadastrado.\n\n";
    } else {
        foreach ($testStats as $stat) {
            echo "📁 Pasta {$stat['folder_number']}: {$stat['test_count']} casos\n";
        }
        echo "\n";
    }
    
    // Submissões
    echo "💻 SUBMISSÕES RECENTES:\n";
    echo str_repeat("-", 30) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT s.*, e.passed_tests, e.failed_tests, e.total_execution_time_ms
        FROM submissions s
        LEFT JOIN executions e ON s.id = e.submission_id
        WHERE s.challenge_id = ?
        ORDER BY s.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$challenge['id']]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($submissions)) {
        echo "Nenhuma submissão encontrada.\n\n";
    } else {
        foreach ($submissions as $submission) {
            $status = "❓";
            if ($submission['passed_tests'] !== null) {
                $status = ($submission['failed_tests'] == 0) ? "✅" : "❌";
            }
            
            echo sprintf("%s %s (%s) - %s\n", 
                        $status,
                        $submission['filename'], 
                        $submission['language'],
                        $submission['author'] ?? 'Anônimo');
            
            if ($submission['passed_tests'] !== null) {
                echo sprintf("     %d/%d testes passaram | %sms\n", 
                            $submission['passed_tests'],
                            ($submission['passed_tests'] + $submission['failed_tests']),
                            $submission['total_execution_time_ms'] ?? 0);
            }
            
            echo "     " . $submission['created_at'] . "\n\n";
        }
    }
}

/**
 * Cria um novo desafio
 */
function createChallenge($pdo, $name, $title, $description = '', $difficulty = 'medium') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO challenges (name, title, description, difficulty) 
            VALUES (?, ?, ?, ?)
            RETURNING id
        ");
        
        $stmt->execute([$name, $title, $description, $difficulty]);
        $challengeId = $stmt->fetchColumn();
        
        echo "✅ Desafio '$title' criado com sucesso! (ID: $challengeId)\n";
        echo "Nome: $name\n";
        echo "Dificuldade: $difficulty\n\n";
        
        return $challengeId;
        
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate key') !== false) {
            echo "❌ Já existe um desafio com o nome '$name'.\n";
        } else {
            echo "❌ Erro ao criar desafio: " . $e->getMessage() . "\n";
        }
        return false;
    }
}

/**
 * Lista submissões com resultados
 */
function listSubmissions($pdo, $limit = 20) {
    echo "💻 SUBMISSÕES RECENTES\n";
    echo str_repeat("=", 70) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as challenge_name, c.title,
               e.passed_tests, e.failed_tests, e.total_execution_time_ms, e.status
        FROM submissions s
        JOIN challenges c ON s.challenge_id = c.id
        LEFT JOIN executions e ON s.id = e.submission_id
        ORDER BY s.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($submissions)) {
        echo "Nenhuma submissão encontrada.\n\n";
        return;
    }
    
    foreach ($submissions as $submission) {
        $status = "❓";
        $details = "";
        
        if ($submission['status']) {
            switch ($submission['status']) {
                case 'completed':
                    $status = ($submission['failed_tests'] == 0) ? "✅" : "❌";
                    break;
                case 'failed':
                    $status = "❌";
                    break;
                case 'running':
                    $status = "⏳";
                    break;
                case 'timeout':
                    $status = "⏰";
                    break;
            }
            
            if ($submission['passed_tests'] !== null) {
                $details = sprintf("(%d/%d testes | %sms)", 
                                  $submission['passed_tests'],
                                  ($submission['passed_tests'] + $submission['failed_tests']),
                                  $submission['total_execution_time_ms'] ?? 0);
            }
        }
        
        echo sprintf("%s %s - %s (%s) %s\n", 
                    $status,
                    $submission['challenge_name'],
                    $submission['filename'],
                    $submission['language'],
                    $details);
        
        echo sprintf("    Autor: %s | %s\n", 
                    $submission['author'] ?? 'Anônimo',
                    $submission['created_at']);
        echo "\n";
    }
}

/**
 * Mostra estatísticas gerais
 */
function showStats($pdo) {
    echo "📊 ESTATÍSTICAS GERAIS\n";
    echo str_repeat("=", 50) . "\n";
    
    $stats = [
        'Desafios' => $pdo->query("SELECT COUNT(*) FROM challenges")->fetchColumn(),
        'Casos de teste' => $pdo->query("SELECT COUNT(*) FROM test_cases")->fetchColumn(),
        'Submissões' => $pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn(),
        'Execuções' => $pdo->query("SELECT COUNT(*) FROM executions")->fetchColumn(),
    ];
    
    foreach ($stats as $label => $count) {
        echo sprintf("%-20s: %d\n", $label, $count);
    }
    
    echo "\n";
    
    // Top linguagens
    echo "🔥 LINGUAGENS MAIS USADAS:\n";
    echo str_repeat("-", 30) . "\n";
    
    $stmt = $pdo->query("
        SELECT language, COUNT(*) as count 
        FROM submissions 
        GROUP BY language 
        ORDER BY count DESC 
        LIMIT 5
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-15s: %d submissões\n", ucfirst($row['language']), $row['count']);
    }
    
    echo "\n";
}

/**
 * Menu principal
 */
function showMenu() {
    echo "\n🚀 CODEDOJO - ADMIN\n";
    echo str_repeat("=", 30) . "\n";
    echo "1. Listar desafios\n";
    echo "2. Ver detalhes do desafio\n";
    echo "3. Criar novo desafio\n";
    echo "4. Listar submissões\n";
    echo "5. Estatísticas\n";
    echo "6. Sair\n\n";
    echo "Escolha uma opção: ";
}

// Execução principal
if (php_sapi_name() === 'cli') {
    // Modo CLI interativo
    while (true) {
        showMenu();
        $option = trim(fgets(STDIN));
        
        switch ($option) {
            case '1':
                echo "\n";
                listChallenges($pdo);
                break;
                
            case '2':
                echo "\nDigite o nome do desafio: ";
                $name = trim(fgets(STDIN));
                echo "\n";
                showChallenge($pdo, $name);
                break;
                
            case '3':
                echo "\nNome do desafio (ex: problema1): ";
                $name = trim(fgets(STDIN));
                echo "Título (ex: Ordenação de Arrays): ";
                $title = trim(fgets(STDIN));
                echo "Descrição (opcional): ";
                $description = trim(fgets(STDIN));
                echo "Dificuldade (easy/medium/hard) [medium]: ";
                $difficulty = trim(fgets(STDIN)) ?: 'medium';
                
                echo "\n";
                createChallenge($pdo, $name, $title, $description, $difficulty);
                break;
                
            case '4':
                echo "\n";
                listSubmissions($pdo);
                break;
                
            case '5':
                echo "\n";
                showStats($pdo);
                break;
                
            case '6':
                echo "👋 Até logo!\n";
                exit(0);
                
            default:
                echo "❌ Opção inválida. Tente novamente.\n";
        }
        
        echo "\nPressione Enter para continuar...";
        fgets(STDIN);
    }
} else {
    // Modo web simples
    echo "CodeDojo Admin - Execute via linha de comando para interface interativa\n";
    echo "Uso: php admin.php\n";
}
?>
