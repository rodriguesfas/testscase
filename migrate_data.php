<?php
/**
 * Script para migrar dados existentes (templates/) para o banco PostgreSQL
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
    echo "✓ Conectado ao banco PostgreSQL\n";
} catch (PDOException $e) {
    die("❌ Erro ao conectar ao banco: " . $e->getMessage() . "\n");
}

const TEMPLATES_PATH = "templates/";

/**
 * Migra casos de teste de uma pasta para o banco
 */
function migrateTestCases($pdo, $challengeName) {
    $testPath = TEMPLATES_PATH . $challengeName . '/';
    
    if (!is_dir($testPath)) {
        echo "❌ Pasta de testes não encontrada: $testPath\n";
        return false;
    }
    
    // Buscar ID do desafio
    $stmt = $pdo->prepare("SELECT id FROM challenges WHERE name = ?");
    $stmt->execute([$challengeName]);
    $challenge = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$challenge) {
        echo "❌ Desafio '$challengeName' não encontrado no banco\n";
        return false;
    }
    
    $challengeId = $challenge['id'];
    echo "📁 Migrando casos de teste para desafio: $challengeName (ID: $challengeId)\n";
    
    $folders = scandir($testPath);
    $folders = array_filter($folders, function($item) use ($testPath) {
        return $item !== '.' && $item !== '..' && is_dir($testPath . $item);
    });
    
    $totalCases = 0;
    
    foreach ($folders as $folderNumber) {
        echo "  📂 Processando pasta: $folderNumber\n";
        
        $folderPath = $testPath . $folderNumber . '/';
        $files = scandir($folderPath);
        
        $inputFiles = array_filter($files, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'in';
        });
        
        foreach ($inputFiles as $inputFile) {
            $testNumber = pathinfo($inputFile, PATHINFO_FILENAME);
            
            $inputPath = $folderPath . $testNumber . '.in';
            $outputPath = $folderPath . $testNumber . '.sol';
            
            if (!file_exists($outputPath)) {
                echo "    ⚠️  Arquivo .sol não encontrado para teste $testNumber\n";
                continue;
            }
            
            $inputData = file_get_contents($inputPath);
            $expectedOutput = file_get_contents($outputPath);
            
            // Inserir caso de teste
            $stmt = $pdo->prepare("
                INSERT INTO test_cases (challenge_id, folder_number, test_number, input_data, expected_output, is_sample) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON CONFLICT (challenge_id, folder_number, test_number) DO UPDATE SET
                    input_data = EXCLUDED.input_data,
                    expected_output = EXCLUDED.expected_output
            ");
            
            $isSample = ($folderNumber == '1' && $testNumber <= 2); // Primeiros casos como exemplo
            
            $stmt->execute([
                $challengeId,
                intval($folderNumber),
                intval($testNumber),
                $inputData,
                $expectedOutput,
                $isSample
            ]);
            
            $totalCases++;
            echo "    ✓ Caso {$folderNumber}/{$testNumber} migrado\n";
        }
    }
    
    echo "✅ Total de $totalCases casos de teste migrados para '$challengeName'\n\n";
    return true;
}

/**
 * Migra arquivos de código existentes para o banco
 */
function migrateSubmissions($pdo, $challengeName) {
    $codeFiles = [
        'postes.py' => 'python',
        'postes.js' => 'javascript', 
        'postes.cpp' => 'cpp'
    ];
    
    // Buscar ID do desafio
    $stmt = $pdo->prepare("SELECT id FROM challenges WHERE name = ?");
    $stmt->execute([$challengeName]);
    $challenge = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$challenge) {
        echo "❌ Desafio '$challengeName' não encontrado no banco\n";
        return false;
    }
    
    $challengeId = $challenge['id'];
    echo "💻 Migrando submissões de código para: $challengeName\n";
    
    foreach ($codeFiles as $filename => $language) {
        if (file_exists($filename)) {
            $sourceCode = file_get_contents($filename);
            
            $stmt = $pdo->prepare("
                INSERT INTO submissions (challenge_id, filename, language, source_code, author) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $challengeId,
                $filename,
                $language,
                $sourceCode,
                'Sistema (migração)'
            ]);
            
            echo "  ✓ $filename ($language) migrado\n";
        } else {
            echo "  ⚠️  $filename não encontrado\n";
        }
    }
    
    echo "✅ Submissões migradas para '$challengeName'\n\n";
    return true;
}

/**
 * Função principal de migração
 */
function runMigration($pdo) {
    echo "🚀 Iniciando migração de dados...\n\n";
    
    // Verificar se existem desafios no banco
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM challenges");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        echo "❌ Nenhum desafio encontrado no banco. Execute primeiro os scripts de inicialização.\n";
        return false;
    }
    
    // Migrar casos de teste
    echo "📋 FASE 1: Migrando casos de teste\n";
    echo str_repeat("=", 50) . "\n";
    migrateTestCases($pdo, 'postes');
    
    // Migrar submissões
    echo "💾 FASE 2: Migrando submissões de código\n";
    echo str_repeat("=", 50) . "\n";
    migrateSubmissions($pdo, 'postes');
    
    // Estatísticas finais
    echo "📊 ESTATÍSTICAS FINAIS\n";
    echo str_repeat("=", 50) . "\n";
    
    $stats = [
        'challenges' => $pdo->query("SELECT COUNT(*) FROM challenges")->fetchColumn(),
        'test_cases' => $pdo->query("SELECT COUNT(*) FROM test_cases")->fetchColumn(),
        'submissions' => $pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn()
    ];
    
    foreach ($stats as $table => $count) {
        echo sprintf("%-15s: %d registros\n", ucfirst($table), $count);
    }
    
    echo "\n🎉 Migração concluída com sucesso!\n";
    return true;
}

// Executar migração
runMigration($pdo);
?>
